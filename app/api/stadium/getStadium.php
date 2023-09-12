<?php

use touchdownstars\stadium\StadiumController;
use touchdownstars\team\TeamController;

$logFile = 'stadium';
include($_SERVER['DOCUMENT_ROOT'] . '/init.php');

session_start();

if (isset($pdo, $log)) {
    $teamController = new TeamController($pdo, $log);
    $stadiumController = new StadiumController($pdo, $log);

    if ($_SERVER['REQUEST_METHOD'] == 'GET') {
        if (!empty($_SESSION['team'])) {
            $team = $_SESSION['team'];
            $stadium = $team->getStadium();
            echo json_encode($stadium);
        } else if (!empty($_GET['userId'])) {
            $userId = $_GET['userId'];
            $team = $teamController->fetchTeam($userId);
            $stadium = $stadiumController->fetchStadium($team);
            echo json_encode($stadium);
        }
    }
}
exit;