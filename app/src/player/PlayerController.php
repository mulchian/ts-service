<?php


namespace touchdownstars\player;


use Faker\Factory;
use PDO;
use touchdownstars\contract\ContractController;
use Monolog\Logger;
use touchdownstars\player\character\CharacterController;
use touchdownstars\player\position\Position;
use touchdownstars\player\skill\SkillController;
use touchdownstars\player\status\StatusController;
use touchdownstars\player\type\TypeController;
use touchdownstars\statistics\StatisticsController;
use touchdownstars\team\Team;

class PlayerController
{

    private PDO $pdo;
    private Logger $log;
    private array $localeArray = array('en_US', 'en_GB', 'en_CA', 'en_AU', 'es_ES', 'fr_FR', 'it_IT', 'nl_NL', 'de_DE', 'de_CH', 'de_AT');
    private array $countryArray = array('USA', 'England', 'Kanada', 'Australien', 'Spanien', 'Frankreich', 'Italien', 'Niederlande', 'Deutschland', 'Schweiz', 'Österreich');

    public function __construct(PDO $pdo, Logger $log)
    {
        $this->pdo = $pdo;
        $this->log = $log;
    }

    public function fetchPlayers(Team $team): array
    {
        $selectStmt = $this->pdo->prepare('SELECT * FROM `t_player` where idTeam = :idTeam;');
        $selectStmt->execute(['idTeam' => $team->getId()]);
        $selectStmt->setFetchMode(PDO::FETCH_CLASS, 'touchdownstars\\player\\Player');
        $players = $selectStmt->fetchAll(PDO::FETCH_CLASS, 'touchdownstars\\player\\Player');

        // Für jeden Spieler werden Skills, Status, Character und Type gebraucht
        foreach ($players as $player) {
            $this->addPlayersInformation($player);
        }

        return $players;
    }

    public function fetchPlayer(string $playerId): ?Player
    {
        $selectPlayer = 'SELECT * FROM `t_player` tp WHERE tp.id = :playerId;';

        $selectStmt = $this->pdo->prepare($selectPlayer);
        $selectStmt->execute(['playerId' => $playerId]);
        $selectStmt->setFetchMode(PDO::FETCH_CLASS, 'touchdownstars\\player\\Player');
        $player = $selectStmt->fetch(PDO::FETCH_CLASS);

        if ($player) {
            $this->addPlayersInformation($player);
            return $player;
        } else {
            return null;
        }
    }

    private function addPlayersInformation(Player $player): void
    {
        $skillController = new SkillController($this->pdo, $this->log);
        $characterController = new CharacterController($this->pdo);
        $statusController = new StatusController($this->pdo);
        $typeController = new TypeController($this->pdo);
        $contractController = new ContractController($this->pdo);
        $statisticsController = new StatisticsController($this->pdo, $this->log);

        $player->setSkills($skillController->fetchSkills($player->getId()));
        $player->setCharacter($characterController->fetchCharacter($player->getId()));
        $player->setStatus($statusController->fetchStatus($player->getId()));
        $player->setType($typeController->fetchType($player->getIdType()));
        $player->setContract($contractController->fetchContractOfPlayer($player->getId()));
        $player->setStatistics($statisticsController->fetchStatisticsForPlayer($player->getId()));
        //TODO: setDraftposition
    }

    public function fetchTrainingAbility(Player $player): ?string
    {
        $selectStmt = $this->pdo->prepare('SELECT training FROM `t_type_to_skill` where idType = :idType;');
        $selectStmt->execute(['idType' => $player->getType()->getId()]);
        $result = $selectStmt->fetch(PDO::FETCH_ASSOC);

        return match ($result['training']) {
            'L' => 150,
            'T' => -150,
            default => 0,
        };
    }

