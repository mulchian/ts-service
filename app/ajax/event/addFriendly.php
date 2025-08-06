<?php

use touchdownstars\league\LeagueController;
use touchdownstars\main\MainController;
use touchdownstars\team\TeamController;

$logFile = 'addFriendly';
include($_SERVER['DOCUMENT_ROOT'] . '/init.php');

session_start();

if (isset($pdo) && isset($log)) {
    $teamController = new TeamController($pdo, $log);
    $mainController = new MainController($pdo, $log);
    $leagueController = new LeagueController($pdo, $log);

    if (!empty($_SESSION['team'])) {
        $team = $_SESSION['team'];
    }

    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($team)) {
        $content = trim(file_get_contents("php://input"));
        $input = json_decode($content, true);
        if (isset($input['home'], $input['opponent'], $input['gameTime'])) {
            $isHome = filter_var($input['home'], FILTER_VALIDATE_BOOLEAN);
            $opponent = $input['opponent'];
            $gameTime = new DateTime($input['gameTime']);

            if ($isHome) {
                $home = $team->getName();
                $away = $opponent;
                $homeAccepted = true;
            } else {
                $home = $opponent;
                $away = $team->getName();
                $awayAccepted = true;
            }

            $timeInTwoHours = new DateTime('+ 2 hours');
            $timeInTwoHours->modify('-' . $timeInTwoHours->format('i') . ' minutes')
                ->modify('-' . $timeInTwoHours->format('s') . ' seconds')
                ->modify('-' . $timeInTwoHours->format('u') . ' microseconds');
            $log->debug('Now in 2 hours: ' . print_r($timeInTwoHours, true));

            // Spielzeit muss mindestens 2 Stunden in der Zukunft liegen
            $timeIsInFuture = $gameTime >= $timeInTwoHours;
            // Spielzeit darf maximal bis zu 1 Stunde vor einem Ligaspiel sein und mindestens 2 Stunden nach Ligaspielstart
            $timeHasLeagueGame = $leagueController->hasGameAtGivenTime($team, $gameTime);
            $log->debug('Ist bereits ein Siel geplant? : ' . ($timeHasLeagueGame ? 'Ja' : 'Nein'));

            if ($timeIsInFuture && !$timeHasLeagueGame) {

                $main = $mainController->fetchSeasonAndGameday();
                $season = $main->getSeason();
                if ($gameTime > $main->getLastSeasonday()) {
                    $season++;
                }

                $log->debug('Speichere Freundschaftsspiel um ' . $gameTime->format('d.m.Y H:i') . ' | Heim: ' . $home . ' | Gast: ' . $away);

                $id = $leagueController->saveFriendly($gameTime, $season, $home, $away, $homeAccepted ?? false, $awayAccepted ?? false);

                $data['id'] = $id;
                $data['gameTime'] = $gameTime;
                $data['season'] = $season;
                $data['home'] = $home;
                $data['away'] = $away;
                $data['accepted'] = '1/2';
            } else {
                $log->debug('Freundschaftsspiel um ' . date('d.m.Y H:i', $gameTime) . ' | Heim: ' . $home . ' | Gast: ' . $away . ' wird nicht gespeichert.');
                $data['timeIsInFuture'] = $timeIsInFuture;
                $data['timeHasLeagueGame'] = $timeHasLeagueGame;
            }
        }

        if (isset($data) && !empty($data)) {
            echo json_encode($data);
        }
    }
}
exit;
