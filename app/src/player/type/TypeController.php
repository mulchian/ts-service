<?php


namespace touchdownstars\player\type;

use PDO;
use touchdownstars\player\position\Position;

class TypeController
{

    private PDO $pdo;

    /**
     * TypeController constructor.
     * @param PDO $pdo
     */
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function fetchType($idType): Type
    {
        $stmt = $this->pdo->prepare("SELECT * FROM `t_type` WHERE id = :idType;");
        $stmt->execute(['idType' => $idType]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        $position = select($this->pdo, 'SELECT * FROM `t_position` WHERE id = :idPosition;', 'touchdownstars\\player\\position\\Position', ['idPosition' => $result['idPosition']]);

        return new Type($result['id'], $position, $result['description'], $result['minHeight'], $result['maxHeight'], $result['minWeight'], $result['maxWeight'], $result['assignedTeamPart']);
    }

    public function fetchTypeByPosition(Position $position): Type
    {
        $stmt = $this->pdo->prepare("SELECT t.* FROM `t_type` t INNER JOIN t_position p ON t.idPosition = p.id WHERE p.position = :position;");
        $stmt->execute(['position' => $position->getPosition()]);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $randomIdx = rand(0, count($result) - 1);
        $typeArr = $result[$randomIdx];

        return new Type($typeArr['id'], $position, $typeArr['description'], $typeArr['minHeight'], $typeArr['maxHeight'], $typeArr['minWeight'], $typeArr['maxWeight'], $typeArr['assignedTeamPart']);
    }
}