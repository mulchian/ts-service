<?php

use touchdownstars\league\LeagueController;
use touchdownstars\live\GameController;
use touchdownstars\team\TeamController;

$logFile = 'calculateLiveGame';
include('../init.php');

if (isset($pdo, $log)) {
    $leagueController = new LeagueController($pdo, $log);
    $teamController = new TeamController($pdo, $log);
    $gameController = new GameController($pdo, $log);

    $log->debug('cron starts at ' . date('Y-m-d H:i:s'));

    $liveGames = $leagueController->fetchAllLiveGames();

    $log->debug('found ' . count($liveGames) . ' live games');

    foreach ($liveGames as $liveGame) {
        $log->debug('game calculation starts for ' . $liveGame['home'] . ' vs ' . $liveGame['away'] . ' from ' .
            $liveGame['gameTime']->format('Y.m.d H:i:s') . ' at ' . date('Y.m.d H:i:s'));
        $gameController->getOrCalcLastGameplayResult(new DateTime('now'), $liveGame);
        $log->debug('game calculation ends at ' . date('Y.m.d H:i:s'));
    }

    $log->debug('cron ends at ' . date('Y-m-d H:i:s'));
}
