<?php

use touchdownstars\league\LeagueController;
use touchdownstars\main\MainController;
use touchdownstars\player\draft\DraftController;
use touchdownstars\player\draft\Draftposition;
use touchdownstars\player\PlayerController;
use touchdownstars\player\position\PositionController;

include('../init.php');

if (isset($pdo, $log)) {
    $playerController = new PlayerController($pdo, $log);
    $positionController = new PositionController($pdo);
    $leagueController = new LeagueController($pdo, $log);
    $draftController = new DraftController($pdo);
    $mainController = new MainController($pdo, $log);

    $numberOfPlayerPerDraftPerLeague = 256;

    $main = $mainController->fetchSeasonAndGameday();
    $season = $main->getSeason();

    $positions = $positionController->fetchAllPositions();
    $country = 'Deutschland';
    $leagues = $leagueController->fetchAllLeagues($country);

    foreach ($leagues as $league) {
        // Draftposition für Spieler setzen:
        for ($i = 0; $i < $numberOfPlayerPerDraftPerLeague; $i++) {
            $randomPosition = $positions[array_rand($positions)]->getPosition();
            $position = $positionController->fetchPosition($randomPosition);

            $player = $playerController->createNewPlayer(null, $position);

            $draftposition = new Draftposition();
            $draftposition->setLeague($league);
            $draftposition->setSeason($season);
            $idDraftposition = $draftController->saveDraftposition($draftposition);
            if ($idDraftposition > 0) {
                $draftposition->setId($idDraftposition);
            }

            $player->setDraftposition($draftposition);

            $playerController->savePlayer($player);
        }

    }
}
