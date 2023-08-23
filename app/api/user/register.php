<?php

use touchdownstars\player\PlayerController;
use touchdownstars\team\TeamController;
use touchdownstars\user\UserController;

$logFile = 'user';
include($_SERVER['DOCUMENT_ROOT'] . '/init.php');

session_start();

if (isset($pdo, $log)) {
    $userController = new UserController($pdo, $log);
    $teamController = new TeamController($pdo, $log);
    $playerController = new PlayerController($pdo, $log);

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $content = trim(file_get_contents("php://input"));
        $input = json_decode($content, true);
        if (!empty($input['username']) && !empty($input['email']) && !empty($input['password'])) {
            $log->debug('Login-Request: ' . print_r($input, true));
            $username = $input['username'];
            $email = $input['email'];
            $password = $input['password'];

            $user = $userController->registerNewUser($username, $email, $password);

            if ($user) {
                $_SESSION['created'] = time();
                $_SESSION['user'] = $user;
                echo json_encode($user);
            } else {
                // no user found or password wrong
                echo json_encode('Der User oder die E-Mail-Adresse sind schon registriert.');
            }
        }
    }
}
exit;