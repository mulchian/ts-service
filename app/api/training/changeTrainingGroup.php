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
    $trainingGroupChanged = false;
    $trainingGroups = ['TE0', 'TE1', 'TE2', 'TE3'];

    if (isset($_SESSION['team'])) {
        $team = $_SESSION['team'];
    }

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $content = trim(file_get_contents("php://input"));
        $input = json_decode($content, true);
        if (isset($team, $input['newTrainingGroup'])) {
            $newTrainingGroup = $input['newTrainingGroup'];
            if (!empty($input['playerId'])) {
                $playerId = $input['playerId'];
            }
            $log->debug('changeTrainingGroup', ['newTrainingGroup' => $newTrainingGroup, 'playerId' => $playerId ?? null]);

            if (!empty($playerId)) {
                $player = array_values(array_filter($team->getPlayers(), function ($player) use ($playerId) {
                    return $player->getId() == $playerId;
                }))[0];
                $newTgTrainingTime = $teamController->getTimeToCount($team, $newTrainingGroup);
                $oldTrainingTime = $teamController->getTimeToCount($team, $player->getTrainingGroup());
                if (updatable($newTgTrainingTime) && updatable($oldTrainingTime)) {
                    $trainingGroupChanged = $skillController->setTrainingGroup($team, $newTrainingGroup, $playerId);
                }
            } else {
                $isUpdatable = true;
                foreach ($trainingGroups as $trainingGroup) {
                    $trainingTime = $teamController->getTimeToCount($team, $trainingGroup);
                    if (!updatable($trainingTime)) {
                        $isUpdatable = false;
                    }
                }
                if ($isUpdatable) {
                    $trainingGroupChanged = $skillController->setTrainingGroup($team, $newTrainingGroup);
                } else {
                    // only update the player not training right now
                    $players = array_filter($team->getPlayers(), function ($player) use ($team, $teamController) {
                        return updatable($teamController->getTimeToCount($team, $player->getTrainingGroup()));
                    });
                    foreach ($players as $player) {
                        $trainingGroupChanged = $skillController->setTrainingGroup($team, $newTrainingGroup, $player->getId());
                    }
                }
            }
        }
    }

    $data['trainingGroupChanged'] = $trainingGroupChanged;
    echo json_encode($data);
}
exit;