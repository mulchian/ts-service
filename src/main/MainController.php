<?php


namespace touchdownstars\main;


use PDO;
use Monolog\Logger;

class MainController
{
    private PDO $pdo;
    private Logger $log;

    public function __construct(PDO $pdo, Logger $log)
    {
        $this->pdo = $pdo;
        $this->log = $log;
    }

    public function fetchSeasonAndGameday(): array
    {
        $selectStmt = $this->pdo->prepare('SELECT * FROM `t_main` where id = 1;');
        $selectStmt->execute();
        $result = $selectStmt->fetch(PDO::FETCH_ASSOC);

        $this->log->debug('LastChanged: ' . date('Y-m-d', $result['lastChanged']));
        $this->log->debug('now: ' . date('Y-m-d', time()));
        if (date('Y-m-d', $result['lastChanged']) < date('Y-m-d', time())) {
            if (date('Y-m-d', $result['firstGameday']) < date('Y-m-d', time())) {
                if ($result['gameday'] < 16) {
                    $result['gameday'] += 1;
                }
            } else {
                $result['gameday'] = 0;
            }

            if (date('Y-m-d', time()) == date('Y-m-d', strtotime('monday this week'))) {
                $result['gameweek'] += 1;
            }

            if (date('Y-m-d', $result['lastSeasonday']) < date('Y-m-d', time())) {
                //TODO: Season-Wechsel
//              $result['season'] += 1;
                $result['season'] = 1;
                $result['gameday'] = 0;
                $result['gameweek'] = 1;
                $result['firstGameday'] = strtotime('next monday');
                $result['lastSeasonday'] = strtotime('+3 weeks sunday 23:59:59');
            }

            $this->saveSeasonAndGameday($result);
        }

        return $result;
    }

    private function saveSeasonAndGameday(array $result): void
    {
        $selectStmt = $this->pdo->prepare('SELECT id from `t_main` where season = :season and firstGameday = :firstGameday and lastSeasonday = :lastSeasonday;');
        $selectStmt->execute(['season' => $result['season'], 'firstGameday' => $result['firstGameday'], 'lastSeasonday' => $result['lastSeasonday']]);
        $id = $selectStmt->fetch(PDO::FETCH_ASSOC)['id'];

        $saveMain = 'INSERT INTO `t_main` (id, firstGameday, lastSeasonday, season, gameweek, gameday, highestSalaryCap, startBudget, lastChanged) 
                        VALUES (:id, :firstGameday, :lastSeasonday, :season, :gameweek, :gameday, :highestSalaryCap, :startBudget, :lastChanged)
                        ON DUPLICATE KEY UPDATE season = :newSeason, gameweek = :newGameweek, gameday = :newGameday, lastChanged = :newLastChanged';
        $saveStmt = $this->pdo->prepare($saveMain);
        $saveStmt->execute([
            'id' => $id ?? null,
            'firstGameday' => $result['firstGameday'],
            'lastSeasonday' => $result['lastSeasonday'],
            'season' => $result['season'],
            'gameday' => $result['gameday'],
            'gameweek' => $result['gameweek'],
            'highestSalaryCap' => 150000000,
            'startBudget' => 50000000,
            'lastChanged' => $result['lastChanged'],
            'newSeason' => $result['season'],
            'newGameday' => $result['gameday'],
            'newGameweek' => $result['gameweek'],
            'newLastChanged' => time()
        ]);
    }

}