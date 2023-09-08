<?php

use touchdownstars\user\UserController;

$logFile = 'user';
include($_SERVER['DOCUMENT_ROOT'] . '/init.php');

session_start();

if (isset($pdo, $log)) {
    $userController = new UserController($pdo, $log);

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $content = trim(file_get_contents("php://input"));
        $input = json_decode($content, true);
        if (!empty($input['userId']) && !empty($input['password'])) {
            $userId = $input['userId'];
            $password = $input['password'];

            $user = $userController->fetchUserById($userId);
            if ($user) {
                // return boolean "requested" that the user can be informed
                echo json_encode($userController->changeUserPassword($user, $password));
            } else {
                // no user found
                $log->info('Die User-ID ist nicht bekannt.');
                echo json_encode(false);
            }
        }
    }
}