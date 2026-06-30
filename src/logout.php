<?php
session_start();
session_destroy(); // ล้าง session
header("Location: ../../HomePage.html");
exit();
?>
