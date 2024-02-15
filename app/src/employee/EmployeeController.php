<?php


namespace touchdownstars\employee;


use Faker\Factory;
use PDO;
use touchdownstars\contract\Contract;
use touchdownstars\contract\ContractController;
use touchdownstars\employee\job\Job;
use touchdownstars\employee\job\JobController;
use Monolog\Logger;
use touchdownstars\team\Team;

class EmployeeController
{

    private PDO $pdo;
    private Logger $log;
    private array $localeArray = array('en_US', 'en_GB', 'en_CA', 'en_AU', 'es_ES', 'fr_FR', 'it_IT', 'de_DE', 'de_CH', 'de_AT');
    private array $countryArray = array('USA', 'England', 'Kanada', 'Australien', 'Spanien', 'Frankreich', 'Italien', 'Deutschland', 'Schweiz', 'Österreich');


    public function __construct(PDO $pdo, Logger $log)
    {
        $this->pdo = $pdo;
        $this->log = $log;
    }

    private function getJobAndContractForEmployees(array $employees): array
    {
        $jobController = new JobController($this->pdo);
        foreach ($employees as $employee) {
            if (null != $employee->getIdTeam()) {
                $contractController = new ContractController($this->pdo);
                $employee->setContract($contractController->fetchContractOfEmployee($employee->getId()));
            }
            $employee->setJob($jobController->fetchJobOfEmployee($employee->getId()));
        }
        return $employees;
    }

    public function fetchEmployee(int $idEmployee): ?Employee
    {
        if ($idEmployee > 0) {
            $selectStmt = $this->pdo->prepare('SELECT * FROM `t_employee` te WHERE id = :idEmployee;');
            $selectStmt->execute(['idEmployee' => $idEmployee]);
            $selectStmt->setFetchMode(PDO::FETCH_CLASS, 'touchdownstars\\employee\\Employee');
            $resultEmployee = $selectStmt->fetch(PDO::FETCH_CLASS);

            if (isset($resultEmployee) && !empty($resultEmployee)) {
                return $this->getJobAndContractForEmployees(array($resultEmployee))[0];
            }
        }
        return null;
    }

    public function fetchEmployeesOfTeam(Team $team): array
    {
        $selectStmt = $this->pdo->prepare('SELECT * FROM `t_employee` te WHERE idTeam = :idTeam;');
        $selectStmt->execute(['idTeam' => $team->getId()]);
        $selectStmt->setFetchMode(PDO::FETCH_CLASS, 'touchdownstars\\employee\\Employee');
        $employees = $selectStmt->fetchAll(PDO::FETCH_CLASS, 'touchdownstars\\employee\\Employee');

        if (!empty($employees)) {
            return $this->getJobAndContractForEmployees($employees);
        }
        return array();
    }

    public function fetchEmployeeOfTeam(Team $team, string $jobName): ?Employee
    {
        if (!empty($team)) {
            $jobController = new JobController($this->pdo);
            $contractController = new ContractController($this->pdo);
            $selectStmt = $this->pdo->prepare('SELECT te.* FROM `t_employee` te JOIN t_job tj ON te.idJob = tj.id WHERE idTeam = :idTeam AND name = :name;');
            $selectStmt->execute(['idTeam' => $team->getId(), 'name' => $jobName]);
            $selectStmt->setFetchMode(PDO::FETCH_CLASS, 'touchdownstars\\employee\\Employee');
            $employee = $selectStmt->fetch(PDO::FETCH_CLASS);
            if (isset($employee) && !empty($employee)) {
                $employee->setJob($jobController->fetchJobOfEmployee($employee->getId()));
                $employee->setContract($contractController->fetchContractOfEmployee($employee->getId()));
                return $employee;
            }
        }
        return null;
    }

