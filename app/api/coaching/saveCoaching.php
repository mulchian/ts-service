<?php

use touchdownstars\coaching\Coaching;
use touchdownstars\coaching\CoachingController;
use touchdownstars\team\TeamController;

$logFile = 'coaching';
include($_SERVER['DOCUMENT_ROOT'] . '/init.php');

session_start();

if (isset($pdo, $log)) {
    $teamController = new TeamController($pdo, $log);
    $coachingController = new CoachingController($pdo, $log);
    $coachingSaved = false;

    if (isset($_SESSION['team'])) {
        $team = $_SESSION['team'];
    }

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $content = trim(file_get_contents("php://input"));
        $input = json_decode($content, true);
        if (isset($team, $input['coaching'])) {
            $newCoaching = new Coaching($input['coaching']);
            $log->debug('saveCoaching', ['coaching' => $newCoaching]);

            // save coaching in the database
            $coachingController->saveCoaching($team, $newCoaching);

            // update Coaching in Session-Team
            $teamCoaching = array_values(array_filter($team->getCoachings(), function (Coaching $coaching) use ($newCoaching) {
                return $coaching->getGameplanNr() == $newCoaching->getGameplanNr() && $coaching->getTeamPart() == $newCoaching->getTeamPart() && $coaching->getDown() == $newCoaching->getDown() && $coaching->getPlayrange() == $newCoaching->getPlayrange();
            }))[0];

            if (isset($teamCoaching)) {
                $teamCoaching->setGameplay1($newCoaching->getGameplay1());
                $teamCoaching->setGameplay2($newCoaching->getGameplay2());
                $teamCoaching->setRating($newCoaching->getRating());
            } else {
                $coachings = $team->getCoachings();
                $coachings[] = $newCoaching;
                $team->setCoachings($coachings);
            }

            $_SESSION['team'] = $team;
            $coachingSaved = true;
        }

        $data['coachingSaved'] = $coachingSaved;
        echo json_encode($data);

    }
}
exit;
