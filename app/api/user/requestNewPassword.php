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
            if ($user) {
                // return boolean "requested" that the user can be informed
                echo json_encode($userController->sendNewPasswordMail($user, $activationLink));
            } else {
                // no user found
                $log->info('Die Kombination aus User und E-Mail-Adresse ist nicht bekannt.');
                echo json_encode(false);
            }
        }
    }
}
exit;