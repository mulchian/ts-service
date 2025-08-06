<?php

use touchdownstars\coaching\CoachingController;
use touchdownstars\coaching\CoachingName;
use touchdownstars\team\TeamController;

$logFile = 'coaching';
include($_SERVER['DOCUMENT_ROOT'] . '/init.php');

session_start();

if (isset($pdo, $log)) {
    $teamController = new TeamController($pdo, $log);
    $coachingController = new CoachingController($pdo, $log);
    $gameplanRenamed = false;

    if (isset($_SESSION['team'])) {
        $team = $_SESSION['team'];
    }

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $content = trim(file_get_contents("php://input"));
        $input = json_decode($content, true);
        if (isset($team, $input['coachingName'])) {
            $log->debug('save coachingName: ' . print_r($input, true));
            $coachingName = $input['coachingName'];
            $newCoachingName = new CoachingName($coachingName);
            $log->debug('new CoachingName from frontend: ' . print_r($newCoachingName, true));

            $idCoachingName = $coachingController->saveCoachingName($team, $newCoachingName);
            $log->debug('coachingName saved with id: ' . $idCoachingName);

            if ($idCoachingName && $idCoachingName > 0) {
                $newCoachingName->setId($idCoachingName);

                // update Coaching in Session-Team
                $coachingname = array_values(array_filter($team->getCoachingNames(), function (CoachingName $coachingname) use ($newCoachingName) {
                    return $coachingname->getGameplanNr() == $newCoachingName->getGameplanNr() && $coachingname->getTeamPart() == $newCoachingName->getTeamPart();
                }))[0];

                if (isset($coachingname)) {
                    $coachingname->setName($newCoachingName->getName());
                } else {
                    $coachingnames = $team->getCoachingNames();
                    $coachingnames[] = $newCoachingName;
                    $team->setCoachingNames($coachingnames);
                }

                $_SESSION['team'] = $team;
                $gameplanRenamed = true;
            }
        }
        $data['coachingNameSaved'] = $gameplanRenamed;
        echo json_encode($data);
    }
}
exit;
