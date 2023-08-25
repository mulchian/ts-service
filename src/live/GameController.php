<?php

namespace touchdownstars\live;

use PDO;
use touchdownstars\coaching\Coaching;
use touchdownstars\coaching\CoachingController;
use touchdownstars\gametext\Gametext;
use touchdownstars\gametext\GametextController;
use touchdownstars\league\LeagueController;
use Monolog\Logger;
use touchdownstars\penalty\Penalty;
use touchdownstars\player\Player;
use touchdownstars\statistics\StatisticsController;
use touchdownstars\team\Team;
use touchdownstars\team\TeamController;

class GameController
{

    private Logger $log;
    private PDO $pdo;
    private array $runPositions = array('RB', 'FB', 'QB');

    public function __construct(PDO $pdo, Logger $log = null)
    {
        $this->pdo = $pdo;
        if (isset($log)) {
            $this->log = $log;
        }
    }

    public function probability($chance, $out_of = 100): bool
    {
        $random = mt_rand(1, $out_of);
        return $random <= $chance;
    }

    function dw_rand($space, $errorValue = false)
    {
        $psum = 0;
        $res = 1000000000;
        $rn = mt_rand(0, $res - 1);

        foreach ($space as $element => $probability) {
//            if (isset($this->log)) {
//                $this->log->debug('Element: ' . $element . ' Probability: ' . $probability);
//            }
            $psum += $probability * $res;
            if ($psum > $rn) return $element;
        }

        return $errorValue;
    }

    public function isSecondRB(): bool
    {
        return $this->probability(15);
    }

    public function getGame(Team $team): ?array
    {
        if (isset($_SESSION['season'], $_SESSION['gameday'])) {
            $leagueController = new LeagueController($this->pdo, $this->log);
            $game = $leagueController->fetchGame($team, $_SESSION['season'], $_SESSION['gameday']);
            $game['isLeagueGame'] = $game['idLeague'] != null;
            return $game;
        }

        return null;
    }

    public function getGameById(int $idGame): ?array
    {
        $leagueController = new LeagueController($this->pdo, $this->log);
        $game = $leagueController->fetchGameById($idGame);
        $game['isLeagueGame'] = $game['idLeague'] != null;
        return $game;
    }

    public function getStartingEleven(Team $team, string $teamPart, bool $secondRB = false): array
    {
        $teamController = new TeamController($this->pdo, $this->log);

        $teamPartPlayers = $teamController->getStartingPlayers($team, $teamPart);
        return array_values(array_filter($teamPartPlayers, function (Player $player) use ($team, $teamPart, $secondRB) {
            $isStartingPlayer = false;
            $lineup = $team->getLineupOff() == 'TE' ? 'FB' : 'TE';
            if ($teamPart == 'Defense') {
                $lineup = $team->getLineupDef() == 'NT' ? 'MLB2' : 'NT';
            }
            if (!strpos($player->getLineupPosition(), 'b') && $player->getLineupPosition() != $lineup) {
                $isStartingPlayer = true;
            }
            if ($player->getLineupPosition() == 'RB1' && $secondRB) {
                $isStartingPlayer = false;
            } else if ($player->getLineupPosition() == 'RB2' && !$secondRB) {
                $isStartingPlayer = false;
            }
            return $isStartingPlayer;
        }));
    }

    public function getVsTeam(Team $team): ?Team
    {
        $leagueController = new LeagueController($this->pdo, $this->log);
        $teamController = new TeamController($this->pdo, $this->log);
        if (isset($_SESSION['season'], $_SESSION['gameday'])) {
            $game = $leagueController->fetchGame($team, $_SESSION['season'], $_SESSION['gameday']);
            $vsTeamName = $game['home'] == $team->getName() ? $game['away'] : $game['home'];
            return $teamController->fetchTeam(null, $vsTeamName);
        }
        return null;
    }

    public function getGameplay(Team $team, string $teamPart, string $down, ?string $playrange, ?int $playDistance = null): string
    {
        if (isset($playDistance)) {
            if ($playDistance <= 3) {
                $playrange = 'Short';
            } elseif ($playDistance >= 7) {
                $playrange = 'Long';
            } else {
                $playrange = 'Middle';
            }
        }

        $gameplanNr = $teamPart == 'Offense' ? $team->getGameplanOff() : $team->getGameplanDef();
        $coaching = array_values(array_filter($team->getCoachings(), function (Coaching $coaching) use ($gameplanNr, $teamPart, $down, $playrange) {
            return $coaching->getGameplanNr() == $gameplanNr && $coaching->getTeamPart() == $teamPart && $coaching->getDown() == $down && $coaching->getPlayrange() == $playrange;
        }))[0];

        $useFirstGP = $this->probability($coaching->getRating());
        if ($useFirstGP) {
            $gameplay = $coaching->getGameplay1();
        } else {
            $gameplay = $coaching->getGameplay2();
        }

        return $gameplay;
    }

    public function getSkillSum(array $players, string $gameplayName, string $gameplay, int $calcNr = 1): int
    {
        $positionalSkills = $this->fetchPositionalSkills($gameplayName, $calcNr);
        $skillSum = 0;

        foreach ($players as $player) {
            if ($gameplay == 'Pass') {
                $skillSum += $player->getHeight() + floor($player->getWeight() / 2);
            } else {
                $skillSum += $player->getHeight() + $player->getWeight();
            }
            $skills = $player->getSkills();
            $lineupPosition = str_contains($player->getLineupPosition(), 'RB') ? 'RB' : $player->getLineupPosition();
            $skillsToSum = explode(';', $positionalSkills[$lineupPosition]);
            foreach ($skillsToSum as $skillName) {
                $skillSum += floor($skills[$skillName]);
            }
        }

        return $skillSum;
    }

    public function getPassSkillSum(array $players, string $gameplayName, ?Player $receiver = null): int
    {
        $positionalSkills = $this->fetchPositionalSkills($gameplayName, 3);
        $skillSum = 0;

        foreach ($players as $player) {
            $skillSum += $this->getPlayersSkillSum($player, $positionalSkills);
        }

        if (null != $receiver) {
            $skills = $receiver->getSkills();
            $skillsToSum = explode(';', $positionalSkills['receiver']);
            foreach ($skillsToSum as $skillName) {
                $skillSum += floor($skills[$skillName]);
            }
        }

        return $skillSum;
    }

    public function getPlayersSkillSum(Player $player, array $positionalSkills): int
    {
        $skillSum = 0;
        $skills = $player->getSkills();
        $lineupPosition = str_contains($player->getLineupPosition(), 'RB') ? 'RB' : $player->getLineupPosition();
        $skillsToSum = explode(';', $positionalSkills[$lineupPosition]);
        foreach ($skillsToSum as $skillName) {
            $skillSum += floor($skills[$skillName]);
        }
        return $skillSum;
    }

    public function fetchPositionalSkills(string $gameplay, int $calcNr = 1): array
    {
        $selectStmt = 'SELECT * FROM `t_gameplay_to_positional_skills` WHERE gameplay = :gameplay AND calcNr = :calcNr;';
        $stmt = $this->pdo->prepare($selectStmt);
        $stmt->execute(['gameplay' => $gameplay, 'calcNr' => $calcNr]);
        $skills = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $positionalSkills = array();
        foreach ($skills as $skill) {
            if ($skill['lineupPosition'] == 'MLB') {
                // Bei MLB stehen entweder 1 und 2 auf dem Feld (dann gleiche Werte).
                // Steht nur ein MLB auf dem Feld ist die Lineup-Position mit Nummerierung.
                $positionalSkills[$skill['lineupPosition'] . '1'] = $skill['skillNames'];
                $positionalSkills[$skill['lineupPosition'] . '2'] = $skill['skillNames'];
            } else {
                $positionalSkills[$skill['lineupPosition']] = $skill['skillNames'];
            }
        }
        return $positionalSkills;
    }

