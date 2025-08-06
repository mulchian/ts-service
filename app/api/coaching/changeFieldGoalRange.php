<?php

use touchdownstars\coaching\Coaching;
use touchdownstars\coaching\CoachingController;

$logFile = 'coaching';
include($_SERVER['DOCUMENT_ROOT'] . '/init.php');

session_start();

if (isset($pdo, $log)) {
    $coachingController = new CoachingController($pdo, $log);
    $isUpdated = false;

    if (isset($_SESSION['team'])) {
        $team = $_SESSION['team'];
    }

    $log->debug('changeFieldGoalRange - ' . print_r($_SERVER['REQUEST_METHOD'], true));
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $content = trim(file_get_contents("php://input"));
        $input = json_decode($content, true);
        if (isset($team, $input['generalCoaching'], $input['newRange'])) {
            $log->debug('changeFieldGoalRange: ' . print_r($input, true));
            $generalCoaching = new Coaching($input['generalCoaching']);
            $newRange = 'FGRange;' . $input['newRange'];

            // Update the field goal range in the general coaching
            $generalCoaching->setGameplay1($newRange);
            $coachingController->saveCoaching($team, $generalCoaching);

            // update Coaching in Session-Team
            $teamCoaching = array_values(array_filter($team->getCoachings(), function (Coaching $coaching) use ($generalCoaching) {
                return $coaching->getGameplanNr() == $generalCoaching->getGameplanNr() && $coaching->getTeamPart() == $generalCoaching->getTeamPart() && $coaching->getDown() == $generalCoaching->getDown() && $coaching->getPlayrange() == $generalCoaching->getPlayrange();
            }))[0];
            if (isset($teamCoaching)) {
                $teamCoaching->setGameplay1($newRange);
            } else {
                $coachings = $team->getCoachings();
                $coachings[] = $generalCoaching;
                $team->setCoachings($coachings);
            }

            $_SESSION['team'] = $team;
            $isUpdated = true;
        }

        $data['fieldGoalRangeUpdated'] = $isUpdated;
        echo json_encode($data);
    }
}
exit;