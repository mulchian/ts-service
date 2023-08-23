<?php

use touchdownstars\contract\ContractController;
use touchdownstars\player\Player;
use touchdownstars\player\PlayerController;
use touchdownstars\team\TeamController;

$logFile = 'player';
include($_SERVER['DOCUMENT_ROOT'] . '/init.php');

session_start();

if (isset($pdo, $log)) {
    $playerController = new PlayerController($pdo, $log);
    $teamController = new TeamController($pdo, $log);
    $contractController = new ContractController($pdo);

    if (!empty($_SESSION['team'])) {
        $team = $_SESSION['team'];
    }

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        if (isset($team, $_POST['idPlayer'], $_POST['position'])) {
            $idPlayer = $_POST['idPlayer'];
            $position = $_POST['position'];

            if (isset($_SESSION['gameday']) && !empty($_SESSION['gameday'])) {
                $remainingDays = 28 - $_SESSION['gameday'];
            } else {
                $remainingDays = 0;
            }

            if ($teamController->hasPlayer($team, $idPlayer)) {
                $player = array_values(array_filter($team->getPlayers(), function ($value) use ($idPlayer) {
                    return $value->getId() == $idPlayer;
                }))[0];

                if (isset($player) && $player->getType()->getPosition()->getPosition() == $position) {
                    $salary = $player->getContract()->getSalary();
                    $contract = $player->getContract();
                    if (!empty($contract)) {
                        //Contract muss so oder so aus der Datenbank gelöscht werden, also zu erst.
                        if ($contractController->deleteContract($contract->getId()) == 1) {
                            if ($player->getAge() > 32) {
                                //Spieler zu alt für Free Agency also Löschen
                                if ($playerController->deletePlayer($player) == 1) {
                                    $isReleased = true;
                                } else {
                                    //Logging, dass entweder kein Spieler gelöscht wurde oder zu viele
                                    $log->debug('Player wurde nicht gelöscht. ' . print_r($player, true));
                                }
                            } else {
                                $player->setContract(null);
                                $player->setIdTeam(null);
                                $player->setMoral(1);
                                if ($playerController->savePlayer($player) > 0) {
                                    $isReleased = true;
                                }
                            }

                            if (isset($isReleased) && $isReleased) {
                                // Spieler wurde aktualisiert oder gelöscht, also kann das Team aktualisiert werden.
                                $correctedSalary = $teamController->updateSalaryCap($team, $remainingDays, $salary);
                                $team->setSalaryCap($correctedSalary);
                                if ($teamController->saveTeam($team) > 0) {
                                    // Team ist in der Datenbank aktuell, also wird es in der Session aktualisiert.
                                    $players = array_values(array_filter($team->getPlayers(), function (Player $value) use ($idPlayer) {
                                        return $value->getId() != $idPlayer;
                                    }));
                                    $team->setPlayers($players);
                                    $_SESSION['team'] = $team;
                                    $data['playerIsReleased'] = true;
                                    $data['correctedSalary'] = $correctedSalary;
                                }
                            }
                        }
                    }
                }
            }
        }
        if (!empty($data)) {
            echo json_encode($data);
        }
    }
}
exit;