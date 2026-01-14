<?php
session_start();

session_unset();
session_destroy();

header("Location: logged_out.html");
exit();