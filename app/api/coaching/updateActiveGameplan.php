<?php

use touchdownstars\team\TeamController;

$logFile = 'coaching';
include($_SERVER['DOCUMENT_ROOT'] . '/init.php');

session_start();

if (isset($pdo, $log)) {
    $teamController = new TeamController($pdo, $log);
    $isUpdated = false;

    if (isset($_SESSION['team'])) {
        $team = $_SESSION['team'];
    }

    $log->debug('updateSelectedGameplan - ' . print_r($_SERVER['REQUEST_METHOD'], true));
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $content = trim(file_get_contents("php://input"));
        $input = json_decode($content, true);
        if (isset($team, $input['gameplanNr'], $input['teamPart'])) {
            $log->debug('updateLineupPosition: ' . print_r($input, true));
            $gameplanNr = $input['gameplanNr'];
            $teamPart = $input['teamPart'];

            $gameplan = '';
            switch ($teamPart) {
                case 'offense':
                    $gameplan = 'GameplanOff';
                    break;
                case 'defense':
                    $gameplan = 'GameplanDef';
                    break;
                case 'general':
                    $gameplan = 'GameplanGeneral';
                    break;
            }
            $teamController->updateGameplan($team, $gameplan, $gameplanNr);

            $_SESSION['team'] = $team;
            $isUpdated = true;
        }

        $data['isUpdated'] = $isUpdated;
        echo json_encode($data);
    }
}
exit;