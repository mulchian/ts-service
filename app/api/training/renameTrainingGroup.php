<?php

use touchdownstars\team\TeamController;

$logFile = 'training';
include($_SERVER['DOCUMENT_ROOT'] . '/init.php');

session_start();

if (isset($pdo, $log)) {
    $teamController = new TeamController($pdo, $log);
    $trainingGroupNameChanged = false;
    $trainingGroups = ['TE0', 'TE1', 'TE2', 'TE3'];

    if (isset($_SESSION['team'])) {
        $team = $_SESSION['team'];
    }

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $content = trim(file_get_contents("php://input"));
        $input = json_decode($content, true);
        if (isset($team, $input['trainingGroup'], $input['newName'])) {
            $trainingGroupId = $input['trainingGroup']['id'];
            $newName = $input['newName'];
            $log->debug('changeTrainingGroup', ['trainingGroup' => $trainingGroupId, 'newName' => $newName]);

            if ($teamController->saveTrainingGroupName($team, $trainingGroupId, $newName)) {
                $trainingGroupNameChanged = true;
            }
        }
    }

    $data['trainingGroupNameChanged'] = $trainingGroupNameChanged;
    echo json_encode($data);
}
exit;
