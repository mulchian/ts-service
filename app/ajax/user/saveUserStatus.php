<?php

use touchdownstars\user\UserController;

$logFile = 'saveUserStatus';
include($_SERVER['DOCUMENT_ROOT'] . '/init.php');

session_start();

if (isset($pdo) && isset($log)) {
    $userController = new UserController($pdo, $log);

    if (isset($_SESSION['user']) && !empty($_SESSION['user'])) {
        $user = $_SESSION['user'];
    }
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($user)) {
        // save lastActiveTime
        if (isset($_POST['lastActiveTime'], $_POST['status'])) {
            $lastActiveTime = $_POST['lastActiveTime'];
            $status = $_POST['status'];
            $user->setLastActiveTime($lastActiveTime);
            $user->setStatus($status);
            $userController->saveUser($user);
        }
    }
}