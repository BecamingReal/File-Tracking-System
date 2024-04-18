<?php
session_start();
session_unset();
session_destroy();

// Delete the auth_token cookie by setting an expired time in the past
setcookie("auth_token", 0, time() - (30 * 360), "/");
header("Location: index.php");
exit;
?>
