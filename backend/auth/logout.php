<?php
session_start();
session_destroy();
header("Location: ../../frontend/router.php?page=login");
exit();
