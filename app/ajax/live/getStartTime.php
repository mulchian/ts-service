<?php

use touchdownstars\league\LeagueController;

$logFile = 'getStartTime';
include($_SERVER['DOCUMENT_ROOT'] . '/init.php');

session_start();

if (isset($pdo) && isset($log)) {
    $leagueController = new LeagueController($pdo, $log);

    if (isset($_SESSION['team']) && !empty($_SESSION['team'])) {
        $team = $_SESSION['team'];
    }
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($team, $_SESSION['season'], $_SESSION['gameday'])) {
        $game = $leagueController->fetchGame($team, $_SESSION['season'], $_SESSION['gameday']);
        $data['startTime'] = $game['gameTime'];

        if (isset($data) && !empty($data)) {
            echo json_encode($data);
        }
    }
}