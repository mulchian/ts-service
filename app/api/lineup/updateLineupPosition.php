<?php

use touchdownstars\team\TeamController;

$logFile = 'lineup';
include($_SERVER['DOCUMENT_ROOT'] . '/init.php');

session_start();

if (isset($pdo, $log)) {
    $teamController = new TeamController($pdo, $log);
    $isUpdated = false;

    if (isset($_SESSION['team'])) {
        $team = $_SESSION['team'];
    }

    $log->debug('updateLineupPosition - ' . print_r($_SERVER['REQUEST_METHOD'], true));
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $content = trim(file_get_contents("php://input"));
        $input = json_decode($content, true);
        if (isset($team, $input['lineupPosition'])) {
            $log->debug('updateLineupPosition: ' . print_r($input, true));
            $lineupPosition = $input['lineupPosition'];

            $team = $teamController->updateLineupFlag($team, $lineupPosition);
            $_SESSION['team'] = $team;
            $isUpdated = true;
        }

        $data['isUpdated'] = $isUpdated;
        echo json_encode($data);
    }
}
exit;