    public function countUnemployedEmployees(string $name): int
    {
        $selectStmt = $this->pdo->prepare('SELECT count(*) AS anzahl FROM `t_employee` te JOIN t_job tj ON te.idJob = tj.id WHERE idTeam IS NULL AND name = :name;');
        $selectStmt->execute(['name' => $name]);
        $countResult = $selectStmt->fetch(PDO::FETCH_ASSOC);
        if (!empty($countResult)) {
            return $countResult['anzahl'];
        } else {
            return 0;
        }
    }

    public function countTeamEmployees(Team $team): int
    {
        if (!empty($team)) {
            $selectStmt = $this->pdo->prepare('SELECT count(*) AS anzahl FROM `t_employee` te JOIN t_job tj ON te.idJob = tj.id WHERE idTeam = :idTeam;');
            $selectStmt->execute(['idTeam' => $team->getId()]);
            $countResult = $selectStmt->fetch(PDO::FETCH_ASSOC);
            if (isset($countResult) && !empty($countResult)) {
                return $countResult['anzahl'];
            }
        }
        return 0;
    }

    public function fetchUnemployedEmployees(string $jobName): array
    {
        if (!empty($jobName)) {
            $selectEmployee = 'SELECT te.id, te.lastName, te.firstName, te.age, te.nationality, te.ovr, te.talent, te.experience, 
                                te.moral, te.unemployedSeasons, te.marketValue
                                FROM `t_employee` te JOIN t_job tj ON te.idJob = tj.id 
                                WHERE idTeam IS NULL AND name = :name;';
            $selectStmt = $this->pdo->prepare($selectEmployee);
            $selectStmt->execute(['name' => $jobName]);
            $selectStmt->setFetchMode(PDO::FETCH_CLASS, 'touchdownstars\\employee\\Employee');
            $employees = $selectStmt->fetchAll(PDO::FETCH_CLASS, 'touchdownstars\\employee\\Employee');
            $this->log->debug('fetchUnemployedEmployees: ' . print_r($employees, true));
            if (!empty($employees)) {
                return $this->getJobAndContractForEmployees($employees);
            }
        }
        return array();
    }

    public function createNewEmployee(Job $job): void
    {
        /* Randomize the country and at the same time the Person */
        $randLocaleIdx = array_rand($this->localeArray);
        $faker = Factory::create($this->localeArray[$randLocaleIdx]);
        $employee = new Employee();
        $employee->setFirstName($faker->firstNameMale);
        $employee->setLastName($faker->lastName);
        $employee->setNationality($this->countryArray[$randLocaleIdx]);
        $employee->setAge($faker->numberBetween(35, 50));
        $employee->setOvr($faker->numberBetween(20, 40));
        $employee->setTalent($faker->numberBetween(1, 10));
        $employee->setExperience(0);
        $employee->setMoral(0.8);
        $employee->setJob($job);
        $this->saveEmployee($employee);
    }

