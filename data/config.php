<?php
$dsn = "mysql:host=localhost;dbname=scheduler_howto_php";
$username = "root";
$password = "";

$options = array(
	PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'",
	PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
	PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
);