<?php

use touchdownstars\team\TeamController;

$logFile = 'team';
include($_SERVER['DOCUMENT_ROOT'] . '/init.php');

session_start();

if (isset($pdo, $log)) {
    $teamController = new TeamController($pdo, $log);

    if ($_SERVER['REQUEST_METHOD'] == 'GET') {
        if (!empty($_SESSION['team'])) {
            $team = $_SESSION['team'];
            echo json_encode($team);
        } else if (!empty($_GET['userId'])) {
            $userId = $_GET['userId'];
            $team = $teamController->fetchTeam($userId);
            echo json_encode($team);
        }
    }
}
exit;