<?php


namespace touchdownstars\live;


use PDO;
use touchdownstars\coaching\CoachingController;
use touchdownstars\gametext\GametextController;
use Monolog\Logger;
use touchdownstars\player\Player;
use touchdownstars\statistics\StatisticsController;
use touchdownstars\team\Team;

class RunCalculation
{

    private Logger $log;
    private PDO $pdo;
    private array $defenseBoost = array(
        'Run Inside' => array(
            'Box' => 250,
            'Outside Contain' => -250,
            'Auf Reaktion' => -200,
            'Inside Blitz' => 500,
            'Outside Blitz' => -500
        ),
        'Outside Run rechts' => array(
            'Box' => -250,
            'Outside Contain' => 250,
            'Auf Reaktion' => -200,
            'Inside Blitz' => -500,
            'Outside Blitz' => 500
        ),
        'Outside Run links' => array(
            'Box' => -250,
            'Outside Contain' => 250,
            'Auf Reaktion' => -200,
            'Inside Blitz' => -500,
            'Outside Blitz' => 500
        )
    );
    private array $lineupPosition = array(
        1 => array(
            'Inside Run' => array('DT', 'NT', 'MLB1', 'MLB2', 'RG', 'LG', 'C', 'FB', 'TE'),
            'Outside Run rechts' => array('DT', 'NT', 'MLB1', 'LE', 'RG', 'RT', 'C', 'FB', 'TE'),
            'Outside Run links' => array('DT', 'NT', 'MLB1', 'RE', 'LG', 'LT', 'C', 'FB', 'TE')
        ),
        2 => array(
            'Inside Run' => array('ROLB', 'LOLB', 'RG', 'LG', 'C'),
            'Outside Run rechts' => array('MLB1', 'LOLB', 'RG', 'RT', 'C'),
            'Outside Run links' => array('MLB1', 'ROLB', 'LG', 'LT', 'C')
        ),
        3 => array(
            'Inside Run' => array('SS', 'FS', 'RG', 'LG', 'C'),
            'Outside Run rechts' => array('SS', 'FS', 'RG', 'RT', 'C'),
            'Outside Run links' => array('SS', 'FS', 'LG', 'LT', 'C')
        )
    );

    public function __construct(PDO $pdo, Logger $log = null)
    {
        $this->pdo = $pdo;
        if (isset($log)) {
            $this->log = $log;
        }
    }

    /**
     * Berechnet die Position für den Laufspielzug.
     * @param Team $team
     * @return string - Position des ausgewählten Runners ('RB', 'QB' oder 'FB')
     */
    public function getRunner(Team $team): string
    {
        $gameController = new GameController($this->pdo);
        $runner = 'RB';
        $coachingController = new CoachingController($this->pdo);
        $generalCoaching = $coachingController->getCoachingFromTeam($team, $team->getGameplanOff(), 'offense', '2nd', 'General');
        $isQB = explode(';', $generalCoaching->getGameplay2())[1] == '1';
        $isFB = $team->getLineupOff() == 'FB';

        $weightings = array();
        if ($isQB && $isFB) {
            $weightings['RB'] = 0.7;
            $weightings['QB'] = 0.15;
        } elseif ($isQB && !$isFB) {
            $weightings['RB'] = 0.85;
            $weightings['QB'] = 0.15;
        } elseif (!$isQB && $isFB) {
            $weightings['RB'] = 0.85;
            $weightings['FB'] = 0.15;
        }

        if (count($weightings) > 0) {
            return $gameController->dw_rand($weightings, array_key_first($weightings));
        }
        return $runner;
    }

