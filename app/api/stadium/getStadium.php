<?php

use touchdownstars\stadium\StadiumController;
use touchdownstars\team\TeamController;

$logFile = 'stadium';
include($_SERVER['DOCUMENT_ROOT'] . '/init.php');
include('../team/util.php');

session_start();

if (isset($pdo, $log)) {
    $teamController = new TeamController($pdo, $log);
    $stadiumController = new StadiumController($pdo, $log);

    if ($_SERVER['REQUEST_METHOD'] == 'GET') {
        $team = getTeam($log, $teamController);

        if ($team) {
            $stadium = $stadiumController->fetchStadium($team);
            $log->debug('Stadium is fetched: ' . $stadium->getName());
            echo json_encode($stadium);
        }
    }
}
exit;