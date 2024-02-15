<?php

use touchdownstars\employee\EmployeeController;
use touchdownstars\team\TeamController;

$logFile = 'employee';
include($_SERVER['DOCUMENT_ROOT'] . '/init.php');
include('../team/util.php');

session_start();

if (isset($pdo, $log)) {
    $teamController = new TeamController($pdo, $log);
    $employeeController = new EmployeeController($pdo, $log);

    if ($_SERVER['REQUEST_METHOD'] == 'GET') {
        $team = getTeam($log, $teamController);

        if ($team) {
            $employees = $employeeController->fetchEmployeesOfTeam($team);
            $log->debug('employees fetched: ' . count($employees));
            echo json_encode($employees);
        }
    }
}
exit;