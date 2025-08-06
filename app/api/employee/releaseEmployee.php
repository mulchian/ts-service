<?php

use touchdownstars\contract\ContractController;
use touchdownstars\employee\Employee;
use touchdownstars\employee\EmployeeController;
use touchdownstars\team\Team;
use touchdownstars\team\TeamController;

$logFile = 'employee';
include($_SERVER['DOCUMENT_ROOT'] . '/init.php');

session_start();

if (isset($pdo, $log)) {
    $employeeController = new EmployeeController($pdo, $log);
    $teamController = new TeamController($pdo, $log);
    $contractController = new ContractController($pdo);
    $isReleased = false;

    if (!empty($_SESSION['team'])) {
        $team = $_SESSION['team'];
    }
    if (!empty($_SESSION['gameday'])) {
        $gameday = $_SESSION['gameday'];
    }

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $content = trim(file_get_contents("php://input"));
        $input = json_decode($content, true);
        if (isset($team) && !empty($input['employeeId']) && !empty($input['jobName'])) {
            $log->debug('releaseEmployee: ' . print_r($input, true));
            $employeeId = $input['employeeId'];
            $jobName = $input['jobName'];
            if (isset($gameday)) {
                $remainingDays = 28 - $gameday;
            } else {
                $remainingDays = 0;
            }

            $employees = $team->getEmployees();
            // Überprüfe, ob Team wirklich den Employee mit der ID hat.
            $employeesToUpdate = array_values(array_filter($employees, function (Employee $employee) use ($employeeId, $jobName) {
                return $employee->getId() == $employeeId && $employee->getJob()->getName() == $jobName;
            }));
            if (count($employeesToUpdate) > 0) {
                // Team hat wirklich den zu löschenden Employee
                $employee = $employeeController->fetchEmployee($employeeId);
                if (!empty($employee)) {
                    // Lösche den Vertrag
                    $salary = $employee->getContract()->getSalary();
                    if ($contractController->deleteContract($employee->getContract()->getId()) == 1) {
                        // Prüfe, ob der Mitarbeiter gelöscht werden muss (älter als 63) oder auf den Free Agents Markt kommt (jünger als oder gleich 63)
                        if ($employee->getAge() > 63) {
                            // Mitarbeiter ist zu alt und muss gelöscht werden
                            if ($employeeController->deleteEmployee($employee) == 1) {
                                // Employee wurde gelöscht
                                $isReleased = true;
                            } else {
                                //Logging, dass entweder kein Employee gelöscht wurde oder zu viele
                                $log->debug('Employee wurde nicht gelöscht. ' . print_r($employee, true));
                            }
                        } else {
                            // Mitarbeiter ist jung genug und kommt auf den Free Agents Markt
                            $employee->setIdTeam(null);
                            $employee->setContract(null);
                            if ($employeeController->saveEmployee($employee, $team) != 0) {
                                // Employee wurde entlassen.
                                $isReleased = true;
                            }
                        }
                        if ($isReleased) {
                            // Mitarbeiter wurde aktualisiert oder gelöscht, also kann das Team aktualisiert werden.
                            $correctedSalary = $teamController->updateSalaryCap($team, $remainingDays, $salary);
                            $team->setSalaryCap($correctedSalary);
                            $team->setEmployees($employeeController->fetchEmployeesOfTeam($team));
                            $teamController->saveTeam($team);
                            $_SESSION['team'] = $team;
                            $data['correctedSalary'] = $correctedSalary;
                        } else {
                            $errorMsg = 'Der Mitarbeiter konnte nicht entlassen werden.';
                        }
                    } else {
                        $errorMsg = 'Der Vertrag konnte nicht gelöscht werden.';
                    }
                } else {
                    $errorMsg = 'Es wurde kein Mitarbeiter zur ID gefunden.';
                }
            } else {
                $errorMsg = 'Keine Mitarbeiter im Team zum Aktualisieren.';
            }
        }

        if (!empty($errorMsg)) {
            $log->warning('releaseEmployee: ' . $errorMsg);
            $data['error'] = $errorMsg;
        }
        $data['isReleased'] = $isReleased;
        if (!empty($data)) {
            echo json_encode($data);
        }
    }
}
exit;