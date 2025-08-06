<?php

use touchdownstars\coaching\CoachingController;
use touchdownstars\team\TeamController;

$logFile = 'coaching';
include($_SERVER['DOCUMENT_ROOT'] . '/init.php');

session_start();

if (isset($pdo, $log)) {
    $teamController = new TeamController($pdo, $log);
    $coachingController = new CoachingController($pdo, $log);
    $gameplanCreated = false;

    if (isset($_SESSION['team'])) {
        $team = $_SESSION['team'];
    }

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $content = trim(file_get_contents("php://input"));
        $input = json_decode($content, true);
        if (isset($team, $input['gameplanNr'], $input['teamPart'])) {
            $log->debug('create and add new coaching' . print_r($input, true));
            $teamPart = $input['teamPart'];
            $gameplanNr = $input['gameplanNr'];

            // 1st test if gameplanNr is already set -> if already used, return error
            // 2nd test if gameplanNr is maximal 5 -> if more than 5 gameplans, return error

            // team->getCoachings() has all coachings with the teamId -> so we have to filter for teamPart and map the gameplanNr
            $existingGameplanNrs = array_values(array_unique(array_map(function ($coaching) {
                return $coaching->getGameplanNr();
            }, array_filter($team->getCoachings(), function ($coaching) use ($teamPart) {
                return $coaching->getTeamPart() === $teamPart;
            }))));
            $log->debug('existingGameplanNrs: ' . print_r($existingGameplanNrs, true));
            if (in_array($gameplanNr, $existingGameplanNrs)) {
                $data['error'] = 'Gameplan already exists for this team part.';
            } else if (count($existingGameplanNrs) >= 5) {
                $data['error'] = 'You can only have a maximum of 5 Gameplans for each team part.';
            } else {
                $gameplans = [
                    'offense' => 'GameplanOff',
                    'defense' => 'GameplanDef',
                    'general' => 'GameplanGeneral'
                ];

                // we can create a new coaching
                $coachingController->createBotCoaching($team, $gameplanNr, $teamPart);
                $coachings = $coachingController->fetchAllCoachings($team->getId());
                $team->setCoachings($coachings);
                $teamController->updateGameplan($team, $gameplans[$teamPart], $gameplanNr);
                $_SESSION['team'] = $team;
                $gameplanCreated = true;
            }
        }
        $data['gameplanCreated'] = $gameplanCreated;
        echo json_encode($data);
    }
}
exit;