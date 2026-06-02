<?php
session_start();
session_destroy();
header("Location: ../index.php?erro=3");
exit;
