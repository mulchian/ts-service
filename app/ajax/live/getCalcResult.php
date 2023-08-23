<?php

use touchdownstars\league\LeagueController;
use touchdownstars\live\GameController;
use touchdownstars\team\TeamController;

$logFile = 'getCalcResult';
include($_SERVER['DOCUMENT_ROOT'] . '/init.php');

session_start();

if (isset($pdo) && isset($log)) {
    $leagueController = new LeagueController($pdo, $log);
    $teamController = new TeamController($pdo, $log);
    $gameController = new GameController($pdo, $log);

    if (!empty($_SESSION['team'])) {
        $team = $_SESSION['team'];
    }

    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($team)) {
        $isFGAttempt = false;
        $changeSides = false;
        $content = trim(file_get_contents("php://input"));
        $input = json_decode($content, true);
        $gameTime = $input['gameTime'];

        if (isset($_SESSION['season'], $_SESSION['gameday'], $gameTime)) {
            $log->debug('gameTime: ' . $gameTime);

            $game = $gameController->getGame($team);
            $log->debug('Game: ' . print_r($game, true));

            $gameplay = $gameController->getOrCalcLastGameplayResult($gameTime, $game);
            $log->debug('GameplayResult: ' . print_r($gameplay, true));
            if (!isset($gameplay['isEnd'])) {
                $gameplay['isEnd'] = $gameController->checkAndCalcEnding($gameplay, $game);
            }

            $user = $_SESSION['user'];
            if (isset($user) && $user->isAdmin()) {
                $data['runner'] = $gameplay['runner'];
                $data['defGameplay'] = $gameplay['defGameplay'];
            }

            // Ein Text ins Frontend, dort muss eventuell am ';' getrennt werden, wenn zwei Texte (z.B. Strafe, Kick, Punt) vorhanden sind.
            $data['gametext'] = $gameplay['gametext'];

            $offTeamName = $teamController->fetchTeamNameById($gameplay['idOffTeam']);

            $data['left'] = $team->getName();
            $data['leftTeamPart'] = $offTeamName == $team->getName() ? 'Offense' : 'Defense';
            $data['right'] = $team->getName() == $game['home'] ? $game['away'] : $game['home'];
            $data['rightTeamPart'] = $offTeamName == $team->getName() ? 'Defense' : 'Offense';
            $data['secondRB'] = $gameplay['secondRB'];

            // needed variables for moving the ball
            $data['offGameplay'] = $gameplay['offGameplay'];
            $data['direction'] = $offTeamName == $team->getName() ? 'right' : 'left';

            $data['playClock'] = $gameplay['playClock'];
            $data['quarter'] = $gameplay['quarter'];
            $data['down'] = $gameplay['down'];
            $data['yardsToFirstDown'] = $gameplay['yardsToFirstDown'];
            $data['yardsToTD'] = $gameplay['yardsToTD'];

            $data['isEnd'] = $gameplay['isEnd'] ?? false;
        }

        if (isset($_POST['gameId'])) {
            $game = $gameController->getGameById($_POST['gameId']);

            $gameplay = $gameController->getOrCalcLastGameplayResult(time(), $game);
            $data['recalculationFinished'] = $gameplay['isEnd'] ?? false;

            $standings = $leagueController->getStandings($game);
            $data['result'] = $standings['score'];
        }

        if (!empty($data)) {
            $log->debug('JSON: ' . json_encode($data));
            echo json_encode($data);
        }
    }
}
exit;
