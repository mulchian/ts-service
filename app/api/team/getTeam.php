<?php

use touchdownstars\team\TeamController;

$logFile = 'team';
include($_SERVER['DOCUMENT_ROOT'] . '/init.php');
include('../util/util.php');

session_start();

if (isset($pdo, $log)) {
    $teamController = new TeamController($pdo, $log);

    if ($_SERVER['REQUEST_METHOD'] == 'GET') {
        $team = getTeam($log, $teamController);

        if ($team) {
            echo json_encode($team);
        }
    }
}
exit;