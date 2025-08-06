<?php


namespace touchdownstars\player\skill;


use PDO;
use touchdownstars\employee\EmployeeController;
use Monolog\Logger;
use touchdownstars\player\Player;
use touchdownstars\player\PlayerController;
use touchdownstars\stadium\StadiumController;
use touchdownstars\team\Team;

class SkillController
{
    private PDO $pdo;
    private Logger $log;
    private static array $fitnessSkills = array('strength', 'speed', 'agility', 'acceleration', 'jump');

    private array $trainFactors = [
        'technique' => 1.0,
        'fitness' => 0.4,
        'scrimmage' => 0.5
    ];
    private static array $intensityFactors = [
        1 => 0.5,
        2 => 0.7,
        3 => 1
    ];
    private static array $talentFactors = [
        1 => 10,
        2 => 20,
        3 => 35,
        4 => 55,
        5 => 80,
        6 => 110,
        7 => 145,
        8 => 185,
        9 => 230,
        10 => 300
    ];
    private static array $ageFactors = [
        20 => 100,
        21 => 95,
        22 => 90,
        23 => 85,
        24 => 75,
        25 => 70,
        26 => 55,
        27 => 40,
        28 => 25,
        29 => 10,
        30 => 0,
        31 => -50,
        32 => -70,
        33 => -90,
        34 => -100,
    ];

    public function __construct(PDO $pdo, Logger $log)
    {
        $this->pdo = $pdo;
        $this->log = $log;
    }

    public function fetchSkills(int $idPlayer): array
    {
        $selectStmt = $this->pdo->prepare('SELECT * FROM `t_skill_to_player` WHERE idPlayer = :idPlayer;');
        $selectStmt->execute(['idPlayer' => $idPlayer]);
        $result = $selectStmt->fetch(PDO::FETCH_ASSOC);
        unset($result['id']);
        unset($result['idPlayer']);

        $skills = array();
        foreach (array_keys($result) as $key) {
            $skills[$key] = $result[$key];
        }

        return array_filter($skills);
    }

    public function fetchSkillNames(string $language = 'de'): array
    {
        if (isset($_SESSION['skillNames'])) {
            return $_SESSION['skillNames'];
        }

        $selectStmt = $this->pdo->prepare('SELECT skillKey, ' . $language . ' FROM `t_skill`;');
        $selectStmt->execute();
        $result = $selectStmt->fetchAll(PDO::FETCH_ASSOC);

        $skillNames = array();
        foreach ($result as $skillName) {
            $skillNames[$skillName['skillKey']] = $skillName[$language];
        }

        $_SESSION['skillNames'] = $skillNames;

        return $skillNames;
    }

    public function getRandomizedStartSkills(Player $player, bool $isFreePlayer): array
    {
        $selectSkillsForPlayer = 'SELECT s.skillKey, tts.minOVR, tts.maxOVR FROM t_type_to_skill tts INNER JOIN t_skill s ON tts.idSkill = s.id WHERE tts.idType = :idType;';
        $stmt = $this->pdo->prepare($selectSkillsForPlayer);
        $stmt->execute(['idType' => $player->getType()->getId()]);
        $typeSkills = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $skills = $player->getSkills();

        $difference = 0;
        if ($isFreePlayer) {
            $difference = rand(12, 15);
        }

        foreach ($typeSkills as $skill) {
            $skills[$skill['skillKey']] = rand($skill['minOVR'] - $difference, $skill['maxOVR'] - $difference);
        }

        return $skills;
    }

