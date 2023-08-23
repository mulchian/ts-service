<?php

use touchdownstars\league\LeagueController;

$logFile = 'deleteUnplayedEvents';
include(__DIR__ . '/../init.php');

if (isset($pdo) && isset($log)) {
    $leagueController = new LeagueController($pdo, $log);
    $isDeleted = $leagueController->deleteUnplayedFriendlies();
    $log->debug('Unplayed Friendlies gelöscht? ' . $isDeleted ? 'true' : 'false');
}