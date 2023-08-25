<?php

use touchdownstars\player\Player;
use touchdownstars\player\PlayerController;
use touchdownstars\team\Team;
use touchdownstars\team\TeamController;

$logFile = 'saveLineup';
include('../../init.php');

session_start();

if (isset($pdo, $log)) {
    $playerController = new PlayerController($pdo, $log);
    $teamController = new TeamController($pdo, $log);

    if (isset($_SESSION['team']) && !empty($_SESSION['team'])) {
        $team = $_SESSION['team'];
    }
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        if (isset($_POST['position']) && !empty($_POST['position'])) {
            $lineupPosition = $_POST['position'];
        }
        if (isset($_POST['starterPlayers']) && !empty($_POST['starterPlayers'])) {
            $starterPlayers = $_POST['starterPlayers'];
        }
        if (isset($_POST['backupPlayers']) && !empty($_POST['backupPlayers'])) {
            $backupPlayers = $_POST['backupPlayers'];
        }
        if (isset($_POST['listPlayers']) && !empty($_POST['listPlayers'])) {
            $listPlayers = $_POST['listPlayers'];
        }

        $data['playersLinedUp'] = false;

        if (isset($team) && isset($lineupPosition)) {
            if (isset($starterPlayers)) {
                $countPos = 1;
                foreach ($starterPlayers as $playerId) {
                    if (is_numeric($playerId) && $teamController->hasPlayer($team, $playerId)) {
                        $lineupPos = $lineupPosition;
                        if (strpos($lineupPosition,'RB') !== false || strpos($lineupPosition, 'MLB') !== false) {
                            $lineupPos = $lineupPosition . $countPos++;
                        }
                        $log->debug('LineupPosition: ' . $lineupPos);
                        $playerController->updateLineupPosition($playerId, $lineupPos);
                        updateTeamInSession($team, $playerId, $lineupPos);
                    }
                }
            }
            if (isset($backupPlayers)) {
                foreach ($backupPlayers as $playerId) {
                    if (is_numeric($playerId) && $teamController->hasPlayer($team, $playerId)) {
                        $playerController->updateLineupPosition($playerId, $lineupPosition . 'b');
                        updateTeamInSession($team, $playerId, $lineupPosition . 'b');
                    }
                }
            }
            if (isset($listPlayers)) {
                foreach ($listPlayers as $playerId) {
                    if (is_numeric($playerId) && $teamController->hasPlayer($team, $playerId)) {
                        $playerController->updateLineupPosition($playerId, null);
                        updateTeamInSession($team, $playerId, null);
                    }
                }
            }
            $_SESSION['team'] = $team;
            $data['playersLinedUp'] = true;
            $data['teamPart'] = $teamController->getTeamPartToPosition($lineupPosition);
        }

        if (isset($data) && !empty($data)) {
            echo json_encode($data);
        }
    }
}
exit;

function updateTeamInSession(Team $team, string $playerId, ?string $lineupPosition) {
    $player = array_values(array_filter($team->getPlayers(), function (Player $player) use ($playerId) {
        return $player->getId() == $playerId;
    }))[0];
    $player->setLineupPosition($lineupPosition);
    unset($_SESSION['lineupPlayer' . $playerId]);
}