    public function getDifferenceString(int $difference, int $calcNr, ?string $kind = null): string
    {
        if ($calcNr == 1) {
            if ($difference >= 750) {
                $differenceString = '>750';
            } elseif ($difference >= 500) {
                $differenceString = '>500';
            } elseif ($difference >= 250) {
                $differenceString = '>250';
            } elseif ($difference >= 100) {
                $differenceString = '>100';
            } elseif ($difference <= -750) {
                $differenceString = '<-750';
            } elseif ($difference <= -500) {
                $differenceString = '<-500';
            } elseif ($difference <= -250) {
                $differenceString = '<-250';
            } elseif ($difference <= -100) {
                $differenceString = '<-100';
            } else {
                $differenceString = '0';
            }
        } else if (null != $kind && $calcNr == 3) {
            if ($difference >= 75) {
                $differenceString = '>75';
            } elseif ($difference >= 50) {
                $differenceString = '>50';
            } elseif ($difference >= 25) {
                $differenceString = '>25';
            } elseif ($difference >= 10) {
                $differenceString = '>10';
            } elseif ($difference <= -75) {
                $differenceString = '<-75';
            } elseif ($difference <= -50) {
                $differenceString = '<-50';
            } elseif ($difference <= -25) {
                $differenceString = '<-25';
            } elseif ($difference <= -10) {
                $differenceString = '<-10';
            } else {
                $differenceString = '0';
            }
        } else {
            if ($difference >= 100) {
                $differenceString = '>100';
            } elseif ($difference >= 75) {
                $differenceString = '>75';
            } elseif ($difference >= 50) {
                $differenceString = '>50';
            } elseif ($difference >= 25) {
                $differenceString = '>25';
            } elseif ($difference <= -100) {
                $differenceString = '<-100';
            } elseif ($difference <= -75) {
                $differenceString = '<-75';
            } elseif ($difference <= -50) {
                $differenceString = '<-50';
            } elseif ($difference <= -25) {
                $differenceString = '<-25';
            } else {
                $differenceString = '0';
            }
        }
        return $differenceString;
    }

    public function getCalcResult(array $gameplayCalc): string
    {
        $distances = explode(';', $gameplayCalc['distances']);
        $chances = explode(';', $gameplayCalc['chances']);
        $calcSpace = array_combine($distances, $chances);
        $middleKey = array_keys($calcSpace)[floor(count($calcSpace) / 2)];

        return $this->dw_rand($calcSpace, $middleKey);
    }

    public function getOrCalcLastGameplayResult(int $gameTime, array $game): array
    {
        // $gameplayTime = 15 * ceil($gameTime / 15);
        $gameplayTime = 10 * ceil($gameTime / 10);
        $this->log->debug('gameplayTime: ' . $gameplayTime);

        // Suche in der Datenbank nach dem Game (League oder Friendly), ob schon Daten vorhanden sind.
        // Andernfalls muss ein Spielzug berechnet werden.
        $gameplayHistory = $this->getGameCalculation($game, $gameplayTime);
        $this->log->debug('History-GameplayTime: ' . (isset($gameplayHistory) ? $gameplayHistory['gameplayTime'] : 'null'));

        if (null == $gameplayHistory && $game['gameTime'] <= $gameplayTime) {
            $this->log->debug('game-gameTime: ' . $game['gameTime'] . ' | gameplayTime: ' . $gameplayTime);
            $gameplayHistory = $this->calculateGameplay($game, $game['gameTime']);
            $this->log->debug('Calculated Gameplay History: ' . print_r($gameplayHistory, true));
            $this->log->debug('Calculated-GameplayTime: ' . $gameplayHistory['gameplayTime']);
            $this->log->debug('Is gameplayHistory-gameplayTime smaller than gameplayTime - 10? ' . ($gameplayHistory['gameplayTime'] < $gameplayTime - 10 ? 'true' : 'false'));
            if (!$gameplayHistory['isEnd'] && $gameplayHistory['gameplayTime'] < $gameplayTime - 10) {
                $this->log->debug('It was the first gameplay, but the game already started. We have to calculate recursively until we reach the current gameplayTime.');
                $gameplayHistory = $this->getOrCalcLastGameplayResult($gameTime, $game);
            }
        } elseif ($gameplayHistory['gameplayTime'] <= $gameplayTime - 10) {
            $this->log->debug('starting loop for older gameplays.');
            $this->log->debug('gameplayHistory-gameplayTime: ' . $gameplayHistory['gameplayTime'] . ' | gameplayTime: ' . $gameplayTime);
            // Wenn die GameplayHistory älter ist als 10 Sekunden (also älter als der letztmögliche Spielzug),
            // müssen alle nachfolgenden Spielzüge bis zum aktuellen berechnet werden.
            // Die Schleife sollte also alle Spielzüge berechnen und in die Datenbank schreiben.
            // Der letzte berechnete Spielzug ist dann der aktuelle Spielzug und wird ans Frontend geschickt.
            while (($gameplayHistory['gameplayTime'] <= ($gameplayTime - 10)
                // && !($gameplayHistory['quarter'] == 4 && $gameplayHistory['playClock'] == 0))
                && !$gameplayHistory['isEnd'])) {
                // Der aktuelle Spielzug ist immer der letzte Spielzug + 10 Sekunden.
                $timeOfActualGameplay = $gameplayHistory['gameplayTime'] + 10;
                $this->log->debug('Gameplay-Schleifendurchlauf');
                $this->log->debug('gameplayTime: ' . $gameplayTime);
                $this->log->debug('gameplayHistory: ' . $gameplayHistory['gameplayTime']);
                $this->log->debug('timeOfActualGameplay: ' . $timeOfActualGameplay);
                $gameplayHistory = $this->calculateGameplay($game, $timeOfActualGameplay);
                $gameplayHistory['gameplayTime'] = $timeOfActualGameplay;
            }
        }
        return $gameplayHistory;
    }

    public function getDistance(string $gameplay, ?string $defGameplay, string $differenceString, int $calcNr = 1): string
    {
        $selectStmt = 'SELECT * FROM `t_gameplay_calculation` WHERE gameplay = :gameplay AND calculation = :calcNr AND difference = :difference';
        if (null != $defGameplay) {
            $selectStmt .= ' AND defGameplay = :defGameplay';
        }

        $stmt = $this->pdo->prepare($selectStmt);
        $execValues = array('gameplay' => $gameplay, 'calcNr' => $calcNr, 'difference' => $differenceString);
        if (null != $defGameplay) {
            $execValues['defGameplay'] = $defGameplay;
        }
        $stmt->execute($execValues);
        $gameplayCalc = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->log->debug('handlePass');
        $this->log->debug('Off-Gameplay: ' . $gameplay);
        $this->log->debug('Def-Gameplay: ' . $defGameplay);
        $this->log->debug('CalcNr: ' . $calcNr);
        $this->log->debug('Diff: ' . $differenceString);

        return $this->getCalcResult($gameplayCalc);
    }