    public function train(Team $team, string $trainingGroup, string $training): bool
    {
        $playerController = new PlayerController($this->pdo, $this->log);
        $employeeController = new EmployeeController($this->pdo, $this->log);
        $stadiumController = new StadiumController($this->pdo);
        $players = $team->getPlayers();

        $trainingGroupPlayers = array_filter($players, function (Player $value) use ($trainingGroup) {
            return $value->getTrainingGroup() == $trainingGroup;
        });

        if (count($trainingGroupPlayers) > 0 && !$this->hasThreeTrainings($trainingGroupPlayers)) {
            foreach ($trainingGroupPlayers as $player) {
                $intensityFactor = self::$intensityFactors[$player->getIntensity()];
                $ovrFactor = 101 - $player->getOVR();
                $talentFactor = self::$talentFactors[$player->getTalent()];
                $ageFactor = self::$ageFactors[$player->getAge()];
                $experienceFactor = $player->getExperience() == 0 ? 1 : 10 * $player->getExperience();
                $trainAbilityFactor = $playerController->fetchTrainingAbility($player);

                //Head Coach für alle
                $headCoach = $employeeController->fetchEmployeeOfTeam($team, 'Head Coach');
                if (!empty($headCoach)) {
                    $headCoachFactor = $headCoach->getOvr();
                } else {
                    $headCoachFactor = 0;
                }

                //Offensive, Defensive, Special Team Coordinator
                $coordinatorFactor = 0;
                switch ($player->getType()->getAssignedTeamPart()) {
                    case 'Offense':
                        $offensiveCoordinator = $employeeController->fetchEmployeeOfTeam($team, 'Offensive Coordinator');
                        if (!empty($offensiveCoordinator)) {
                            $coordinatorFactor = $offensiveCoordinator->getOvr();
                        }
                        break;
                    case 'Defense':
                        $defensiveCoordinator = $employeeController->fetchEmployeeOfTeam($team, 'Defensive Coordinator');
                        if (!empty($defensiveCoordinator)) {
                            $coordinatorFactor = $defensiveCoordinator->getOvr();
                        }
                        break;
                    case 'Special Teams':
                        $specialTeamsCoach = $employeeController->fetchEmployeeOfTeam($team, 'Special Teams Coach');
                        if (!empty($specialTeamsCoach)) {
                            $coordinatorFactor = $specialTeamsCoach->getOvr();
                        }
                        break;
                }

                //Trainingszentrum
                $trainingCenter = $stadiumController->getBuildingWithName($team->getStadium(), 'Trainingszentrum');
                if (!empty($trainingCenter)) {
                    $trainingCenterFactor = $trainingCenter->getLevel() * 10;
                } else {
                    $trainingCenterFactor = 0;
                }

                $this->log->debug('training factors:', [
                    'ovrFactor' => $ovrFactor,
                    'talentFactor' => $talentFactor,
                    'ageFactor' => $ageFactor,
                    'experienceFactor' => $experienceFactor,
                    'trainAbilityFactor' => $trainAbilityFactor,
                    'headCoachFactor' => $headCoachFactor,
                    'coordinatorFactor' => $coordinatorFactor,
                    'trainingCenterFactor' => $trainingCenterFactor
                ]);
                $addableFactors = ($ovrFactor + $talentFactor + $ageFactor + $experienceFactor + $trainAbilityFactor + $headCoachFactor + $coordinatorFactor + $trainingCenterFactor);
                $this->log->debug('addableFactors', ['addableFactors' => $addableFactors]);
                $this->log->debug('intensityFactor', ['intensityFactor' => $intensityFactor]);
                $this->log->debug('trainingPart trainFactor', ['trainingPart' => $training, 'trainFactor' => $this->trainFactors[$training]]);
                $trainFactor = $this->trainFactors[$training] * $intensityFactor * $addableFactors;
                $playerSkills = $player->getSkills();

                if ($training === 'scrimmage') {
                    //Training Scrimmage
                    //Scrimmage skillt den SP -> 10000 = 1 SP
                    $this->log->debug('scrimmage training for player', ['playerId' => $player->getId(), 'trainFactor' => $trainFactor]);
                    $trainingEffect = round(($trainFactor / 10000), 4);
                    $this->log->debug('trainingEffect', ['trainingEffect' => $trainingEffect]);
                    $newSp = max(round(($player->getSkillpoints() + $trainingEffect), 4), 0);
                    $this->log->debug('newSp', ['newSp' => $newSp]);
                    $player->setSkillpoints($newSp);
                    $playerController->updateSkillPoints($player);
                } else {
                    $this->log->debug($training . ' training for player', ['playerId' => $player->getId(), 'trainFactor' => $trainFactor]);
                    foreach (array_keys($playerSkills) as $skillName) {
                        switch ($training) {
                            case 'fitness':
                                //Training Fitness
                                if (in_array($skillName, self::$fitnessSkills)) {
                                    $playerSkills[$skillName] += round(($trainFactor / 10000), 4);
                                }
                                break;
                            case 'technique':
                                //Training Technique
                                if (!in_array($skillName, self::$fitnessSkills)) {
                                    $playerSkills[$skillName] += round(($trainFactor / 10000), 4);
                                }
                                break;
                        }
                    }
                }

                $player->setSkills($playerSkills);
                $this->updateSkillsToPlayer($player);
                $player->setNumberOfTrainings($player->getNumberOfTrainings() + 1);
                $playerController->updateNumberOfTrainings($player);
            }
            $_SESSION['team'] = $team;
            return true;
        } else {
            //mindestens ein Spieler hat bereits drei Trainings
            return false;
        }
    }

    public function insertSkillsToPlayer(Player $player): void
    {
        $skillsToPlayer = $this->getSkillsToPlayer($player);

        /** @noinspection SqlInsertValues */
        $insertSkills = 'INSERT INTO `t_skill_to_player` (' . implode(', ', array_keys($skillsToPlayer)) . ') SELECT ';
        // Reserved Words must be quoted with `` for MYSQL -> returning, release
        $insertSkills = str_replace('returning', '`returning`', $insertSkills);
        $insertSkills = str_replace('release', '`release`', $insertSkills);
        for ($i = 0; $i < count($skillsToPlayer); $i++) {
            $insertSkills .= ':' . array_keys($skillsToPlayer)[$i];
            if ($i !== (count($skillsToPlayer) - 1)) {
                $insertSkills .= ', ';
            }
        }
        $skillsToPlayer['idPlayer2'] = $player->getId();
        $insertSkills .= ' FROM dual WHERE NOT EXISTS (SELECT * FROM `t_skill_to_player` WHERE idPlayer = :idPlayer2);';
        $stmt = $this->pdo->prepare($insertSkills);
        $stmt->execute($skillsToPlayer);
    }

