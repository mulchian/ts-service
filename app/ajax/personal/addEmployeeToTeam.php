<?php

use touchdownstars\contract\ContractController;
use touchdownstars\employee\EmployeeController;
use touchdownstars\employee\job\JobController;
use touchdownstars\stadium\StadiumController;
use touchdownstars\team\TeamController;

$logFile = 'personal';
include($_SERVER['DOCUMENT_ROOT'] . '/init.php');

session_start();

if (isset($pdo, $log)) {
    $employeeController = new EmployeeController($pdo, $log);
    $jobController = new JobController($pdo);
    $contractController = new ContractController($pdo);
    $stadiumController = new StadiumController($pdo);
    $employeeIsInTeam = false;

    if (!empty($_SESSION['team'])) {
        $team = $_SESSION['team'];
    }

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $content = trim(file_get_contents("php://input"));
        $input = json_decode($content, true);
        if (isset($team, $input['idEmployee'], $input['timeOfContract'], $input['salary'])) {
            $bueroGebaeude = $stadiumController->getBuildingWithName($team->getStadium(), 'Bürogebäude');
            if (isset($bueroGebaeude) && count($team->getEmployees()) < $bueroGebaeude->getLevel()) {
                $idEmployee = $input['idEmployee'];
                $salary = $input['salary'];
                $timeOfContract = $input['timeOfContract'];
                $employee = $employeeController->fetchEmployee($idEmployee);
                if (!empty($employee)) {
                    if (null == $employee->getIdTeam()) {
                        $employeeCost = $salary + $contractController->calcSigningBonus($employee->getMarketValue(), $timeOfContract);
                        if ($employeeCost <= $team->getSalaryCap()) {
                            $moral = round($salary / ($employee->getMarketValue() * 20 / 100), 2);
                            if ($moral >= 0.75) {
                                $employee->setMoral($moral);
                                $contract = $contractController->createContract($employee->getMarketValue(), $salary, $timeOfContract);
                                if (!empty($contract)) {
                                    $employee->setContract($contract);
                                    $employee->setIdTeam($team->getId());
                                    // Weil Employee vorher kein Team hatte, muss als Team null für die Suche des Employees mitgegeben werden.
                                    // Dies prüft gleichzeitig, dass der Employee kein Team hat, bevor er eingestellt wird.
                                    if ($employeeController->saveEmployee($employee, null, $contract) > 0) {
                                        $employeeIsInTeam = true;
                                        $teamController = new TeamController($pdo, $log);
                                        $team->setSalaryCap($team->getSalaryCap() - $employeeCost);
                                        $team->setEmployees($employeeController->fetchEmployeesOfTeam($team));
                                        $teamController->saveTeam($team);
                                        $_SESSION['team'] = $team;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        $data['employeeIsInTeam'] = $employeeIsInTeam;
        if (!empty($data)) {
            echo json_encode($data);
        }
    }
}
exit;