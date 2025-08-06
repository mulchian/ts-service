<?php

use touchdownstars\player\skill\SkillController;
use touchdownstars\team\TeamController;

$logFile = 'training';
include($_SERVER['DOCUMENT_ROOT'] . '/init.php');

session_start();

if (isset($pdo, $log)) {
    $teamController = new TeamController($pdo, $log);
    $skillController = new SkillController($pdo, $log);
    $trainingStarted = false;
    $trainingGroups = ['TE0', 'TE1', 'TE2', 'TE3'];

    if (isset($_SESSION['team'])) {
        $team = $_SESSION['team'];
    }

    $log->debug('startTraining');
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $content = trim(file_get_contents("php://input"));
        $input = json_decode($content, true);
        $log->debug('startTraining', ['input' => $input]);
        if (isset($team, $input['trainingGroup'], $input['trainingPart'])) {
            $trainingGroupId = $input['trainingGroup'];
            $trainingPart = $input['trainingPart'];
            $timeToCount = (new DateTime('now', new DateTimeZone('Europe/Berlin')))->modify(' +1 hour');
            $log->debug('Training get bis ' . $timeToCount->format('d.m.Y H:i:s'));
            $log->debug('changeTrainingGroup', ['trainingGroup' => $trainingGroupId, 'trainingPart' => $trainingPart]);

            if ($skillController->train($team, $trainingGroupId, $trainingPart)) {
                $teamController->saveTimeToCount($team, $trainingGroupId, $timeToCount);
                $trainingStarted = true;
            } else {
                $data['error'] = 'Mindestens ein Spieler der Trainingsgruppe hat bereits drei Trainingseinheiten am heutigen Tag durchgeführt.';
            }
        }
    }

    $data['trainingStarted'] = $trainingStarted;
    echo json_encode($data);
}
exit;
