<?php

use touchdownstars\user\UserController;

$logFile = 'changeUserStatus';
include(__DIR__ . '/../init.php');

if (isset($pdo) && isset($log)) {
    $userController = new UserController($pdo, $log);
    $users = $userController->fetchAllUsers();

    foreach ($users as $user) {
        $log->debug('User-ID: ' . $user->getId());
        $log->debug('LastActiveTime: ' . $user->getLastActiveTime());
        if ($user->getStatus() !== 'offline' && $user->getLastActiveTime() <= (time() - 330)) {
            $user->setStatus('offline');
            $userController->saveUser($user);
        }
    }
}