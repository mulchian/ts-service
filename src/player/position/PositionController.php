<?php


namespace touchdownstars\player\position;


use PDO;

class PositionController
{

    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function fetchPosition(string $positionAbb): Position
    {
        $stmt = $this->pdo->prepare('SELECT * FROM `t_position` where position = :positionAbb;');
        $stmt->execute(['positionAbb' => $positionAbb]);
        $stmt->setFetchMode(PDO::FETCH_CLASS, 'touchdownstars\\player\\position\\Position');
        return $stmt->fetch(PDO::FETCH_CLASS);
    }

    public function fetchAllPositions(): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM `t_position`;');
        $stmt->execute();
        $stmt->setFetchMode(PDO::FETCH_CLASS, 'touchdownstars\\player\\position\\Position');
        $positions = $stmt->fetchAll(PDO::FETCH_CLASS, 'touchdownstars\\player\\position\\Position');
        if (!empty($positions)) {
            return $positions;
        }
        return array();
    }

}