    public function getNextDown($down): string
    {
        return match ($down) {
            '1st' => '2nd',
            '2nd' => '3rd',
            '3rd' => '4th',
            default => '1st',
        };
    }

    public function getText(string $textName, array $texts, ?Player $triggeringPlayer, ?Player $tacklingPlayer, ?Player $quarterback, ?string $defGameplay, ?int $distance, ?string $situation, bool $isTD, ?Team $offTeam = null, bool $isFumble = false): string
    {
        $gametextController = new GametextController($this->pdo);

        // Die Distanz kann auch größer 100 sein → Fehlerfall: Distanz = 101, aber die DB hat als "maxDistance" 100.
        $textDistance = $distance;
        if ($textDistance > 100) {
            $textDistance = 100;
        }

        $this->log->debug('TextName: ' . $textName);
        $this->log->debug('Distance: ' . $distance);
        $this->log->debug('TextDistance: ' . $textDistance);
        $this->log->debug('DefGameplay: ' . $defGameplay);
        $this->log->debug('IsFumble: ' . $isFumble);
        $this->log->debug('Situation: ' . $situation);
        $this->log->debug('isTD: ' . $isTD);
        if ($isFumble !== false) {
            $gametext = array_values(array_filter($texts, function (Gametext $gametext) use ($textName, $textDistance, $isTD, $situation) {
                $useText = false;
                if ($gametext->getSituation() == $situation) {
                    if ($textName == 'Defense' && $gametext->getTextName() == $textName) {
                        if ($isTD) {
                            $useText = $gametext->isTd() == $isTD;
                        } else {
                            $useText = $textDistance >= $gametext->getPlayrangeVon() && $textDistance <= $gametext->getPlayrangeBis();
                        }
                    } else {
                        $useText = $gametext->getTextName() == $textName;
                    }
                }
                return $useText;
            }))[0]->getText();
        } else {
            $texts = array_values(array_filter($texts, function (Gametext $gametext) use ($textName, $textDistance, $isTD, $situation, $triggeringPlayer, $defGameplay) {
                $useText = false;
                if (isset($situation) && $gametext->getSituation() == $situation && $textDistance >= $gametext->getPlayrangeVon() && $textDistance <= $gametext->getPlayrangeBis()) {
                    if (strlen($textName) > 0 && $textName == $gametext->getTextName() && $isTD == $gametext->isTd()) {
                        $useText = true;
                    } elseif (strlen($textName) <= 0 && $isTD == $gametext->isTd()) {
                        $useText = true;
                    } elseif ($situation == 'Safety') {
                        $useText = true;
                    }
                } elseif (isset($defGameplay) && $gametext->getTextName() == $textName && $textDistance >= $gametext->getPlayrangeVon() && $textDistance <= $gametext->getPlayrangeBis()) {
                    // Bei Paessen darf nur ein Text je nach Def-Gameplay aus der Filterung kommen
                    if (null !== $gametext->getTriggeringPosition() && $gametext->getTriggeringPosition() == $defGameplay && $gametext->isTd() == $isTD) {
                        $useText = true;
                    }
                } else {
                    if (null == $situation && null == $defGameplay && $gametext->getTextName() == $textName && $textDistance >= $gametext->getPlayrangeVon() && $textDistance <= $gametext->getPlayrangeBis()) {
                        if (null !== $gametext->getTriggeringPosition() && in_array($gametext->getTriggeringPosition(), $this->runPositions) && !$isTD) {
                            $lineupPosition = str_contains($triggeringPlayer->getLineupPosition(), 'RB') ? 'RB' : $triggeringPlayer->getLineupPosition();
                            $useText = str_contains($gametext->getTriggeringPosition(), $lineupPosition);
                        } elseif ($gametext->isTd() == $isTD) {
                            $useText = true;
                            if ($textName === 'FieldGoal' && null !== $gametext->getTriggeringPosition()) {
                                $useText = false;
                            }
                        }
                    }
                }
                return $useText;
            }));
            $this->log->debug('Texte: ' . print_r($texts, true));
            if (count($texts) > 1) {
                shuffle($texts);
            }
            $gametext = $texts[0]->getText();
        }

        if (null !== $distance) {
            return $gametextController->changeNamePosInText($gametext, $triggeringPlayer, $tacklingPlayer, $quarterback, $offTeam, $distance);
        } else {
            return $gametextController->changeNamePosInText($gametext, $triggeringPlayer, $tacklingPlayer, $quarterback, $offTeam);
        }
    }

    public function fumble(int $gameId, array $defensePlayers, array $runTexts, Team $fumblingTeam, Player $triggeringPlayer, Player $tacklingPlayer, int $yardsToTD): array
    {
        $isTD = false;
        $recoveringYards = 0;
        $isDefense = $this->probability(477, 1000);
        $this->log->debug('isDefenseFumbleRecovery: ' . $isDefense);
        $ballWinningSide = $isDefense ? 'Defense' : 'Offense';
        $this->log->debug('BallWinningSide: ' . $ballWinningSide);

        if ($ballWinningSide == 'Defense') {
            $recoveryChance = 0.99 / count($defensePlayers);

            $highRecoveryChancePlayers = array_values(array_filter($defensePlayers, function (Player $player) use ($tacklingPlayer) {
                return $player->getLineupPosition() !== $tacklingPlayer->getLineupPosition();
            }));

            $recoveryPlayerSpace = array();
            foreach ($highRecoveryChancePlayers as $highRecoveryChancePlayer) {
                $recoveryPlayerSpace[$highRecoveryChancePlayer->getLineupPosition()] = $recoveryChance;
            }
            $recoveryPlayerSpace[$triggeringPlayer->getLineupPosition()] = 0.01;
            $recoveringPosition = $this->dw_rand($recoveryPlayerSpace, array_key_first($recoveryPlayerSpace));
            $this->log->debug('Recovering Position: ' . $recoveringPosition);
            // TacklingPlayer wird durch RecoveringPlayer ersetzt für korrekten Text
            $recoveringPlayer = array_values(array_filter($defensePlayers, function (Player $player) use ($recoveringPosition) {
                return $player->getLineupPosition() == $recoveringPosition;
            }))[0];

            $chancesPerYard = array();
            for ($i = 0; $i <= 100; $i++) {
                if ($i == 0) {
                    $chancesPerYard[$i] = 90;
                } else {
                    $chancesPerYard[$i] = 0.1;
                }
            }
            $recoveringYards = $this->dw_rand($chancesPerYard);
            $this->log->debug('Recovering Yards: ' . $recoveringYards);

            //Prüfung auf isTD
            $newYardsToTD = (100 - $yardsToTD);
            $isTD = $newYardsToTD - $recoveringYards <= 0;

            // Statistik Fumble -> Force Fumble = Tackling Player
            $statisticsController = new StatisticsController($this->pdo, $this->log);
            $statisticsController->saveFumble($gameId, $fumblingTeam, $recoveringPlayer, $recoveringYards, $newYardsToTD, $isTD);
        }

        $fumbleText = $this->getText($ballWinningSide, $runTexts, $triggeringPlayer, $recoveringPlayer ?? $tacklingPlayer, null, null, $recoveringYards, 'Fumble', $isTD, null, true);
        return array($recoveringYards => $fumbleText);
    }

