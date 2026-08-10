<?php
require_once 'includes/auth.php';

session_unset();
session_destroy();
header("Location: login.php?logged_out=1");
exit();