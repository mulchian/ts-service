<?php


namespace touchdownstars\player\status;


use PDO;

class StatusController
{

    private PDO $pdo;

    /**
     * StatusController constructor.
     * @param PDO $pdo
     */
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function fetchStatus(int $idPlayer): Status
    {
        $stmt = $this->pdo->prepare("SELECT ts.* FROM `t_status` ts INNER JOIN t_player tp on ts.id = tp.idStatus WHERE tp.id = :idPlayer;");
        $stmt->execute(['idPlayer' => $idPlayer]);
        $stmt->setFetchMode(PDO::FETCH_CLASS, "touchdownstars\\player\\status\\Status");
        return $stmt->fetch(PDO::FETCH_CLASS);
    }

    public function getStatusByDescription($description)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM `t_status` WHERE description = :description;");
        $stmt->execute(['description' => $description]);
        $stmt->setFetchMode(PDO::FETCH_CLASS, "touchdownstars\\player\\status\\Status");
        return $stmt->fetch(PDO::FETCH_CLASS);
    }
}