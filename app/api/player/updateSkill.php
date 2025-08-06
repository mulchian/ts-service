<?php

use touchdownstars\player\PlayerController;
use touchdownstars\player\skill\SkillController;
use touchdownstars\team\TeamController;

$logFile = 'player';
include($_SERVER['DOCUMENT_ROOT'] . '/init.php');

session_start();

if (isset($pdo, $log)) {
    $teamController = new TeamController($pdo, $log);
    $playerController = new PlayerController($pdo, $log);
    $skillController = new SkillController($pdo, $log);
    $isUpdated = false;

    if (!empty($_SESSION['team'])) {
        $team = $_SESSION['team'];
    }

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $content = trim(file_get_contents("php://input"));
        $input = json_decode($content, true);
        if (isset($team, $input['playerId'], $input['skillName'])) {
            $log->debug('updateSkill: ' . print_r($input, true));
            $playerId = $input['playerId'];
            $skillName = $input['skillName'];

            $player = $team->getPlayers()[$playerId];

            $skillExists = false;
            $allSkillNames = $skillController->fetchSkillNames();
            if (!array_key_exists($skillName, $allSkillNames)) {
                if (in_array($skillName, $allSkillNames)) {
                    $skillName = array_search($skillName, $allSkillNames);
                    $skillExists = true;
                } else {
                    $errorMsg = 'Skill ' . $skillName . ' nicht gefunden.';
                }
            } else {
                $skillExists = true;
            }

            if ($skillExists && isset($player)) {
                $skillpoints = $player->getSkillpoints();
                if ($skillExists && floor($skillpoints) >= 1) {
                    $player->setSkillpoints($skillpoints - 1);
                    $skills = $player->getSkills();
                    $skills[$skillName] = $skills[$skillName] + 1;
                    $player->setSkills($skills);

                    $skillController->updateSkillsToPlayer($player);
                    $playerController->savePlayer($player);

                    $_SESSION['team'] = $team;
                    unset($_SESSION['player' . $playerId]);
                    unset($_SESSION['player' . $playerId . 'Team']);
                    $isUpdated = true;
                } else {
                    $errorMsg = 'Keine Skillpunkte vorhanden.';
                }
            } else {
                $errorMsg = 'Spieler nicht gefunden.';
            }
        }

        if (!empty($errorMsg)) {
            $log->warning('updateSkill: ' . $errorMsg);
            $data['error'] = $errorMsg;
        }
        $data['isUpdated'] = $isUpdated;

        if (!empty($data)) {
            echo json_encode($data);
        }
    }
}
exit;