    /**
     * Führt die Berechnung für den Run aus.
     * @param int $gameId - ID des Spiels (Freundschaftsspiel oder Ligaspiel)
     * @param Team $offTeam - Team, welches aktuell die Offense auf dem Feld hat und fumblen kann
     * @param Team $defTeam - Team, welches aktuell die Defense auf dem Feld hat
     * @param array $offEleven - 11 Offense-Player
     * @param array $defEleven - 11 Defense-Player
     * @param string $runner - laufende Spielposition (RB, FB oder QB)
     * @param string $offGameplay - getGameplay() aus Coaching z.B. Run;Inside Run
     * @param string $defGameplay - getGameplay() aus Coaching z.B. Run;Box
     * @param int $yardsToTD - Yards bis zum Erreichen der TD-Linie
     * @param bool $isPenalty - Boolean für Penalty aus vorheriger Penalty-Berechnung
     * @return array - RanDistance (Key) und Gametext (Value) für die Distanz des Spielzuges
     */
    public function run(int $gameId, Team $offTeam, Team $defTeam, array $offEleven, array $defEleven, string $runner, string $offGameplay, string $defGameplay, int $yardsToTD, bool $isPenalty = false): array
    {
        $gameController = new GameController($this->pdo, $this->log);
        $gametextController = new GametextController($this->pdo);
        $statisticsController = new StatisticsController($this->pdo, $this->log);

        $runTexts = $_SESSION['runTexts'] ?? $gametextController->fetchAllGameplayTexts('Run');

        $offGameplay = explode(';', $offGameplay)[1];
        $defGameplay = explode(';', $defGameplay)[1];
        $offensePlayers = array_values(array_filter($offEleven, function (Player $player) use ($runner, $offGameplay) {
            $lineupPos = $this->lineupPosition[1];
            if ($runner == 'QB') {
                $lineupPos[$offGameplay][] = 'QB';
            } else {
                $lineupPos[$offGameplay][] = 'RB1';
                $lineupPos[$offGameplay][] = 'RB2';
            }
            return in_array($player->getLineupPosition(), $lineupPos[$offGameplay]);
        }));

        $defensePlayers = $this->getDefensePlayers($defEleven, 1, $offGameplay, $defTeam->getLineupDef());

        $gameplayName = $runner . ' ' . $offGameplay;
        $offenseSkillSum = $gameController->getSkillSum($offensePlayers, $gameplayName, 'Run');
        $defenseSkillSum = $gameController->getSkillSum($defensePlayers, $gameplayName, 'Run');
        $defenseSkillSum += $this->defenseBoost[$offGameplay][$defGameplay];

        $calcNr = 1;
        $difference = $offenseSkillSum - $defenseSkillSum;
        $ranDistance = $this->getGameplayDistance($offGameplay, $calcNr, $difference);

        $isTD = ($yardsToTD - $ranDistance) <= 0;

        $situation = null;
        if (((100 - $yardsToTD) + $ranDistance) <= 0) {
            $situation = 'Safety';
        }

        if (!$isTD && $ranDistance == 3) {
            // Laufe 2. Berechnung
            $calcNr = 2;
            $distance = $this->runMore($offEleven, $defEleven, $gameplayName, $calcNr, $runner, $offGameplay);

            if (null !== $distance) {
                $ranDistance += $distance;
                $isTD = ($yardsToTD - $ranDistance) <= 0;
                if (!$isTD && $distance == 3) {
                    $calcNr = 3;
                    $distance = $this->runMore($offEleven, $defEleven, $gameplayName, $calcNr, $runner, $offGameplay);

                    if (null !== $distance) {
                        $ranDistance += $distance;
                    }
                }
            }
            if (null === $distance) {
                $ranDistance = null;
            }
        }

        $triggeringPlayer = array_values(array_filter($offensePlayers, function (Player $player) use ($runner) {
            return $player->getType()->getPosition()->getPosition() == $runner;
        }))[0];

        $defensePlayers = $this->getDefensePlayers($defEleven, $calcNr, $offGameplay, $defTeam->getLineupDef());
        $tacklingPlayer = $this->getTacklingPlayer($offensePlayers, $defensePlayers, $defTeam->getLineupDef(), $offGameplay, $runner, $calcNr);

        if (!$isPenalty) {
            // Wenn kein Penalty stattfindet, können die Statistiken geschrieben werden.
            // Run-Statistik immer, Run-Defense-Statistik nur, wenn kein Fumble passiert, da es sonst im Fumble abgehandelt wird.
            $statisticsController->saveRun($gameId, $offTeam, $triggeringPlayer, $ranDistance ?? 0, $isTD, !isset($ranDistance));
            if (isset($ranDistance)) {
                $tflYds = min($ranDistance, 0);
                $statisticsController->saveRunDef($gameId, $tacklingPlayer, $ranDistance >= 0, $ranDistance < 0, $tflYds);
            } else {
                $statisticsController->saveRunDef($gameId, $tacklingPlayer, false, false, 0, true);
            }
        }
        $this->log->debug('Run-Situation: ' . $situation);
        $this->log->debug('Ran-Distance: ' . $ranDistance);

        if (null === $ranDistance) {
            return $gameController->fumble($gameId, $defensePlayers, $runTexts, $offTeam, $triggeringPlayer, $tacklingPlayer, $yardsToTD);
        } else {
            // Spielzug vorbei mit Distanz -1 bis 2
            $isTD = ($yardsToTD - $ranDistance) <= 0;
            $gametext = $gameController->getText($offGameplay, $runTexts, $triggeringPlayer, $tacklingPlayer, null, null, $ranDistance, $situation, $isTD);
        }

        return array($ranDistance => $gametext);
    }

