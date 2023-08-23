<?php


namespace touchdownstars\live;


use PDO;
use touchdownstars\gametext\Gametext;
use touchdownstars\gametext\GametextController;
use Monolog\Logger;
use touchdownstars\penalty\Penalty;
use touchdownstars\player\Player;
use touchdownstars\statistics\StatisticsController;
use touchdownstars\team\Team;

class PassCalculation
{

    private Logger $log;
    private PDO $pdo;
    private array $offPositions = array(
        'Screen Pass' => array('RB', 'FB', 'TE', 'WR'),
        'Short Pass' => array('RB', 'FB', 'TE', 'WR'),
        'Medium Pass' => array('TE', 'WR'),
        'Long Pass' => array('WR')
    );
    private array $defenseBoost = array(
        'Screen Pass' => array(
            'Coverage' => 250,
            'Blitz' => 750,
            'Coverage Tief' => -750,
            'Auf Reaktion' => -200
        ),
        'Short Pass' => array(
            'Coverage' => 500,
            'Blitz' => 0,
            'Coverage Tief' => -250,
            'Auf Reaktion' => -200
        ),
        'Medium Pass' => array(
            'Coverage' => 250,
            'Blitz' => 500,
            'Coverage Tief' => -250,
            'Auf Reaktion' => -200
        ),
        'Long Pass' => array(
            'Coverage' => -250,
            'Blitz' => 500,
            'Coverage Tief' => 500,
            'Auf Reaktion' => -200
        )
    );
    private array $lineup = array(
        'Pass' => array('LOLB', 'ROLB', 'MLB1', 'MLB2', 'CB', 'SS', 'FS', 'LT', 'LG', 'C', 'RG', 'RT', 'WR', 'FB', 'TE', 'RB')
    );

    public function __construct(PDO $pdo, Logger $log = null)
    {
        $this->pdo = $pdo;
        if (isset($log)) {
            $this->log = $log;
        }
    }

    private function getReceiver(array $offEleven, array $defEleven, string $gameplay): Player
    {
        $gameController = new GameController($this->pdo, $this->log);
        $receivers = array_values(array_filter($offEleven, function (Player $player) use ($gameplay) {
            return in_array($player->getType()->getPosition()->getPosition(), $this->offPositions[$gameplay]);
        }));
        if ($gameplay == 'Long Pass') {
            // fur Long Pass muessen die Receiver nach OVR sortiert werden -> WR1 vs CB1, WR2 vs CB2, WR3 vs CB3
            uasort($receivers, function (Player $r1, Player $r2) {
                return $r1->getOVR() <=> $r2->getOVR();
            });
        }

        $positionalSkills = $gameController->fetchPositionalSkills($gameplay, 2);

        $skillSum = 0;
        $receiverSkills = array();
        foreach ($receivers as $receiver) {
            $catcherSkillSum = 0;
            if ($gameplay == 'Long Pass') {
                $skillSum += $receiver->getHeight();
                $catcherSkillSum += $receiver->getHeight();
            }
            $skills = $receiver->getSkills();
            $position = $receiver->getType()->getPosition()->getPosition();
            $skillsToSum = explode(';', $positionalSkills[$position]);
            foreach ($skillsToSum as $skillName) {
                $skillSum += floor($skills[$skillName]);
                $catcherSkillSum += floor($skills[$skillName]);
            }
            $receiverSkills[$receiver->getId()] = $catcherSkillSum;
        }

        $space = array();
        if ($gameplay == 'Long Pass') {
            $weightings = array(0.5, 0.3, 0.2);
            $cornerbacks = $this->getSortedPlayer($defEleven, 'CB');
            $thirdDefPosition = $gameController->probability(50) ? 'SS' : 'FS';

            $cornerbacks[] = array_values(array_filter($defEleven, function (Player $player) use ($thirdDefPosition) {
                return $player->getLineupPosition() == $thirdDefPosition;
            }))[0];

            $cornerbackSkills = array();
            foreach ($cornerbacks as $cornerback) {
                $cbSkillSum = 0;
                $skills = $cornerback->getSkills();
                $skillsToSum = explode(';', $positionalSkills[$cornerback->getType()->getPosition()->getPosition()]);
                foreach ($skillsToSum as $skillName) {
                    $cbSkillSum += floor($skills[$skillName]);
                }
                $cornerbackSkills[$cornerback->getId()] = $cbSkillSum;
            }

            // Berechne die Differenz zwischen den WR und CB
            $differences = array();
            for ($i = 0; $i < count($receivers); $i++) {
                $receiverId = $receivers[array_keys($receivers)[$i]]->getId();
                $cbId = $cornerbacks[array_keys($cornerbacks)[$i]]->getId();
                $difference = $receiverSkills[$receiverId] - $cornerbackSkills[$cbId];
                $differences[$receiverId] = $difference;
            }
            arsort($differences);
            $ids = array_keys($differences);
            // sortierte IDs der WR anhand der Differenz mit der Gewichtung 50%, 30%, 20%
            $space = array_combine($ids, $weightings);
        } else {
            // Bei den anderen Passarten werden einfach die Skills der Receiver genommen
            // Die Gewichtung ist der prozentuale Anteil der Receiver-Skillsumme von der Gesamt-Skillsumme der O-Line
            foreach ($receivers as $receiver) {
                $catcherSkillSum = $receiverSkills[$receiver->getId()];
                $space[$receiver->getId()] = $catcherSkillSum * 100 / $skillSum;
            }
        }

        // Shuffle für Unterschiede bei gleichen Summen/Differenzen
        shuffle_assoc($space);
        $receiverId = $gameController->dw_rand($space, array_key_first($space));

        $this->log->debug('Receiver-Space: ' . print_r($space, true));
        $this->log->debug('Receiver-ID: ' . $receiverId);

        return array_values(array_filter($offEleven, function (Player $player) use ($receiverId) {
            return $player->getId() == $receiverId;
        }))[0];
    }