    /**
     * Erstellt einen neues Spieler mit Randomized Values und Skills für das Team.
     * @param Team|null $team - Team, für das der Spieler erstellt und gespeichert wird.
     * @param Position $position - Position des Spielers
     * @param bool $isFreePlayer - Default: false - Bei true wird das Alter des Spielers höher gesetzt
     * @param bool $isBotTeam - Default: false - Bei true wird ein Bot-Spieler ohne Start-Contract erstellt.
     * @return Player|null - Gibt des erstellten Spieler zurück.
     */
    public function createNewPlayer(Team|null $team, Position $position, bool $isFreePlayer = false, bool $isBotTeam = false): ?Player
    {
        $typeController = new TypeController($this->pdo);
        $statusController = new StatusController($this->pdo);
        $characterController = new CharacterController($this->pdo);
        $skillController = new SkillController($this->pdo, $this->log);

        /* Randomize the country and at the same time the Person */
        $randLocaleIdx = array_rand($this->localeArray);
        $faker = Factory::create($this->localeArray[$randLocaleIdx]);
        $player = new Player();
        if ($isBotTeam) {
            $player->setFirstName('Bot');
        } else {
            $player->setFirstName($faker->firstNameMale);
        }
        $player->setLastName($faker->lastName);
        $player->setNationality($this->countryArray[$randLocaleIdx]);
        if ($isFreePlayer) {
            $player->setAge(rand(25, 30));
        } else {
            $player->setAge(rand(18, 23));
        }


        $type = $typeController->fetchTypeByPosition($position);
        $player->setType($type);
        $player->setHeight(rand($type->getMinHeight(), $type->getMaxHeight()));
        $player->setWeight(rand($type->getMinWeight(), $type->getMaxWeight()));

        $player->setEnergy(1);
        $player->setMoral(1);
        $player->setExperience(0);
        $player->setTalent(rand(1, 10));
        $player->setTimeInLeague(0);
        $player->setHallOfFame(false);

        // Trainingswerte setzen
        $player->setTrainingGroup('TE0');
        $player->setSkillpoints(0);
        $player->setIntensity(1);
        $player->setNumberOfTrainings(0);

        $player->setStatus($statusController->getStatusByDescription('Gesund'));
        $player->setCharacter($characterController->getRandomCharacter($isFreePlayer, $player));

        $player->setIdTeam($team?->getId());

        $player->setSkills($skillController->getRandomizedStartSkills($player, $isFreePlayer));


        // Player speichern berechnet den Marktwert und dies benötigt Skills
        $idPlayer = $this->savePlayer($player);
        if ($idPlayer && $idPlayer > 0) {
            $player->setId($idPlayer);
        }

        // Skills speichern benötigt die Player-ID
        $skillController->insertSkillsToPlayer($player);

        $player->setMarketValue($this->calcMarketValue($position, $player->getAge(), $player->getTalent(), $player->getOVR()));

        if (!$isBotTeam) {
            // Erstellung des Start-Contracts
            $contractController = new ContractController($this->pdo);
            $startSalary = floor($player->getMarketValue() * 20 / 100 * $player->getMoral());
            $contract = $contractController->createContract($player->getMarketValue(), $startSalary, 3);
            $player->setContract($contract);
        }

        $id = $this->savePlayer($player);
        if ($id == $player->getId() || $isBotTeam) {
            return $player;
        } else {
            return null;
        }
    }

    private function calcMarketValue(Position $position, int $age, int $talent, int $ovr): int
    {
        $positionWeightings = [
            'QB' => 1,
            'RB' => 0.9,
            'WR' => 0.9,
            'OT' => 0.8,
            'CB' => 0.8,
            'MLB' => 0.75,
            'DE' => 0.6,
            'OLB' => 0.6,
            'C' => 0.55,
            'DT' => 0.4,
            'TE' => 0.4,
            'OG' => 0.35,
            'SS' => 0.3,
            'FS' => 0.3,
            'FB' => 0.25,
            'K' => 0.2,
            'P' => 0.1
        ];

        // OVR-Gewichtung = OVR / 100
        $ovrWeighting = $ovr / 100;

        // Gewichtung der Position fest im Array
        $positionWeighting = $positionWeightings[$position->getPosition()];

        // Gewichtung bei 18 = 1, danach immer minus 0,07
        $ageWeighting = 1 - (($age - 18) * 0.07);

        // Talent 1 = 1 | Talent 2 = 0,9 | Talent 3 = 0,8 | Talent 4 = 0,7 ... Talent 10 = 0,1
        // (Talent/2 = Stern-Anzeige)
        if ($talent == 1) {
            $talentWeighting = 1;
        } else {
            $talentWeighting = 1.1 - ($talent / 10);
        }

        $marketValue = round(($ovr * $ovrWeighting * 100000) * $positionWeighting * $ageWeighting / $talentWeighting);

        return (int)$marketValue;
    }