    private function runMore(array $offEleven, array $defEleven, string $gameplayName, int $calcNr, string $runner, string $offGameplay): ?int
    {
        $offensePlayers = array_values(array_filter($offEleven, function (Player $player) use ($calcNr, $runner, $offGameplay) {
            $lineupPos = $this->lineupPosition[$calcNr];
            if ($runner == 'RB') {
                $lineupPos[$offGameplay][] = 'RB1';
                $lineupPos[$offGameplay][] = 'RB2';
            } else {
                $lineupPos[$offGameplay][] = $runner;
            }
            return in_array($player->getLineupPosition(), $lineupPos[$offGameplay]);
        }));

        $defensePlayers = array_values(array_filter($defEleven, function (Player $player) use ($calcNr, $offGameplay) {
            return in_array($player->getLineupPosition(), $this->lineupPosition[$calcNr][$offGameplay]);
        }));

        $gameController = new GameController($this->pdo, $this->log);
        $offenseSkillSum = $gameController->getSkillSum($offensePlayers, $gameplayName, 'Run', $calcNr);
        $defenseSkillSum = $gameController->getSkillSum($defensePlayers, $gameplayName, 'Run', $calcNr);
        $difference = $offenseSkillSum - $defenseSkillSum;

        return $this->getGameplayDistance($offGameplay, $calcNr, $difference);
    }

    /**
     * Gibt die Spiellauflänge in Yards an. Wenn es zu einem Fumble kommt wird null zurückgegeben.
     * @param string $gameplay - Spielzug (z.B. Inside Run)
     * @param int $calcNr -> Berechnungsnummer (1., 2. oder 3. Berechnung)
     * @param int $difference -> Differenz von Offense zu Defense
     * @return int|null - geschaffte Yards oder null bei Fumble
     */
    private function getGameplayDistance(string $gameplay, int $calcNr, int $difference): ?int
    {
        $gameController = new GameController($this->pdo);
        $differenceString = $gameController->getDifferenceString($difference, $calcNr);

        $calcGameplay = str_contains($gameplay, 'Outside Run') ? 'Outside Run' : $gameplay;

        $selectStmt = 'SELECT * FROM `t_gameplay_calculation` WHERE gameplay = :gameplay AND calculation = :calcNr AND difference = :difference;';
        $stmt = $this->pdo->prepare($selectStmt);
        $stmt->execute(['gameplay' => $calcGameplay, 'calcNr' => $calcNr, 'difference' => $differenceString]);
        $gameplayCalc = $stmt->fetch(PDO::FETCH_ASSOC);

        $distance = $gameController->getCalcResult($gameplayCalc);

        $this->log->debug('Distance: ' . $distance);

        if ('Fumble' == $distance) {
            return null;
        }
        return $distance;
    }

