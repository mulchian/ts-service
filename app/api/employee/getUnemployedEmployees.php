<?php

use touchdownstars\employee\EmployeeController;
use touchdownstars\employee\job\JobController;

$logFile = 'employee';
include($_SERVER['DOCUMENT_ROOT'] . '/init.php');

session_start();

if (isset($pdo, $log)) {
    $employeeController = new EmployeeController($pdo, $log);
    $jobController = new JobController($pdo);

    $unemployedEmployees = array();

    if (!empty($_SESSION['team'])) {
        $team = $_SESSION['team'];
    }

    if ($_SERVER['REQUEST_METHOD'] == 'GET') {
        if (!empty($_GET['jobName'])) {
            $jobName = $_GET['jobName'];
            $job = $jobController->fetchJobByName($jobName);
            // Gucke, ob zu dem Job die unemployed Employees in der Session gespeichert sind.
            if (!empty($_SESSION['unemployedEmployees' . $jobName])) {
                $unemployedEmployees = $_SESSION['unemployedEmployees' . $jobName];
            }
            // Wenn weniger Mitarbeiter in der Session stehen, müssen die Mitarbeiter neu geladen werden.
            if (count($unemployedEmployees) < $employeeController->countUnemployedEmployees($jobName)) {
                $unemployedEmployees = $employeeController->fetchUnemployedEmployees($jobName);

                if (!empty($unemployedEmployees) && !empty($job)) {
                    $data = array();
                    foreach ($unemployedEmployees as $employee) {
                        $data[] = $employee;
                    }
                    $_SESSION['unemployedEmployees' . $jobName] = $data;
                } else {
                    $errorMsg = 'Es gibt keine freien Mitarbeiter.';
                    $log->debug('getUnemployedEmployees' . $errorMsg);
                    $data['error'] = $errorMsg;
                }
            } else {
                $data = $unemployedEmployees;
            }
        }

        if (!empty($data)) {
            echo json_encode($data);
        }
    }
}
exit;