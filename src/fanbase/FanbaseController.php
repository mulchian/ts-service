<?php


namespace touchdownstars\fanbase;


use PDO;
use touchdownstars\team\Team;

class FanbaseController
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function saveFanbase(Team $team, Fanbase $fanbase): ?Fanbase
    {
        $selectStmt = $this->pdo->prepare('SELECT id from `t_fanbase` where idTeam = :idTeam;');
        $selectStmt->execute(['idTeam' => $team->getId()]);
        $id = $selectStmt->fetch(PDO::FETCH_ASSOC)['id'];

        $saveStmt = $this->pdo->prepare('INSERT INTO `t_fanbase` (id, idTeam, amount) VALUES (:id, :idTeam, :amount) ' .
            'ON DUPLICATE KEY UPDATE amount = :newAmount;');
        $saveStmt->execute([
            'id' => $id ?? null,
            'idTeam' => $team->getId(),
            'amount' => $fanbase->getAmount(),
            'newAmount' => $fanbase->getAmount()
        ]);
        return $this->fetchFanbase($this->pdo->lastInsertId());
    }

    public function fetchFanbase(int $idFanbase): ?Fanbase
    {
        if ($idFanbase > 0) {
            $selectStmt = $this->pdo->prepare('select * from `t_fanbase` where id = :idFanbase;');
            $selectStmt->execute(['idFanbase' => $idFanbase]);
            $selectStmt->setFetchMode(PDO::FETCH_CLASS, 'touchdownstars\\fanbase\\Fanbase');
            $fanbase = $selectStmt->fetch(PDO::FETCH_CLASS);

            if ($fanbase) {
                return $fanbase;
            }
        }
        return null;
    }
}