    private function getSortedPlayer(array $players, string $posToSort): array
    {
        $filteredPlayers = array_values(array_filter($players, function (Player $player) use ($posToSort) {
            return $player->getType()->getPosition()->getPosition() == $posToSort;
        }));
        // fur Long Pass muessen auch die CBs nach OVR sortiert werden -> WR1 vs CB1, WR2 vs CB2, WR3 vs CB3
        uasort($filteredPlayers, function (Player $player1, Player $player2) {
            return $player1->getOVR() <=> $player2->getOVR();
        });
        return $filteredPlayers;
    }

    public function pass(int $gameId, Team $offTeam, Team $defTeam, array $offEleven, array $defEleven, string $offGameplay, string $defGameplay, int $yardsToTD, ?Penalty $penalty = null): array
    {
        $gameController = new GameController($this->pdo, $this->log);
        $gametextController = new GametextController($this->pdo);
        $defGameplay = explode(';', $defGameplay)[1];
        $offGameplay = explode(';', $offGameplay)[1];
        if (isset($_SESSION['passTexts'])) {
            $passTexts = $_SESSION['passTexts'];
        } else {
            $passTexts = $gametextController->fetchAllGameplayTexts('Pass');
            $_SESSION['passTexts'] = $passTexts;
        }

        // 1. Prüfung auf Pass-Completion
        $offSkillSum = $gameController->getSkillSum($offEleven, $offGameplay, 'Pass');
        if ($defGameplay == 'Blitz') {
            // Blitz erkennen der Offense aufaddieren
            foreach ($offEleven as $offender) {
                $skills = $offender->getSkills();
                if (array_key_exists('realizeBlitz', $skills)) {
                    $offSkillSum += floor($skills['realizeBlitz']);
                } elseif ($offender->getLineupPosition() == 'QB') {
                    $offSkillSum += floor($skills['agility']);
                }
            }
        }
        $defSkillSum = $gameController->getSkillSum($defEleven, $offGameplay, 'Pass');
        $defSkillSum += $this->defenseBoost[$offGameplay][$defGameplay];

        $difference = $offSkillSum - $defSkillSum;

        // calculation number 1 in getPassResult
        $passResult = $this->getPassResult($offGameplay, $defGameplay, $difference);

        $quarterback = array_values(array_filter($offEleven, function (Player $player) {
            return $player->getLineupPosition() == 'QB';
        }))[0];

        // 2. Handle die verschiedenen Passsituationen
        switch ($passResult) {
            case 'Sack':
                return $this->handleSack($gameId, $offTeam, $defTeam, $offEleven, $defEleven, $quarterback, $passTexts, $yardsToTD, $defTeam->getLineupDef(), isset($penalty));
            case 'Deflection':
                return $this->handleDeflection($gameId, $offEleven, $defEleven, $quarterback, $passTexts, $offGameplay, $defTeam->getLineupDef(), isset($penalty));
            case 'Interception':
                $throwDistance = $this->getThrowDistance($offGameplay);
                return $this->handleInterception($gameId, $defTeam, $offEleven, $defEleven, $quarterback, $passTexts, $offGameplay, $throwDistance, $yardsToTD, $defTeam->getLineupDef(), isset($penalty));
            case 'Pass':
                $receiver = $this->getReceiver($offEleven, $defEleven, $offGameplay);
                $throwDistance = $this->getThrowDistance($offGameplay);
                if (null != $penalty && $penalty->getPenalty() == 'Pass Interference') {
                    // Spot des Fouls anhand der $throwing-Distance
                    return array($throwDistance => $penalty->getPenaltyText());
                } else {
                    return $this->handlePass($gameId, $offTeam, $offEleven, $defEleven, $quarterback, $receiver, $offGameplay, $defGameplay, $throwDistance, $yardsToTD, $passTexts);
                }
            default:
                // Incomplete - Statistik trotzdem Passing Attempt
                $statisticsController = new StatisticsController($this->pdo, $this->log);
                $statisticsController->saveIncomplete($gameId, $quarterback);
                $incompleteText = array_values(array_filter($passTexts, function (Gametext $gametext) use ($offGameplay, $defGameplay) {
                    return $gametext->getSituation() == 'Incomplete' && $gametext->getTextName() == $offGameplay && $gametext->getTriggeringPosition() == $defGameplay;
                }))[0]->getText();
                $incompleteText = $gametextController->changeNamePosInText($incompleteText, $quarterback);
                return array(0 => $incompleteText);
        }
    }

