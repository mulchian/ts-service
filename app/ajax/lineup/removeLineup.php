<?php

use touchdownstars\player\Player;
use touchdownstars\player\PlayerController;
use touchdownstars\team\Team;
use touchdownstars\team\TeamController;

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

        $data['lineupRemoved'] = false;

        if (isset($team) && isset($lineupPosition)) {
            foreach ($teamController->getPlayerIdsToLineupPosition($team, $lineupPosition) as $playerId) {
                $playerController->updateLineupPosition($playerId, null);
                updateTeamInSession($team, $playerId);
            }
            $_SESSION['team'] = $team;
            $data['lineupRemoved'] = true;
        }

        if (isset($data) && !empty($data)) {
            echo json_encode($data);
        }
    }
}
exit;

function updateTeamInSession(Team $team, string $playerId)
{
    $player = array_values(array_filter($team->getPlayers(), function (Player $player) use ($playerId) {
        return $player->getId() == $playerId;
    }))[0];
    $player->setLineupPosition(null);
    unset($_SESSION['lineupPlayer' . $playerId]);
}