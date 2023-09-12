<?php

use touchdownstars\employee\job\JobController;

$logFile = 'job';
include($_SERVER['DOCUMENT_ROOT'] . '/init.php');

session_start();

if (isset($pdo, $log)) {
    $jobController = new JobController($pdo, $log);

    if ($_SERVER['REQUEST_METHOD'] == 'GET') {
        $jobs = $jobController->fetchJobs();
        echo json_encode($jobs);
    }
}
exit;