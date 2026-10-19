import http.cookiejar
import re
import urllib.error
import urllib.parse
import urllib.request

BASE = "http://127.0.0.1:8091"

class NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, req, fp, code, msg, headers, newurl):
        return None

def session():
    jar = http.cookiejar.CookieJar()
    opener = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(jar), NoRedirect())
    return opener

def request(opener, path, data=None):
    body = urllib.parse.urlencode(data).encode() if data is not None else None
    req = urllib.request.Request(BASE + path, data=body)
    try:
        res = opener.open(req, timeout=10)
        return res.status, res.headers, res.read().decode("utf-8", "replace")
    except urllib.error.HTTPError as e:
        return e.code, e.headers, e.read().decode("utf-8", "replace")

def login(path, username, password="Codex!2026Test"):
    op = session()
    status, headers, html = request(op, path)
    token = re.search(r'name=["\']csrf_token["\']\s+value=["\']([^"\']+)', html)
    data = {"username": username, "password": password}
    if token:
        data["csrf_token"] = token.group(1)
    status, headers, html = request(op, path, data)
    return op, status, headers.get("Location", ""), html

results = []
roles = [
    ("admin", "/admin/admin_login.php", "codex_admin", "admin_dashboard.php", "/admin/admin_dashboard.php"),
    ("student", "/student/student_login.php", "codex_student", "security_questions.php", "/student/security_questions.php?setup=1"),
    ("teacher", "/faculty/faculty_login.php", "codex_teacher", "faculty_dashboard.php", "/faculty/faculty_dashboard.php"),
    ("staff", "/faculty/staff_login.php", "codex_staff", "staff_dashboard.php", "/faculty/staff_dashboard.php"),
    ("dean", "/dean/dean_login.php", "codex_dean", "dean_dashboard.php", "/dean/dean_dashboard.php"),
    ("principal", "/principal/principal_login.php", "codex_principal", "principal_dashboard.php", "/principal/principal_dashboard.php"),
]
sessions = {}
for role, path, user, expected, landing_path in roles:
    op, status, location, html = login(path, user)
    sessions[role] = op
    logged_in = status in (301, 302, 303) and expected in location
    page_status, page_headers, page_html = request(op, landing_path) if logged_in else (0, {}, "")
    no_php_error = not re.search(r"(Fatal error|Uncaught mysqli_sql_exception|Warning: mysqli_)", page_html, re.I)
    ok = logged_in and page_status == 200 and no_php_error
    results.append({"check": "AUTH-01/" + role, "login_status": status, "location": location, "landing_status": page_status, "no_php_error": no_php_error, "pass": ok})

# Student account state and credential rejection checks.
for case, user, expected_text in [
    ("pending", "codex_pending", "awaiting admin approval"),
    ("blocked", "codex_blocked", "has been blocked"),
    ("inactive", "codex_inactive", "Incorrect username or password"),
    ("invalid", "codex_student", "Incorrect username or password"),
]:
    op = session()
    _, _, html = request(op, "/student/student_login.php")
    token = re.search(r'name=["\']csrf_token["\']\s+value=["\']([^"\']+)', html)
    data = {"username": user, "password": "wrong-password" if case == "invalid" else "Codex!2026Test"}
    if token: data["csrf_token"] = token.group(1)
    status, headers, html = request(op, "/student/student_login.php", data)
    results.append({"check": "AUTH-02/student-" + case, "status": status, "message_seen": expected_text.lower() in html.lower(), "pass": expected_text.lower() in html.lower() and "Location" not in headers})

# Missing CSRF token must not authenticate the student.
op = session()
request(op, "/student/student_login.php")
status, headers, html = request(op, "/student/student_login.php", {"username": "codex_student", "password": "Codex!2026Test"})
results.append({"check": "SECURITY-01/missing-csrf", "status": status, "rejected": "session expired" in html.lower(), "pass": "session expired" in html.lower() and "Location" not in headers})

# Each role tries a different role's protected dashboard, then logs out and
# revisits its own protected dashboard to verify the session was invalidated.
for i, (role, path, user, expected, own_dashboard) in enumerate(roles):
    other = roles[(i + 1) % len(roles)]
    op = sessions[role]
    cross_status, cross_headers, _ = request(op, other[4])
    denied = cross_status in (301,302,303) and other[1].split("/")[-1] in cross_headers.get("Location", "")
    results.append({"check": "AUTH-03/" + role + "-to-" + other[0], "status": cross_status, "location": cross_headers.get("Location", ""), "pass": denied})
    logout_status, logout_headers, _ = request(op, "/logout.php")
    protected_status, protected_headers, _ = request(op, own_dashboard)
    invalidated = protected_status in (301,302,303) and path.split("/")[-1] in protected_headers.get("Location", "")
    results.append({"check": "AUTH-04/" + role, "logout_status": logout_status, "logout_location": logout_headers.get("Location", ""), "protected_status": protected_status, "protected_location": protected_headers.get("Location", ""), "pass": logout_status in (301,302,303) and invalidated})

# Legacy endpoints remain unavailable without session or data output.
for path in ("/admin/evaluate_questionnaire.php", "/faculty/my_evaluations.php"):
    status, headers, html = request(session(), path)
    results.append({"check": "SECURITY-02" + path, "status": status, "body": html.strip(), "pass": status == 410 and "retired" in html.lower()})

print(__import__("json").dumps(results, indent=2))
