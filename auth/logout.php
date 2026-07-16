<?php
require '../includes/functions.php';

session_destroy();
redirect('/event-booking/auth/login.php');
?>