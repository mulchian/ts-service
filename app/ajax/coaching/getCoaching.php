<?php

use touchdownstars\coaching\Coaching;
use touchdownstars\coaching\CoachingController;
use touchdownstars\team\TeamController;

$logFile = 'getCoaching';
include($_SERVER['DOCUMENT_ROOT'] . '/init.php');

session_start();

if (isset($pdo, $log)) {
    $teamController = new TeamController($pdo, $log);
    $coachingController = new CoachingController($pdo);

    if (!empty($_SESSION['team'])) {
        $team = $_SESSION['team'];
    }
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($team)) {
        $log->debug('getCoaching - ' . print_r($_POST, true));
        // getOneCoaching
        if (isset($_POST['gameplanNr']) && isset($_POST['teamPart']) && isset($_POST['down']) && isset($_POST['playrange'])) {
            $gameplanNr = 1;
            $teamPart = 'offense';
            $down = '1st';
            $playrange = 'Short';
            if (!empty($_POST['gameplanNr'])) {
                $gameplanNr = $_POST['gameplanNr'];
            }
            if (!empty($_POST['teamPart'])) {
                $teamPart = $_POST['teamPart'];
            }
            if (!empty($_POST['down'])) {
                $down = $_POST['down'];
            }
            if (!empty($_POST['playrange'])) {
                $playrange = $_POST['playrange'];
            }

            $coaching = $coachingController->getCoachingFromTeam($team, $gameplanNr, $teamPart, $down, $playrange);
            $data['coaching'] = $coaching;
        }

        // getAllCoachings
        if (isset($_POST['allCoachings']) && $_POST['allCoachings'] && isset($_POST['gameplanOff'], $_POST['gameplanDef'])) {
            $gameplanOff = $_POST['gameplanOff'];
            $gameplanDef = $_POST['gameplanDef'];
            $teamCoachings = array_values(array_filter($team->getCoachings(), function (Coaching $coaching) use ($gameplanOff, $gameplanDef) {
                return (in_array($coaching->getTeamPart(), ['offense', 'general']) && $coaching->getGameplanNr() == $gameplanOff) || ($coaching->getTeamPart() == 'defense' && $coaching->getGameplanNr() == $gameplanDef);
            }));

            $data['coachings'] = array();
            foreach ($teamCoachings as $coaching) {
                $json = [
                    'gameplanNr' => $coaching->getGameplanNr(),
                    'teamPart' => $coaching->getTeamPart(),
                    'down' => $coaching->getDown(),
                    'playrange' => $coaching->getPlayrange(),
                    'gameplay1' => $coaching->getGameplay1(),
                    'gameplay2' => $coaching->getGameplay2(),
                    'rating' => $coaching->getRating()
                ];
                $data['coachings'][] = $json;
            }
        }

        if (!empty($data)) {
            $log->debug('getCoaching - ' . print_r($data, true));
            echo json_encode($data);
        }
    }
}
exit;