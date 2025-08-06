<?php

use touchdownstars\team\TeamController;

$logFile = 'team';
include($_SERVER['DOCUMENT_ROOT'] . '/init.php');
include('../util/util.php');

session_start();

if (isset($pdo, $log)) {
    $teamController = new TeamController($pdo, $log);

    $log->debug('getTeams - ' . print_r($_SERVER['REQUEST_METHOD'], true));
    if ($_SERVER['REQUEST_METHOD'] == 'GET') {
        $log->debug('getTeams', ['input' => $_GET]);
        $teamSelectionNum = (int)$_GET['teamSelectionNum'] ?? 0;
        $country = $_GET['country'] ?? null;
        $log->debug('getTeams', [
            'teamSelectionNum' => $teamSelectionNum,
            'country' => $country
        ]);

        $teams = $teamController->fetchAllTeams($country, $teamSelectionNum);

        if (!empty($teams)) {
            echo json_encode($teams);
        }
    }
}
exit;