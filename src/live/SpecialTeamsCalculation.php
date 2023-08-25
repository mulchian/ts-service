<?php


namespace touchdownstars\live;

use PDO;
use touchdownstars\gametext\GametextController;
use Monolog\Logger;
use touchdownstars\player\Player;
use touchdownstars\statistics\StatisticsController;
use touchdownstars\team\Team;

class SpecialTeamsCalculation
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
     * Ausfuehrung des Kicks von der 50 Yard-Linie.
     * @param int $gameId
     * @param array $kickingPlayers
     * @param array $returningPlayers
     * @return array
     */
    public function kickOff(int $gameId, array $kickingPlayers, array $returningPlayers): array
    {
        $gameController = new GameController($this->pdo, $this->log);
        $statisticsController = new StatisticsController($this->pdo, $this->log);
        $specialTexts = $this->getSpecialTexts();

        $kicker = array_values(array_filter($kickingPlayers, function (Player $player) {
            return $player->getLineupPosition() == 'K';
        }))[0];

        $kickDistance = $this->getDistance('Kick', $kicker, 'power');

        if ((65 - $kickDistance) <= 0) {
            // Touchback - Start an der 25 Yard-Linie
            $kickText = $gameController->getText('Kick', $specialTexts, $kicker, null, null, null, $kickDistance, null, true);
            return array($kickDistance => $kickText);
        }

        $returner = array_values(array_filter($returningPlayers, function (Player $player) {
            return $player->getLineupPosition() == 'R';
        }))[0];

        $yardsToTD = (65 - $kickDistance) >= 0 ? (100 - (65 - $kickDistance)) : 100;
        $returnDistance = $this->getReturnDistance('Kickreturn', $returner);

        $isTD = false;
        $newYardsToTD = $yardsToTD - $returnDistance;
        if ($newYardsToTD <= 0) {
            $isTD = true;
        }

        $kickText = $gameController->getText('Kick', $specialTexts, $kicker, null, null, null, $kickDistance, null, false);
        $returnText = $gameController->getText('Kickreturn', $specialTexts, $returner, null, null, null, $returnDistance, null, $isTD);

        // Statistik für Kickreturn
        $statisticsController->saveKickreturn($gameId, $returner, $yardsToTD, $returnDistance);

        return array($kickDistance => $kickText, $newYardsToTD => $returnText);
    }

    /**
     * Ausführung des Punts von der aktuellen Position im vierten Down.
     * @param int $gameId
     * @param Team $puntTeam
     * @param array $puntingPlayers
     * @param array $returningPlayers
     * @param int $yardsToTD
     * @return array
     */
    public function punt(int $gameId, Team $puntTeam, array $puntingPlayers, array $returningPlayers, int $yardsToTD): array
    {
        $gameController = new GameController($this->pdo, $this->log);
        $specialTexts = $this->getSpecialTexts();

        $punter = array_values(array_filter($puntingPlayers, function (Player $player) {
            return $player->getLineupPosition() == 'P';
        }))[0];
        $returner = array_values(array_filter($returningPlayers, function (Player $player) {
            return $player->getLineupPosition() == 'R';
        }))[0];

        $puntDistance = $this->getDistance('Punt', $punter, 'power');

        $yardsToTouchback = $yardsToTD - $puntDistance;
        if ($yardsToTouchback > 0) {
            // Nur Punt ins Feld mit Return
            // newYardsToTD sind die Yards, die der Returner zum TD braucht.
            $newYardsToTD = 100 - $yardsToTouchback;
            return $this->calcPuntreturnDistance($gameId, $specialTexts, $puntTeam, $punter, $returner, 'inField', $puntDistance, $newYardsToTD);
        }

        // Berechnung, ob Touchback oder Punt kurz vor die Endzone
        $isTouchback = $gameController->probability(50);
        if ($isTouchback) {
            $statisticsController = new StatisticsController($this->pdo, $this->log);
            $statisticsController->savePunt($gameId, $puntTeam, $punter, $yardsToTD);
            $puntText = $gameController->getText('Punt', $specialTexts, $punter, null, null, null, $puntDistance, null, true);
            return array(75 => $puntText);
        }

        // Kein Touchback, also landet der Ball in einer Range von Yards vor der Endzone (z.B. an der 10 Yard-Linie)
        $puntResult = $this->getDistance('Puntaccuracy', $punter, 'puntAccuracy');
        // PuntDistance nach der Berechnung mithilfe der Genauigkeit auf einen bestimmten Yard-Punkt (z.B. die 10 Yard-Linie)
        $correctedPuntDistance = $yardsToTD - $puntResult;
        // Yards zum TD ab der Linie, wo der Ball landet (z.B.: von der 10 Yard-Linie)
        $newYardsToTD = 100 - $puntResult;

        return $this->calcPuntreturnDistance($gameId, $specialTexts, $puntTeam, $punter, $returner, null, $correctedPuntDistance, $newYardsToTD);
    }

    /**
     * Ausführung des Field Goals
     * @param int $gameId
     * @param array $kickingPlayers
     * @param int $yardsToTD - Yards zum Field Goal
     * @param string|null $isPAT
     * @return array - KickDistance (Key) und Gametext (Value) für die Distanz des Field Goals
     */
    public function fieldGoal(int $gameId, array $kickingPlayers, int $yardsToTD, ?string $isPAT = null): array
    {
        $gameController = new GameController($this->pdo, $this->log);
        $statisticsController = new StatisticsController($this->pdo, $this->log);
        $specialTexts = $this->getSpecialTexts();

        $kicker = array_values(array_filter($kickingPlayers, function (Player $player) {
            return $player->getLineupPosition() == 'K';
        }))[0];

        $kickDistance = $this->getDistance('FieldGoal', $kicker, 'power', true);

        if ($yardsToTD - $kickDistance > 0) {
            // Field Goal zu kurz
            $statisticsController->saveKick($gameId, $kicker, $kickDistance, false);
            $kickText = $gameController->getText('FieldGoal', $specialTexts, $kicker, null, null, 'noPower', $kickDistance, null, false);
            $newYardsToTD = ($yardsToTD + 8) <= 20 ? 80 : 100 - ($yardsToTD + 8);
            $this->log->debug('Field Goal Result: Zu kurz. Nächster Spielzug von ' . $newYardsToTD . ' Yards.');
            return array($newYardsToTD => $kickText);
        }

        // Power reicht für die Distanz -> Berechnung anhand der Genauigkeit, ob Field Goal oder nicht
        if ($yardsToTD < 30) {
            $differenceString = '<30';
        } elseif ($yardsToTD < 50) {
            $differenceString = '<50';
        } elseif ($yardsToTD < 56) {
            $differenceString = '<56';
        } else {
            $differenceString = '<65';
        }

        $probability = $this->getDistance('FieldGoal', $kicker, 'kickAccuracy', true, $differenceString);
        $isFG = $gameController->probability($probability, 1000);

        if (!$isFG) {
            $newYardsToTD = ($yardsToTD + 8) <= 20 ? 80 : 100 - ($yardsToTD + 8);
        } else {
            // Kick-Off
            $newYardsToTD = 65;
        }

        if ($yardsToTD < $kickDistance) {
            $kickDistance = $yardsToTD;
        }

        $statisticsController->saveKick($gameId, $kicker, $kickDistance, $isFG, isset($isPAT));

        $kickText = $gameController->getText('FieldGoal', $specialTexts, $kicker, null, null, $isPAT, $kickDistance, null, $isFG);
        $result = array($newYardsToTD => $kickText, 'isFG' => $isFG);
        $this->log->debug('Field Goal Result: ' . print_r($result, true));
        return $result;
    }

    private function getSpecialTexts(): array
    {
        $gametextController = new GametextController($this->pdo, $this->log);
        if (!isset($_SESSION['specialTexts'])) {
            $_SESSION['specialTexts'] = $gametextController->fetchAllGameplayTexts('Special');
        }
        return $_SESSION['specialTexts'];
    }

    /**
     * Gibt die Distanz eines Kicks oder Punts zurueck.
     * @param string $gameplay - Punt. Kick oder FieldGoal
     * @param Player $player - Kicker oder Punter
     * @param string $skillName - Zu nutzender Skill (power, puntAccuracy oder kickAccuracy)
     * @param bool $isFieldGoal - Standard: false
     * @param string $differenceString - Standard 0
     * @return string - Distanz
     */
    private function getDistance(string $gameplay, Player $player, string $skillName, bool $isFieldGoal = false, string $differenceString = '0'): string
    {

        $selectStmt = 'SELECT * FROM `t_gameplay_calculation` WHERE gameplay = :gameplay AND calculation = :calcNr AND difference = :difference';
        $stmt = $this->pdo->prepare($selectStmt);
        $stmt->execute(['gameplay' => $gameplay, 'calcNr' => 1, 'difference' => $differenceString]);
        $gameplayCalc = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->log->debug('Gameplay: ' . $gameplay);
        $this->log->debug('SkillName: ' . $skillName);
        $this->log->debug('GameplayCalc: ' . print_r($gameplayCalc, true));

        $distances = explode(';', $gameplayCalc['distances']);
        $neededSkill = explode(';', $gameplayCalc['chances']);
        $distanceSpace = array_combine($neededSkill, $distances);

        $skill = floor($player->getSkills()[$skillName]);

        $this->log->debug('Skill: ' . $skill);

        if ($isFieldGoal) {
            if ($differenceString !== '0') {
                $distanceSpace = array_combine($distances, $neededSkill);
            }
            return $distanceSpace[$skill];
        }

        $distance = explode('-', $distanceSpace[$skill]);
        $this->log->debug($gameplay . ' Distanz: ' . print_r($distance, true));
        return rand($distance[0], $distance[1]);
    }

    /**
     * Berechnet die Distanz, die der Returner nach einem Kick oder Punt zurück läuft.
     * @param string $gameplay - Kickreturn oder Puntreturn
     * @param Player $player - Returner
     * @return int - Distanz
     */
    private function getReturnDistance(string $gameplay, Player $player): int
    {
        $gameController = new GameController($this->pdo, $this->log);
        $ovr = $player->getOVR();

        if ($ovr <= 20) {
            // Return Stufe 1
            $difference = '<20';
        } elseif ($ovr <= 40) {
            // Return Stufe 2
            $difference = '<40';
        } elseif ($ovr <= 60) {
            // Return Stufe 3
            $difference = '<60';
        } elseif ($ovr <= 90) {
            // Return Stufe 4
            $difference = '<90';
        } else {
            // Return Stufe 5
            $difference = '<100';
        }

        return $gameController->getDistance($gameplay, null, $difference);
    }

    private function calcPuntreturnDistance(int $gameId, array $specialTexts, Team $puntTeam, Player $punter, Player $returner, ?string $inField, int $puntDistance, int $newYardsToTD): array
    {
        $gameController = new GameController($this->pdo, $this->log);
        $statisticsController = new StatisticsController($this->pdo, $this->log);

        $returnDistance = $this->getReturnDistance('Puntreturn', $returner);

        $isTD = false;
        if ($newYardsToTD - $returnDistance <= 0) {
            $isTD = true;
        }

        if (isset($inField)) {
            $distance = $puntDistance;
        } else {
            $distance = (100 - $newYardsToTD);
        }
        $this->log->debug('Punt-Result-Yards: ' . $distance);
        $this->log->debug('inField?: ' . $inField);
        $puntText = $gameController->getText('Punt', $specialTexts, $punter, null, null, $inField, $distance, null, false);
        $returnText = $gameController->getText('Puntreturn', $specialTexts, $returner, null, null, null, $returnDistance, null, $isTD);

        $statisticsController->savePunt($gameId, $puntTeam, $punter, $puntDistance);
        $statisticsController->savePuntreturn($gameId, $returner, $newYardsToTD, $returnDistance);

        $newYardsToTD -= $returnDistance;
        return array($puntDistance => $puntText, $newYardsToTD => $returnText);
    }

    public function isFourthDown(string $fourthDown, int $yardsToTD, string $score, bool $isHome): bool
    {
        switch ($fourthDown) {
            case 'Immer':
                return true;
            case 'GegnerischeHaelfte':
                if ($yardsToTD < 50) {
                    return true;
                }
                return false;
            case 'GegnerischeHaelfteInRueckstand':
                $score = explode(';', $score);
                if ($isHome) {
                    $isBehind = $score[0] - $score[1] < 0;
                } else {
                    $isBehind = $score[1] - $score[0] < 0;
                }
                if ($yardsToTD < 50 && $isBehind) {
                    return true;
                }
                return false;
            default:
                return false;
        }
    }

    public function isTwoPtCon(string $twoPtCon, int $offTeamPoints, int $defTeamPoints): bool
    {
        switch ($twoPtCon) {
            case '1':
                return true;
            case '2':
                $difference = $defTeamPoints - $offTeamPoints;
                if ($difference == 2) {
                    return true;
                }
                return false;
            case '5':
                $difference = $defTeamPoints - $offTeamPoints;
                if ($difference == 5) {
                    return true;
                }
                return false;
            case '8':
                if ($defTeamPoints - $offTeamPoints >= 8) {
                    return true;
                }
                return false;
            default:
                return false;
        }
    }
}