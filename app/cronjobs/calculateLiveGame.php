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
        $log->debug('game calculation starts for ' . $liveGame['home'] . ' vs ' . $liveGame['away'] . ' from ' . date('d.m.Y H:i', $liveGame['gameTime']) . ' at ' . date('d.m.Y H:i:s'));
        $gameController->getOrCalcLastGameplayResult(time(), $liveGame);
        $log->debug('game calculation ends at ' . date('d.m.Y H:i:s'));
    }

    $log->debug('cron ends at ' . date('Y-m-d H:i:s'));
}
