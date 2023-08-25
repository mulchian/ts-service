<?php
// Stündlicher Cronjob
// Erstellung von neuen freien Mitarbeitern, wenn es weniger als 10 gibt
// Löscht Mitarbeiter, die seit 2 Saisons nicht übernommen wurden
use touchdownstars\employee\EmployeeController;
use touchdownstars\employee\job\JobController;

$logFile = 'cron/createPersonal';
include('../init.php');

const MAX_UNEMPLOYED_EMPLOYEES_PER_JOB = 10;

if (isset($pdo, $log)) {

    $jobController = new JobController($pdo);
    $employeeController = new EmployeeController($pdo, $log);

    $jobs = $jobController->fetchJobs();
    foreach ($jobs as $job) {
        $countUnemployedEmployee = $employeeController->countUnemployedEmployees($job->getName());
        if ($countUnemployedEmployee < MAX_UNEMPLOYED_EMPLOYEES_PER_JOB) {
            for ($i = $countUnemployedEmployee; $i < MAX_UNEMPLOYED_EMPLOYEES_PER_JOB; $i++) {
                $employeeController->createNewEmployee($job);
            }
        } else {
            // Überprüfe, dass jeder nicht angestellte Mitarbeiter maximal zwei Saisons auf Jobsuche ist.
            $unemployedEmployees = $employeeController->fetchUnemployedEmployees($job->getName());
            foreach ($unemployedEmployees as $unemployedEmployee) {
                if ($unemployedEmployee->getUnemployedSeasons() > 2) {
                    $employeeController->deleteEmployee($unemployedEmployee);
                    $employeeController->createNewEmployee($job);
                }
            }
        }
    }
}