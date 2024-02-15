<?php

use Monolog\Logger;
use touchdownstars\team\Team;
use touchdownstars\team\TeamController;

/**
 * @param Logger $log
 * @param TeamController $teamController
 * @return mixed|Team|null
 */
function getTeam(Logger $log, TeamController $teamController): mixed
{
    $team = $_SESSION['team'];
    if (!empty($team)) {
        $log->debug('Team is in session: ' . $team->getName());
    } else if (!empty($_GET['userId'])) {
        $log->debug('Team is not in session');
        $log->debug('userId: ' . $_GET['userId']);
        $userId = $_GET['userId'];
        $team = $teamController->fetchTeam($userId);
        $log->debug('Team is fetched: ' . $team->getName());
    }
    return $team;
}
