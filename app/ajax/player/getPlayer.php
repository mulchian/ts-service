<?php

use touchdownstars\player\Player;
use touchdownstars\player\PlayerController;
use touchdownstars\player\skill\SkillController;
use touchdownstars\team\TeamController;

$logFile = 'player';
include($_SERVER['DOCUMENT_ROOT'] . '/init.php');

session_start();

if (isset($pdo, $log)) {
    $playerController = new PlayerController($pdo, $log);
    $teamController = new TeamController($pdo, $log);
    $skillController = new SkillController($pdo, $log);

    if (!empty($_SESSION['team'])) {
        $team = $_SESSION['team'];
    }
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        if (isset($team) && !empty($_POST['idPlayer'])) {
            $idPlayer = $_POST['idPlayer'];

            if (isset($_SESSION['player' . $idPlayer]) && !empty($_SESSION['player' . $idPlayer])
                && isset($_SESSION['player' . $idPlayer . 'Team']) && !empty($_SESSION['player' . $idPlayer . 'Team'])) {
                $playerJson = $_SESSION['player' . $idPlayer];
                $data['team'] = $_SESSION['player' . $idPlayer . 'Team'];
            } else {
                $player = array_values(array_filter($team->getPlayers(), function (Player $player) use ($idPlayer) {
                    return $player->getId() == $idPlayer;
                }))[0];

                if (empty($player)) {
                    // Spieler ist kein eigener Spieler, sondern von fremdem Team → Aus Datenbank laden
                    $player = $playerController->fetchPlayer($idPlayer);
                }
                $playerJson = $player->getJson();
                $teamOfPlayer = $teamController->fetchTeamOfPlayer($player->getId());

                $_SESSION['player' . $idPlayer] = $playerJson;
                $_SESSION['player' . $idPlayer . 'Team'] = $teamOfPlayer->getName();

                $data['team'] = $teamOfPlayer->getName();
            }

            $data['skillNames'] = $skillController->fetchSkillNames();
            $data['player'] = $playerJson;
        }
        if (!empty($data)) {
            echo json_encode($data);
        }
    }
}
exit;