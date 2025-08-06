<?php

use touchdownstars\player\Player;
use touchdownstars\player\PlayerController;
use touchdownstars\player\position\PositionController;
use touchdownstars\team\TeamController;

include($_SERVER['DOCUMENT_ROOT'] . '/init.php');

session_start();

if (isset($pdo, $log)) {
    $playerController = new PlayerController($pdo, $log);
    $teamController = new TeamController($pdo, $log);
    $positionController = new PositionController($pdo);

    if (isset($_SESSION['team']) && !empty($_SESSION['team'])) {
        $team = $_SESSION['team'];
    }
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        if (isset($team) && isset($_POST['position']) && !empty($_POST['position'])) {
            $position = $positionController->fetchPosition($_POST['position']);

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

            $playerJsons = array();
            $positionJson = '';

            foreach ($players as $player) {
                $playerToReturn = array(
                    'id' => $player->getId(),
                    'name' => $player->getFirstName() . ' ' . $player->getLastName(),
                    'age' => $player->getAge(),
                    'ovr' => $player->getOVR(),
                    'talent' => $player->getTalent(),
                    'lineupPosition' => $player->getLineupPosition(),
                    'position' => $player->getType()->getPosition()->getPosition()
                );

                if (isset($_SESSION['lineupPlayer' . $player->getId()]) && !empty($_SESSION['lineupPlayer' . $player->getId()])) {
                    $playerJson = $_SESSION['lineupPlayer' . $player->getId()];
                } else {
                    $playerJson = json_encode($playerToReturn);
                    $_SESSION['lineupPlayer' . $player->getId()] = $playerJson;
                }
                $playerJsons[] = $playerJson;
            }

            $positionJson = json_encode(array(
                'position' => $position->getPosition(),
                'description' => $position->getDescription(),
                'countStarter' => $position->getCountStarter(),
                'countBackup' => $position->getCountBackup(),
                'countPlayer' => count($players)
            ));

            $data['players'] = $playerJsons;
            $data['position'] = $positionJson;
        }

        if (isset($data) && !empty($data)) {
            echo json_encode($data);
        }
    }
}
exit;