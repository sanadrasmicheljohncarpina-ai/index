<?php
// principal/db.php
// Place this file inside: htdocs/index/principal/
// Include in every principal PHP file with: require_once 'db.php';

// Defaults match the local XAMPP setup; override with environment variables
// (DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT) for any non-local deployment.
$mysqli = new mysqli(
    getenv('DB_HOST') ?: "localhost",
    getenv('DB_USER') ?: "root",
    getenv('DB_PASS') !== false ? getenv('DB_PASS') : "",
    getenv('DB_NAME') ?: "evaluation",
    (int)(getenv('DB_PORT') ?: 3306)
);
if ($mysqli->connect_errno) {
    die("Database connection failed: " . $mysqli->connect_error);
}
$mysqli->set_charset("utf8mb4");

// Absolute path to the shared image folder
// principal/ is one level inside index/, so go up one level
// dirname(__DIR__) from principal/ = htdocs/index/
define('UPLOAD_DIR', dirname(__DIR__) . '/image/');

// Relative URL used in HTML <img src=""> tags inside principal pages
define('UPLOAD_URL', '../image/');

// ---- Self-healing schema check (matches your ALTER TABLE-on-load pattern) ----
// Confirms education_level is VARCHAR (not the old 3-value ENUM) and that
// employee_id exists, in case this environment hasn't been migrated yet.
// Runs once per login session instead of on every request / live-poll.
$__schemaKey  = 'principal_db_schema_v1';
$__hasSession = session_status() === PHP_SESSION_ACTIVE;
if (!$__hasSession || empty($_SESSION[$__schemaKey])) {
$col = $mysqli->query("SHOW COLUMNS FROM users LIKE 'education_level'")->fetch_assoc();
if ($col && stripos($col['Type'], 'enum') !== false) {
    $mysqli->query("ALTER TABLE users MODIFY COLUMN education_level VARCHAR(20) NULL");
}
// (portable: "ADD COLUMN IF NOT EXISTS" is MariaDB-only syntax)
$__eid = $mysqli->query("SHOW COLUMNS FROM users LIKE 'employee_id'");
if ($__eid && $__eid->num_rows === 0) {
    $mysqli->query("ALTER TABLE users ADD COLUMN employee_id VARCHAR(50) NULL");
}
if ($__hasSession && $mysqli->errno === 0) {
    $_SESSION[$__schemaKey] = 1;
}
}
