<?php

use touchdownstars\team\TeamController;

$logFile = 'lineup';
include($_SERVER['DOCUMENT_ROOT'] . '/init.php');

session_start();

if (isset($pdo, $log)) {
    $teamController = new TeamController($pdo, $log);
    $playersLinedUp = false;

    if (isset($_SESSION['team'])) {
        $team = $_SESSION['team'];
    }

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        if (isset($team)) {
            $log->debug('autoLineup');

            // Starters automatisch aufstellen
            $team = $teamController->updateLineup($team);
            // Backup automatisch aufstellen
            $team = $teamController->updateLineup($team, 'b');

            foreach ($team->getPlayers() as $player) {
                unset($_SESSION['lineupPlayer' . $player->getId()]);
            }

            $_SESSION['team'] = $team;
            $playersLinedUp = true;
        }

        $data['playersLinedUp'] = $playersLinedUp;

        if (!empty($data)) {
            echo json_encode($data);
        }
    }
}
exit;
