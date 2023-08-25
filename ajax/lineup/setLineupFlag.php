<?php

use touchdownstars\team\TeamController;

include($_SERVER['DOCUMENT_ROOT'] . '/init.php');

session_start();

if (isset($pdo, $log)) {
    $teamController = new TeamController($pdo, $log);

    if (isset($_SESSION['team']) && !empty($_SESSION['team'])) {
        $team = $_SESSION['team'];
    }
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        if (isset($_POST['position']) && !empty($_POST['position'])) {
            $lineupPosition = $_POST['position'];
        }

        $data['lineupFlagSet'] = false;

        if (isset($team) && isset($lineupPosition)) {
            $team = $teamController->updateLineupFlag($team, $lineupPosition);
            $_SESSION['team'] = $team;
            $data['lineupFlagSet'] = true;
        }

        if (isset($data) && !empty($data)) {
            echo json_encode($data);
        }
    }
}