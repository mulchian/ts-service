<?php

use touchdownstars\player\position\PositionController;

$logFile = 'position';
include($_SERVER['DOCUMENT_ROOT'] . '/init.php');

session_start();

if (isset($pdo, $log)) {
    $positionController = new PositionController($pdo, $log);

    if ($_SERVER['REQUEST_METHOD'] == 'GET') {
        $positions = $positionController->fetchAllPositions();

        if ($positions) {
            echo json_encode($positions);
        }
    }
}
exit;