    public function getGameCalculation(array $game, int $gameplayTime): ?array
    {
        $selectStmt = 'SELECT * FROM `t_gameplay_history` 
                    WHERE (gameplayTime = :gameplayTime AND (idLeagueGame = :idLeagueGame OR idFriendlyGame = :idFriendlyGame)) 
                    OR (idLeagueGame = :idLeagueGame2 OR idFriendlyGame = :idFriendlyGame2) 
                    ORDER BY quarter DESC, playClock, gameplayTime DESC LIMIT 1;';
        $stmt = $this->pdo->prepare($selectStmt);
        $stmt->execute([
            'gameplayTime' => $gameplayTime,
            'idLeagueGame' => $game['id'],
            'idFriendlyGame' => $game['id'],
            'idLeagueGame2' => $game['id'],
            'idFriendlyGame2' => $game['id']
        ]);
        $gameHistory = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($gameHistory) {
            return $gameHistory;
        }
        return null;
    }

    public function getScoringGameplaysForGame(array $game): ?array
    {
        if (isset($_SESSION['scoringGameplays' . $game['id']])) {
            $scoringGameplays = $_SESSION['scoringGameplays' . $game['id']];
        } else {
            $selectStmt = 'SELECT * FROM `t_gameplay_history` 
                            WHERE idLeagueGame = :idLeagueGame OR idFriendlyGame = :idFriendlyGame 
                            AND (isTD = true OR isFG = true)
                            ORDER BY quarter, playClock DESC;';
            $stmt = $this->pdo->prepare($selectStmt);
            $stmt->execute(['idLeagueGame' => $game['id'], 'idFriendlyGame' => $game['id']]);
            $scoringGameplays = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        return $scoringGameplays ?? null;
    }

    public function checkAndCalcEnding(array|null $lastGameplayHistory, array $game): bool
    {
        $leagueController = new LeagueController($this->pdo, $this->log);

        $isEnd = false;
        if (null != $lastGameplayHistory && $lastGameplayHistory['quarter'] == 5) {
            $isEnd = true;
            // if game ended we can save the score in t_event.result
            $leagueController->saveFinalScoreToEvent($game);
        }
        return $isEnd;
    }

    public function calculateGameplay(array $game, int $gameplayTime): array
    {
        $teamController = new TeamController($this->pdo, $this->log);
        $gameController = new GameController($this->pdo, $this->log);
        $leagueController = new LeagueController($this->pdo, $this->log);
        $specialCalc = new SpecialTeamsCalculation($this->pdo, $this->log);
        $coachingController = new CoachingController($this->pdo, $this->log);
        $statisticsController = new StatisticsController($this->pdo, $this->log);

        $gameId = $game['id'];
        $gameplay = null;
//        $lastGameplayHistory = $this->getGameCalculation($game, ($gameplayTime - 15));
        $lastGameplayHistory = $this->getGameCalculation($game, ($gameplayTime - 10));

        $isEnd = $this->checkAndCalcEnding($lastGameplayHistory, $game);
        if ($isEnd) {
            $this->log->debug('Game has ended!');
            $gameplay = $lastGameplayHistory;
            $gameplay['isEnd'] = true;
        } else {
            if (null == $lastGameplayHistory) {
                // First KickOff
                if (!isset($_SESSION['standings' . $game['id']])) {
                    $leagueController->saveScore($game, null);
                }
                $playClock = 900;
                $teams = $this->getTeams($game);
                if (null != $teams) {
                    $offTeam = $teams['offTeam'];
                    $defTeam = $teams['defTeam'];

                    $gameplay = $this->handleKickOff($gameId, $playClock, $offTeam, $defTeam);

                    $gameplay['startQuarter'] = 1;
                    $gameplay['startPlayClock'] = 900;
                    $gameplay['startYardsToTD'] = 65;
                    $gameplay['startYardsToFirstDown'] = 10;

                    $gameplay['idOffTeam'] = $offTeam->getId();
                    $gameplay['idDefTeam'] = $defTeam->getId();
                    if (isset($_SESSION['team'])) {
                        $_SESSION['team'] = $offTeam->getName() === $_SESSION['team']->getName() ? $offTeam : $defTeam;
                    }
                }
            } else {
                $changeSides = false;
                $isTwoPointConversion = false;
                $quarter = $lastGameplayHistory['quarter'];
                $playClock = $lastGameplayHistory['playClock'];
                $offTeam = $teamController->fetchTeamById($lastGameplayHistory['idOffTeam']);
                $defTeam = $teamController->fetchTeamById($lastGameplayHistory['idDefTeam']);
                $isKickOff = $lastGameplayHistory['isKickOff'] ?? false;

                if (($quarter == 3 && $playClock == 900) || $isKickOff) {
                    // KickOff der zweiten Halbzeit oder Kickoff nach PAT/Two-Point-Conversion oder Field Goal
                    $gameplay = $this->handleKickOff($gameId, $playClock, $offTeam, $defTeam);
                }

                if (!isset($gameplay)) {
                    // Mehrere mögliche Szenarien
                    // 1. Field Goal oder Punt
                    // 2. PAT oder Two-Point Conversion
                    // 3. Einfach neuer Spielzug (Run oder Pass)
                    $down = $lastGameplayHistory['down'];
                    $yardsToTD = $lastGameplayHistory['yardsToTD'];
                    $yardsToFirstDown = $lastGameplayHistory['yardsToFirstDown'];

                    $isPAT = $lastGameplayHistory['isPAT'];

                    // TODO: 4th Down ausspielen oder punten > aus Coaching holen
                    if (!$isPAT && $down == '4th') {
                        // Field Goal oder Punt
                        $gameplay = $this->handleKicks($game, $quarter, $offTeam, $yardsToTD, null, null);

                        if (null == $gameplay) {
                            // Prüfung → 4th Down ausspielen oder punten
                            $coaching2nd = $coachingController->getGeneralCoachingFromTeam($offTeam, $offTeam->getGameplanOff(), '2nd');
                            $fourthDown = explode(';', $coaching2nd->getGameplay1())[1];
                            $isHome = $game['home'] == $offTeam->getName();
                            $standings = $leagueController->getStandings($game);
                            $isPunt = !$specialCalc->isFourthDown($fourthDown, $yardsToTD, $standings['score'], $isHome);
                            if ($isPunt) {
                                $gameplay = $this->handlePunt($gameId, $playClock, $offTeam, $defTeam, $yardsToTD);
                                $gameplay['isPunt'] = true;
                            }
                        } else {
                            if ($gameplay['isFG']) {
                                $gameplay['isKickOff'] = true;
                            }
                            $gameplay['playClock'] = $lastGameplayHistory['playClock'];
                        }
                        if (null != $gameplay) {
                            // FG-Attempt/Punt -> Seitenwechsel
                            $changeSides = true;
                        }
                    }

                    if ($isPAT) {
                        // PAT oder Two-Point-Conversion
                        $gameplay = $this->handleKicks($game, $quarter, $offTeam, null, 0, 0);
                        if (null == $gameplay) {
                            // Two-Point Conversion
                            $isTwoPointConversion = true;
                            $down = '4th';
                            $yardsToFirstDown = 2;
                        } else {
                            $gameplay['playClock'] = $lastGameplayHistory['playClock'];
                        }
                        $gameplay['isPAT'] = $isPAT;
                        $gameplay['isKickOff'] = true;
                        $changeSides = true;
                    }

                    if (null == $gameplay) {
                        // Spielzug
                        $secondRB = $lastGameplayHistory['secondRB'] ?? false;
                        $offEleven = $gameController->getStartingEleven($offTeam, 'Offense', $secondRB);
                        $defEleven = $gameController->getStartingEleven($defTeam, 'Defense');


                        // 2. Offense Spielzug waehlen, um korrekte Penalty-Text zu zeigen
                        $offGameplay = $gameController->getGameplay($offTeam, 'Offense', $down, null, $yardsToFirstDown);
                        $kind = explode(';', $offGameplay)[0];

                        if (!$isTwoPointConversion) {
                            // 1. Penalty berechnen
                            $penaltyGameplay = $this->handlePenalty($gameId, $offTeam, $defTeam, $offEleven, $defEleven, $kind);
                            $penalty = array_pop($penaltyGameplay);
                        }

                        $gameplay = $this->handleGameplay($playClock, $game, $quarter, $offTeam, $defTeam, $offEleven, $defEleven, $offGameplay, $down, $yardsToFirstDown, $yardsToTD, $isTwoPointConversion, $penalty ?? null);
                        $changeSides = array_pop($gameplay);
                        $gameplay['offGameplay'] = $offGameplay;

                        if (isset($penaltyGameplay)) {
                            $gameplay = array_merge($penaltyGameplay, $gameplay);
                        }
                    }
                }


                // Wenn die PlayClock nach dem Spielzug auf 0 geht, ist nächstes Viertel
                if ($gameplay['playClock'] <= 0) {
                    $quarter += 1;
                    $playClock = 900;

                    if ($quarter == 3) {
                        $changeSides = true;
                    }
                    $gameplay['playClock'] = $playClock;

                    if ($quarter == 5) {
                        // Spielende
                        $gameplay['quarter'] = 4;
                        $gameplay['playClock'] = 0;
                        // Berechne die Quarterback-Statistiken für Durchschnittswerte
                        $qbs = array_merge(array_filter($offTeam->getPlayers(), function ($player) {
                            return $player->getType()->getPosition()->getPosition() == 'QB';
                        }), array_filter($defTeam->getPlayers(), function ($player) {
                            return $player->getType()->getPosition()->getPosition() == 'QB';
                        }));
                        $this->log->debug('QBs für Statistik: ' . print_r($qbs, true));
                        $statisticsController->saveCalculatedStatistics($gameId, $qbs);
                    }
                }
                $gameplay['quarter'] = $quarter;

                $gameplay['startQuarter'] = $lastGameplayHistory['quarter'];
                $gameplay['startPlayClock'] = $lastGameplayHistory['playClock'];
                $gameplay['startYardsToTD'] = $lastGameplayHistory['yardsToTD'];
                $gameplay['startYardsToFirstDown'] = $lastGameplayHistory['yardsToFirstDown'];

                if ($changeSides) {
                    $gameplay['idOffTeam'] = $defTeam->getId();
                    $gameplay['idDefTeam'] = $offTeam->getId();
                } else {
                    $gameplay['idOffTeam'] = $offTeam->getId();
                    $gameplay['idDefTeam'] = $defTeam->getId();
                }

                if (isset($_SESSION['team'])) {
                    $_SESSION['team'] = $offTeam->getName() === $_SESSION['team']->getName() ? $offTeam : $defTeam;
                }
            }

            $gameplay['secondRB'] = $this->isSecondRB();

            if (!isset($gameplay['gameplayTime'])) {
                $gameplay['gameplayTime'] = $gameplayTime;
            }

            $this->log->debug('Gameplay: ' . print_r($gameplay, true));

            $this->saveGameplay($game, $gameplay, $gameplayTime);
        }

        return $gameplay;
    }

    private function saveGameplay(array $game, array $gameplay, int $gameplayTime): void
    {
        $selectStmt = $this->pdo->prepare('SELECT id from `t_gameplay_history` where gameplayTime = :gameplayTime AND (idLeagueGame = :idLeagueGame OR idFriendlyGame = :idFriendlyGame);');
        $selectStmt->execute(['gameplayTime' => $gameplayTime, 'idLeagueGame' => $game['id'], 'idFriendlyGame' => $game['id']]);
        $result = $selectStmt->fetch(PDO::FETCH_ASSOC);
        $id = $result ? $result['id'] : null;

        $saveGameplay = 'INSERT INTO `t_gameplay_history` (id, gameplayTime, quarter, playClock, yardsToTD, yardsToFirstDown, startQuarter, 
                                  startPlayClock, startYardsToTD, startYardsToFirstDown, down, runner, secondRB, isKickOff, isFG, isTD, isPAT, isPunt, 
                                  isTwoPointConversion, isInterception, gametext, offGameplanNr, defGameplanNr, offGameplay, defGameplay, idOffTeam, 
                                  idDefTeam, idLeagueGame, idFriendlyGame) 
                            values (:id, :gameplayTime, :quarter, :playClock, :yardsToTD, :yardsToFirstDown, :startQuarter, :startPlayClock, 
                                    :startYardsToTD, :startYardsToFirstDown, :down, :runner, :secondRB, :isKickOff, :isFG, :isTD, :isPAT, :isPunt, 
                                    :isTwoPointConversion, :isInterception, :gametext, :offGameplanNr, :defGameplanNr, :offGameplay, :defGameplay, 
                                    :idOffTeam, :idDefTeam, :idLeagueGame, :idFriendlyGame) 
                            ON DUPLICATE KEY UPDATE gameplayTime = :newGameplayTime, quarter = :newQuarter, playClock = :newPlayClock, yardsToTD = :newYardsToTD, 
                                                    yardsToFirstDown = :newYardsToFirstDown, startQuarter = :newStartQuarter, startPlayClock = :newStartPlayClock, 
                                                    startYardsToTD = :newStartYardsToTD, startYardsToFirstDown = :newStartYardsToFirstDown, down = :newDown, 
                                                    runner = :newRunner, secondRB = :newSecondRB, isKickOff = :newIsKickOff, isFG = :newIsFG, isTD = :newIsTD, 
                                                    isPAT = :newIsPAT, isPunt = :newIsPunt, isTwoPointConversion = :newIsTwoPointConversion, 
                                                    isInterception = :newIsInterception, gametext = :newGametext, offGameplanNr = :newOffGameplanNr, 
                                                    defGameplanNr = :newDefGameplanNr, offGameplay = :newOffGameplay, defGameplay = :newDefGameplay, 
                                                    idOffTeam = :newIdOffTeam, idDefTeam = :newIdDefTeam, idLeagueGame = :newIdLeagueGame, 
                                                    idFriendlyGame = :newIdFriendlyGame;';
        $saveStmt = $this->pdo->prepare($saveGameplay);
        $saveStmt->execute([
            'id' => $id ?? null,
            'gameplayTime' => $gameplayTime,
            'quarter' => $gameplay['quarter'],
            'playClock' => $gameplay['playClock'],
            'yardsToTD' => $gameplay['yardsToTD'],
            'yardsToFirstDown' => $gameplay['yardsToFirstDown'],
            'startQuarter' => $gameplay['startQuarter'],
            'startPlayClock' => $gameplay['startPlayClock'],
            'startYardsToTD' => $gameplay['startYardsToTD'],
            'startYardsToFirstDown' => $gameplay['startYardsToFirstDown'],
            'down' => $gameplay['down'],
            'runner' => $gameplay['runner'] ?? null,
            'secondRB' => $gameplay['secondRB'] ?? false,
            'isKickOff' => $gameplay['isKickOff'] ?? false,
            'isFG' => $gameplay['isFG'] ?? false,
            'isTD' => $gameplay['isTD'] ?? false,
            'isPAT' => $gameplay['isPAT'] ?? false,
            'isPunt' => $gameplay['isPunt'] ?? false,
            'isTwoPointConversion' => $gameplay['isTwoPointConversion'] ?? false,
            'isInterception' => $gameplay['isInterception'] ?? false,
            'gametext' => $gameplay['gametext'],
            'offGameplanNr' => $gameplay['offGameplanNr'] ?? null,
            'defGameplanNr' => $gameplay['defGameplanNr'] ?? null,
            'offGameplay' => $gameplay['offGameplay'] ?? null,
            'defGameplay' => $gameplay['defGameplay'] ?? null,
            'idOffTeam' => $gameplay['idOffTeam'],
            'idDefTeam' => $gameplay['idDefTeam'],
            'idLeagueGame' => $game['isLeagueGame'] ? $game['id'] : null,
            'idFriendlyGame' => $game['isLeagueGame'] ? null : $game['id'],
            'newGameplayTime' => $gameplayTime,
            'newQuarter' => $gameplay['quarter'],
            'newPlayClock' => $gameplay['playClock'],
            'newYardsToTD' => $gameplay['yardsToTD'],
            'newYardsToFirstDown' => $gameplay['yardsToFirstDown'],
            'newStartQuarter' => $gameplay['startQuarter'],
            'newStartPlayClock' => $gameplay['startPlayClock'],
            'newStartYardsToTD' => $gameplay['startYardsToTD'],
            'newStartYardsToFirstDown' => $gameplay['startYardsToFirstDown'],
            'newDown' => $gameplay['down'],
            'newRunner' => $gameplay['runner'] ?? null,
            'newSecondRB' => $gameplay['secondRB'] ?? false,
            'newIsKickOff' => $gameplay['isKickOff'] ?? false,
            'newIsFG' => $gameplay['isFG'] ?? false,
            'newIsTD' => $gameplay['isTD'] ?? false,
            'newIsPAT' => $gameplay['isPAT'] ?? false,
            'newIsPunt' => $gameplay['isPunt'] ?? false,
            'newIsTwoPointConversion' => $gameplay['isTwoPointConversion'] ?? false,
            'newIsInterception' => $gameplay['isInterception'] ?? false,
            'newGametext' => $gameplay['gametext'],
            'newOffGameplanNr' => $gameplay['offGameplanNr'] ?? null,
            'newDefGameplanNr' => $gameplay['defGameplanNr'] ?? null,
            'newOffGameplay' => $gameplay['offGameplay'] ?? null,
            'newDefGameplay' => $gameplay['defGameplay'] ?? null,
            'newIdOffTeam' => $gameplay['idOffTeam'],
            'newIdDefTeam' => $gameplay['idDefTeam'],
            'newIdLeagueGame' => $game['isLeagueGame'] ? $game['id'] : null,
            'newIdFriendlyGame' => $game['isLeagueGame'] ? null : $game['id']
        ]);
        $this->log->debug('Saved Gameplay. Last Inserted ID: ' . $this->pdo->lastInsertId());
    }

