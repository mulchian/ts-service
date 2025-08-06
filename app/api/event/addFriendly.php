<?php

use touchdownstars\league\LeagueController;
use touchdownstars\main\MainController;

$logFile = 'event';
include($_SERVER['DOCUMENT_ROOT'] . '/init.php');

session_start();

if (isset($pdo, $log)) {
    $mainController = new MainController($pdo, $log);
    $leagueController = new LeagueController($pdo, $log);
    $added = false;

    if ($_SESSION['team']) {
        $team = $_SESSION['team'];
    }

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $content = trim(file_get_contents("php://input"));
        $input = json_decode($content, true);
        if (isset($team, $input['friendly'])) {
            $log->debug('add friendly' . print_r($input, true));
            $friendly = $input['friendly'];
            $log->debug('friendly data', ['friendly' => $friendly]);

            $home = $friendly['home'];
            $away = $friendly['away'];

            $homeAccepted = filter_var($friendly['homeAccepted'], FILTER_VALIDATE_BOOLEAN) ?? false;
            $awayAccepted = filter_var($friendly['awayAccepted'], FILTER_VALIDATE_BOOLEAN) ?? false;

            $gameTime = new DateTime($friendly['gameTime'], new DateTimeZone('Europe/Berlin'));
            $log->debug('Spielzeit: ' . $gameTime->format('d.m.Y H:i'));

            $timeInTwoHours = new DateTime('+ 2 hours', new DateTimeZone('Europe/Berlin'));
            $timeInTwoHours->setTime($timeInTwoHours->format('H'), 0);
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

                $addedFriendly = [
                    'id' => $id,
                    'gameTime' => $gameTime->format('Y-m-d H:i'),
                    'gameDay' => null,
                    'home' => $home,
                    'away' => $away,
                    'homeAccepted' => $homeAccepted,
                    'awayAccepted' => $awayAccepted
                ];

                $data['friendly'] = $addedFriendly;
                $added = true;
            } else {
                $log->debug('Freundschaftsspiel um ' . $gameTime->format('d.m.Y H:i') . ' | Heim: ' . $home . ' | Gast: ' . $away . ' wird nicht gespeichert.');
                if (!$timeIsInFuture) {
                    $errorMsg = 'Das Spiel muss mindestens 2 Stunden in der Zukunft liegen.';
                } else if ($timeHasLeagueGame) {
                    $errorMsg = 'Es ist bereits ein anderes Spiel um diese Zeit geplant.';
                }
                if (!empty($errorMsg)) {
                    $log->debug($errorMsg);
                    $data['error'] = $errorMsg;
                }
            }
        }
        $data['added'] = $added;
        echo json_encode($data);
    }
}
exit;