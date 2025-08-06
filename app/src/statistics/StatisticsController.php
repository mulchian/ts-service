<?php


namespace touchdownstars\statistics;


use PDO;
use Monolog\Logger;
use touchdownstars\main\MainController;
use touchdownstars\player\Player;
use touchdownstars\team\Team;

class StatisticsController
{
    private Logger $log;
    private PDO $pdo;

    public function __construct(PDO $pdo, Logger $log)
    {
        $this->pdo = $pdo;
        $this->log = $log;
    }

    public function fetchStatisticsForPlayer(int $idPlayer): array
    {
        $selectStmt = $this->pdo->prepare('SELECT * FROM `t_statistics_player` where idPlayer = :idPlayer');
        $selectStmt->execute(['idPlayer' => $idPlayer]);
        $selectStmt->setFetchMode(PDO::FETCH_CLASS, 'touchdownstars\\statistics\\StatisticsPlayer');
        $result = $selectStmt->fetchAll(PDO::FETCH_CLASS, 'touchdownstars\\statistics\\StatisticsPlayer');
        // $this->log->debug('fetched statistics for player ' . $idPlayer . ': ' . print_r($result, true));
        if ($result) {
            return $result;
        }
        return array();
    }

    public function fetchStatisticsForTeam(int $idTeam): array
    {
        $selectStmt = $this->pdo->prepare('SELECT * FROM `t_statistics_team` where idTeam = :idTeam');
        $selectStmt->execute(['idTeam' => $idTeam]);
        $selectStmt->setFetchMode(PDO::FETCH_CLASS, 'touchdownstars\\statistics\\StatisticsTeam');
        $result = $selectStmt->fetchAll(PDO::FETCH_CLASS, 'touchdownstars\\statistics\\StatisticsTeam');
        if ($result) {
            return $result;
        }
        return array();
    }

    public function getStatisticsForSeason(array $statistics, int $season): ?array
    {
        $filteredStatistics = array_values(array_filter($statistics, function (StatisticsPlayer $statistics) use ($season) {
            return $statistics->getSeason() == $season;
        }));
        if (count($filteredStatistics) > 0) {
            return $filteredStatistics;
        }
        return null;
    }

    private function getSeason(): int
    {
        if (isset($_SESSION['season'])) {
            $season = $_SESSION['season'];
        } else {
            $mainController = new MainController($this->pdo, $this->log);
            $seasonAndGameday = $mainController->fetchSeasonAndGameday();
            $season = $seasonAndGameday->getSeason();
        }
        return $season;
    }

    public function getStatisticsForGame(int $gameId, array $statistics, int $idPlayer): StatisticsPlayer
    {
        $season = $this->getSeason();
        // $this->log->debug('Statistics for player ' . $idPlayer . ': ' . print_r($statistics, true));
        if (!empty($statistics)) {
            $filteredStatistics = array_values(array_filter($statistics, function (StatisticsPlayer $statistics) use ($season, $gameId) {
                return $statistics->getSeason() == $season && $statistics->getGameId() == $gameId;
            }));
        }
        if (isset($filteredStatistics) && count($filteredStatistics) > 0) {
            return $filteredStatistics[0];
        } else {
            $statistics = new StatisticsPlayer();
            $statistics->setSeason($season);
            $statistics->setGameId($gameId);
            $statistics->setIdPlayer($idPlayer);
            return $statistics;
        }
    }

    private function setPlayersStatistics(Player $player, StatisticsPlayer $statistics): void
    {
        $statisticsId = $this->saveStatisticsForPlayer($statistics, $player->getId());
        $this->log->debug('Statistics saved for player ' . $player->getId() . ' with id ' . $statisticsId);

        if (null === $player->getStatistics() || count($player->getStatistics()) < 1) {
            $player->setStatistics(array($statistics));
        } else {
            $reStatistics = $player->getStatistics();
            $key = array_key_first(array_filter($reStatistics, function (StatisticsPlayer $playersStatistics) use ($statistics) {
                return $playersStatistics->getSeason() === $statistics->getSeason() && $playersStatistics->getGameId() === $statistics->getGameId();
            }));
            $reStatistics[$key] = $statistics;
            $player->setStatistics($reStatistics);
        }
    }

