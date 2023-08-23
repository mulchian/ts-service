<?php

use touchdownstars\league\LeagueController;
use touchdownstars\main\MainController;
use touchdownstars\team\TeamController;

$logFile = 'acceptFriendly';
include($_SERVER['DOCUMENT_ROOT'] . '/init.php');

session_start();

if (isset($pdo) && isset($log)) {
    $teamController = new TeamController($pdo, $log);
    $mainController = new MainController($pdo, $log);
    $leagueController = new LeagueController($pdo, $log);

    if (!empty($_SESSION['team'])) {
        $selfTeam = $_SESSION['team'];
    }

    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($selfTeam)) {
        $content = trim(file_get_contents("php://input"));
        $input = json_decode($content, true);
        if (isset($input['home'], $input['away'], $input['gameTime'])) {
            $gameTime = $input['gameTime'];
            $home = $input['home'];
            $away = $input['away'];
            $homeAccepted = ($home == $selfTeam->getName());
            $awayAccepted = ($away == $selfTeam->getName());

            $log->debug('gameTime: ' . $gameTime);
            $log->debug('home: ' . $home);
            $log->debug('away: ' . $away);

            $id = $leagueController->acceptFriendly($gameTime, $home, $away, $homeAccepted, $awayAccepted);

            $log->debug('friendly-id: ' . $id);

            $data['isAccepted'] = true;
            $data['id'] = $id;
        }

        if (!empty($data)) {
            echo json_encode($data);
        }
    }
}
exit;