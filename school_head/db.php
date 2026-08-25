<?php
// school_head/db.php
$mysqli = new mysqli("localhost", "root", "", "evaluation", 3306);
if ($mysqli->connect_errno) {
    die("Database connection failed: " . $mysqli->connect_error);
}
$mysqli->set_charset("utf8mb4");
define('UPLOAD_DIR', dirname(__DIR__) . '/image/');
define('UPLOAD_URL', '../image/');