    private function getPassResult(string $offGameplay, string $defGameplay, int $difference): string
    {
        $gameController = new GameController($this->pdo, $this->log);
        $differenceString = $gameController->getDifferenceString($difference, 1);
        return $gameController->getDistance($offGameplay, $defGameplay, $differenceString);
    }

    private function handlePass(int $gameId, Team $offTeam, array $offEleven, array $defEleven, Player $quarterback, Player $receiver, string $offGameplay, string $defGameplay, int $throwDistance, int $yardsToTD, array $passTexts): array
    {
        // Pass completed -> Finde Receiver und berechne Yards after Catch
        $gameController = new GameController($this->pdo, $this->log);

        // Fuehre 3. Berechnung aus
        if ($offGameplay == 'Long Pass') {
            $offSkillSum = $gameController->getPassSkillSum(array($receiver), $offGameplay);

            $cornerback = null;
            $wideReceivers = $this->getSortedPlayer($offEleven, 'WR');
            foreach ($wideReceivers as $key => $wideReceiver) {
                if ($wideReceiver->getId() == $receiver->getId()) {
                    $cornerback = $this->getSortedPlayer($defEleven, 'CB')[$key];
                    break;
                }
            }

            // DefPositions = Direkter Gegenspieler (CB), SS & FS
            $defPlayers = array_values(array_filter($defEleven, function (Player $player) {
                return in_array($player->getLineupPosition(), array('SS', 'FS'));
            }));
            if (null != $cornerback) {
                $defPlayers[] = $cornerback;
            }
        } else {
            $offPlayers = array_values(array_filter($offEleven, function (Player $player) use ($receiver) {
                $lineupPosition = str_contains($player->getLineupPosition(), 'RB') ? 'RB' : $player->getLineupPosition();
                return in_array($lineupPosition, $this->lineup['Pass']) && $player->getId() != $receiver->getId();
            }));
            $offSkillSum = $gameController->getPassSkillSum($offPlayers, $offGameplay, $receiver);

            $defPlayers = array_values(array_filter($defEleven, function (Player $player) {
                return in_array($player->getLineupPosition(), $this->lineup['Pass']);
            }));
        }
        $defSkillSum = $gameController->getPassSkillSum($defPlayers, $offGameplay);

        $difference = $offSkillSum - $defSkillSum;
        $differenceString = $gameController->getDifferenceString($difference, $offGameplay == 'Long Pass' ? 3 : 1, 'Pass');

        $ranDistance = $gameController->getDistance($offGameplay, null, $differenceString, 3);

        if ($ranDistance == 'Fumble') {
            if (isset($_SESSION['runTexts'])) {
                $runTexts = $_SESSION['runTexts'];
            } else {
                $gametextController = new GametextController($this->pdo, $this->log);
                $runTexts = $gametextController->fetchAllGameplayTexts('Run');
            }
            $tacklingPlayer = $this->getTacklingPlayer($defPlayers, true);
            return $gameController->fumble($gameId, $defEleven, $runTexts, $offTeam, $receiver, $tacklingPlayer, $yardsToTD);
        }

        $ranDistance += $throwDistance;
        $tacklingPlayer = $this->getTacklingPlayer($defPlayers);
        $isTD = ($yardsToTD - $ranDistance) <= 0;

        // Statistik für Pass
        $statisticsController = new StatisticsController($this->pdo, $this->log);
        $statisticsController->savePass($gameId, $offTeam, $quarterback, $receiver, $tacklingPlayer, $ranDistance, ($ranDistance - $throwDistance), $isTD);

        // Mit der Distanz wird der Text geholt
        $this->log->debug('handlePass beforeGetText');
        $this->log->debug('defGameplay: ' . $defGameplay);
        $gametext = $gameController->getText($offGameplay, $passTexts, $receiver, $tacklingPlayer, $quarterback, $defGameplay, $ranDistance, null, $isTD);

        return array($ranDistance => $gametext);
    }