    public function updateSkillsToPlayer(Player $player): void
    {
        $skillsToPlayer = $this->getSkillsToPlayer($player);

        $updateSkills = 'UPDATE `t_skill_to_player` SET' . ' ';
        for ($i = 0; $i < count($skillsToPlayer); $i++) {
            $updateSkills .= array_keys($skillsToPlayer)[$i] . '= :' . array_keys($skillsToPlayer)[$i];
            if ($i !== (count($skillsToPlayer) - 1)) {
                $updateSkills .= ', ';
            }
        }

        // Reserved Words must be quoted with `` for MYSQL -> returning, release
        $updateSkills = str_replace('returning=', '`returning`=', $updateSkills);
        $updateSkills = str_replace('release=', '`release`=', $updateSkills);
        $skillsToPlayer['idPlayer2'] = $player->getId();
        $updateSkills .= ' WHERE idPlayer = :idPlayer2;';
        $stmt = $this->pdo->prepare($updateSkills);
        $stmt->execute($skillsToPlayer);
    }

    private function getSkillsToPlayer(Player $player): array
    {
        $playerArr = array('idPlayer' => $player->getId());
        $skills = $player->getSkills();
        $skillsToPlayer = $playerArr;
        foreach (array_keys($skills) as $key) {
            $skillsToPlayer[$key] = $skills[$key];
        }
        return $skillsToPlayer;
    }

    /**
     * Setzt die Trainingsgruppe des Spielers auf die Auswahl des Managers.
     * @param Team $team - aktuell angemeldetes Team
     * @param string $trainingGroup - Wert der Enum('TE0', 'TE1', 'TE2', 'TE3')
     * @param string|null $idPlayer
     * @return bool true, wenn die neue Trainingsgruppe in der Datenbank steht
     */
    public function setTrainingGroup(Team $team, string $trainingGroup, string $idPlayer = null): bool
    {
        /** @noinspection SqlWithoutWhere */
        $updateTrainingGroup = 'UPDATE `t_player` SET trainingGroup = :trainingGroup ';
        if (isset($idPlayer)) {
            $updateTrainingGroup .= 'WHERE id = :idPlayer;';
            $params = ['trainingGroup' => $trainingGroup, 'idPlayer' => $idPlayer];
        } else {
            $updateTrainingGroup .= 'WHERE idTeam = :idTeam';
            $params = ['trainingGroup' => $trainingGroup, 'idTeam' => $team->getId()];
        }

        $updateStmt = $this->pdo->prepare($updateTrainingGroup);
        $updateStmt->execute($params);

        if ($this->pdo->lastInsertId() > 0 || $updateStmt->rowCount() > 0) {
            if (isset($idPlayer)) {
                foreach (array_keys($team->getPlayers()) as $key) {
                    if ($team->getPlayers()[$key]->getId() == $idPlayer) {
                        $team->getPlayers()[$key]->setTrainingGroup($trainingGroup);
                        $_SESSION['team'] = $team;
                        return true;
                    }
                }
            } else {
                foreach (array_keys($team->getPlayers()) as $key) {
                    $team->getPlayers()[$key]->setTrainingGroup($trainingGroup);
                }
                $_SESSION['team'] = $team;
                return true;
            }
        }
        return false;
    }

    public function setIntensity(Team $team, string $newIntensity, string $idPlayer = null): bool
    {
        /** @noinspection SqlWithoutWhere */
        $updateIntensity = 'UPDATE `t_player` SET intensity = :intensity ';
        if (isset($idPlayer)) {
            $updateIntensity .= 'WHERE id = :idPlayer;';
            $params = ['intensity' => $newIntensity, 'idPlayer' => $idPlayer];
        } else {
            $updateIntensity .= 'WHERE idTeam = :idTeam';
            $params = ['intensity' => $newIntensity, 'idTeam' => $team->getId()];
        }

        $updateStmt = $this->pdo->prepare($updateIntensity);
        $updateStmt->execute($params);

        if ($this->pdo->lastInsertId() > 0 || $updateStmt->rowCount() > 0) {
            if (isset($idPlayer)) {
                foreach (array_keys($team->getPlayers()) as $key) {
                    if ($team->getPlayers()[$key]->getId() == $idPlayer) {
                        $team->getPlayers()[$key]->setIntensity($newIntensity);
                        $_SESSION['team'] = $team;
                        return true;
                    }
                }
            } else {
                foreach (array_keys($team->getPlayers()) as $key) {
                    $team->getPlayers()[$key]->setIntensity($newIntensity);
                }
                $_SESSION['team'] = $team;
                return true;
            }
        }
        return false;
    }

    private function hasThreeTrainings(array $trainingGroupPlayers): bool
    {
        $hasThreeTrainings = false;
        foreach ($trainingGroupPlayers as $player) {
            if ($player->getNumberOfTrainings() >= 3) {
                $hasThreeTrainings = true;
            }
        }
        return $hasThreeTrainings;
    }
}