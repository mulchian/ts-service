<?php

use touchdownstars\league\LeagueController;
use touchdownstars\main\MainController;
use touchdownstars\team\TeamController;

$logFile = 'declineFriendly';
include($_SERVER['DOCUMENT_ROOT'] . '/init.php');

session_start();

if (isset($pdo) && isset($log)) {
    $teamController = new TeamController($pdo, $log);
    $mainController = new MainController($pdo, $log);
    $leagueController = new LeagueController($pdo, $log);

    if (!empty($_SESSION['team'])) {
        $team = $_SESSION['team'];
    }

    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($team)) {
        $content = trim(file_get_contents("php://input"));
        $input = json_decode($content, true);
        if (isset($input['rowId'], $input['gameTime'], $input['home'], $input['away'])) {
            $id = $input['rowId'];
            $gameTime = $input['gameTime'];
            $home = $input['home'];
            $away = $input['away'];

            $log->debug('Anpfiff: ' . print_r($gameTime, true));
            $data['isDeclined'] = $leagueController->declineFriendly($id, $gameTime, $home, $away);;
        }

        if (!empty($data)) {
            echo json_encode($data);
        }
    }
}
exit;