    private function handleSack(int $gameId, Team $offTeam, Team $defTeam, array $offEleven, array $defEleven, Player $quarterback, array $passTexts, int $yardsToTD, string $lineupDef, bool $isPenalty): array
    {
        $gameController = new GameController($this->pdo, $this->log);

        // Berechne Sacking-Player
        $sackingPlayer = $this->getSackingPlayer($offEleven, $defEleven, $lineupDef);

        // Hole Distanz des Sacks
        $distance = $gameController->getDistance('Sack', null, 0);

        // Zu Nahe an der eigenen Endzone kann es zu einem Safety kommen
        $isSafety = ((100 - $yardsToTD) - $distance) <= 0;

        // TODO: Safety-Berechnung: 2 Punkte für $defTeam + Teamwechsel und Anstoß

        $situation = 'Sack';
        if ($isSafety) {
            $situation = 'Safety';
        }

        $this->log->debug('Sack');
        $this->log->debug('Situation: ' . $situation);
        $this->log->debug('Distanz: ' . $distance);

        // Sacking-Player in Statistik + Tackle for loss
        if (!$isPenalty) {
            $statisticsController = new StatisticsController($this->pdo, $this->log);
            $statisticsController->saveSack($gameId, $offEleven, $defTeam, $quarterback, $sackingPlayer, $distance, $isSafety, $lineupDef);
        }

        $sackText = $gameController->getText('', $passTexts, $quarterback, $sackingPlayer, null, null, $distance, $situation, false, $offTeam);

        return array(-$distance => $sackText, 'isSafety' => $isSafety);
    }

    private function handleInterception(int $gameId, Team $defTeam, array $offEleven, array $defEleven, Player $quarterback, array $passTexts, string $offGameplay, int $throwDistance, int $yardsToTD, string $lineupDef, bool $isPenalty): array
    {
        $gameController = new GameController($this->pdo, $this->log);

        $interceptingPlayer = $this->getInterceptingDeflectingPlayer($offEleven, $defEleven, $quarterback, $offGameplay, $lineupDef, 'Interception');


        $distance = $gameController->getDistance('Interception', null, 0);

        // Bei der Touchdown-Pruefung muss die ThrowDistance von der Interception-Distanz abgezogen werden.
        $isTD = ((100 - $yardsToTD) - ($distance - $throwDistance)) <= 0;

        $this->log->debug('Interception');
        $this->log->debug('Distanz: ' . $distance);
        $this->log->debug('isTD: ' . $isTD);

        // Statistik Interception
        if (!$isPenalty) {
            $statisticsController = new StatisticsController($this->pdo, $this->log);
            $statisticsController->saveInterception($gameId, $defTeam, $quarterback, $interceptingPlayer, $distance, $isTD);
        }

        // Beim Text muss die zurückgelegte Distanz des Intercepting Players ausgegeben werden.
        $interceptionText = $gameController->getText($offGameplay, $passTexts, $quarterback, $interceptingPlayer, null, null, $distance, 'Interception', $isTD);

        // Für den nächsten Spielzug muss die Wurfdistanz von der Distanz nach der Interception abgezogen werden.
        $distance -= $throwDistance;

        return array($distance => $interceptionText, 'isInterception' => true);
    }

