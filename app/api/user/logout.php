<?php

use touchdownstars\user\UserController;

$logFile = 'user';
include($_SERVER['DOCUMENT_ROOT'] . '/init.php');

session_start();

if (isset($pdo, $log)) {
    $userController = new UserController($pdo, $log);

    $log->debug('Logout-Request: ' . print_r($_SESSION['user'], true));
    if (isset($_SESSION['user'])) {
        $userController = new UserController($pdo, $log);
        $user = $_SESSION['user'];
        $user->setStatus('offline');
        $userController->saveUser($user);
    }
    unset($_SESSION);
    session_unset();
    session_destroy();

    $data['loggedOut'] = true;
    echo json_encode($data);
}
