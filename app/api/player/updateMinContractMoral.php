<?php

use touchdownstars\player\PlayerController;

$logFile = 'player';
include($_SERVER['DOCUMENT_ROOT'] . '/init.php');

session_start();

if (isset($pdo, $log)) {
    $playerController = new PlayerController($pdo, $log);
    $isUpdated = false;

    if (isset($_SESSION['team'])) {
        $team = $_SESSION['team'];
    }

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $content = trim(file_get_contents("php://input"));
        $input = json_decode($content, true);
        if (isset($team, $input['playerId'], $input['minContractMoral'])) {
            $log->debug('updateMinContractMoral: ' . print_r($input, true));
            $playerId = $input['playerId'];
            $minContractMoral = $input['minContractMoral'];

            $player = $team->getPlayers()[$playerId];
            if (isset($player)) {
                if ($minContractMoral > 0.75) {
                    $player->setMinContractMoral($minContractMoral);

                    $playerController->savePlayer($player);
                    $_SESSION['team'] = $team;
                    unset($_SESSION['player' . $playerId]);
                    unset($_SESSION['player' . $playerId . 'Team']);
                    $isUpdated = true;
                } else {
                    $errorMsg = 'Mindestverhandlungsmoral muss mindestens 0.75 sein.';
                }
            } else {
                $errorMsg = 'Spieler nicht gefunden.';
            }
        }


        if (!empty($errorMsg)) {
            $log->warning('updateMinContractMoral: ' . $errorMsg);
            $data['error'] = $errorMsg;
        }
        $data['isUpdated'] = $isUpdated;

        if (!empty($data)) {
            echo json_encode($data);
        }
    }
}
exit;