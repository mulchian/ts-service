<?php

use touchdownstars\player\PlayerController;
use touchdownstars\team\TeamController;

$logFile = 'autoLineup';
include('../../init.php');

session_start();

if (isset($pdo) && isset($log)) {
    $playerController = new PlayerController($pdo, $log);
    $teamController = new TeamController($pdo, $log);

    if (isset($_SESSION['team']) && !empty($_SESSION['team'])) {
        $team = $_SESSION['team'];
    }
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $data['playersLinedUp'] = false;

        if (isset($team)) {
            // Starters automatisch aufstellen
            $team = $teamController->updateLineup($team);
            // Backup automatisch aufstellen
            $team = $teamController->updateLineup($team, 'b');

            foreach ($team->getPlayers() as $player) {
                unset($_SESSION['lineupPlayer' . $player->getId()]);
            }

            $_SESSION['team'] = $team;
            $data['playersLinedUp'] = true;
        }

        if (isset($data) && !empty($data)) {
            echo json_encode($data);
        }
    }
}