    private function getTeams(array $game): ?array
    {
        $teamController = new TeamController($this->pdo, $this->log);
        $team = $_SESSION['team'] ?? null;
        if (!isset($team)) {
            // when calling via cronjob, we don't have a session
            $team = $teamController->fetchTeam(null, $game['home']);
            // but we can't save the team in the session, 'cause cron job is calculating for all teams
        }
        if (isset($team)) {
            $vsTeamName = $game['home'] == $team->getName() ? $game['away'] : $game['home'];
            $vsOrAt = $game['home'] == $team->getName() ? 'vs' : 'at';
            $vsTeam = $teamController->fetchTeam(null, $vsTeamName);
            if ($vsOrAt == 'vs') {
                // eigenes Team ist Offense
                $offTeam = $team;
                $defTeam = $vsTeam;
            } else {
                $defTeam = $team;
                $offTeam = $vsTeam;
            }
            $teams['offTeam'] = $offTeam;
            $teams['defTeam'] = $defTeam;
            return $teams;
        }
        return null;
    }

    private function handleGameplay(int $playClock, array $game, string $quarter, Team $offTeam, Team $defTeam, array $offEleven, array $defEleven, string $offGameplay, string $down, int $yardsToFirstDown, int $yardsToTD, bool $isTwoPointConversion, ?Penalty $penalty): array
    {
        $gameController = new GameController($this->pdo, $this->log);
        $leagueController = new LeagueController($this->pdo, $this->log);
        $runCalc = new RunCalculation($this->pdo, $this->log);
        $passCalc = new PassCalculation($this->pdo, $this->log);

        $gameId = $game['id'];
        $gameplay = array();
        $gameplayYards = 0;
        $isInterception = false;
        $isDeflected = false;
        $isSafety = false;
        $penaltyDeclined = false;
        $kind = explode(';', $offGameplay)[0];
        $timescale = isset($penalty) ? $penalty->getTimescale() : '';

        //Spielzug nur berechnen, wenn keine Strafe oder Strafe danach
        if ($timescale === 'vorher') {
            $this->log->debug('Vorher-Penalty: ' . print_r($penalty, true));
            if ($penalty->getTeamPart() !== null) {
                $gameplay['yardsToFirstDown'] = $yardsToFirstDown - $penalty->getYards();
                $gameplay['yardsToTD'] = $yardsToTD - $penalty->getYards();
                $gameplay['down'] = $this->getNextDown($down);
            } else {
                // Kein TeamPart → Beide Seiten haben Penalty = Spielzug wird wiederholt
                $gameplay['yardsToFirstDown'] = $yardsToFirstDown;
                $gameplay['yardsToTD'] = $yardsToTD;
                $gameplay['down'] = $down;
            }
        } else {
            $defGameplay = $gameController->getGameplay($defTeam, 'Defense', $down, $kind);
            $gameplay['defGameplay'] = $defGameplay;

            // Spielzug berechnen
            if ($kind == 'Run') {
                $runner = $runCalc->getRunner($offTeam);
                $gameplayResult = $runCalc->run($gameId, $offTeam, $defTeam, $offEleven, $defEleven, $runner, $offGameplay, $defGameplay, $yardsToTD, isset($penalty));
                $gameplay['runner'] = $runner;
            } elseif ($kind == 'Pass') {
                if (isset($penalty) && $penalty->getPenalty() == 'Pass Interference') {
                    //nur die Throwing-Distance berechnen
                    $gameplayResult = $passCalc->pass($gameId, $offTeam, $defTeam, $offEleven, $defEleven, $offGameplay, $defGameplay, $yardsToTD, $penalty);
                } else {
                    $gameplayResult = $passCalc->pass($gameId, $offTeam, $defTeam, $offEleven, $defEleven, $offGameplay, $defGameplay, $yardsToTD);
                    if (count($gameplayResult) == 2) {
                        if (isset($gameplayResult['isInterception']) && $gameplayResult['isInterception']) {
                            $isInterception = array_pop($gameplayResult);
                            $gameplay['isInterception'] = $isInterception ?? false;
                            $yardsToTD = 100 - $yardsToTD;
                        }
                        if (isset($gameplayResult['isDeflected']) && $gameplayResult['isDeflected']) {
                            $isDeflected = array_pop($gameplayResult);
                        }
                        if (isset($gameplayResult['isSafety']) && $gameplayResult['isSafety']) {
                            // es kam zum Safety
                            $isSafety = array_pop($gameplayResult);
                            $gameplay['isSafety'] = $isSafety ?? false;
                        }
                    }
                }
            }
        }

        // GameplayResult auswerten
        if (isset($gameplayResult)) {
            $gameplayYards = array_key_first($gameplayResult);
            $gametext = $gameplayResult[$gameplayYards];

            if (isset($penalty)) {
                $this->log->debug('Nachher-Penalty: ' . print_r($penalty, true));
                // Penalty-Yards gegen Spielzug-Yards prüfen.
                // Bringt Penalty mehr Yards, dann wird es genommen, ansonsten wird die Strafe (Penalty) abgelehnt.
                if ($penalty->getTeamPart() == 'Defense' && $yardsToTD - $penalty->getYards() <= 0) {
                    // TD kann nicht durch Penalty gegeben werden.
                    // Penalty-Yards sind die halbe Distanz zur Endzone
                    if ($yardsToTD == 1) {
                        $penalty->setYards(1);
                    } else {
                        $penalty->setYards(floor($yardsToTD / 2));
                    }
                } elseif ($penalty->getTeamPart() == 'Offense' && $yardsToTD - $penalty->getYards() >= 100) {
                    // Offense-Penaltys geben immer Minus-Yards → Prüfung mit yardsToTD - (-Yards) >= 100
                    // Safety kann nicht durch Penalty gegeben werden.
                    // Penalty-Yards sind die halbe Distanz zur Endzone
                    if ((100 - $yardsToTD) == 1) {
                        $penalty->setYards(0);
                    } else {
                        $penalty->setYards(-1 * floor((100 - $yardsToTD) / 2));
                    }
                }

                if (!$isInterception && $penalty->getTeamPart() == 'Defense' && $gameplayYards > $penalty->getYards()) {
                    $this->log->debug('Defense-Penalty wird direkt abgelehnt: ' . print_r($penalty, true));
                    $penalty->setPenaltyText($penalty->getPenaltyText() . ' Das Penalty wird abgelehnt.');
                    $penaltyDeclined = true;
                } elseif ($isInterception) {
                    if ($penalty->getTeamPart() == 'Defense') {
                        // Zurückdrehen der Interception
                        $isInterception = false;
                        $yardsToTD = 100 - $yardsToTD;
                        $gameplayYards = $penalty->getYards();
                        $penalty->setPenaltyText($penalty->getPenaltyText() . ' Die Interception ist ungültig. Die Offense bleibt auf dem Feld.');
                    } else {
                        // Penalty wird abgelehnt
                        $this->log->debug('Offense-Penalty mit TD wird abgelehnt: ' . print_r($penalty, true));
                        $penalty->setPenaltyText($penalty->getPenaltyText() . ' Das Penalty wird abgelehnt.');
                        $penaltyDeclined = true;
                    }
                } else {
                    $gameplayYards = $penalty->getYards();
                }

                $gametext .= ';' . $penalty->getPenaltyText();
            }

            // Statistik für Down inkl. möglicher Completion
            $statisticsController = new StatisticsController($this->pdo, $this->log);
            $isCompleted = !isset($penalty) && !$isInterception && !$isSafety && !$isDeflected;
            $statisticsController->saveDown($gameId, $down, $isCompleted, $offTeam);

            $yardsToTD -= $gameplayYards;
            $yardsToFirstDown -= $gameplayYards;

            // Szenarien
            // 1. Neues First Down (wenn kein TD und keine Interception)
            // 2. Neues First Down aufgrund von Penalty
            // 3. Out of Downs (4th Down nicht geschafft)
            // 4. Intercepted
            // 5. Safety
            // 6. Touchdown
            // 7. Nächstes Down

            // Überarbeiten -----------------
            if ($yardsToFirstDown <= 0 && $yardsToTD > 0 && !$isInterception) {
                $down = '1st';
                $yardsToFirstDown = 10;
            } elseif (isset($penalty) && !$penaltyDeclined) {
                if ($penalty->isFirstDown()) {
                    $down = '1st';
                    $yardsToFirstDown = 10;
                } else {
                    $down = $gameController->getNextDown($down);
                }
            } elseif (($isInterception && $yardsToTD > 0) || ($down == '4th' && $yardsToFirstDown > 0)) {
                $changeSides = true;
                $down = '1st';
                $yardsToFirstDown = 10;
                if (!$isInterception) {
                    $yardsToTD = 100 - $yardsToTD;
                } elseif ($yardsToTD >= 100) {
                    $yardsToTD = 80;
                }
            } elseif ($isSafety) {
                $changeSides = true;
                $down = '1st';
                $yardsToFirstDown = 10;
                // TODO updateScore für den Safety -> Punkte an Def-Team
            } elseif ($yardsToTD <= 0) {
                // Touchdown oder 2 Point Conversion
                if ($isInterception) {
                    $leagueController->updateScore($game, $defTeam, $quarter, 6);
                    $changeSides = true;
                } else {
                    $points = $isTwoPointConversion ? 2 : 6;
                    $leagueController->updateScore($game, $offTeam, $quarter, $points);
                    $gameplay['isTD'] = true;
                    if (!$isTwoPointConversion) {
                        $gameplay['isPAT'] = true;
                    }
                }
            } else {
                $down = $gameController->getNextDown($down);
            }
            // ------------------------------------------------------------

            if ($yardsToTD < $yardsToFirstDown) {
                $yardsToFirstDown = $yardsToTD;
            }

            $gameplay['isTwoPointConversion'] = $isTwoPointConversion;

            $gameplay['gametext'] = $gametext;
            $gameplay['yardsToFirstDown'] = $yardsToFirstDown;
            $gameplay['yardsToTD'] = $yardsToTD;
            $gameplay['down'] = $down;
        }

        // PlayClock aktualisieren
        if (isset($kind) && $kind == 'Run') {
            $gameplayTime = rand(15, 20);
        } else {
            $gameplayTime = rand(10, 15);
        }
        $distance = $gameplayYards <= 0 ? 1 : $gameplayYards;
        $playClock -= ($gameplayTime + floor($distance / 5));

        $gameplay['playClock'] = max($playClock, 0);

        // letzter Wert changeSides - wegen array_pop
        $gameplay['changeSides'] = $changeSides ?? false;
        return $gameplay;
    }

