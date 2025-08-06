<?php

use touchdownstars\player\Player;
use touchdownstars\player\PlayerController;
use touchdownstars\team\Team;
use touchdownstars\team\TeamController;

$logFile = 'lineup';
include($_SERVER['DOCUMENT_ROOT'] . '/init.php');

session_start();

if (isset($pdo, $log)) {
    $teamController = new TeamController($pdo, $log);
    $playerController = new PlayerController($pdo, $log);
    $playersLinedUp = false;

    if (isset($_SESSION['team'])) {
        $team = $_SESSION['team'];
    }

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $content = trim(file_get_contents("php://input"));
        $input = json_decode($content, true);
        if (isset($team, $input['lineupPosition'], $input['starters'], $input['backups'], $input['players'])) {
            $log->debug('saveLineup');
            $lineupPosition = $input['lineupPosition'];
            $starters = $input['starters'];
            $backups = $input['backups'];
            $players = $input['players'];

            $countPos = 1;
            foreach ($starters as $starter) {
                $player = array_values(array_filter($team->getPlayers(), function (Player $p) use ($starter) {
                    return $p->getId() == $starter['id'];
                }))[0];

                if ($player) {
                    $lineupPos = $lineupPosition;
                    if (str_contains($lineupPosition, 'RB') || str_contains($lineupPosition, 'MLB')) {
                        $lineupPos = $lineupPosition . $countPos++;
                    }
                    $log->debug('LineupPosition: ' . $lineupPos);
                    $playerController->updateLineupPosition($player->getId(), $lineupPos);
                    updateTeamInSession($team, $player->getId(), $lineupPos);
                }
            }

            foreach ($backups as $backup) {
                $player = array_values(array_filter($team->getPlayers(), function (Player $p) use ($backup) {
                    return $p->getId() == $backup['id'];
                }))[0];
                if ($player) {
                    $playerController->updateLineupPosition($player->getId(), $lineupPosition . 'b');
                    updateTeamInSession($team, $player->getId(), $lineupPosition . 'b');
                }
            }

            foreach ($players as $freePlayer) {
                $player = array_values(array_filter($team->getPlayers(), function (Player $p) use ($freePlayer) {
                    return $p->getId() == $freePlayer['id'];
                }))[0];
                if ($player) {
                    $playerController->updateLineupPosition($player->getId(), null);
                    updateTeamInSession($team, $player->getId(), null);
                }
            }

            $_SESSION['team'] = $team;
            $playersLinedUp = true;
        }

        $data['playersLinedUp'] = $playersLinedUp;
        echo json_encode($data);
    }
}
exit;

function updateTeamInSession(Team $team, string $playerId, ?string $lineupPosition)
{
    $player = array_values(array_filter($team->getPlayers(), function (Player $player) use ($playerId) {
        return $player->getId() == $playerId;
    }))[0];
    $player->setLineupPosition($lineupPosition);
    unset($_SESSION['lineupPlayer' . $playerId]);
}