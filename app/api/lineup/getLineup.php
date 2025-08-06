<?php

use touchdownstars\player\Player;
use touchdownstars\player\position\PositionController;
use touchdownstars\team\TeamController;

$logFile = 'lineup';
include($_SERVER['DOCUMENT_ROOT'] . '/init.php');

session_start();

if (isset($pdo, $log)) {
    $teamController = new TeamController($pdo, $log);
    $positionController = new PositionController($pdo);
    $isUpdated = false;

    if (isset($_SESSION['team'])) {
        $team = $_SESSION['team'];
    }

    $log->debug('getLineup - ' . print_r($_SERVER['REQUEST_METHOD'], true));
    if ($_SERVER['REQUEST_METHOD'] == 'GET') {
        if (isset($team, $_GET['position'])) {
            $log->debug('getLineup for: ' . print_r($_GET, true));

            $position = $positionController->fetchPosition($_GET['position']);

            if ($position->getPosition() == 'R') {
                $returnPos = array('RB', 'WR', 'CB', 'SS', 'FS');
                $players = array_values(array_filter($team->getPlayers(), function (Player $player) use ($returnPos) {
                    return in_array($player->getType()->getPosition()->getPosition(), $returnPos);
                }));
            } else {
                $players = array_values(array_filter($team->getPlayers(), function (Player $player) use ($position) {
                    return $player->getType()->getPosition()->getPosition() == $position->getPosition();
                }));
            }

            if ($position->getPosition() == 'RB') {
                $rbOrder = array('RB1', 'RB2', 'RB');
                usort($players, function (Player $player1, Player $player2) {
                    $lineupPos1 = strlen($player1->getLineupPosition()) < 3 ? 3 : substr($player1->getLineupPosition(), -1);
                    $lineupPos2 = strlen($player2->getLineupPosition()) < 3 ? 3 : substr($player2->getLineupPosition(), -1);
                    return $lineupPos1 <=> $lineupPos2;
                });
            }

            $positionJson = array(
                'position' => $position->getPosition(),
                'description' => $position->getDescription(),
                'countStarters' => $position->getCountStarter(),
                'countBackups' => $position->getCountBackup(),
                'countPlayers' => count($players)
            );

            $data['players'] = $players;
            $data['position'] = $positionJson;
        }

        if (!empty($data)) {
            echo json_encode($data);
        }
    }
}
exit;