    private function handleDeflection(int $gameId, array $offEleven, array $defEleven, Player $quarterback, array $passTexts, string $offGameplay, string $lineupDef, bool $isPenalty): array
    {
        $gameController = new GameController($this->pdo, $this->log);
        $deflectingPlayer = $this->getInterceptingDeflectingPlayer($offEleven, $defEleven, $quarterback, $offGameplay, $lineupDef, 'Deflection');

        // Statistik Deflection
        if (!$isPenalty) {
            $statisticsController = new StatisticsController($this->pdo, $this->log);
            $statisticsController->saveDeflection($gameId, $deflectingPlayer);
        }

        $deflectionText = $gameController->getText($offGameplay, $passTexts, $quarterback, $deflectingPlayer, null, null, 0, 'Deflection', false);


        return array(0 => $deflectionText, 'isDeflected' => true);
    }

    private function getThrowDistance(string $gameplay): int
    {
        $gameController = new GameController($this->pdo, $this->log);
        $distance = $gameController->getDistance($gameplay, null, 0, 2);
        $this->log->debug('Throwing-Distance: ' . $distance);
        return $distance;
    }

    private function getTacklingPlayer(array $defPlayers, bool $isFumble = false): Player
    {
        $gameController = new GameController($this->pdo, $this->log);
        $skillName = $isFumble ? 'hardHit' : 'safeTackle';

        $skillSum = 0;
        foreach ($defPlayers as $defender) {
            $skillSum += floor($defender->getSkills()[$skillName]);
        }

        $space = array();
        foreach ($defPlayers as $defender) {
            $space[$defender->getId()] = floor($defender->getSkills()[$skillName]) * 100 / $skillSum;
        }
        shuffle_assoc($space);
        $tacklingId = $gameController->dw_rand($space, array_key_first($space));

        $tacklingPlayer = new Player();
        foreach ($defPlayers as $defender) {
            if ($defender->getId() == $tacklingId) {
                $tacklingPlayer = $defender;
                break;
            }
        }
        return $tacklingPlayer;
    }

    private function getSackingPlayer(array $offEleven, array $defEleven, string $lineupDef): Player
    {
        $gameController = new GameController($this->pdo, $this->log);
        $gameplayOffVsDef = array(
            'NT' => array(
                'DT' => 'RG',
                'NT' => 'LG',
                'MLB1' => 'C', // Blitz erkennen
                'RE' => 'LT',
                'LE' => 'RT',
                'ROLB' => 'LT', // Blitz erkennen
                'LOLB' => 'RT' // Blitz erkennen
            ),
            'MLB' => array(
                'DT' => 'C',
                'MLB1' => 'RG', // Blitz erkennen
                'MLB2' => 'LG', // Blitz erkennen
                'RE' => 'LT',
                'LE' => 'RT',
                'ROLB' => 'LT', // Blitz erkennen
                'LOLB' => 'RT' // Blitz erkennen
            )
        );
        $blitzPositions = array('MLB1', 'MLB2', 'ROLB', 'LOLB');

        $positionalSkills = $gameController->fetchPositionalSkills('Sack');

        $differences = array();
        foreach ($defEleven as $defensePlayer) {
            $offensePlayer = array_values(array_filter($offEleven, function (Player $player) use ($defensePlayer, $gameplayOffVsDef, $lineupDef) {
                $lineupPosition = $player->getLineupPosition();
                return $lineupPosition == $gameplayOffVsDef[$lineupDef][$defensePlayer->getLineupPosition()];
            }))[0];

            if (isset($offensePlayer)) {
                $offSkillSum = $gameController->getPlayersSkillSum($offensePlayer, $positionalSkills);
                $defSkillSum = $gameController->getPlayersSkillSum($defensePlayer, $positionalSkills);

                // Um eine höhere Wahrscheinlichkeit für die Defense-Ends zu haben, wird BLitz bei den Linebackern addiert.
                if (in_array($defensePlayer->getLineupPosition(), $blitzPositions)) {
                    $offSkillSum += floor($offensePlayer->getSkills()['realizeBlitz']);
                }

                $differences[$defensePlayer->getLineupPosition()] = $offSkillSum - $defSkillSum;
            }
        }

        shuffle_assoc($differences);
        asort($differences);

        $positions = array_keys($differences);

        $differences[$positions[0]] = 0.4;
        $differences[$positions[1]] = 0.25;
        $differences[$positions[2]] = 0.13;
        $differences[$positions[3]] = 0.1;
        $differences[$positions[4]] = 0.06;
        $differences[$positions[5]] = 0.04;
        $differences[$positions[6]] = 0.02;

        $sackingPosition = $gameController->dw_rand($differences, array_key_first($differences));

        return array_values(array_filter($defEleven, function (Player $player) use ($sackingPosition) {
            return $player->getLineupPosition() == $sackingPosition;
        }))[0];
    }

