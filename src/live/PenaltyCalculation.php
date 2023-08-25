<?php


namespace touchdownstars\live;


use PDO;
use touchdownstars\gametext\Gametext;
use touchdownstars\gametext\GametextController;
use Monolog\Logger;
use touchdownstars\penalty\Penalty;
use touchdownstars\penalty\PenaltyController;
use touchdownstars\player\Player;
use touchdownstars\statistics\StatisticsController;
use touchdownstars\team\Team;
use touchdownstars\team\TeamController;

class PenaltyCalculation
{

    private Logger $log;
    private PDO $pdo;

    public function __construct(PDO $pdo, Logger $log = null)
    {
        $this->pdo = $pdo;
        if (isset($log)) {
            $this->log = $log;
        }
    }

    /**
     * Berechnet, ob eine Strafe für die aktiven Spieler anhand der Teamchemie und Durchschnittsmoral auftritt.
     * @param Team $team
     * @param array $activePlayers
     * @return bool - true, wenn es zu einer Strafe kommt
     */
    public function isPenalty(Team $team, array $activePlayers): bool
    {
        $gameController = new GameController($this->pdo);
        $teamController = new TeamController($this->pdo, $this->log);
        $teamchemie = $team->getChemie();
        $playerchemie = $teamController->getAverageMoral($activePlayers);

        $result = round(($teamchemie + $playerchemie) / 2, 0, PHP_ROUND_HALF_UP);

        if ($result >= 59) {
            $chance = 149 - $result;
        } else {
            if (in_array($result, [58, 57, 56, 55, 54, 53])) {
                $chance = 59;
            } else if (in_array($result, [52, 51, 50])) {
                $chance = $result + 1;
            } else {
                $chance = 50;
            }
        }

        return $gameController->probability($chance, 1000);
    }

    /**
     * Berechnet, ob eine Strafe für den TeamPart (Offense oder Defense) stattfindet.
     * Führt ebenfalls die Statistik für das Penalty durch.
     * @param int $gameId
     * @param Team $penaltyTeam
     * @param array $activePlayers
     * @param string $gameplay
     * @param string $teamPart
     * @return Penalty - Penalty, welchen den gametext enthält
     */
    public function calcPenalty(int $gameId, Team $penaltyTeam, array $activePlayers, string $gameplay, string $teamPart): Penalty
    {
        $gameController = new GameController($this->pdo);
        $gametextController = new GametextController($this->pdo);
        $penaltyController = new PenaltyController($this->pdo);
        $statisticsController = new StatisticsController($this->pdo, $this->log);
        $penaltyText = '';
        $penaltyTexts = $gametextController->fetchAllSituationalTexts('Penalty');
        $penalties = $penaltyController->fetchAllPenalties($gameplay, $teamPart);

        // Penalties in Space mergen (name => chance)
        $penaltySpace = array();
        foreach ($penalties as $penalty) {
            $penaltySpace[$penalty->getPenalty()] = $penalty->getChance();
        }

        $penaltyKey = $gameController->dw_rand($penaltySpace, array_key_first($penaltySpace));

        $penalty = $penaltyController->fetchPenalty($gameplay, $teamPart, $penaltyKey);

        if ($teamPart == 'Offense') {
            // Offense Penalty
            switch ($penalty->getPenalty()) {
                case 'Offense Holding':
                    $positions = array('OT', 'OG', 'C', 'TE', 'FB');

                    $weighting = array(0.5, 0.23, 0.12, 0.09, 0.06, 0);

                    $penaltyPlayer = $this->getPenaltyPlayer($activePlayers, $positions, $weighting);

                    //Statistik PenaltyPlayer bekommt Strafe
                    $statisticsController->savePenalty($gameId, $penaltyTeam, $penaltyPlayer, $penalty->getYards());

                    $penaltyText = $this->getPenaltyText($penalty->getPenalty(), $penaltyTexts, $penaltyPlayer);
                    break;
                case 'False Start':
                    $positions = array('OT', 'OG', 'C', 'TE', 'FB', 'WR');
                    $weighting = array(0.5, 0.15, 0.12, 0.1, 0.06, 0.04, 0.02, 0.01, 0);

                    $penaltyPlayer = $this->getPenaltyPlayer($activePlayers, $positions, $weighting);

                    //Statistik PenaltyPlayer bekommt Strafe
                    $statisticsController->savePenalty($gameId, $penaltyTeam, $penaltyPlayer, $penalty->getYards());

                    $penaltyText = $this->getPenaltyText($penalty->getPenalty(), $penaltyTexts, $penaltyPlayer);
                    break;
                case 'Delay of Game':
                    $positions = array('QB', 'C');
                    $weighting = array(0.7, 0.3);

                    $penaltyPlayer = $this->getPenaltyPlayer($activePlayers, $positions, $weighting);

                    //Statistik PenaltyPlayer bekommt Strafe
                    $statisticsController->savePenalty($gameId, $penaltyTeam, $penaltyPlayer, $penalty->getYards());

                    $penaltyText = $this->getPenaltyText($penalty->getPenalty(), $penaltyTexts, $penaltyPlayer, $activePlayers);
                    break;
                case 'Unsportsmanlike Conduct':
                    $positions = array();
                    foreach ($activePlayers as $activePlayer) {
                        $positions[] = $activePlayer->getType()->getPosition()->getPosition();
                    }
                    $weighting = array(0.5, 0.12, 0.1, 0.07, 0.06, 0.05, 0.04, 0.03, 0.02, 0.01, 0);

                    $penaltyPlayer = $this->getPenaltyPlayer($activePlayers, $positions, $weighting);

                    //Statistik PenaltyPlayer bekommt Strafe
                    $statisticsController->savePenalty($gameId, $penaltyTeam, $penaltyPlayer, $penalty->getYards());

                    $penaltyText = $this->getPenaltyText($penalty->getPenalty(), $penaltyTexts, $penaltyPlayer);
                    break;
            }
        } else {
            // Defense Penalty
            if ($penalty->getPenalty() == 'Encroachment') {
                $positions = array('DE', 'DT', 'OLB', 'MLB');
                $weighting = array(0.5, 0.2, 0.1, 0.08, 0.07, 0.05, 0);
            } elseif ($penalty->getPenalty() == 'Pass Interference') {
                $positions = array('CB', 'SS', 'FS');
                $weighting = array(0.5, 0.25, 0.12, 0.07, 0.06, 0);
            } else {
                $positions = array();
                foreach ($activePlayers as $activePlayer) {
                    $positions[] = $activePlayer->getType()->getPosition()->getPosition();
                }
                $weighting = array(0.5, 0.12, 0.1, 0.07, 0.06, 0.05, 0.04, 0.03, 0.02, 0.01, 0);
            }

            $penaltyPlayer = $this->getPenaltyPlayer($activePlayers, $positions, $weighting);
            //Statistik PenaltyPlayer bekommt Strafe
            $statisticsController->savePenalty($gameId, $penaltyTeam, $penaltyPlayer, $penalty->getYards());

            $penaltyText = $this->getPenaltyText($penalty->getPenalty(), $penaltyTexts, $penaltyPlayer);
        }

        $penalty->setPenaltyText($penaltyText);

        return $penalty;
    }

