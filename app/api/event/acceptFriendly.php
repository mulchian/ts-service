<?php

use touchdownstars\league\LeagueController;

$logFile = 'event';
include($_SERVER['DOCUMENT_ROOT'] . '/init.php');

session_start();

if (isset($pdo, $log)) {
    $leagueController = new LeagueController($pdo, $log);
    $accepted = false;

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $content = trim(file_get_contents("php://input"));
        $input = json_decode($content, true);
        if (isset($input['friendly'])) {
            $log->debug('accept friendly' . print_r($input, true));
            $friendly = $input['friendly'];
            $log->debug('friendly data', ['friendly' => $friendly]);

            $homeAccepted = filter_var($friendly['homeAccepted'], FILTER_VALIDATE_BOOLEAN) ?? false;
            $awayAccepted = filter_var($friendly['awayAccepted'], FILTER_VALIDATE_BOOLEAN) ?? false;

            $gameTime = new DateTime($friendly['gameTime'], new DateTimeZone('Europe/Berlin'));
            $log->debug('Spielzeit: ' . $gameTime->format('d.m.Y H:i'));
            $id = $leagueController->acceptFriendly($gameTime, $friendly['home'], $friendly['away'], $homeAccepted, $awayAccepted);
            $log->debug('friendly-id: ' . $id);

            if ($id > 0) {
                $accepted = true;
            } else {
                $data['error'] = 'Unable to accept friendly match.';
            }
        }
        $data['added'] = $accepted;
        echo json_encode($data);
    }
}
exit;