    private function getInterceptingDeflectingPlayer(array $offEleven, array $defEleven, Player $quarterback, string $offGameplay, string $lineupDef, string $situation)
    {
        $gameController = new GameController($this->pdo, $this->log);
        // Nur bei Screen Pass oder Short Pass
        $gameplayOffVsDef = array(
            'NT' => array(
                'DT' => 'RG',
                'NT' => 'LG',
                'MLB1' => 'C',
                'RE' => 'LT',
                'LE' => 'RT',
                'ROLB' => 'LG',
                'LOLB' => 'RG'
            ),
            'MLB' => array(
                'DT' => 'C',
                'MLB1' => 'RG',
                'MLB2' => 'LG',
                'RE' => 'LT',
                'LE' => 'RT',
                'ROLB' => 'LT',
                'LOLB' => 'RT'
            )
        );
        $defPositions = array(
            'Screen Pass' => array('RE', 'LE', 'DT', 'NT', 'MLB1', 'MLB2', 'ROLB', 'LOLB'),
            'Short Pass' => array('RE', 'LE', 'DT', 'NT', 'MLB1', 'MLB2', 'ROLB', 'LOLB'),
            'Medium Pass' => array('MLB1', 'MLB2', 'ROLB', 'LOLB', 'CB', 'SS', 'FS'),
            'Long Pass' => array('CB', 'SS', 'FS')
        );

        if ($offGameplay == 'Medium Pass') {
            $calcNr = 2;
        } elseif ($offGameplay == 'Long Pass') {
            $calcNr = 3;
        } else {
            $calcNr = 1;
        }
        $positionalSkills = $gameController->fetchPositionalSkills($situation, $calcNr);

        $defensePlayers = array_values(array_filter($defEleven, function (Player $player) use ($defPositions, $offGameplay) {
            return in_array($player->getLineupPosition(), $defPositions[$offGameplay]);
        }));

        $differences = array();
        foreach ($defensePlayers as $defensePlayer) {
            if ($offGameplay == 'Medium Pass' || $offGameplay == 'Long Pass') {
                $offensePlayer = $quarterback;
            } else {
                $offensePlayer = array_values(array_filter($offEleven, function (Player $player) use ($defensePlayer, $gameplayOffVsDef, $lineupDef) {
                    $lineupPosition = $player->getLineupPosition();
                    return $lineupPosition == $gameplayOffVsDef[$lineupDef][$defensePlayer->getLineupPosition()];
                }))[0];
            }

            if (isset($offensePlayer)) {
                $offSkillSum = $gameController->getPlayersSkillSum($offensePlayer, $positionalSkills);
                $defSkillSum = $gameController->getPlayersSkillSum($defensePlayer, $positionalSkills);

                $differences[$defensePlayer->getId()] = $offSkillSum - $defSkillSum;
            }
        }

        shuffle_assoc($differences);
        asort($differences);

        $ids = array_keys($differences);

        if ($offGameplay == 'Long Pass') {
            $differences[$ids[0]] = 0.5;
            $differences[$ids[1]] = 0.3;
            $differences[$ids[2]] = 0.13;
            $differences[$ids[3]] = 0.07;
        } elseif ($offGameplay == 'Medium Pass') {
            $differences[$ids[0]] = 0.4;
            $differences[$ids[1]] = 0.25;
            $differences[$ids[2]] = 0.15;
            $differences[$ids[3]] = 0.1;
            $differences[$ids[4]] = 0.07;
            $differences[$ids[5]] = 0.02;
            $differences[$ids[6]] = 0.01;
        } else {
            // Short Pass & Medium Pass
            $differences[$ids[0]] = 0.4;
            $differences[$ids[1]] = 0.25;
            $differences[$ids[2]] = 0.15;
            $differences[$ids[3]] = 0.12;
            $differences[$ids[4]] = 0.05;
            $differences[$ids[5]] = 0.02;
            $differences[$ids[6]] = 0.01;
        }

        $interceptingId = $gameController->dw_rand($differences, array_key_first($differences));

        $this->log->debug($situation . '-ID: ' . $interceptingId);

        return array_values(array_filter($defEleven, function (Player $player) use ($interceptingId) {
            return $player->getId() == $interceptingId;
        }))[0];
    }
}