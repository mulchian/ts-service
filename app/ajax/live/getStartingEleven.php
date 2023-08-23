<?php

use touchdownstars\league\LeagueController;
use touchdownstars\live\GameController;
use touchdownstars\player\Player;
use touchdownstars\team\TeamController;

$logFile = 'getStartingEleven';
include($_SERVER['DOCUMENT_ROOT'] . '/init.php');

session_start();

if (isset($pdo) && isset($log)) {
    $teamController = new TeamController($pdo, $log);
    $gameController = new GameController($pdo, $log);
    $leagueController = new LeagueController($pdo, $log);

    if (!empty($_SESSION['team'])) {
        $team = $_SESSION['team'];
    }
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($team, $_SESSION['season'], $_SESSION['gameday'])) {
        $content = trim(file_get_contents("php://input"));
        $input = json_decode($content, true);
        if (isset($input['teamName'], $input['teamPart'])) {
            $teamName = $input['teamName'];
            $teamPart = $input['teamPart'];

            $team = $team->getName() == $teamName ? $team : $teamController->fetchTeam(null, $teamName);
            $game = $leagueController->fetchGame($team, $_SESSION['season'], $_SESSION['gameday']);
//            $gameplayTime = 15 * ceil(time() / 15);
            $gameplayTime = 10 * ceil(time() / 10);
            $currentGameplay = $gameController->getGameCalculation($game, $gameplayTime);
//            $gameplayHistory = $gameController->getGameCalculation($game, $currentGameplay['gameplayTime'] - 15);
            $gameplayHistory = $gameController->getGameCalculation($game, $currentGameplay['gameplayTime'] - 10);

            $log->debug('GameplayHistory: ' . $currentGameplay);

            if (null != $currentGameplay) {
                $secondRB = $currentGameplay['secondRB'];
                $isKickOff = $gameplayHistory['isKickOff'];
                $isPAT = $currentGameplay['isPAT'];
                $isFG = $currentGameplay['isFG'];
                $isPunt = $gameplayHistory['isPunt'];
            } else {
                $secondRB = false;
                $isKickOff = false;
                $isPunt = false;
                $isPAT = false;
                $isFG = false;
            }

            if ($isPAT && $isFG) {
                if ($teamPart == 'Offense') {
                    $startingPlayers = $gameController->getStartingEleven($team, 'Defense');
                } elseif ($teamPart == 'Defense') {
                    $startingPlayers = $gameController->getStartingEleven($team, 'Special');
                    $startingPlayers = array_values(array_filter($startingPlayers, function (Player $player) {
                        return $player->getLineupPosition() == 'K';
                    }));
                }
            } elseif ($isPunt || $isKickOff) {
                $startingPlayers = $gameController->getStartingEleven($team, 'Special');
                if ($teamPart == 'Offense') {
                    $startingPlayers = array_values(array_filter($startingPlayers, function (Player $player) {
                        return $player->getLineupPosition() == 'R';
                    }));
                } elseif ($teamPart == 'Defense') {
                    $position = $isKickOff ? 'K' : 'R';
                    $startingPlayers = array_values(array_filter($startingPlayers, function (Player $player) use ($position) {
                        return $player->getLineupPosition() == $position;
                    }));
                }
            } else {
                $startingPlayers = $gameController->getStartingEleven($team, $teamPart, $secondRB);
            }

            include($_SERVER['DOCUMENT_ROOT'] . '/pages/live/game/startingElevenListInfo.php');
            include($_SERVER['DOCUMENT_ROOT'] . '/pages/live/game/startingElevenList.php');
        }
    }
}