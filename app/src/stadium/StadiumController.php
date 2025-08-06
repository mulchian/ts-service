<?php


namespace touchdownstars\stadium;

use PDO;
use touchdownstars\team\Team;

class StadiumController
{
    private PDO $pdo;

    private array $startBuildings = [
        'Bauhof' => 0,
        'Bürogebäude' => 3,
        'Merchandise' => 1,
        'Front Office' => 1,
        'Medizinische Abteilung' => 1,
        'Trainingszentrum' => 1,
        'Coaching Office' => 5,
        'Scouting Office' => 5
    ];

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Fügt Startgebäude zu dem Stadion in die Tabelle oder aktualisiert die Level der Gebäude,
     * wenn diese schon in der Tabelle (t_building_to_stadium) vorhanden sind.
     * @param Stadium $stadium
     * @return Stadium - mitgegebenes Stadion inklusive der Gebäude
     */
    public function saveBuildingsToStadium(Stadium $stadium): Stadium
    {
        $buildings = $stadium->getBuildings();
        if (count($buildings) == 0) {
            $buildings = $this->fetchAllBuildings();
        }

        foreach ($buildings as $key => $building) {
            $selectStmt = $this->pdo->prepare('SELECT id FROM `t_building_to_stadium` WHERE idBuilding = :idBuilding AND idStadium = :idStadium;');
            $selectStmt->execute(['idBuilding' => $building->getId(), 'idStadium' => $stadium->getId()]);
            $id = $selectStmt->fetch(PDO::FETCH_ASSOC)['id'];

            $level = $this->startBuildings[$building->getName()];
            $saveBuildingToStadiumStmt = $this->pdo->prepare('INSERT INTO `t_building_to_stadium` (id, idStadium, idBuilding, level) VALUES (:id, :idStadium, :idBuilding, :level) ' .
                'ON DUPLICATE KEY UPDATE level = :newLevel;');
            $saveBuildingToStadiumStmt->execute([
                'id' => $id ?? null,
                'idStadium' => $stadium->getId(),
                'idBuilding' => $building->getId(),
                'level' => $level,
                'newLevel' => $building->getLevel()
            ]);

            if (!$building->getLevel()) {
                $building->setLevel($level);
            }
            $buildings[$key] = $building;
        }

        $stadium->setBuildings($buildings);
        return $stadium;
    }

    /**
     * Fügt das Stadion neu in die Tabelle oder aktualisiert den vorhandenen Eintrag.
     * @param Team $team
     * @param Stadium $stadium
     * @return int - lastInsertId: ID des neu eingefügten Stadions oder des aktualisierten Stadions. 0 bei Fehler.
     */
    public function saveStadium(Team $team, Stadium $stadium): int
    {
        $selectStmt = $this->pdo->prepare('SELECT id from `t_stadium` where idTeam = :idTeam;');
        $selectStmt->execute(['idTeam' => $team->getId()]);
        $id = $selectStmt->fetch(PDO::FETCH_ASSOC)['id'];

        $saveStmt = $this->pdo->prepare('INSERT INTO `t_stadium` (id, idTeam, name, description) 
            VALUES (:id, :idTeam, :name, :description) 
            ON DUPLICATE KEY UPDATE name = :newName, description = :newDescription;');
        $saveStmt->execute([
            'id' => $id ?? null,
            'idTeam' => $team->getId(),
            'name' => $stadium->getName(),
            'description' => $stadium->getDescription(),
            'newName' => $stadium->getName(),
            'newDescription' => $stadium->getDescription()
        ]);
        return $this->pdo->lastInsertId();
    }

    /**
     * Gibt das Stadion inklusiver aller Gebäude zurück.
     * @param Team $team
     * @return Stadium|null → gefundenes Stadion oder null bei keinem Eintrag in der Tabelle (t_stadium).
     */
    public function fetchStadium(Team $team): ?Stadium
    {
        $stadium = select($this->pdo, 'SELECT * FROM `t_stadium` where idTeam = :idTeam;', 'touchdownstars\\stadium\\Stadium', ['idTeam' => $team->getId()]);

        if (!empty($stadium)) {
            //fetch Buildings to stadium
            $selectStmt = $this->pdo->prepare('SELECT * FROM `t_building` tb JOIN `t_building_to_stadium` tbts ON tb.id = tbts.idBuilding WHERE tbts.idStadium = :idStadium');
            $selectStmt->execute(['idStadium' => $stadium->getId()]);
            $arrResult = $selectStmt->fetchAll(PDO::FETCH_ASSOC);

            if (!empty($arrResult)) {
                $buildings = array();
                //TODO: Auswertung des Results - Buildings in array und dann in Stadium
                for ($i = 0; $i < count($arrResult); $i++) {
                    $building = new Building();
                    $building->setId($arrResult[$i]['id']);
                    $building->setName($arrResult[$i]['name']);
                    $building->setDescription($arrResult[$i]['description']);
                    $building->setMaxLevel($arrResult[$i]['maxLevel']);
                    $building->setLevel($arrResult[$i]['level']);
                    //TODO: BuildingEffect passend zum Level mit setzen
                    //$building->setBuildingEffect($arrResult[$i]['buildingEffect']);

                    $buildings[$i] = $building;
                }

                $stadium->setBuildings($buildings);
            }

            return $stadium;
        }
        return null;
    }

    /**
     * Gibt das Gebäude zu dem Namen zurück.
     * @param Stadium $stadium
     * @param string $name
     * @return Building|null - gefundenes Gebäude oder null bei keinem Eintrag in der Tabelle (t_buildings).
     */
    public function getBuildingWithName(Stadium $stadium, string $name): ?Building
    {
        $arrBuildings = array_values(array_filter($stadium->getBuildings(), function (Building $value) use ($name) {
            return $value->getName() == $name;
        }));
        if (!empty($arrBuildings)) {
            return $arrBuildings[0];
        } else {
            return null;
        }
    }

    public function fetchAllBuildings(): array
    {
        $selectBuildingsStmt = $this->pdo->prepare('SELECT * FROM `t_building`');
        $selectBuildingsStmt->execute();
        $selectBuildingsStmt->setFetchMode(PDO::FETCH_CLASS, 'touchdownstars\\stadium\\Building');
        $buildings = $selectBuildingsStmt->fetchAll(PDO::FETCH_CLASS, 'touchdownstars\\stadium\\Building');
        if (!empty($buildings)) {
            foreach ($buildings as $building) {
                $selectBuildingLevelsStmt = $this->pdo->prepare('SELECT * FROM `t_building_level` WHERE idBuilding = :idBuilding ORDER BY level;');
                $selectBuildingLevelsStmt->execute(['idBuilding' => $building->getId()]);
                $buildingLevels = $selectBuildingLevelsStmt->fetchAll(PDO::FETCH_CLASS, 'touchdownstars\\stadium\\BuildingLevel');
                $building->setBuildingLevels($buildingLevels);
            }
        }
        return $buildings;
    }
}