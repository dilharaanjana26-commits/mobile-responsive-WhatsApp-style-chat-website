<?php
require_once __DIR__ . '/../lib.php';

session_destroy();
header('Location: /admin/login.php');
exit;