    private function getTacklingPlayer(array $offensePlayers, array $defensePlayers, string $lineupDef, string $offGameplay, string $runner, int $calcNr = 1): Player
    {
        $gameController = new GameController($this->pdo, $this->log);
        $gameplayOffVsDef = array(
            1 => array(
                'NT' => array(
                    'Inside Run' => array(
                        'DT' => 'RG',
                        'NT' => 'LG',
                        'MLB1' => 'C'
                    ),
                    'Outside Run rechts' => array(
                        'DT' => 'RG',
                        'LE' => 'RT',
                        'NT' => 'C'
                    ),
                    'Outside Run links' => array(
                        'DT' => 'LG',
                        'RE' => 'LT',
                        'NT' => 'C'
                    )
                ),
                'MLB' => array(
                    'Inside Run' => array(
                        'DT' => 'C',
                        'MLB1' => 'RG',
                        'MLB2' => 'LG'
                    ),
                    'Outside Run rechts' => array(
                        'DT' => 'C',
                        'MLB1' => 'RG',
                        'LE' => 'RT'
                    ),
                    'Outside Run links' => array(
                        'DT' => 'C',
                        'MLB1' => 'LG',
                        'RE' => 'LT'
                    )
                )
            ),
            2 => array(
                'Inside Run' => array(
                    'ROLB' => 'LG',
                    'LOLB' => 'RG'
                ),
                'Outside Run rechts' => array(
                    'LOLB' => 'RT',
                    'MLB1' => 'RG'
                ),
                'Outside Run links' => array(
                    'ROLB' => 'LT',
                    'MLB1' => 'LG'
                )
            ),
            3 => array(
                'RB' => array(
                    'SS' => 'RB',
                    'FS' => 'RB'
                ),
                'FB' => array(
                    'SS' => 'FB',
                    'FS' => 'FB'
                ),
                'QB' => array(
                    'SS' => 'QB',
                    'FS' => 'QB'
                )
            )
        );

        $differences = array();
        foreach ($defensePlayers as $defensePlayer) {
            $offensePlayer = match ($calcNr) {
                1 => array_values(array_filter($offensePlayers, function (Player $player) use ($calcNr, $defensePlayer, $lineupDef, $offGameplay, $gameplayOffVsDef) {
                    return $player->getLineupPosition() == $gameplayOffVsDef[$calcNr][$lineupDef][$offGameplay][$defensePlayer->getLineupPosition()];
                }))[0],
                2 => array_values(array_filter($offensePlayers, function (Player $player) use ($calcNr, $defensePlayer, $offGameplay, $gameplayOffVsDef) {
                    return $player->getLineupPosition() == $gameplayOffVsDef[$calcNr][$offGameplay][$defensePlayer->getLineupPosition()];
                }))[0],
                default => array_values(array_filter($offensePlayers, function (Player $player) use ($calcNr, $defensePlayer, $gameplayOffVsDef, $runner) {
                    $lineupPosition = $player->getLineupPosition();
                    if (str_contains($lineupPosition, 'RB')) {
                        $lineupPosition = 'RB';
                    }
                    return $lineupPosition == $gameplayOffVsDef[$calcNr][$runner][$defensePlayer->getLineupPosition()];
                }))[0]
            };

            $differences[$defensePlayer->getLineupPosition()] = $offensePlayer->getOVR() - $defensePlayer->getOVR();
        }

        shuffle_assoc($differences);
        asort($differences);

        $positions = array_keys($differences);

        if (count($defensePlayers) == 2) {
            $differences[$positions[0]] = 0.7;
            $differences[$positions[1]] = 0.3;
        } else {
            $differences[$positions[0]] = 0.5;
            $differences[$positions[1]] = 0.3;
            $differences[$positions[2]] = 0.2;
        }

        $tacklingPosition = $gameController->dw_rand($differences, array_key_first($differences));

        return array_values(array_filter($defensePlayers, function (Player $player) use ($tacklingPosition) {
            return $player->getLineupPosition() == $tacklingPosition;
        }))[0];
    }

    private function getDefensePlayers(array $defEleven, int $calcNr, string $offGameplay, string $lineupDef): array
    {
        return array_values(array_filter($defEleven, function (Player $player) use ($calcNr, $offGameplay, $lineupDef) {
            $usePlayer = in_array($player->getLineupPosition(), $this->lineupPosition[$calcNr][$offGameplay]);
            if ($calcNr == 1) {
                if ($lineupDef == 'MLB' && $player->getLineupPosition() == 'NT') {
                    $usePlayer = false;
                } elseif ($lineupDef == 'NT' && str_contains($player->getLineupPosition(), 'MLB')) {
                    $usePlayer = false;
                }
            }
            return $usePlayer;
        }));
    }
}