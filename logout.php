<?php
require_once 'php/functions.php';
session_destroy();
header('Location: index.php');
exit();
?>