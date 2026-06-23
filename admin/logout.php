<?php
require_once __DIR__ . '/../config/security.php';
secure_session_start();
session_destroy();
header("Location: login.php");
exit;
