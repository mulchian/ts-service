<?php
include('../../init.php');

session_start();

use touchdownstars\player\PlayerController;
use touchdownstars\player\skill\SkillController;
use touchdownstars\team\TeamController;

$isTrainingTE1 = false;
$isTrainingTE2 = false;
$isTrainingTE3 = false;
if (isset($_SESSION['team'])) {
    $team = $_SESSION['team'];
}

if (isset($pdo, $log, $team)) {
    $skillController = new SkillController($pdo, $log);
    $teamController = new TeamController($pdo, $log);
    $playerController = new PlayerController($pdo, $log);

    $trainingGroups = ['TE0', 'TE1', 'TE2', 'TE3'];

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        if (isset($_POST['trainingGroup']) && isset($_POST['training'])) {
            $trainingGroup = $_POST['trainingGroup'];
            $training = $_POST['training'];
            $timeToCount = time() + 3600;
            switch ($trainingGroup) {
                case 'TE1':
                    $isTrainingTE1 = $skillController->train($team, $trainingGroup, $training);
                    $data['isTraining'] = $isTrainingTE1;
                    break;
                case 'TE2':
                    $isTrainingTE2 = $skillController->train($team, $trainingGroup, $training);
                    $data['isTraining'] = $isTrainingTE2;
                    break;
                case 'TE3':
                    $isTrainingTE3 = $skillController->train($team, $trainingGroup, $training);
                    $data['isTraining'] = $isTrainingTE3;
                    break;
                default:
                    break;
            }
            if ($isTrainingTE1 || $isTrainingTE2 || $isTrainingTE3) {
                // nur wenn das Training gestartet wurde, darf saveTimeToCount ausgeführt werden
                $teamController->saveTimeToCount($team, $trainingGroup, $timeToCount);
                $_SESSION[$trainingGroup . 'TrainingTime'] = $timeToCount;
                $data['timeToCount'] = $timeToCount;
            } else {
                $data['errorMessage'] = 'Mindestens ein Spieler der Trainingsgruppe hat bereits drei Trainingseinheiten am heutigen Tag durchgeführt.';
            }
        }
        if (isset($_POST['newIntensity']) && !empty($_POST['newIntensity'])) {
            if (isset($_POST['playerId']) && !empty($_POST['playerId'])) {
                $data['intIsUpdated'] = $skillController->setIntensity($team, $_POST['newIntensity'], $_POST['playerId']);
            } else {
                $data['intIsUpdated'] = $skillController->setIntensity($team, $_POST['newIntensity']);
            }
        }
        if (isset($_POST['newTrainingGroup']) && !empty($_POST['newTrainingGroup'])) {
            $tgIsUpdated = false;
            $newTrainingGroup = $_POST['newTrainingGroup'];
            if (isset($_POST['oldTrainingGroup']) && !empty($_POST['oldTrainingGroup']) && isset($_POST['playerId']) && !empty($_POST['playerId'])) {
                $oldTrainingTime = $teamController->getTimeToCount($team, $_POST['oldTrainingGroup']);
                $newTrainingTime = $teamController->getTimeToCount($team, $newTrainingGroup);
                $trainingGroupHasPlayerBeforeChange = $teamController->trainingGroupHasPlayer($team, $newTrainingGroup);
                if (tgIsUpdatable($oldTrainingTime) && tgIsUpdatable($newTrainingTime)) {
                    $tgIsUpdated = $skillController->setTrainingGroup($team, $newTrainingGroup, $_POST['playerId']);
                }
                $data['tgIsUpdated'] = $tgIsUpdated;
                if ($tgIsUpdated) {
                    $data['newTrainingGroupName'] = $teamController->getTrainingGroup($team, $newTrainingGroup);
                    $data['trainingGroup0Name'] = $teamController->getTrainingGroup($team, 'TE0');
                    $data['trainingGroup1Name'] = $teamController->getTrainingGroup($team, 'TE1');
                    $data['trainingGroup2Name'] = $teamController->getTrainingGroup($team, 'TE2');
                    $data['trainingGroup3Name'] = $teamController->getTrainingGroup($team, 'TE3');

                    if (!$trainingGroupHasPlayerBeforeChange) {
                        $data['playersToTrainingGroup'] = json_encode($teamController->getPlayersToTrainingGroup($team, $newTrainingGroup));
                    }
                    if (!$teamController->trainingGroupHasPlayer($team, $_POST['oldTrainingGroup'])) {
                        $data['noPlayerInOldTrainingGroup'] = true;
                    }
                }
            } else {
                // Trainingsgroup für das gesamte Team ändern, sofern kein Spieler trainiert.
                $isUpdatable = true;
                foreach ($trainingGroups as $trainingGroup) {
                    $trainingTime = $teamController->getTimeToCount($team, $trainingGroup);
                    if (!tgIsUpdatable($trainingTime)) {
                        $isUpdatable = false;
                    }
                }
                if ($isUpdatable) {
                    $tgIsUpdated = $skillController->setTrainingGroup($team, $newTrainingGroup);
                }
                $data['tgIsUpdated'] = $tgIsUpdated;
            }
        }
        if (isset($_POST['idPlayer']) && !empty($_POST['skillName']) && isset($_POST['skillName']) && !empty($_POST['skillName'])) {
            $idPlayer = $_POST['idPlayer'];
            $skillName = $_POST['skillName'];

            foreach ($team->getPlayers() as $key => $player) {
                if ($player->getId() == $idPlayer) {
                    $skillpoints = $player->getSkillpoints();
                    if (floor($skillpoints) >= 1) {
                        $player->setSkillpoints($skillpoints - 1);
                        $skills = $player->getSkills();
                        $skills[$skillName] = $skills[$skillName] + 1;
                        $player->setSkills($skills);

                        $skillController->updateSkillsToPlayer($player);
                        $playerController->savePlayer($player);

                        unset($_SESSION['player' . $idPlayer]);
                        $data['skillIsUpdated'] = true;
                    }
                }
            }
        }

        if (isset($data) && !empty($data)) {
            echo json_encode($data);
        }
    }
}
exit;

function tgIsUpdatable($trainingTime): bool {
    if ((isset($trainingTime) && $trainingTime < time()) || !isset($trainingTime)) {
        return true;
    }
    return false;
}