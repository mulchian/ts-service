<?php

use touchdownstars\contract\ContractController;
use touchdownstars\employee\EmployeeController;
use touchdownstars\player\PlayerController;
use touchdownstars\stadium\StadiumController;
use touchdownstars\team\TeamController;

$logFile = 'player';
include($_SERVER['DOCUMENT_ROOT'] . '/init.php');

session_start();

if (isset($pdo, $log)) {
    $teamController = new TeamController($pdo, $log);
    $playerController = new PlayerController($pdo, $log);
    $contractController = new ContractController($pdo);
    $isNegotiated = false;

    if (!empty($_SESSION['team'])) {
        $team = $_SESSION['team'];
    }

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $content = trim(file_get_contents("php://input"));
        $input = json_decode($content, true);
        if (isset($team, $input['playerId'], $input['timeOfContract'], $input['newSalary'])) {
            $log->debug('negotiateContract: ' . print_r($input, true));
            $playerId = $input['playerId'];
            $timeOfContract = $input['timeOfContract'];
            $newSalary = $input['newSalary'];

            $player = $team->getPlayers()[$playerId];

            if (!empty($player) && (null == $player->getIdTeam() || $player->getIdTeam() == $team->getId())) {
                $isExtension = false;
                if (null != $player->getIdTeam() && $player->getIdTeam() == $team->getId()) {
                    $isExtension = true;
                }

                $extensionAllowed = true;
                if ($isExtension && !(($player->getContract()->getEndOfContract() < 2 && $timeOfContract >= 1) &&
                        !($player->getContract()->getEndOfContract() == 2 && $timeOfContract > 2))) {
                    // Nur wenn der Vertrag noch 1 oder 2 Saisons geht, darf der Vertrag aktualisiert werden.
                    $errorMsg = 'Der Vertrag kann nicht verlängert werden.';
                    $extensionAllowed = false;
                }

                if ($extensionAllowed || !$isExtension) {
                    $playerCost = $newSalary + $contractController->calcSigningBonus($player->getMarketValue(), $timeOfContract);
                    if ($playerCost <= $team->getSalaryCap()) {
                        $moral = round($newSalary / ($player->getMarketValue() * 20 / 100), 2);
                        if ($moral >= 0.75) {
                            $player->setMoral($moral);

                            if ($isExtension) {
                                $contract = $player->getContract();
                                $contract->setSalary($newSalary);
                                $contract->setSigningBonus($contractController->calcSigningBonus($player->getMarketValue(), $timeOfContract));
                                $contract->setEndOfContract($timeOfContract);
                                $contractController->saveContract($contract);
                            } else {
                                $contract = $contractController->createContract($player->getMarketValue(), $newSalary, $timeOfContract);
                            }

                            if (!empty($contract)) {
                                $player->setContract($contract);
                                $player->setIdTeam($team->getId());
                                if ($playerController->savePlayer($player) > 0) {
                                    $isNegotiated = true;
                                    $team->setSalaryCap($team->getSalaryCap() - $playerCost);
                                    $teamController->saveTeam($team);
                                    $_SESSION['team'] = $team;
                                } else {
                                    $errorMsg = 'Der Spieler konnte nicht eingestellt werden.';
                                }
                            } else {
                                $errorMsg = 'Der Vertrag konnte nicht erstellt oder verlängert werden.';
                            }
                        } else {
                            $errorMsg = 'Der Moralwert ist zu niedrig.';
                        }
                    } else {
                        $errorMsg = 'Das Salary Cap reicht nicht aus.';
                    }
                }
            } else {
                $errorMsg = 'Der Spieler existiert nicht.';
            }
        }

        if (!empty($errorMsg)) {
            $log->warning('negotiateContract: ' . $errorMsg);
            $data['error'] = $errorMsg;
        }
        $data['isNegotiated'] = $isNegotiated;

        if (!empty($data)) {
            echo json_encode($data);
        }
    }
}
exit;