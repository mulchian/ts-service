<?php


namespace touchdownstars\main;


use DateTime;
use Monolog\Logger;
use PDO;

class MainController
{
    private PDO $pdo;
    private Logger $log;

    public function __construct(PDO $pdo, Logger $log)
    {
        $this->pdo = $pdo;
        $this->log = $log;
    }

    public function fetchSeasonAndGameday(): Main
    {
        $this->log->debug('Fetching season and gameday');
        $selectStmt = $this->pdo->prepare('SELECT id, firstGameday AS firstGamedayString, 
            lastSeasonday AS lastSeasondayString, season, gameweek AS gameweekValue, 
            gameday, highestSalaryCap, startBudget, lastChanged AS lastChangedString 
            FROM `t_main` ORDER BY id DESC LIMIT 1');
        $selectStmt->execute();
        $selectStmt->setFetchMode(PDO::FETCH_CLASS, 'touchdownstars\\main\\Main');
        $main = $selectStmt->fetch(PDO::FETCH_CLASS);
//        $main = MAIN::fromData($result);
        $this->log->debug('Fetched main data', ['main-data' => $main]);

        $today = (new DateTime('now'))->setTime(0, 0);
        $this->log->debug('LastChanged: ' . $main->getLastChanged()->format('c'));
        $this->log->debug('Now: ' . $today->format('c'));

        if ((clone $main->getLastChanged())->setTime(0, 0) < $today) {
            if ((clone $main->getFirstGameday())->setTime(0, 0) < $today) {
                if ($main->getGameday() < 16) {
                    $main->setGameday($main->getGameday() + 1);
                }
            } else {
                $main->setGameday(0);
            }

            $mondayThisWeek = (clone $today)->modify('monday this week');
            if ($today == $mondayThisWeek) {
                $main->setGameweek(Gameweek::from($main->getGameweek()->value + 1));
            }

            if ($main->getLastSeasonday() < $today) {
                //TODO: Season-Wechsel
//              $main->setSeason($main->getSeason() + 1);
                $main->setSeason(1);
                $main->setGameday(0);
                $main->setGameweek(Gameweek::from(1));
                $main->setFirstGameday((new DateTime('now'))->modify('next monday')->setTime(0, 0));
                $main->setLastSeasonday((new DateTime('now'))->modify('+4 weeks sunday')->setTime(23, 59, 59));
            }

            $this->log->debug("Updating season and gameday.", ["main-data" => $main]);
            $this->saveSeasonAndGameday($main);
        }

        return $main;
    }

    private function saveSeasonAndGameday(Main $main): void
    {
        $selectStmt = $this->pdo->prepare('SELECT id from `t_main` where season = :season and firstGameday = :firstGameday and lastSeasonday = :lastSeasonday;');
        $selectStmt->execute(['season' => $main->getSeason(), 'firstGameday' => $main->getFirstGameday()->format('Y-m-d H:i:s'), 'lastSeasonday' => $main->getLastSeasonday()->format('Y-m-d H:i:s')]);
        $id = $selectStmt->fetch(PDO::FETCH_ASSOC)['id'];

        $saveMain = 'INSERT INTO `t_main` (id, firstGameday, lastSeasonday, season, gameweek, gameday, highestSalaryCap, startBudget, lastChanged) 
                        VALUES (:id, :firstGameday, :lastSeasonday, :season, :gameweek, :gameday, :highestSalaryCap, :startBudget, :lastChanged)
                        ON DUPLICATE KEY UPDATE season = :newSeason, gameweek = :newGameweek, gameday = :newGameday, lastChanged = :newLastChanged';
        $saveStmt = $this->pdo->prepare($saveMain);
        $saveStmt->execute(['id' => $id ?? null, 'firstGameday' => $main->getFirstGameday()->format('Y-m-d H:i:s'), 'lastSeasonday' => $main->getLastSeasonday()->format('Y-m-d H:i:s'), 'season' => $main->getSeason(), 'gameday' => $main->getGameday(), 'gameweek' => $main->getGameweek()->value, 'highestSalaryCap' => 150000000, 'startBudget' => 50000000, 'lastChanged' => $main->getLastChanged()->format('Y-m-d H:i:s'), 'newSeason' => $main->getSeason(), 'newGameday' => $main->getGameday(), 'newGameweek' => $main->getGameweek()->value, 'newLastChanged' => (new DateTime('now'))->format('Y-m-d H:i:s')]);
    }

}