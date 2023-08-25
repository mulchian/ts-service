<?php

use touchdownstars\contract\ContractController;
use touchdownstars\employee\Employee;
use touchdownstars\employee\EmployeeController;
use touchdownstars\team\Team;
use touchdownstars\team\TeamController;

$logFile = 'personal';
include($_SERVER['DOCUMENT_ROOT'] . '/init.php');

session_start();

if (isset($pdo, $log)) {
    $employeeController = new EmployeeController($pdo, $log);
    $teamController = new TeamController($pdo, $log);
    $contractController = new ContractController($pdo);

    if (!empty($_SESSION['team'])) {
        $team = $_SESSION['team'];
    }
    if (!empty($_SESSION['gameday'])) {
        $gameday = $_SESSION['gameday'];
    }

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $content = trim(file_get_contents("php://input"));
        $input = json_decode($content, true);
        if (isset($team) && !empty($input['idEmployee']) && !empty($input['jobname'])) {
            $idEmployee = $input['idEmployee'];
            $jobname = $input['jobname'];
            if (isset($gameday)) {
                $remainingDays = 28 - $gameday;
            } else {
                $remainingDays = 0;
            }

            $employees = $team->getEmployees();
            // Überprüfe, ob Team wirklich den Employee mit der ID hat.
            $employeesToUpdate = array_values(array_filter($employees, function (Employee $employee) use ($idEmployee, $jobname) {
                return $employee->getId() == $idEmployee && $employee->getJob()->getName() == $jobname;
            }));
            if (count($employeesToUpdate) > 0) {
                // Team hat wirklich den zu löschenden Employee
                $employee = $employeeController->fetchEmployee($idEmployee);
                if (isset($employee) && !empty($employee)) {
                    // Lösche den Vertrag
                    $salary = $employee->getContract()->getSalary();
                    if ($contractController->deleteContract($employee->getContract()->getId()) == 1) {
                        $isUpdated = false;
                        // Prüfe, ob der Mitarbeiter gelöscht werden muss (älter als 63) oder auf den Free Agents Markt kommt (jünger als oder gleich 63)
                        if ($employee->getAge() > 63) {
                            // Mitarbeiter ist zu alt und muss gelöscht werden
                            if ($employeeController->deleteEmployee($employee) == 1) {
                                // Employee wurde gelöscht
                                $isUpdated = true;
                            } else {
                                //Logging, dass entweder kein Employee gelöscht wurde oder zu viele
                                $log->debug('Employee wurde nicht gelöscht. ' . print_r($employee, true));
                            }
                        } else {
                            // Mitarbeiter ist jung genug und kommt auf den Free Agents Markt
                            $employee->setIdTeam(null);
                            $employee->setContract(null);
                            if ($employeeController->saveEmployee($employee, $team) != 0) {
                                // Employee wurde aktualisiert.
                                $isUpdated = true;
                            }
                        }
                        if ($isUpdated) {
                            // Mitarbeiter wurde aktualisiert oder gelöscht, also kann das Team aktualisiert werden.
                            $correctedSalary = $teamController->updateSalaryCap($team, $remainingDays, $salary);
                            $team->setSalaryCap($correctedSalary);
                            if ($teamController->saveTeam($team) > 0) {
                                // Team ist in der Datenbank aktuell, also wird es in der Session aktualisiert.
                                $data = updateEmployeesInSession($team, $employees, $idEmployee, $jobname);
                                $data['correctedSalary'] = $correctedSalary;
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

function updateEmployeesInSession(Team $team, array $employees, string $idEmployee, string $jobname): array
{
    // Employee aus dem Session-Team nehmen.
    $employees = array_values(array_filter($employees, function (Employee $employee) use ($idEmployee, $jobname) {
        return $employee->getId() != $idEmployee && $employee->getJob()->getName() != $jobname;
    }));
    $team->setEmployees($employees);
    $_SESSION['team'] = $team;
    $data['employeeIsReleased'] = true;
    return $data;
}