<?php

use touchdownstars\team\TeamController;

$logFile = 'getLineupRow';
include($_SERVER['DOCUMENT_ROOT'] . '/init.php');

session_start();

if (isset($pdo, $log)) {
    $teamController = new TeamController($pdo, $log);

    if (isset($_SESSION['team']) && !empty($_SESSION['team'])) {
        $team = $_SESSION['team'];
    }
    if ($_SERVER['REQUEST_METHOD'] == 'GET') {
        if (isset($team) && isset($_GET['position']) && !empty($_GET['position'])) {
            $log->debug('pos: ' . $_GET['position']);
            $teamPart = $teamController->getTeamPartToPosition($_GET['position']);
            $players = $teamController->getStartingPlayers($team, $teamPart);

            include($_SERVER['DOCUMENT_ROOT'] . '/pages/team/lineup/lineupInfo.php');
            include($_SERVER['DOCUMENT_ROOT'] . '/pages/team/lineup/lineupRow.php');

        }
    }
}



