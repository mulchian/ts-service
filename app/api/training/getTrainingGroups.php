<?php

use touchdownstars\team\TeamController;

$logFile = 'training';
include($_SERVER['DOCUMENT_ROOT'] . '/init.php');

session_start();

if (isset($pdo, $log)) {
    $teamController = new TeamController($pdo, $log);

    if (isset($_SESSION['team'])) {
        $team = $_SESSION['team'];
    }

    if ($_SERVER['REQUEST_METHOD'] == 'GET') {
        if (isset($team)) {
            $trainingGroups = array();
            for ($i = 1; $i <= 3; $i++) {
                $playerIds = [];
                foreach ($team->getPlayers() as $player) {
                    if ($player->getTrainingGroup() == 'TE' . $i) {
                        $playerIds[] = $player->getId();
                    }
                }

                $trainingTime = $teamController->getTimeToCount($team, 'TE' . $i);
                if (isset($trainingTime)) {
                    $trainingTime = $trainingTime->format('Y-m-d H:i:s');
                }
                $trainingGroups[] = [
                    'id' => 'TE' . $i,
                    'name' => $teamController->getTrainingGroup($team, 'TE' . $i),
                    'trainingTimeToCount' => $trainingTime,
                    'tgPlayerIds' => $playerIds,
                ];
            }
            $log->debug('getTrainingGroups', ['trainingGroups' => $trainingGroups]);
            if (count($trainingGroups) > 0) {
                echo json_encode($trainingGroups);
            }
        }
    }
}
exit;