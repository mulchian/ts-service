<?php

use touchdownstars\league\LeagueController;
use touchdownstars\live\GameController;
use touchdownstars\main\MainController;
use touchdownstars\team\TeamController;

$logFile = 'getCalendar';
include($_SERVER['DOCUMENT_ROOT'] . '/init.php');

session_start();

if (isset($pdo) && isset($log)) {
    $start = hrtime(true);
    $log->debug('getCalendar starts ' . $start);
    $teamController = new TeamController($pdo, $log);
    $gameController = new GameController($pdo, $log);
    $leagueController = new LeagueController($pdo, $log);
    $mainController = new MainController($pdo, $log);

    if (!empty($_SESSION['team'])) {
        $team = $_SESSION['team'];
    }
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($team)) {

//        $game = $gameController->getGame($team, true);
//
//        $standings = $gameController->getStandings($game);
//
//        if (!isset($standings) || !$standings || count($standings) == 0) {
//            $standings = $gameController->saveScore($game, null);
//        }
//
//        $first = explode(';', $standings['score1']);
//        $second = explode(';', $standings['score2']);
//        $third = explode(';', $standings['score3']);
//        $fourth = explode(';', $standings['score4']);
//        $sum = explode(';', $standings['score']);

        if (!isset($_SESSION['season'], $_SESSION['gameweek']) || (isset($_SESSION['created']) && date('N', $_SESSION['created']) == 7 && date('N', time()) == 1)) {
            $main = $mainController->fetchSeasonAndGameday();
            $_SESSION['season'] = $main->getSeason();
            $_SESSION['gameweek'] = $main->getGameweek();
        }

        $weekDays = array(
            'monday' => strtotime('monday this week') * 1000,
            'tuesday' => strtotime('tuesday this week') * 1000,
            'wednesday' => strtotime('wednesday this week') * 1000,
            'thursday' => strtotime('thursday this week') * 1000,
            'friday' => strtotime('friday this week') * 1000,
            'saturday' => strtotime('saturday this week') * 1000,
            'sunday' => strtotime('sunday this week') * 1000
        );

        $week = $_SESSION['gameweek'];

        $log->debug('Week: ' . $week->value);

        $mondayGameday = match ($week->value) {
            2 => 1,
            3 => 8,
            4 => 15,
            default => 0,
        };

        $log->debug('Monday-Gameday: ' . $mondayGameday);

        if ($mondayGameday !== 0) {
            $gameDays = array();
            $maxGamedays = $week->value == 4 ? 2 : 7;
            for ($i = 0; $i < $maxGamedays; $i++) {
                $gameday = $mondayGameday + $i;
                $log->debug('Season: ' . $_SESSION['season']);
                $log->debug('Gameday: ' . $gameday);
                $game = $leagueController->fetchGame($team, $_SESSION['season'], $gameday);

                $vsTeamName = $game['home'] == $team->getName() ? $game['away'] : $game['home'];
                $vsOrAt = $game['home'] == $team->getName() ? 'vs' : '@';
                $vsTeam = $teamController->fetchTeam(null, $vsTeamName);

                $gameDay = $vsOrAt . ' ' . $vsTeam->getAbbreviation();
                $gameDays[] = $gameDay;
            }

            if ($maxGamedays == 2) {
                $gameDays[] = 'Playoffs';
                $gameDays[] = 'PLayoffs';
                $gameDays[] = 'Playoffs';
                $gameDays[] = 'Liga Bowl';
                $gameDays[] = 'Saisonabschluss';
            }

            $calendarWeek = array('monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday');
            $calendarWeek = array_combine($calendarWeek, $gameDays);
        } else {
            // TODO: Berechnung erste Woche mit Draft und Preseason
            // erste Woche
            $calendarWeek = array(
                'monday' => 'Combine',
                'tuesday' => '',
                'wednesday' => 'Draft',
                'thursday' => 'Preseason 1<br>Trainingscamp',
                'friday' => 'Preseason 2<br>Trainingscamp',
                'saturday' => 'Preseason 3',
                'sunday' => 'Preseason 4'
            );
        }

        $log->debug('Calendar-Week: ' . print_r($calendarWeek, true));

        $data['calendarTitles'] = $weekDays;
        $data['calendarWeek'] = $calendarWeek;

        $log->debug('getCalendar ends ' . ((hrtime(true) - $start) / 1e+6) . ' ms');
        if (!empty($data)) {
            echo json_encode($data);
        }
    }
}
exit;