    private function getPenaltyPlayer(array $activePlayers, array $positions, array $weighting): Player
    {
        $gameController = new GameController($this->pdo);
        $players = array_values(array_filter($activePlayers, function (Player $player) use ($positions) {
            return in_array($player->getType()->getPosition()->getPosition(), $positions);
        }));
        shuffle($players);
        usort($players, function (Player $player1, Player $player2) {
            return $player1->getMoral() <=> $player2->getMoral();
        });
        $weightingSpace = array();
        $count = 0;
        foreach ($players as $player) {
            $weightingSpace[$player->getId()] = $weighting[$count];
            $count++;
        }
        $penaltyId = $gameController->dw_rand($weightingSpace, array_key_first($weightingSpace));

        return array_values(array_filter($players, function (Player $player) use ($penaltyId) {
            return $player->getId() == $penaltyId;
        }))[0];
    }

    private function getPenaltyText(string $penaltyName, array $penaltyTexts, Player $triggeringPlayer, array $activePlayers = null): string
    {
        $penaltyText = array_values(array_filter($penaltyTexts, function (Gametext $penaltyText) use ($penaltyName, $triggeringPlayer) {
            $isTriggeringPlayer = (null == $penaltyText->getTriggeringPosition() || $penaltyText->getTriggeringPosition() == $triggeringPlayer->getType()->getPosition()->getPosition());
            return $penaltyText->getTextName() == $penaltyName && $isTriggeringPlayer;
        }))[0];

        if (null != $penaltyText->getTriggeringPosition() && $penaltyText->getTriggeringPosition() == 'C' && isset($activePlayers)) {
            $triggeringPlayer = array_values(array_filter($activePlayers, function (Player $player) {
                return $player->getType()->getPosition()->getPosition() == 'QB';
            }))[0];
        }

        $gametextController = new GametextController($this->pdo);
        return $gametextController->changeNamePosInText($penaltyText->getText(), $triggeringPlayer);
    }
}