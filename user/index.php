<?php
// user/index.php
// Default entry point for customer section -> Redirect to Overview
require_once __DIR__ . '/../config/config.php';
header('Location: ' . APP_URL . '/user/dashboard.php');
exit;
