<?php

use touchdownstars\league\LeagueController;

$logFile = 'event';
include($_SERVER['DOCUMENT_ROOT'] . '/init.php');

session_start();

if (isset($pdo, $log)) {
    $leagueController = new LeagueController($pdo, $log);
    $declined = false;

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $content = trim(file_get_contents("php://input"));
        $input = json_decode($content, true);
        if (isset($input['friendly'])) {
            $log->debug('decline friendly' . print_r($input, true));
            $friendly = $input['friendly'];
            $log->debug('friendly data', ['friendly' => $friendly]);

            $gameTime = new DateTime($friendly['gameTime']);
            $declined = $leagueController->declineFriendly($friendly['id'], $gameTime, $friendly['home'], $friendly['away']);
            if (!$declined) {
                $data['error'] = 'Unable to decline friendly match.';
            } else {
                $log->debug('Friendly match declined successfully.');
            }
        }
        $data['declined'] = $declined;
        echo json_encode($data);
    }
}
exit;