    private function handlePenalty(int $gameId, Team $offTeam, Team $defTeam, array $offEleven, array $defEleven, string $gameplay): array
    {
        $penaltyCalc = new PenaltyCalculation($this->pdo, $this->log);
        $gameplayHistory = array();
        $isOffPenalty = $penaltyCalc->isPenalty($offTeam, $offEleven);
        $isDefPenalty = $penaltyCalc->isPenalty($defTeam, $defEleven);

        if ($isOffPenalty && $isDefPenalty) {
            $penalty = new Penalty();
            $penalty->setTimescale('vorher');
            $penalty->setPenaltyText('Ein ' . $gameplay . '-Spielzug der einige Yards bringt. Jedoch liegen gelbe Flaggen auf dem Spielfeld. Es trifft die Offense und die Defense. Somit heben sich die Strafen auf. Der Spielzug wird wiederholt.');
            $penalty->setYards(0);
            $gameplayHistory['isOffPenalty'] = true;
            $gameplayHistory['isDefPenalty'] = true;
        } elseif ($isOffPenalty) {
            $penalty = $penaltyCalc->calcPenalty($gameId, $offTeam, $offEleven, $gameplay, 'Offense');
            $gameplayHistory['isOffPenalty'] = true;
            $gameplayHistory['idOffPenalty'] = $penalty->getId();
        } elseif ($isDefPenalty) {
            $penalty = $penaltyCalc->calcPenalty($gameId, $defTeam, $defEleven, $gameplay, 'Defense');
            $gameplayHistory['isDefPenalty'] = true;
            $gameplayHistory['idDefPenalty'] = $penalty->getId();
        }

        if (isset($penalty)) {
            $gameplayHistory['gametext'] = $penalty->getPenaltyText();
        }
        $gameplayHistory['penalty'] = $penalty ?? null;

        return $gameplayHistory;
    }

