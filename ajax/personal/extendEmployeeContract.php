<?php

use touchdownstars\contract\ContractController;
use touchdownstars\employee\Employee;
use touchdownstars\employee\EmployeeController;
use touchdownstars\employee\job\JobController;

$logFile = 'personal';
include($_SERVER['DOCUMENT_ROOT'] . '/init.php');

session_start();

if (isset($pdo, $log)) {

    $employeeController = new EmployeeController($pdo, $log);
    $jobController = new JobController($pdo);
    $contractController = new ContractController($pdo);

    if (!empty($_SESSION['team'])) {
        $team = $_SESSION['team'];
    }

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $content = trim(file_get_contents("php://input"));
        $input = json_decode($content, true);
        if (isset($team, $input['idEmployee'], $input['timeOfContract'], $input['salary'])) {
            $idEmployee = $input['idEmployee'];
            $salary = $input['salary'];
            $timeOfContract = $input['timeOfContract'];
            $employee = array_values(array_filter($team->getEmployees(), function (Employee $value) use ($idEmployee) {
                return $value->getId() == $idEmployee;
            }))[0];
            if (!empty($employee) && $employee->getContract()->getEndOfContract() <= 2) {
                if (null != $employee->getIdTeam() && $team->getId() == $employee->getIdTeam() &&
                    (($employee->getContract()->getEndOfContract() < 2 && $timeOfContract >= 1) ||
                        ($employee->getContract()->getEndOfContract() == 2 && $timeOfContract > 2))) {
                    // Nur wenn der Vertrag noch 1 oder 2 Saisons geht, darf der Vertrag aktualisiert werden.
                    $employeeCost = $salary + $contractController->calcSigningBonus($employee->getMarketvalue(), $timeOfContract);
                    if ($employeeCost <= $team->getSalaryCap()) {
                        $moral = round($salary / ($employee->getMarketvalue() * 20 / 100), 2);
                        if ($moral >= 0.75) {
                            $employee->setMoral($moral);
                            $contract = $employee->getContract();
                            $contract->setSalary($salary);
                            $contract->setSigningBonus($contractController->calcSigningBonus($employee->getMarketvalue(), $timeOfContract));
                            $contract->setEndOfContract($timeOfContract);
                            // Speichere Employee mit der neuen Moral
                            if ($employeeController->saveEmployee($employee, $team, $contract) > 0 && $contractController->saveContract($contract) > 0) {
                                $employee->setContract($contract);
                                $employees = $team->getEmployees();
                                $employees = array_values(array_filter($employees, function (Employee $value) use ($employee) {
                                    return $value->getId() != $employee->getID();
                                }));
                                $employees[] = $employee;
                                $team->setEmployees($employees);
                                $_SESSION['team'] = $team;
                                $data['contractIsUpdated'] = true;
                            }
                        }
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