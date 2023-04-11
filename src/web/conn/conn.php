<?php
$db_hostname = "localhost";
$db_username = "root";
$db_password = "";
$db_name = "testwsqlnew";
$conn = new mysqli($db_hostname, $db_username, $db_password, $db_name);
$conn->set_charset("utf8mb4");