    private function handlePunt(int $gameId, int $playClock, Team $offTeam, Team $defTeam, int $yardsToTD): array
    {
        $this->log->debug('handlePunt');
        $gameController = new GameController($this->pdo, $this->log);
        $specialCalc = new SpecialTeamsCalculation($this->pdo, $this->log);
        $gameplay = array();

        $puntingPlayers = $gameController->getStartingEleven($offTeam, 'Special');
        $returningPlayers = $gameController->getStartingEleven($defTeam, 'Special');
        $gameplayResult = $specialCalc->punt($gameId, $offTeam, $puntingPlayers, $returningPlayers, $yardsToTD);
        $this->log->debug('Punt gameplayResult: ' . print_r($gameplayResult, true));

        if (count($gameplayResult) == 1) {
            // Touchback
            $newYardsToTD = array_key_first($gameplayResult);
            $playClock -= 2;
            $gametext = $gameplayResult[$newYardsToTD];
        } else {
            // Punt mit Return
            $puntDistance = array_key_first($gameplayResult);
            $newYardsToTD = array_key_last($gameplayResult);

            $yardsToTouchback = $yardsToTD - $puntDistance;
            $this->log->debug('yardsToTouchback: ' . $yardsToTouchback);
            $distance = (100 - $yardsToTouchback) - $newYardsToTD;
            $distance = $distance == 0 ? 1 : $distance;

            $playTime = (2 + floor($distance / 5));
            $this->log->debug('playTime: ' . $playTime);
            $playClock -= $playTime;
            $gametext = $gameplayResult[$puntDistance] . ';' . $gameplayResult[$newYardsToTD];
        }

        $gameplay['down'] = '1st';
        $gameplay['playClock'] = $playClock;
        $gameplay['yardsToFirstDown'] = 10;
        $gameplay['yardsToTD'] = $newYardsToTD;
        $gameplay['gametext'] = $gametext;

        return $gameplay;
    }

