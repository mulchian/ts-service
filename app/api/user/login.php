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
        if (!empty($input['username']) && !empty($input['password'])) {
            $log->debug('Login-Request: ' . print_r($input, true));
            $username = $input['username'];
            $password = $input['password'];

            $user = $userController->fetchUser($username, $password);

            if ($user) {
                $_SESSION['created'] = time();

                $team = $teamController->fetchTeam($user->getId());
                if ($team) {
                    $yesterday = (new DateTime('yesterday'))->setTime(0,0);
                    $log->debug('Last-Active-Date: ' . $user->getLastActiveTime()->format('Y-m-d H:i'));
                    $log->debug('Yesterday-Date: ' . $yesterday->format('Y-m-d H:i'));
                    if ($user->getLastActiveTime() <= $yesterday) {
                        $log->debug('reset Trainings');
                        foreach ($team->getPlayers() as $player) {
                            $log->debug('Player ' . $player->getId() . ' Trainings: ' . $player->getNumberOfTrainings());
                            $player->setNumberOfTrainings(0);
                            $playerController->updateNumberOfTrainings($player);
                        }
                    }
                }

                $user->setLastActiveTime(new DateTime('now', new DateTimeZone('Europe/Berlin')));
                $user->setStatus('online');
                $log->debug('User: ' . print_r($user, true));
                $userController->saveUser($user);
                $_SESSION['user'] = $user;

                if ($team) {
                    $team->setUser($user);
                    $_SESSION['team'] = $team;
                }

                echo json_encode($user);
            } else {
                // no user found or password wrong
                echo json_encode('Der Username oder das Passwort sind falsch.');
            }
        }
    }
}
exit;