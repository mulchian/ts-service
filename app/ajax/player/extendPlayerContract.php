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
        if (isset($team, $_POST['idPlayer'], $_POST['salary'], $_POST['timeOfContract'])) {
            $idPlayer = $_POST['idPlayer'];
            $salary = $_POST['salary'];
            $timeOfContract = $_POST['timeOfContract'];

            $player = array_values(array_filter($team->getPlayers(), function (Player $value) use ($idPlayer) {
                return $value->getId() == $idPlayer;
            }))[0];

            if (!empty($player) && $player->getContract()->getEndOfContract() <= 2) {
                if (null != $player->getIdTeam() && $player->getIdTeam() == $team->getId() &&
                    (($player->getContract()->getEndOfContract() < 2 && $timeOfContract >= 1) ||
                        ($player->getContract()->getEndOfContract() == 2 && $timeOfContract > 2))) {
                    $playerCost = $salary + $contractController->calcSigningBonus($player->getMarketValue(), $timeOfContract);
                    if ($playerCost <= $team->getSalaryCap()) {
                        $moral = round($salary / ($player->getMarketValue() * 20 / 100), 2);
                        if ($moral >= 0.75) {
                            $player->setMoral($moral);
                            $contract = $player->getContract();
                            $contract->setSalary($salary);
                            $contract->setSigningBonus($contractController->calcSigningBonus($player->getMarketValue(), $timeOfContract));
                            $contract->setEndOfContract($timeOfContract);
                            if ($playerController->savePlayer($player) > 0 && $contractController->saveContract($contract) > 0) {
                                $player->setContract($contract);
                                unset($_SESSION['player' . $player->getId()]);
                                unset($_SESSION['player' . $player->getId() . 'Team']);
                                $_SESSION['team'] = $team;
                                $data['contractIsUpdated'] = true;
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