    private function handleKicks(array $game, string $quarter, Team $offTeam, ?int $yardsToTD, ?int $offTeamScore, ?int $defTeamScore): ?array
    {
        $gameController = new GameController($this->pdo, $this->log);
        $leagueController = new LeagueController($this->pdo, $this->log);
        $coachingController = new CoachingController($this->pdo, $this->log);
        $specialCalc = new SpecialTeamsCalculation($this->pdo, $this->log);
        $gameplay = array();
        $coaching1st = $coachingController->getGeneralCoachingFromTeam($offTeam, $offTeam->getGameplanOff(), '1st');
        $coaching2nd = $coachingController->getGeneralCoachingFromTeam($offTeam, $offTeam->getGameplanOff(), '2nd');

        // Wichtig für Two Point Conversion (nach TD)
        if (isset($offTeamScore, $defTeamScore)) {
            $twoPtCon = explode(';', $coaching1st->getGameplay2())[1];
            $is2PtCon = $specialCalc->isTwoPtCon($twoPtCon, $offTeamScore, $defTeamScore);
            if (!$is2PtCon && isset($offTeamScore, $defTeamScore)) {
                // PAT
                $kickingPlayers = $gameController->getStartingEleven($offTeam, 'Special');
                $gameplayResult = $specialCalc->fieldGoal($game['id'], $kickingPlayers, 15, 'isPAT');

                $isFG = array_pop($gameplayResult);
                if ($isFG) {
                    $leagueController->updateScore($game, $offTeam, $quarter, 1);
                }
            }
        }

        // Wichtig für Fourth Down (nur wenn Down == 4th)
        if (isset($yardsToTD)) {
            $fourthDown = explode(';', $coaching2nd->getGameplay1())[1];
            $isHome = $game['home'] == $offTeam->getName();
            $standings = $leagueController->getStandings($game);
            $playFourthDown = $specialCalc->isFourthDown($fourthDown, $yardsToTD, $standings['score'], $isHome);
            if (!$playFourthDown & isset($yardsToTD)) {
                // Field Goal
                $fieldGoalRange = explode(';', $coaching1st->getGameplay1())[1];
                if ($yardsToTD <= $fieldGoalRange) {
                    $kickingPlayers = $gameController->getStartingEleven($offTeam, 'Special');
                    $gameplayResult = $specialCalc->fieldGoal($game['id'], $kickingPlayers, $yardsToTD);

                    // if field goal is too short, result just has one element
                    if (count($gameplayResult) > 1) {
                        $isFG = array_pop($gameplayResult);
                        if ($isFG) {
                            $leagueController->updateScore($game, $offTeam, $quarter, 3);
                        }
                    }
                }
            }
        }

        $this->log->debug('Kicks gameplayResult: ' . print_r($gameplayResult ?? 'null', true));
        if (isset($gameplayResult)) {
            $yardsToTD = array_key_first($gameplayResult);
            $gameplay['isFG'] = $isFG ?? false;
            $gameplay['yardsToTD'] = $yardsToTD;
            $gameplay['gametext'] = $gameplayResult[$yardsToTD];
            // YardsToFirstDown darf nicht null sein, da KickOff folgt wird der Wert auf Standard gesetzt.
            $gameplay['yardsToFirstDown'] = 10;
            $gameplay['down'] = '1st';
            return $gameplay;
        }

        return null;
    }

    private function handleKickOff(int $gameId, int $playClock, Team $offTeam, Team $defTeam): array
    {
        $gameplay = array();
        $gameController = new GameController($this->pdo, $this->log);
        $specialCalc = new SpecialTeamsCalculation($this->pdo, $this->log);

        $kickingPlayers = $gameController->getStartingEleven($defTeam, 'Special');
        $returningPlayers = $gameController->getStartingEleven($offTeam, 'Special');
        $gameplayResult = $specialCalc->kickOff($gameId, $kickingPlayers, $returningPlayers);

        $kickingYards = array_key_first($gameplayResult);
        $yardsToTD = array_key_last($gameplayResult);

        $distance = $kickingYards >= 65 ? 0 : (100 - (65 - $kickingYards)) - $yardsToTD;
        $distance = $distance == 0 ? 1 : $distance;

        $playClock -= (2 + floor($distance / 5));

        $gameplay['quarter'] = 1;
        $gameplay['playClock'] = $playClock;
        $gameplay['yardsToTD'] = $yardsToTD;
        $gameplay['yardsToFirstDown'] = 10;
        $gameplay['down'] = '1st';
        $gameplay['gametext'] = $gameplayResult[$kickingYards] . ';' . $gameplayResult[$yardsToTD];

        return $gameplay;
    }
}