<?php
require_once '../includes/db.php';
startSecureSession();
session_destroy();
header('Location: login.php');
exit;
