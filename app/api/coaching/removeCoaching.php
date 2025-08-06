<?php

use touchdownstars\coaching\CoachingController;
use touchdownstars\team\TeamController;

$logFile = 'coaching';
include($_SERVER['DOCUMENT_ROOT'] . '/init.php');

session_start();

if (isset($pdo, $log)) {
    $teamController = new TeamController($pdo, $log);
    $coachingController = new CoachingController($pdo, $log);
    $gameplanRemoved = false;

    if (isset($_SESSION['team'])) {
        $team = $_SESSION['team'];
    }

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $content = trim(file_get_contents("php://input"));
        $input = json_decode($content, true);
        if (isset($team, $input['gameplanNr'], $input['teamPart'])) {
            $log->debug('remove coaching' . print_r($input, true));
            $teamPart = $input['teamPart'];
            $gameplanNr = $input['gameplanNr'];

            $gameplans = [
                'offense' => 'GameplanOff',
                'defense' => 'GameplanDef',
                'general' => 'GameplanGeneral'
            ];

            // 1st test if gameplanNr exists -> if not, return error
            // 2nd test if minimum of 1 gameplan is left -> if not, return error

            $existingGameplanNrs = array_values(array_unique(array_map(function ($coaching) {
                return $coaching->getGameplanNr();
            }, array_filter($team->getCoachings(), function ($coaching) use ($teamPart) {
                return $coaching->getTeamPart() === $teamPart;
            }))));
            if (in_array($gameplanNr, $existingGameplanNrs)) {
                if (count($existingGameplanNrs) > 1) {
                    $log->debug('existingGameplanNrs before deletion' . print_r($existingGameplanNrs, true));
                    if ($coachingController->deleteCoaching($team, $gameplanNr, $teamPart)) {
                        // delete the gameplanNr from existingGameplanNrs
                        $existingGameplanNrs = array_values(array_diff($existingGameplanNrs, [$gameplanNr]));
                        $log->debug('existingGameplanNrs after deletion' . print_r($existingGameplanNrs, true));
                        $teamController->updateGameplan($team, $gameplans[$teamPart], $existingGameplanNrs[0]);
                        $_SESSION['team'] = $team;
                        $gameplanRemoved = true;
                    } else {
                        $data['error'] = 'Error while deleting the Gameplan.';
                    }
                } else {
                    $data['error'] = 'You must have at least one Gameplan for this team part.';
                }
            } else {
                $data['error'] = 'Gameplan does not exist for this team part.';
            }
        }
        $data['gameplanRemoved'] = $gameplanRemoved;
        echo json_encode($data);
    }
}
exit;