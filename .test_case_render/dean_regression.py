import http.cookiejar
import re
import urllib.error
import urllib.parse
import urllib.request
from pathlib import Path

BASE = "http://127.0.0.1:8092"

class NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, req, fp, code, msg, headers, newurl):
        return None

def client():
    return urllib.request.build_opener(
        urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()), NoRedirect()
    )

def get(opener, path, form=None):
    data = urllib.parse.urlencode(form).encode() if form is not None else None
    try:
        res = opener.open(urllib.request.Request(BASE + path, data=data), timeout=15)
        return res.status, res.headers, res.read().decode("utf-8", "replace")
    except urllib.error.HTTPError as e:
        return e.code, e.headers, e.read().decode("utf-8", "replace")

checks = []
root = Path(__file__).resolve().parents[1]
db_source = (root / "dean" / "db.php").read_text(encoding="utf-8")
include = re.search(r"/shared/([^'\"]*Authentication[^'\"]*\.php)", db_source)
actual_names = {p.name for p in (root / "shared").iterdir()}
include_matches = bool(include and include.group(1) in actual_names)
checks.append(("EVAL41", include_matches,
               f"include target={include.group(1) if include else 'not found'}; exact tracked file match={include_matches}"))

anon = client()
status, headers, html = get(anon, "/dean/dean_login.php")
checks.append(("EVAL15", status == 200 and "PBI — Dean Login" in html,
               f"GET dean_login: HTTP {status}"))
status2, headers2, html2 = get(client(), "/dean/dean_login.php")
checks.append(("EVAL16", status2 == 200 and "PBI — Dean Login" in html2,
               f"independent GET dean_login: HTTP {status2}"))
auth = client()
_, _, html = get(auth, "/dean/dean_login.php")
token = re.search(r'name=["\']csrf_token["\']\s+value=["\']([^"\']+)', html)
form = {"username": "codex_dean", "password": "Codex!2026Test"}
if token:
    form["csrf_token"] = token.group(1)
status, headers, body = get(auth, "/dean/dean_login.php", form)
login_ok = status in (301, 302, 303) and "dean_dashboard.php" in headers.get("Location", "")
checks.append(("EVAL42", login_ok, f"POST dean login: HTTP {status}, redirect={headers.get('Location','')}"))
status, headers, body = get(auth, "/dean/dean_dashboard.php") if login_ok else (0, {}, body)
dashboard_ok = status == 200 and not re.search(r"(Fatal error|Uncaught mysqli_sql_exception|Warning: mysqli_)", body, re.I)
checks.append(("EVAL17", dashboard_ok, f"Authenticated dean dashboard: HTTP {status}, no PHP/database error={dashboard_ok}"))
status, headers, _ = get(client(), "/dean/dean_dashboard.php")
checks.append(("EVAL17 access guard", status in (301, 302, 303) and "dean_login.php" in headers.get("Location", ""),
               f"Unauthenticated dashboard: HTTP {status}, redirect={headers.get('Location','')}"))

for case, passed, evidence in checks:
    print(f"{'PASS' if passed else 'FAIL'}\t{case}\t{evidence}")
if not all(passed for _, passed, _ in checks):
    raise SystemExit(1)
