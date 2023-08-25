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

    if (isset($_SESSION['team']) && !empty($_SESSION['team'])) {
        $team = $_SESSION['team'];
    }
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($team)) {
        // getOneCoaching
        if (isset($_POST['gameplanNr']) && isset($_POST['teamPart']) && isset($_POST['down']) && isset($_POST['playrange'])) {
            $gameplanNr = 1;
            $teamPart = 'Offense';
            $down = '1st';
            $playrange = 'Short';
            if (isset($_POST['gameplanNr']) && !empty($_POST['gameplanNr'])) {
                $gameplanNr = $_POST['gameplanNr'];
            }
            if (isset($_POST['teamPart']) && !empty($_POST['teamPart'])) {
                $teamPart = $_POST['teamPart'];
            }
            if (isset($_POST['down']) && !empty($_POST['down'])) {
                $down = $_POST['down'];
            }
            if (isset($_POST['playrange']) && !empty($_POST['playrange'])) {
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
                return (in_array($coaching->getTeamPart(), ['Offense', 'General']) && $coaching->getGameplanNr() == $gameplanOff) || ($coaching->getTeamPart() == 'Defense' && $coaching->getGameplanNr() == $gameplanDef);
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
                array_push($data['coachings'], $json);
            }
        }

        if (isset($data) && !empty($data)) {
            echo json_encode($data);
        }
    }
}
exit;