    private function saveStatisticsForPlayer(StatisticsPlayer $statistics, int $idPlayer): int
    {
        $selectStmt = $this->pdo->prepare('SELECT id from `t_statistics_player` where idPlayer = :idPlayer and season = :season and gameId = :gameId;');
        $selectStmt->execute(['idPlayer' => $idPlayer, 'season' => $statistics->getSeason(), 'gameId' => $statistics->getGameId()]);
        $result = $selectStmt->fetch(PDO::FETCH_ASSOC);
        $id = $result ? $result['id'] : null;

        $saveStatistics = 'INSERT INTO `t_statistics_player` (id, season, gameId, paCpl, paAtt, paPct, paYds, paAvg, paTd, paInt, sck, paRtg, ruAtt, 
                                   ruYds, ruAvg, ruTd, fum, rec, reYds, reAvg, reTd, reYdsAC, reYdsACAvg, ovrTd, sckA, tkl, tfl, tflYds, sckMade, 
                                   sckYds, defAvg, sft, defl, ff, intcept, intYds, intTd, fumRec, fumYds, fumTd, fgAtt, fgMade, fgLong, xpAtt, xpMade, 
                                   puntAtt, puntYds, puntAvg, krAtt, krYds, krAvg, krTd, prAtt, prYds, prAvg, prTd, penalty, penaltyYds, idPlayer) 
                            VALUES (:id, :season, :gameId, :paCpl, :paAtt, :paPct, :paYds, :paAvg, :paTd, :paInt, :sck, :paRtg, :ruAtt, :ruYds, :ruAvg, 
                                    :ruTd, :fum, :rec, :reYds, :reAvg, :reTd, :reYdsAC, :reYdsACAvg, :ovrTd, :sckA, :tkl, :tfl, :tflYds, :sckMade, 
                                    :sckYds, :defAvg, :sft, :defl, :ff, :intcept, :intYds, :intTd, :fumRec, :fumYds, :fumTd, :fgAtt, :fgMade, :fgLong, 
                                    :xpAtt, :xpMade, :puntAtt, :puntYds, :puntAvg, :krAtt, :krYds, :krAvg, :krTd, :prAtt, :prYds, :prAvg, :prTd, 
                                    :penalty, :penaltyYds, :idPlayer) 
                            ON DUPLICATE KEY UPDATE paCpl = :newPaCpl, paAtt = :newPaAtt, paPct = :newPaPct, paYds = :newPaYds, paAvg = :newPaAvg, paTd = :newPaTd, 
                                                    paInt = :newPaInt, sck = :newSck, paRtg = :newPaRtg, ruAtt = :newRuAtt, ruYds = :newRuYds, ruAvg = :newRuAvg, 
                                                    ruTd = :newRuTd, fum = :newFum, rec = :newRec, reYds = :newReYds, reAvg = :newReAvg, reTd = :newReTd, 
                                                    reYdsAC = :newReYdsAC, reYdsACAvg = :newReYdsACAvg, ovrTd = :newOvrTd, sckA = :newSckA, tkl = :newTkl, 
                                                    tfl = :newTfl, tflYds = :newTflYds, sckMade = :newSckMade, sckYds = :newSckYds, defAvg = :newDefAvg, 
                                                    sft = :newSft, defl = :newDefl, ff = :newFf, intcept = :newIntcept, intYds = :newIntYds, intTd = :newIntTd, 
                                                    fumRec = :newFumRec, fumYds = :newFumYds, fumTd = :newFumTd, fgAtt = :newFgAtt, fgMade = :newFgMade, 
                                                    fgLong = :newFgLong, xpAtt = :newXpAtt, xpMade = :newXpMade, puntAtt = :newPuntAtt, puntYds = :newPuntYds, 
                                                    puntAvg = :newPuntAvg, krAtt = :newKrAtt, krYds = :newKrYds, krAvg = :newKrAvg, krTd = :newKrTd, 
                                                    prAtt = :newPrAtt, prYds = :newPrYds, prAvg = :newPrAvg, prTd = :newPrTd, penalty = :newPenalty, penaltyYds = :newPenaltyYds;';
        $saveStmt = $this->pdo->prepare($saveStatistics);
        $saveStmt->execute([
            'id' => $id ?? null,
            'season' => $statistics->getSeason(),
            'gameId' => $statistics->getGameId(),
            'paCpl' => $statistics->getPaCpl(),
            'paAtt' => $statistics->getPaAtt(),
            'paPct' => $statistics->getPaPct(),
            'paYds' => $statistics->getPaYds(),
            'paAvg' => $statistics->getPaAvg(),
            'paTd' => $statistics->getPaTd(),
            'paInt' => $statistics->getPaInt(),
            'sck' => $statistics->getSck(),
            'paRtg' => $statistics->getPaRtg(),
            'ruAtt' => $statistics->getRuAtt(),
            'ruYds' => $statistics->getRuYds(),
            'ruAvg' => $statistics->getRuAvg(),
            'ruTd' => $statistics->getRuTd(),
            'fum' => $statistics->getFum(),
            'rec' => $statistics->getRec(),
            'reYds' => $statistics->getReYds(),
            'reAvg' => $statistics->getReAvg(),
            'reTd' => $statistics->getReTd(),
            'reYdsAC' => $statistics->getReYdsAC(),
            'reYdsACAvg' => $statistics->getReYdsACAvg(),
            'ovrTd' => $statistics->getOvrTd(),
            'sckA' => $statistics->getSckA(),
            'tkl' => $statistics->getTkl(),
            'tfl' => $statistics->getTfl(),
            'tflYds' => $statistics->getTflYds(),
            'sckMade' => $statistics->getSckMade(),
            'sckYds' => $statistics->getSckYds(),
            'defAvg' => $statistics->getDefAvg(),
            'sft' => $statistics->getSft(),
            'defl' => $statistics->getDefl(),
            'ff' => $statistics->getFf(),
            'intcept' => $statistics->getIntcept(),
            'intYds' => $statistics->getIntYds(),
            'intTd' => $statistics->getIntTd(),
            'fumRec' => $statistics->getFumRec(),
            'fumYds' => $statistics->getFumYds(),
            'fumTd' => $statistics->getFumTd(),
            'fgAtt' => $statistics->getFgAtt(),
            'fgMade' => $statistics->getFgMade(),
            'fgLong' => $statistics->getFgLong(),
            'xpAtt' => $statistics->getXpAtt(),
            'xpMade' => $statistics->getXpMade(),
            'puntAtt' => $statistics->getPuntAtt(),
            'puntYds' => $statistics->getPuntYds(),
            'puntAvg' => $statistics->getPuntAvg(),
            'krAtt' => $statistics->getKrAtt(),
            'krYds' => $statistics->getKrYds(),
            'krAvg' => $statistics->getKrAvg(),
            'krTd' => $statistics->getKrTd(),
            'prAtt' => $statistics->getPrAtt(),
            'prYds' => $statistics->getPrYds(),
            'prAvg' => $statistics->getPrAvg(),
            'prTd' => $statistics->getPrTd(),
            'penalty' => $statistics->getPenalty(),
            'penaltyYds' => $statistics->getPenaltyYds(),
            'idPlayer' => $idPlayer,
            'newPaCpl' => $statistics->getPaCpl(),
            'newPaAtt' => $statistics->getPaAtt(),
            'newPaPct' => $statistics->getPaPct(),
            'newPaYds' => $statistics->getPaYds(),
            'newPaAvg' => $statistics->getPaAvg(),
            'newPaTd' => $statistics->getPaTd(),
            'newPaInt' => $statistics->getPaInt(),
            'newSck' => $statistics->getSck(),
            'newPaRtg' => $statistics->getPaRtg(),
            'newRuAtt' => $statistics->getRuAtt(),
            'newRuYds' => $statistics->getRuYds(),
            'newRuAvg' => $statistics->getRuAvg(),
            'newRuTd' => $statistics->getRuTd(),
            'newFum' => $statistics->getFum(),
            'newRec' => $statistics->getRec(),
            'newReYds' => $statistics->getReYds(),
            'newReAvg' => $statistics->getReAvg(),
            'newReTd' => $statistics->getReTd(),
            'newReYdsAC' => $statistics->getReYdsAC(),
            'newReYdsACAvg' => $statistics->getReYdsACAvg(),
            'newOvrTd' => $statistics->getOvrTd(),
            'newSckA' => $statistics->getSckA(),
            'newTkl' => $statistics->getTkl(),
            'newTfl' => $statistics->getTfl(),
            'newTflYds' => $statistics->getTflYds(),
            'newSckMade' => $statistics->getSckMade(),
            'newSckYds' => $statistics->getSckYds(),
            'newDefAvg' => $statistics->getDefAvg(),
            'newSft' => $statistics->getSft(),
            'newDefl' => $statistics->getDefl(),
            'newFf' => $statistics->getFf(),
            'newIntcept' => $statistics->getIntcept(),
            'newIntYds' => $statistics->getIntYds(),
            'newIntTd' => $statistics->getIntTd(),
            'newFumRec' => $statistics->getFumRec(),
            'newFumYds' => $statistics->getFumYds(),
            'newFumTd' => $statistics->getFumTd(),
            'newFgAtt' => $statistics->getFgAtt(),
            'newFgMade' => $statistics->getFgMade(),
            'newFgLong' => $statistics->getFgLong(),
            'newXpAtt' => $statistics->getXpAtt(),
            'newXpMade' => $statistics->getXpMade(),
            'newPuntAtt' => $statistics->getPuntAtt(),
            'newPuntYds' => $statistics->getPuntYds(),
            'newPuntAvg' => $statistics->getPuntAvg(),
            'newKrAtt' => $statistics->getKrAtt(),
            'newKrYds' => $statistics->getKrYds(),
            'newKrAvg' => $statistics->getKrAvg(),
            'newKrTd' => $statistics->getKrTd(),
            'newPrAtt' => $statistics->getPrAtt(),
            'newPrYds' => $statistics->getPrYds(),
            'newPrAvg' => $statistics->getPrAvg(),
            'newPrTd' => $statistics->getPrTd(),
            'newPenalty' => $statistics->getPenalty(),
            'newPenaltyYds' => $statistics->getPenaltyYds()
        ]);

