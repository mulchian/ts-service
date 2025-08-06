<?php

use touchdownstars\player\skill\SkillController;

$logFile = 'player';
include($_SERVER['DOCUMENT_ROOT'] . '/init.php');

session_start();

if (isset($pdo, $log)) {
    $skillController = new SkillController($pdo, $log);

    if ($_SERVER['REQUEST_METHOD'] == 'GET') {
        $skillNames = $skillController->fetchSkillNames();

        if ($skillNames) {
            echo json_encode($skillNames);
        }
    }
}
exit;