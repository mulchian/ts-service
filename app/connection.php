<?php
$db_host = getenv('MYSQL_HOST');
$db_name = getenv('MYSQL_DBNAME');
$db_user = getenv('MYSQL_USERNAME');
$db_password = getenv('MYSQL_PASSWORD');

if (!isset($pdo)) {
    $pdo = new PDO(
        'mysql:host=' . $db_host . ';dbname=' . $db_name . ';charset=utf8',
        $db_user,
        $db_password
    );

    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
}