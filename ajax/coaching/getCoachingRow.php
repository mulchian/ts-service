<?php

use touchdownstars\coaching\CoachingController;
use touchdownstars\team\TeamController;

$logFile = 'getCoachingRow';
include($_SERVER['DOCUMENT_ROOT'] . '/init.php');

session_start();

if (isset($pdo) && isset($log)) {
    $teamController = new TeamController($pdo, $log);
    $coachingController = new CoachingController($pdo);

    if (isset($_SESSION['team']) && !empty($_SESSION['team'])) {
        $team = $_SESSION['team'];
    }

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        if (isset($team) && isset($_POST['gameplanNr']) && !empty($_POST['gameplanNr']) && isset($_POST['down']) && !empty($_POST['down']) && isset($_POST['teamPart']) && !empty($_POST['teamPart'])) {
            $gameplanNr = $_POST['gameplanNr'];
            $down = $_POST['down'];
            $teamPart = $_POST['teamPart'];

            include($_SERVER['DOCUMENT_ROOT'] . '/pages/team/coaching/coachingInfo.php');
            include($_SERVER['DOCUMENT_ROOT'] . '/pages/team/coaching/coachingRow.php');
        }
    }
}