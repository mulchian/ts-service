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
        if (!empty($input['username']) && !empty($input['email'])) {
            $username = $input['username'];
            $email = $input['email'];
            $activationLink = $input['activationLink'];

            $user = $userController->fetchUserByNameOrMail($username, $email);
            $userController->sendNewPasswordMail($user, $activationLink);

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