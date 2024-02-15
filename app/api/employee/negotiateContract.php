<?php

use touchdownstars\contract\ContractController;
use touchdownstars\employee\EmployeeController;
use touchdownstars\stadium\StadiumController;
use touchdownstars\team\TeamController;

$logFile = 'employee';
include($_SERVER['DOCUMENT_ROOT'] . '/init.php');

session_start();

if (isset($pdo, $log)) {
    $teamController = new TeamController($pdo, $log);
    $employeeController = new EmployeeController($pdo, $log);
    $contractController = new ContractController($pdo);
    $stadiumController = new StadiumController($pdo);
    $isNegotiated = false;

    if (!empty($_SESSION['team'])) {
        $team = $_SESSION['team'];
    }

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $content = trim(file_get_contents("php://input"));
        $input = json_decode($content, true);
        if (isset($team, $input['employeeId'], $input['timeOfContract'], $input['newSalary'])) {
            $log->debug('negotiateContract: ' . print_r($input, true));
            $bueroGebaeude = $stadiumController->getBuildingWithName($team->getStadium(), 'Bürogebäude');
            if (isset($bueroGebaeude) && count($team->getEmployees()) < $bueroGebaeude->getLevel()) {
                $employeeId = $input['employeeId'];
                $timeOfContract = $input['timeOfContract'];
                $newSalary = $input['newSalary'];
                $employee = $employeeController->fetchEmployee($employeeId);
                if (!empty($employee) && (null == $employee->getIdTeam() || $employee->getIdTeam() == $team->getId())) {

                    $isExtension = false;
                    if (null != $employee->getIdTeam() && $employee->getIdTeam() == $team->getId()) {
                        $isExtension = true;
                    }

                    $extensionAllowed = true;
                    if ($isExtension && !(($employee->getContract()->getEndOfContract() < 2 && $timeOfContract >= 1) &&
                            !($employee->getContract()->getEndOfContract() == 2 && $timeOfContract > 2))) {
                        // Nur wenn der Vertrag noch 1 oder 2 Saisons geht, darf der Vertrag aktualisiert werden.
                        $errorMsg = 'Der Vertrag kann nicht verlängert werden.';
                        $extensionAllowed = false;
                    }

                    if ($extensionAllowed || !$isExtension) {
                        $employeeCost = $newSalary + $contractController->calcSigningBonus($employee->getMarketValue(), $timeOfContract);
                        if ($employeeCost <= $team->getSalaryCap()) {
                            $moral = round($newSalary / ($employee->getMarketValue() * 20 / 100), 2);
                            if ($moral >= 0.75) {
                                $employee->setMoral($moral);

                                if ($isExtension) {
                                    $contract = $employee->getContract();
                                    $contract->setSalary($newSalary);
                                    $contract->setSigningBonus($contractController->calcSigningBonus($employee->getMarketValue(), $timeOfContract));
                                    $contract->setEndOfContract($timeOfContract);
                                    $contractController->saveContract($contract);
                                } else {
                                    $contract = $contractController->createContract($employee->getMarketValue(), $newSalary, $timeOfContract);
                                }

                                if (!empty($contract)) {
                                    $employee->setContract($contract);
                                    $employee->setIdTeam($team->getId());
                                    // Weil Employee vorher kein Team hatte, muss als Team null für die Suche des Employees mitgegeben werden.
                                    // Dies prüft gleichzeitig, dass der Employee kein Team hat, bevor er eingestellt wird.
                                    if ($employeeController->saveEmployee($employee, $isExtension ? $team : null, $contract) > 0) {
                                        $isNegotiated = true;
                                        $team->setSalaryCap($team->getSalaryCap() - $employeeCost);
                                        $team->setEmployees($employeeController->fetchEmployeesOfTeam($team));
                                        $teamController->saveTeam($team);
                                        $_SESSION['team'] = $team;
                                    } else {
                                        $errorMsg = 'Der Mitarbeiter konnte nicht eingestellt werden.';
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
                    $errorMsg = 'Der Mitarbeiter existiert nicht.';
                }
            }
        }

        if (!empty($errorMsg)) {
            $log->warning('negotiateContract: ' . $errorMsg);
            $data['errorMessage'] = $errorMsg;
        }
        $data['isNegotiated'] = $isNegotiated;

        if (!empty($data)) {
            echo json_encode($data);
        }
    }
}
exit;