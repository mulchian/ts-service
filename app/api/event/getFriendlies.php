<?php

use touchdownstars\league\LeagueController;
use touchdownstars\team\TeamController;

$logFile = 'event';
include($_SERVER['DOCUMENT_ROOT'] . '/init.php');

session_start();

if (isset($pdo, $log)) {
    $teamController = new TeamController($pdo, $log);
    $leagueController = new LeagueController($pdo, $log);

    if (!empty($_SESSION['team'])) {
        $team = $_SESSION['team'];
    }

    if ($_SERVER['REQUEST_METHOD'] == 'GET') {
        if (isset($team)) {
            $allFriendlies = $leagueController->getAllFriendlies($team);
            $log->debug('all friendlies fetched');
            if (count($allFriendlies) > 0) {
                foreach ($allFriendlies as &$friendly) {
                    $friendly['homeAccepted'] = filter_var($friendly['homeAccepted'], FILTER_VALIDATE_BOOLEAN);
                    $friendly['awayAccepted'] = filter_var($friendly['awayAccepted'], FILTER_VALIDATE_BOOLEAN);
                }
                echo json_encode($allFriendlies);
            }
        }
    }
}
exit;