<?php

use touchdownstars\league\LeagueController;
use touchdownstars\live\GameController;
use touchdownstars\team\TeamController;

$logFile = 'getStandings';
include($_SERVER['DOCUMENT_ROOT'] . '/init.php');

session_start();

if (isset($pdo) && isset($log)) {
    $teamController = new TeamController($pdo, $log);
    $gameController = new GameController($pdo, $log);
    $leagueController = new LeagueController($pdo, $log);

    if (!empty($_SESSION['team'])) {
        $team = $_SESSION['team'];
    }
    if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($team)) {
        $game = $gameController->getGame($team);

        $standings = $leagueController->getStandings($game);

        if (!isset($standings) || !$standings || count($standings) == 0) {
            $standings = $leagueController->saveScore($game, null);
        }

        $first = explode(';', $standings['score1']);
        $second = explode(';', $standings['score2']);
        $third = explode(';', $standings['score3']);
        $fourth = explode(';', $standings['score4']);
        $ot = explode(';', $standings['ot']);
        $sum = explode(';', $standings['score']);

        $isHome = $game['home'] == $team->getName();

        $teamStandings = array(
            'team' => $team->getName(),
            'first' => $isHome ? $first[0] : $first[1],
            'second' => $isHome ? $second[0] : $second[1],
            'third' => $isHome ? $third[0] : $third[1],
            'ot' => $isHome ? $ot[0] : $ot[1],
            'fourth' => $isHome ? $fourth[0] : $fourth[1],
            'sum' => $isHome ? $sum[0] : $sum[1]
        );

        $vsTeam = $gameController->getVsTeam($team);

        $vsTeamStandings = array(
            'team' => $vsTeam->getName(),
            'first' => $isHome ? $first[1] : $first[0],
            'second' => $isHome ? $second[1] : $second[0],
            'third' => $isHome ? $third[1] : $third[0],
            'fourth' => $isHome ? $fourth[1] : $fourth[0],
            'ot' => $isHome ? $ot[1] : $ot[0],
            'sum' => $isHome ? $sum[1] : $sum[0]
        );

        $log->debug('Standings: ' . print_r($standings, true));

        $data['standings'] = array($teamStandings, $vsTeamStandings);

        if (!empty($data)) {
            echo json_encode($data);
        }
    }
}
exit;