    public function savePlayer(Player $player): false|string
    {
        $selectStmt = $this->pdo->prepare('SELECT id from `t_player` 
            where lastName = :lastName and firstName = :firstName and nationality = :nationality and height = :height
              and weight = :weight and idCharacter = :idCharacter and idType = :idType;');
        $selectStmt->execute([
            'lastName' => $player->getLastName(),
            'firstName' => $player->getFirstName(),
            'nationality' => $player->getNationality(),
            'height' => $player->getHeight(),
            'weight' => $player->getWeight(),
            'idCharacter' => $player->getCharacter()->getId(),
            'idType' => $player->getType()->getId()
        ]);
        $id = $selectStmt->fetch(PDO::FETCH_ASSOC)['id'];

        $marketValue = $this->calcMarketValue($player->getType()->getPosition(), $player->getAge(), $player->getTalent(), $player->getOVR());
        $insertPlayer = 'INSERT INTO `t_player` (id, lastName, firstName, age, nationality, height, weight, marketValue, 
                        energy, moral, experience, talent, skillpoints, timeInLeague, idTeam, idStatus, idCharacter, idType, idContract, idDraftposition) 
                        VALUES (:id, :lastName, :firstName, :age, :nationality, :height, :weight, :marketValue, :energy,
                               :moral, :experience, :talent, :skillpoints, :timeInLeague, :idTeam, :idStatus, :idCharacter, :idType, :idContract, :idDraftposition) 
                        ON DUPLICATE KEY UPDATE age = :ageW, marketValue = :marketValueW, energy = :energyW, moral = :moralW, experience = :experienceW, skillpoints = :skillpointsW, 
                                                timeInLeague = :timeInLeagueW, idTeam = :idTeamW, idStatus = :idStatusW, idContract = :idContractW, idDraftposition = :idDraftpositionW;';
        $stmt = $this->pdo->prepare($insertPlayer);
        $stmt->execute([
            'id' => $id ?? null,
            'lastName' => $player->getLastName(),
            'firstName' => $player->getFirstName(),
            'age' => $player->getAge(),
            'nationality' => $player->getNationality(),
            'height' => $player->getHeight(),
            'weight' => $player->getWeight(),
            'marketValue' => $marketValue,
            'energy' => $player->getEnergy(),
            'moral' => $player->getMoral(),
            'experience' => $player->getExperience(),
            'talent' => $player->getTalent(),
            'skillpoints' => $player->getSkillpoints(),
            'timeInLeague' => $player->getTimeInLeague(),
            'idTeam' => $player->getIdTeam(),
            'idStatus' => $player->getStatus()->getId(),
            'idCharacter' => $player->getCharacter()->getId(),
            'idType' => $player->getType()->getId(),
            'idContract' => null != $player->getContract() ? $player->getContract()->getId() : null,
            'idDraftposition' => null != $player->getDraftposition() ? $player->getDraftposition()->getId() : null,
            'ageW' => $player->getAge(),
            'marketValueW' => $marketValue,
            'energyW' => $player->getEnergy(),
            'moralW' => $player->getMoral(),
            'experienceW' => $player->getExperience(),
            'skillpointsW' => $player->getSkillpoints(),
            'timeInLeagueW' => $player->getTimeInLeague(),
            'idTeamW' => $player->getIdTeam(),
            'idStatusW' => $player->getStatus()->getId(),
            'idContractW' => null != $player->getContract() ? $player->getContract()->getId() : null,
            'idDraftpositionW' => null != $player->getDraftposition() ? $player->getDraftposition()->getId() : null,
        ]);

        return $this->pdo->lastInsertId();
    }

    public function updateSkillPoints(Player $player): void
    {
        $updateStmt = $this->pdo->prepare('UPDATE `t_player` SET skillpoints = :skillpoints WHERE id = :idPlayer;');
        $updateStmt->execute(['skillpoints' => $player->getSkillpoints(), 'idPlayer' => $player->getId()]);
    }

    public function updateNumberOfTrainings(Player $player): void
    {
        $updateStmt = $this->pdo->prepare('UPDATE `t_player` SET numberOfTrainings = :numberOfTrainings WHERE id = :idPlayer;');
        $updateStmt->execute(['numberOfTrainings' => $player->getNumberOfTrainings(), 'idPlayer' => $player->getId()]);
    }

    public function deletePlayer($player): int
    {
        $deleteStmt = $this->pdo->prepare('DELETE FROM `t_player` WHERE id = :idPlayer;');
        $deleteStmt->execute(['idPlayer' => $player->getId()]);
        return $deleteStmt->rowCount();
    }

    public function updateLineupPosition($playerId, $lineupPosition): void
    {
        $updateStmt = $this->pdo->prepare('UPDATE `t_player` SET lineupPosition = :lineupPosition where id = :idPlayer;');
        $updateStmt->execute(['lineupPosition' => $lineupPosition, 'idPlayer' => $playerId]);
    }
}