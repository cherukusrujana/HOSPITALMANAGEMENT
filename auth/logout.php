<?php
require_once '../includes/functions.php';

session_destroy();
header('Location: /hospital/auth/login.php');
exit();