<?php
// admin/mail_config.php
// SMTP settings are loaded from environment variables or the root .env.php.
// Never commit a real SMTP password/app password to the repository.

require_once dirname(__DIR__) . '/shared/app_config.php';

define('SMTP_HOST', (string)app_setting('SMTP_HOST', 'smtp.gmail.com'));
define('SMTP_PORT', (int)app_setting('SMTP_PORT', 587));
define('SMTP_USER', (string)app_setting('SMTP_USER', 'youraddress@gmail.com'));
define('SMTP_PASS', (string)app_setting('SMTP_PASS', 'your16charapppassword'));
define('SMTP_FROM_NAME', (string)app_setting('SMTP_FROM_NAME', 'PBI Admin Security'));