        return $this->pdo->lastInsertId();
    }

    public function getTeamStatisticsForGame(int $gameId, array $statistics, int $idTeam): StatisticsTeam
    {
        $season = $this->getSeason();
        $filteredStatistics = array_values(array_filter($statistics, function (StatisticsTeam $statistics) use ($season, $gameId) {
            return $statistics->getSeason() == $season && $statistics->getGameId() == $gameId;
        }));
        if (count($filteredStatistics) > 0) {
            return $filteredStatistics[0];
        } else {
            $statistics = new StatisticsTeam();
            $statistics->setSeason($season);
            $statistics->setGameId($gameId);
            $statistics->setIdTeam($idTeam);
            return $statistics;
        }
    }

    private function setTeamStatistics(Team $team, StatisticsTeam $statistics): void
    {
        $statisticsId = $this->saveStatisticsForTeam($statistics, $team->getId());
        $this->log->debug('Statistics saved for team ' . $team->getId() . ' with id ' . $statisticsId);

        if (null === $team->getStatistics() || count($team->getStatistics()) < 1) {
            $team->setStatistics(array($statistics));
        } else {
            $reStatistics = $team->getStatistics();
            $key = array_key_first(array_filter($reStatistics, function (StatisticsTeam $teamStatistics) use ($statistics) {
                return $teamStatistics->getSeason() === $statistics->getSeason() && $teamStatistics->getGameId() === $statistics->getGameId();
            }));
            $reStatistics[$key] = $statistics;
            $team->setStatistics($reStatistics);
        }
    }

    private function saveStatisticsForTeam(StatisticsTeam $statistics, int $idTeam): int
    {
        $selectStmt = $this->pdo->prepare('SELECT id from `t_statistics_team` where idTeam = :idTeam and season = :season and gameId = :gameId;');
        $selectStmt->execute(['idTeam' => $idTeam, 'season' => $statistics->getSeason(), 'gameId' => $statistics->getGameId()]);
        $id = $selectStmt->fetch(PDO::FETCH_ASSOC)['id'];

        $saveStatistics = 'INSERT INTO `t_statistics_team` (id, season, gameId, paAtt, paYds, paTd, ruAtt, ruYds, ruTd, firstDowns, firstDownsComp, 
                                 secondDowns, secondDownsComp, thirdDowns, thirdDownsComp, fourthDowns, fourthDownsComp, sacks, punts, fumbles, 
                                 lostFumbles, interceptions, timeOfPossession, idTeam) 
                            VALUES (:id, :season, :gameId, :paAtt, :paYds, :paTd, :ruAtt, :ruYds, :ruTd, :firstDowns, :firstDownsComp, :secondDowns, 
                                    :secondDownsComp, :thirdDowns, :thirdDownsComp, :fourthDowns, :fourthDownsComp, :sacks, :punts, :fumbles, 
                                    :lostFumbles, :interceptions, :timeOfPossession, :idTeam)
                            ON DUPLICATE KEY UPDATE paAtt = :newPaAtt, paYds = :newPaYds, paTd = :newPaTd, ruAtt = :newRuAtt, ruYds = :newRuYds, 
                                                    ruTd = :newRuTd, firstDowns = :newFirstDowns, firstDownsComp = :newFirstDownsComp, 
                                                    secondDowns = :newSecondDowns, secondDownsComp = :newSecondDownsComp, 
                                                    thirdDowns = :newThirdDowns, thirdDownsComp = :newThirdDownsComp, 
                                                    fourthDowns = :newFourthDowns, fourthDownsComp = :newFourthDownsComp, 
                                                    sacks = :newSacks, punts = :newPunts, fumbles = :newFumbles, lostFumbles = :newLostFumbles, 
                                                    interceptions = :newInterceptions, timeOfPossession = :newTimeOfPossession;';
        $saveStmt = $this->pdo->prepare($saveStatistics);
        $saveStmt->execute([
            'id' => $id ?? null,
            'season' => $statistics->getSeason(),
            'gameId' => $statistics->getGameId(),
            'paAtt' => $statistics->getPaAtt(),
            'paYds' => $statistics->getPaYds(),
            'paTd' => $statistics->getPaTd(),
            'ruAtt' => $statistics->getRuAtt(),
            'ruYds' => $statistics->getRuYds(),
            'ruTd' => $statistics->getRuTd(),
            'firstDowns' => $statistics->getFirstDowns(),
            'firstDownsComp' => $statistics->getFirstDownsComp(),
            'secondDowns' => $statistics->getSecondDowns(),
            'secondDownsComp' => $statistics->getSecondDownsComp(),
            'thirdDowns' => $statistics->getThirdDowns(),
            'thirdDownsComp' => $statistics->getThirdDownsComp(),
            'fourthDowns' => $statistics->getFourthDowns(),
            'fourthDownsComp' => $statistics->getFourthDownsComp(),
            'sacks' => $statistics->getSacks(),
            'punts' => $statistics->getPunts(),
            'fumbles' => $statistics->getFumbles(),
            'lostFumbles' => $statistics->getLostFumbles(),
            'interceptions' => $statistics->getInterceptions(),
            'timeOfPossession' => $statistics->getTimeOfPossession(),
            'idTeam' => $idTeam,
            'newPaAtt' => $statistics->getPaAtt(),
            'newPaYds' => $statistics->getPaYds(),
            'newPaTd' => $statistics->getPaTd(),
            'newRuAtt' => $statistics->getRuAtt(),
            'newRuYds' => $statistics->getRuYds(),
            'newRuTd' => $statistics->getRuTd(),
            'newFirstDowns' => $statistics->getFirstDowns(),
            'newFirstDownsComp' => $statistics->getFirstDownsComp(),
            'newSecondDowns' => $statistics->getSecondDowns(),
            'newSecondDownsComp' => $statistics->getSecondDownsComp(),
            'newThirdDowns' => $statistics->getThirdDowns(),
            'newThirdDownsComp' => $statistics->getThirdDownsComp(),
            'newFourthDowns' => $statistics->getFourthDowns(),
            'newFourthDownsComp' => $statistics->getFourthDownsComp(),
            'newSacks' => $statistics->getSacks(),
            'newPunts' => $statistics->getPunts(),
            'newFumbles' => $statistics->getFumbles(),
            'newLostFumbles' => $statistics->getLostFumbles(),
            'newInterceptions' => $statistics->getInterceptions(),
            'newTimeOfPossession' => $statistics->getTimeOfPossession()
        ]);

        return $this->pdo->lastInsertId();
    }

    //-----------------------------------------------------------------
    // Special-Teams-Statistiken
    //-----------------------------------------------------------------

    public function saveKickreturn(int $gameId, Player $returner, int $yardsToTD, int $returnDistance): void
    {
        $this->log->debug('saveKickreturn: ' . $returner->getId() . ' ' . $returnDistance . ' ' . $yardsToTD);
        // krAtt, krYds, krAvg, krTd
        $statistics = $this->getStatisticsForGame($gameId, $returner->getStatistics() ?? array(), $returner->getId());

        $isKrTd = ($yardsToTD - $returnDistance) <= 0;
        $correctedReturnDistance = $isKrTd ? $yardsToTD : $returnDistance;

        $statistics->setKrAtt($statistics->getKrAtt() + 1);
        $statistics->setKrYds($statistics->getKrYds() + $correctedReturnDistance);
        $krTD = $isKrTd ? ($statistics->getKrTd() + 1) : $statistics->getKrTd();
        $statistics->setKrTd($krTD);
        $statistics->setKrAvg(floor($statistics->getKrYds() / $statistics->getKrAtt()));

        $this->setPlayersStatistics($returner, $statistics);
    }

    public function savePuntreturn(int $gameId, Player $returner, int $yardsToTD, int $returnDistance): void
    {
        // prAtt, prYds, prAvg, prTd
        $statistics = $this->getStatisticsForGame($gameId, $returner->getStatistics() ?? array(), $returner->getId());

        $isPrTd = ($yardsToTD - $returnDistance) <= 0;
        $correctedReturnDistance = $isPrTd ? $yardsToTD : $returnDistance;

        $statistics->setPrAtt($statistics->getPrAtt() + 1);
        $statistics->setPrYds($statistics->getPrYds() + $correctedReturnDistance);
        $prTD = $isPrTd ? ($statistics->getPrTd() + 1) : $statistics->getPrTd();
        $statistics->setPrTd($prTD);
        $statistics->setPrAvg(floor($statistics->getPrYds() / $statistics->getPrAtt()));

        $this->setPlayersStatistics($returner, $statistics);
    }

    public function savePunt(int $gameId, Team $puntTeam, Player $punter, int $puntDistance): void
    {
        // puntAtt, puntYds, puntAvg
        $statistics = $this->getStatisticsForGame($gameId, $punter->getStatistics() ?? array(), $punter->getId());
        $teamStatistics = $this->getTeamStatisticsForGame($gameId, $puntTeam->getStatistics() ?? array(), $puntTeam->getId());

        $statistics->setPuntAtt($statistics->getPuntAtt() + 1);
        $statistics->setPuntYds($statistics->getPuntYds() + $puntDistance);
        $statistics->setPuntAvg(floor($statistics->getPuntYds() / $statistics->getPuntAtt()));

        $teamStatistics->setPunts($teamStatistics->getPunts() + 1);

        $this->setPlayersStatistics($punter, $statistics);
        $this->setTeamStatistics($puntTeam, $teamStatistics);
    }

    public function saveKick(int $gameId, Player $kicker, int $kickDistance, bool $isTD, bool $isPAT = false): void
    {
        // fgAtt, fgMade, fgLong (längstes je getroffenes FG), xpAtt, xpMade
        $statistics = $this->getStatisticsForGame($gameId, $kicker->getStatistics() ?? array(), $kicker->getId());

        if ($isPAT) {
            $statistics->setXpAtt($statistics->getXpAtt() + 1);
            if ($isTD) {
                $statistics->setXpMade($statistics->getXpMade() + 1);
            }
        } else {
            $statistics->setFgAtt($statistics->getFgAtt() + 1);
            if ($isTD) {
                $statistics->setFgMade($statistics->getFgMade() + 1);
                if ($statistics->getFgLong() < $kickDistance) {
                    $statistics->setFgLong($kickDistance);
                }
            }
        }

        $this->setPlayersStatistics($kicker, $statistics);
    }

    //-----------------------------------------------------------------
    // Run-Statistiken
    //-----------------------------------------------------------------

    public function saveRun(int $gameId, Team $team, Player $runner, int $runDistance, bool $isTD, bool $isFumble = false): void
    {
        // ruAtt, ruYds, ruAvg, ruTd, ovrTd, fum
        $statistics = $this->getStatisticsForGame($gameId, $runner->getStatistics() ?? array(), $runner->getId());
        // ruAtt, ruYds, ruTd, fumbles
        $teamStatistics = $this->getTeamStatisticsForGame($gameId, $team->getStatistics() ?? array(), $team->getId());

        $statistics->setRuAtt($statistics->getRuAtt() + 1);
        $statistics->setRuYds($statistics->getRuYds() + $runDistance);
        $statistics->setRuAvg(floor($statistics->getRuYds() / $statistics->getRuAtt()));

        $teamStatistics->setRuAtt($teamStatistics->getRuAtt() + 1);
        $teamStatistics->setRuYds($teamStatistics->getRuYds() + $runDistance);

        if ($isTD) {
            $statistics->setRuTd($statistics->getRuTd() + 1);
            $statistics->setOvrTd($statistics->getOvrTd() + 1);
            $teamStatistics->setRuTd($teamStatistics->getRuTd() + 1);
        }

        if ($isFumble) {
            $statistics->setFum($statistics->getFum() + 1);
            $teamStatistics->setFumbles($teamStatistics->getFumbles() + 1);
        }

        $this->setPlayersStatistics($runner, $statistics);
        $this->setTeamStatistics($team, $teamStatistics);
    }

    public function saveRunDef(int $gameId, Player $runDefender, bool $isTkl, bool $isTfl, int $tflYds, bool $isForcedFumble = false): void
    {
        // tkl, tfl, tflYds, defAvg, ff
        $statistics = $this->getStatisticsForGame($gameId, $runDefender->getStatistics() ?? array(), $runDefender->getId());

        if ($isTkl) {
            $statistics->setTkl($statistics->getTkl() + 1);
        }

        if ($isTfl) {
            $statistics->setTfl($statistics->getTfl() + 1);
            $statistics->setTflYds($statistics->getTflYds() + $tflYds);
            $statistics->setDefAvg($statistics->getDefAvg() + $tflYds);
        }

        if ($isForcedFumble) {
            $statistics->setFf($statistics->getFf() + 1);
        }

        $this->setPlayersStatistics($runDefender, $statistics);
    }

    //-----------------------------------------------------------------
    // Fumble-Statistiken
    //-----------------------------------------------------------------

    public function saveFumble(int $gameId, Team $fumblingTeam, Player $defender, $fumYds, $yardsToTD, $isFumTD): void
    {
        // fumRec, fumYds, fumTd
        $statistics = $this->getStatisticsForGame($gameId, $defender->getStatistics() ?? array(), $defender->getId());
        // lostFumbles
        $teamStatistics = $this->getTeamStatisticsForGame($gameId, $fumblingTeam->getStatistics() ?? array(), $fumblingTeam->getId());

        $fumYds = $yardsToTD - $fumYds < 0 ? $yardsToTD : $fumYds;
        $statistics->setFumRec($statistics->getFumRec() + 1);
        $statistics->setFumYds($statistics->getFumYds() + $fumYds);
        $teamStatistics->setLostFumbles($teamStatistics->getLostFumbles() + 1);

        if ($isFumTD) {
            $statistics->setFumTd($statistics->getFumTd() + 1);
        }

        $this->setPlayersStatistics($defender, $statistics);
        $this->setTeamStatistics($fumblingTeam, $teamStatistics);
    }

    //-----------------------------------------------------------------
    // Passing-Statistiken
    //-----------------------------------------------------------------

    public function saveSack(int $gameId, array $offEleven, Team $defTeam, Player $quarterback, Player $defender, int $sckYds, bool $isSafety, string $lineupDef): void
    {
        $oLiner = array_values(array_filter($offEleven, function (Player $player) use ($defender, $lineupDef) {
            // sck (QB), sckMade, sckYds, defAvg, sft, sackA (OLine)
            $gameplayOffVsDef = array(
                'NT' => array(
                    'DT' => 'RG',
                    'NT' => 'LG',
                    'MLB1' => 'C',
                    'RE' => 'LT',
                    'LE' => 'RT',
                    'ROLB' => 'LT',
                    'LOLB' => 'RT'
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
            $lineupPosition = $player->getLineupPosition();
            return $lineupPosition == $gameplayOffVsDef[$lineupDef][$defender->getLineupPosition()];
        }))[0];

        $qbStatistics = $this->getStatisticsForGame($gameId, $quarterback->getStatistics() ?? array(), $quarterback->getId());
        $defStatistics = $this->getStatisticsForGame($gameId, $defender->getStatistics() ?? array(), $defender->getId());
        $teamStatistics = $this->getTeamStatisticsForGame($gameId, $defTeam->getStatistics() ?? array(), $defTeam->getId());

        $qbStatistics->setSck($qbStatistics->getSck() + 1);
        $defStatistics->setSckMade($defStatistics->getSckMade() + 1);
        $defStatistics->setSckYds($defStatistics->getSckYds() + $sckYds);
        $defStatistics->setDefAvg($defStatistics->getDefAvg() + $sckYds);
        if ($isSafety) {
            $defStatistics->setSft($defStatistics->getSft() + 1);
        }
        $teamStatistics->setSacks($teamStatistics->getSacks() + 1);

        if (isset($oLiner)) {
            $oLineStatistics = $this->getStatisticsForGame($gameId, $oLiner->getStatistics() ?? array(), $oLiner->getId());
            $oLineStatistics->setSckA($oLineStatistics->getSckA() + 1);
            $this->setPlayersStatistics($oLiner, $oLineStatistics);
        }

        $this->setPlayersStatistics($quarterback, $qbStatistics);
        $this->setPlayersStatistics($defender, $defStatistics);
        $this->setTeamStatistics($defTeam, $teamStatistics);
    }

    public function saveDeflection(int $gameId, Player $deflectingPlayer): void
    {
        // defl
        $defStatistics = $this->getStatisticsForGame($gameId, $deflectingPlayer->getStatistics() ?? array(), $deflectingPlayer->getId());

        $defStatistics->setDefl($defStatistics->getDefl() + 1);

        $this->setPlayersStatistics($deflectingPlayer, $defStatistics);
    }

    public function saveInterception(int $gameId, Team $defTeam, Player $quarterback, Player $interceptingPlayer, int $interceptionYds, bool $isTd): void
    {
        // paInt (QB), intcept, intYds
        $qbStatistics = $this->getStatisticsForGame($gameId, $quarterback->getStatistics() ?? array(), $quarterback->getId());
        $defStatistics = $this->getStatisticsForGame($gameId, $interceptingPlayer->getStatistics() ?? array(), $interceptingPlayer->getId());
        // intcept for intercepting Team (DefTeam)
        $teamStatistics = $this->getTeamStatisticsForGame($gameId, $defTeam->getStatistics() ?? array(), $defTeam->getId());

        $qbStatistics->setPaInt($qbStatistics->getPaInt() + 1);
        $defStatistics->setIntcept($defStatistics->getIntcept() + 1);
        $defStatistics->setIntYds($defStatistics->getIntYds() + $interceptionYds);
        if ($isTd) {
            $defStatistics->setIntTd($defStatistics->getIntTd() + 1);
        }
        $teamStatistics->setInterceptions($teamStatistics->getInterceptions() + 1);

        $this->setPlayersStatistics($quarterback, $qbStatistics);
        $this->setPlayersStatistics($interceptingPlayer, $defStatistics);
        $this->setTeamStatistics($defTeam, $teamStatistics);
    }

    public function saveIncomplete(int $gameId, Player $quarterback): void
    {
        // paAtt
        $qbStatistics = $this->getStatisticsForGame($gameId, $quarterback->getStatistics() ?? array(), $quarterback->getId());
        $qbStatistics->setPaAtt($qbStatistics->getPaAtt() + 1);
        $this->setPlayersStatistics($quarterback, $qbStatistics);
    }

    public function savePass(int $gameId, Team $offTeam, Player $quarterback, Player $receiver, Player $defender, int $distance, int $ranDistance, bool $isTd): void
    {
        // QB: paCpl, paAtt, paYds
        // WR: rec, reYds, reYdsAC, reAvg, reYdsACAvg
        // Defender: tkl
        // passingTD: paTd (QB), ovrTd (QB), reTd (WR), ovrTd (WR)
        $qbStatistics = $this->getStatisticsForGame($gameId, $quarterback->getStatistics() ?? array(), $quarterback->getId());
        $recStatistics = $this->getStatisticsForGame($gameId, $receiver->getStatistics() ?? array(), $receiver->getId());
        $defStatistics = $this->getStatisticsForGame($gameId, $defender->getStatistics() ?? array(), $defender->getId());
        $teamStatistics = $this->getTeamStatisticsForGame($gameId, $offTeam->getStatistics() ?? array(), $offTeam->getId());

        $qbStatistics->setPaCpl($qbStatistics->getPaCpl() + 1);
        $qbStatistics->setPaAtt($qbStatistics->getPaAtt() + 1);
        $qbStatistics->setPaYds($qbStatistics->getPaYds() + $distance);

        $recStatistics->setRec($recStatistics->getRec() + 1);
        $recStatistics->setReYds($recStatistics->getReYds() + $distance);
        $recStatistics->setReYdsAC($recStatistics->getReYdsAC() + $ranDistance);
        $recStatistics->setReAvg($recStatistics->getReYds() / $recStatistics->getRec());
        $recStatistics->setReYdsACAvg($recStatistics->getReYdsAC() / $recStatistics->getRec());

        $defStatistics->setTkl($defStatistics->getTkl() + 1);

        $teamStatistics->setPaAtt($teamStatistics->getPaAtt() + 1);
        $teamStatistics->setPaYds($teamStatistics->getPaYds() + $distance);

        if ($isTd) {
            $qbStatistics->setPaTd($qbStatistics->getPaTd() + 1);
            $qbStatistics->setOvrTd($qbStatistics->getOvrTd() + 1);
            $recStatistics->setReTd($recStatistics->getReTd() + 1);
            $recStatistics->setOvrTd($recStatistics->getOvrTd() + 1);
            $teamStatistics->setPaTd($teamStatistics->getPaTd() + 1);
        }

        $this->setPlayersStatistics($quarterback, $qbStatistics);
        $this->setPlayersStatistics($receiver, $recStatistics);
        $this->setPlayersStatistics($defender, $defStatistics);
        $this->setTeamStatistics($offTeam, $teamStatistics);
    }

    /**
     * Speichert die zu berechnenden Statistiken für den Quarterback nach Ende des Spiels für den Gameday.
     * @param int $gameId
     * @param array $quarterbacks - QBs beider Teams zum Spielende
     * @return void
     */
    public function saveCalculatedStatistics(int $gameId, array $quarterbacks): void
    {
        foreach ($quarterbacks as $quarterback) {
            $qbStatistics = $this->getStatisticsForGame($gameId, $quarterback->getStatistics() ?? array(), $quarterback->getId());

            $paCpl = $qbStatistics->getPaCpl() > 0 ? $qbStatistics->getPaCpl() : 1;
            $paAtt = $qbStatistics->getPaAtt() > 0 ? $qbStatistics->getPaAtt() : 1;

            $qbStatistics->setPaPct($qbStatistics->getPaAtt() / $paCpl);
            $qbStatistics->setPaAvg($qbStatistics->getPaYds() / $paAtt);
            $qbStatistics->setPaRtg($this->calcPasserRating($qbStatistics));

            $this->setPlayersStatistics($quarterback, $qbStatistics);
        }
    }

    private function calcPasserRating(StatisticsPlayer $qb): float
    {
        $paAtt = $qb->getPaAtt() > 0 ? $qb->getPaAtt() : 1;
        $a = (($qb->getPaCpl() / $paAtt) - 0.3) * 5;
        $b = (($qb->getPaYds() / $paAtt) - 3) * 0.25;
        $c = ($qb->getPaTd() / $paAtt) * 20;
        $d = 2.375 - (($qb->getIntcept() / $paAtt) * 25);
        return (($a + $b + $c + $d) / 6) * 100;
    }

    //-----------------------------------------------------------------
    // Penalty-Statistiken
    //-----------------------------------------------------------------

    public function savePenalty(int $gameId, Team $team, Player $penaltyPlayer, int $penaltyYds): void
    {
        $statistics = $this->getStatisticsForGame($gameId, $penaltyPlayer->getStatistics() ?? array(), $penaltyPlayer->getId());
        $teamStatistics = $this->getTeamStatisticsForGame($gameId, $team->getStatistics() ?? array(), $team->getId());

        $statistics->setPenalty($statistics->getPenalty() + 1);
        $statistics->setPenaltyYds($statistics->getPenaltyYds() + $penaltyYds);
        $teamStatistics->setPenalties($teamStatistics->getPenalties() + 1);
        $teamStatistics->setPenaltyYds($teamStatistics->getPenaltyYds() + $penaltyYds);

        $this->setPlayersStatistics($penaltyPlayer, $statistics);
        $this->setTeamStatistics($team, $teamStatistics);
    }

    //-----------------------------------------------------------------
    // Time-Statistiken
    //-----------------------------------------------------------------
    public function saveTimeOfPossession(int $gameId, Team $team, int $gameplayTime): void
    {
        $teamStatistics = $this->getTeamStatisticsForGame($gameId, $team->getStatistics() ?? array(), $team->getId());

        $teamStatistics->setTop($teamStatistics->getTimeOfPossession() + $gameplayTime);

        $this->setTeamStatistics($team, $teamStatistics);
    }

    //-----------------------------------------------------------------
    // Down-Completion-Statistiken
    //-----------------------------------------------------------------
    public function saveDown(int $gameId, string $down, bool $isCompleted, Team $offTeam): void
    {
        $teamStatistics = $this->getTeamStatisticsForGame($gameId, $offTeam->getStatistics() ?? array(), $offTeam->getId());

        switch ($down) {
            case '1st':
                $teamStatistics->setFirstDowns($teamStatistics->getFirstDowns() + 1);
                if ($isCompleted) {
                    $teamStatistics->setFirstDownsComp($teamStatistics->getFirstDownsComp() + 1);
                }
                break;
            case '2nd':
                $teamStatistics->setSecondDowns($teamStatistics->getSecondDowns() + 1);
                if ($isCompleted) {
                    $teamStatistics->setSecondDownsComp($teamStatistics->getSecondDownsComp() + 1);
                }
                break;
            case '3rd':
                $teamStatistics->setThirdDowns($teamStatistics->getThirdDowns() + 1);
                if ($isCompleted) {
                    $teamStatistics->setThirdDownsComp($teamStatistics->getThirdDownsComp() + 1);
                }
                break;
            case '4th':
                $teamStatistics->setFourthDowns($teamStatistics->getFourthDowns() + 1);
                if ($isCompleted) {
                    $teamStatistics->setFourthDownsComp($teamStatistics->getFourthDownsComp() + 1);
                }
                break;
        }
        $this->setTeamStatistics($offTeam, $teamStatistics);
    }

}