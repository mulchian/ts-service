<?php

use touchdownstars\player\skill\SkillController;
use touchdownstars\team\TeamController;

$logFile = 'training';
include($_SERVER['DOCUMENT_ROOT'] . '/init.php');
include('../util/util.php');

session_start();

if (isset($pdo, $log)) {
    $teamController = new TeamController($pdo, $log);
    $skillController = new SkillController($pdo, $log);
    $intensityUpdated = false;

    if (isset($_SESSION['team'])) {
        $team = $_SESSION['team'];
    }

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $content = trim(file_get_contents("php://input"));
        $input = json_decode($content, true);
        if (isset($team, $input['intensity'])) {
            $intensity = $input['intensity'];
            $playerId = $input['playerId'] ?? null;
            $log->debug('change intensity', ['intensity' => $intensity, 'playerId' => $playerId]);

            if (!empty($playerId)) {
                $intensityUpdated = $skillController->setIntensity($team, $intensity, $playerId);
            } else {
                // only update the players that aren't training right now
                $players = array_filter($team->getPlayers(), function ($player) use ($team, $teamController) {
                    return updatable($teamController->getTimeToCount($team, $player->getTrainingGroup()));
                });
                if (count($players) == count($team->getPlayers())) {
                    // if all players are available, update the intensity for all players
                    $intensityUpdated = $skillController->setIntensity($team, $intensity);
                } else {
                    foreach ($players as $player) {
                        $intensityUpdated = $skillController->setIntensity($team, $intensity, $player->getId());
                    }
                }
            }
        }
    }

    $data['intensityChanged'] = $intensityUpdated;
    echo json_encode($data);
}
exit;