    public function saveEmployee(Employee $employee, ?Team $team = null, ?Contract $contract = null): int
    {
        $selectStmt = $this->pdo->prepare('SELECT id from `t_employee` where (idTeam is not null and idTeam = :idTeam and idJob = :idJob) 
                               or (idTeam is null and idJob = :scndIdJob and lastName = :lastName and firstName = :firstName and nationality = :nationality);');
        $selectStmt->execute([
            'idTeam' => $team?->getId(),
            'idJob' => $employee->getJob()->getId(),
            'lastName' => $employee->getLastName(),
            'firstName' => $employee->getFirstName(),
            'nationality' => $employee->getNationality(),
            'scndIdJob' => $employee->getJob()->getId()
        ]);
        $id = $selectStmt->fetch(PDO::FETCH_ASSOC)['id'];

        $marketValue = $this->calcMarketValue($employee);
        $employee->setMarketValue($marketValue);
        $insertStmt = $this->pdo->prepare('INSERT INTO `t_employee` (id, idJob, idTeam, lastName, firstName, age, nationality, ovr, talent, experience, moral, marketValue, idContract) ' .
            'VALUES (:id, :idJob, :idTeam, :lastName, :firstName, :age, :nationality, :ovr, :talent, :experience, :moral, :marketValue, :idContract) ' .
            'ON DUPLICATE KEY UPDATE idTeam = :newIdTeam, age = :newAge, ovr = :newOvr, experience = :newExperience, moral = :newMoral, marketValue = :newMarketValue, idContract = :newIdContract;');
        $insertStmt->execute([
            'id' => $id ?? null,
            'idJob' => $employee->getJob()->getId(),
            'idTeam' => $employee->getIdTeam(),
            'lastName' => $employee->getLastName(),
            'firstName' => $employee->getFirstName(),
            'age' => $employee->getAge(),
            'nationality' => $employee->getNationality(),
            'ovr' => $employee->getOvr(),
            'talent' => $employee->getTalent(),
            'experience' => $employee->getExperience(),
            'moral' => $employee->getMoral(),
            'marketValue' => $employee->getMarketValue(),
            'idContract' => $contract?->getId(),
            'newIdTeam' => $employee->getIdTeam(),
            'newAge' => $employee->getAge(),
            'newOvr' => $employee->getOvr(),
            'newExperience' => $employee->getExperience(),
            'newMoral' => $employee->getMoral(),
            'newMarketValue' => $employee->getMarketValue(),
            'newIdContract' => $contract?->getId(),
        ]);

        return $this->pdo->lastInsertId();
    }

    public function deleteEmployee(Employee $employee): int
    {
        $deleteStmt = $this->pdo->prepare('DELETE FROM `t_employee` WHERE id = :idEmployee;');
        $deleteStmt->execute(['idEmployee' => $employee->getId()]);
        return $deleteStmt->rowCount();
    }

    private function calcMarketValue(Employee $employee): int
    {
        $jobWeightings = [
            'General Manager' => 0.7,
            'Mannschaftsarzt' => 0.2,
            'Head Coach' => 0.6,
            'Offensive Coordinator' => 0.5,
            'Defensive Coordinator' => 0.5,
            'Special Teams Coach' => 0.3,
            'Draft Scout' => 0.1
        ];

        $ageWeightings = [
            47 => 0.99,
            48 => 0.98,
            49 => 0.97,
            50 => 0.96,
            51 => 0.95,
            52 => 0.94,
            53 => 0.93,
            54 => 0.92,
            55 => 0.91,
            56 => 0.90,
            57 => 0.85,
            58 => 0.80,
            59 => 0.75,
            60 => 0.70,
            61 => 0.40,
            62 => 0.30,
            63 => 0.20,
            64 => 0.10,
        ];

        $ovr = $employee->getOvr();
        $talent = $employee->getTalent();

        // OVR-Gewichtung = OVR / 100
        $ovrWeighting = $ovr / 100;
        // Gewichtung der Position fest im Array
        $jobWeighting = $jobWeightings[$employee->getJob()->getName()];
        // Gewichtung des Alters -> Aufteilung bis 46 = 1, danach fest nach Array.
        if ($employee->getAge() <= 46) {
            $ageWeighting = 1;
        } elseif ($employee->getAge() > 64) {
            $ageWeighting = 0.01;
        } else {
            $ageWeighting = $ageWeightings[$employee->getAge()];
        }
        // Gewichtung des Talents
        // Talent 1 = 1 | Talent 2 = 0,9 | Talent 3 = 0,8 | Talent 4 = 0,7 ... Talent 10 = 0,1
        if ($talent == 1) {
            $talentWeighting = 1;
        } else {
            $talentWeighting = 1 - ($talent / 10) + 0.1;
        }

        // Marktwert = (OVR * OVRvalue * 100000) * POSvalue * ALTERvalue / TALENTvalue
        $marketValue = round(($ovr * $ovrWeighting * 100000 * $jobWeighting * $ageWeighting) / $talentWeighting);

        return (int)$marketValue;
    }

    public function calcEmployeeSalary(Team $team): int
    {
        $teamSalary = 0;
        foreach ($team->getEmployees() as $employee) {
            $teamSalary += $employee->getContract()->getSalary();
        }
        return $teamSalary;
    }
}