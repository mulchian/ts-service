<?php

$logFile = 'user';
include($_SERVER['DOCUMENT_ROOT'] . '/init.php');

session_start();

if (isset($pdo, $log)) {
    if ($_SERVER['REQUEST_METHOD'] == 'GET') {
        if (empty($_SESSION) || empty($_SESSION['created'])) {
            $log->debug('Keine Session vorhanden');
            echo true;
            exit;
        }
        if (!isset($_SESSION['user']) || (time() - $_SESSION['created']) > 172800) {
            $log->debug('Session abgelaufen');
            echo true;
            exit;
        }
        $log->debug('Session vorhanden - kein Login nötig');
        echo false;
    }
}
exit;
