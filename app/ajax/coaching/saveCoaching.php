<?php

use touchdownstars\coaching\Coaching;
use touchdownstars\coaching\CoachingController;
use touchdownstars\coaching\Coachingname;
use touchdownstars\team\Team;
use touchdownstars\team\TeamController;

$logFile = 'saveCoaching';
include($_SERVER['DOCUMENT_ROOT'] . '/init.php');

session_start();

if (isset($pdo) && isset($log)) {
    $teamController = new TeamController($pdo, $log);
    $coachingController = new CoachingController($pdo);

    if (isset($_SESSION['team']) && !empty($_SESSION['team'])) {
        $team = $_SESSION['team'];
    }
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($team)) {
        // save Offense & Defense Coaching
        $gameplanNr = $_POST['gameplanNr'];
        if (isset($gameplanNr, $_POST['teamPart'], $_POST['down'])) {
            $data['coachingSaved'] = false;

            $coaching = createCoaching($team->getId(), $_POST);

            if (isset($coaching)) {
                saveCoaching($team, $coachingController, $coaching);
            }


            $_SESSION['team'] = $team;
            $data['coachingSaved'] = true;

            $ratings = $coachingController->getRatingsForDown($team, $gameplanNr, $_POST['teamPart'], $_POST['down']);
            $data['ratings'] = $ratings;
        }

        // save General Coaching
        $general1 = $_POST['general1'];
        $general2 = $_POST['general2'];
        if (isset($general1, $general2)) {

            foreach ([$general1, $general2] as $general) {

                $coaching = createCoaching($team->getId(), $general);

                if (isset($coaching)) {
                    saveCoaching($team, $coachingController, $coaching);
                }

            }

            $_SESSION['team'] = $team;
            $data['coachingSaved'] = true;
        }

        //save gameplan
        if (isset($_POST['gameplan'])) {
            $gameplan = $_POST['gameplan'];
            $gameplanNr = $_POST['gameplanNr'];

            $teamController->updateGameplan($team, $gameplan, $gameplanNr);

            $_SESSION['team'] = $team;
            $data['gameplanSaved'] = true;
        }

        //save gameplanName
        if (isset($gameplanNr, $_POST['gameplanName'], $_POST['teamPart'])) {
            $newCoachingname = new Coachingname();
            $newCoachingname->setIdTeam($team->getId());
            $newCoachingname->setGameplanNr($gameplanNr);
            $newCoachingname->setGameplanName($_POST['gameplanName']);
            $newCoachingname->setTeamPart($_POST['teamPart']);

            $idCoachingName = $coachingController->saveCoachingname($team, $newCoachingname);

            if ($idCoachingName) {
                $newCoachingname->setId($idCoachingName);

                // update Coaching in Session-Team
                $coachingname = array_values(array_filter($team->getCoachingnames(), function (Coachingname $coachingname) use ($newCoachingname) {
                    return $coachingname->getGameplanNr() == $newCoachingname->getGameplanNr() && $coachingname->getTeamPart() == $newCoachingname->getTeamPart();
                }))[0];

                if (isset($coachingname)) {
                    $coachingname->setGameplanName($newCoachingname->getGameplanName());
                } else {
                    $coachingnames = $team->getCoachingnames();
                    array_push($coachingnames, $newCoachingname);
                    $team->setCoachingnames($coachingnames);
                }
            }

            $_SESSION['team'] = $team;
            $data['gameplanNameSaved'] = true;
        }

        if (isset($data) && !empty($data)) {
            echo json_encode($data);
        }
    }
}
exit;

function createCoaching(int $idTeam, array $coachingArray): ?Coaching {
    $gameplanNr = $coachingArray['gameplanNr'];
    $teamPart = $coachingArray['teamPart'];
    $down = $coachingArray['down'];
    $playrange = $coachingArray['playrange'];
    $gameplay1 = $coachingArray['gameplay1'];
    $gameplay2 = $coachingArray['gameplay2'];
    $rating = $coachingArray['rating'];

    if (isset($gameplanNr, $teamPart, $down, $playrange, $gameplay1, $gameplay2, $rating)) {
        $coaching = new Coaching();
        $coaching->setIdTeam($idTeam);
        $coaching->setGameplanNr($gameplanNr);
        $coaching->setTeamPart($teamPart);
        $coaching->setDown($down);
        $coaching->setPlayrange($playrange);
        $coaching->setGameplay1($gameplay1);
        $coaching->setGameplay2($gameplay2);
        $coaching->setRating($rating);
        return $coaching;
    }

    return null;
}

function saveCoaching(Team $team, CoachingController $coachingController, Coaching $newCoaching) {
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
        array_push($coachings, $newCoaching);
        $team->setCoachings($coachings);
    }
}