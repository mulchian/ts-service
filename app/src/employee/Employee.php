<?php


namespace touchdownstars\employee;


use JsonSerializable;
use Lombok\Getter;
use Lombok\Helper;
use Lombok\Setter;
use touchdownstars\contract\Contract;
use touchdownstars\employee\job\Job;

/**
 * Class Employee
 * @package touchdownstars\employee
 *
 * @method int getId()
 * @method void setId(int $id)
 * @method string getFirstName()
 * @method void setFirstName(string $firstName)
 * @method string getLastName()
 * @method void setLastName(string $lastName)
 * @method int getAge()
 * @method void setAge(int $age)
 * @method string getNationality()
 * @method void setNationality(string $nationality)
 * @method int getOvr()
 * @method void setOvr(int $ovr)
 * @method int getTalent()
 * @method void setTalent(int $talent)
 * @method float getExperience()
 * @method void setExperience(float $experience)
 * @method float|null getMoral()
 * @method void setMoral(float $moral)
 * @method int getUnemployedSeasons()
 * @method void setUnemployedSeasons(int $unemployedSeasons)
 * @method int getMarketvalue()
 * @method void setMarketvalue(int $marketvalue)
 * @method int|null getIdTeam()
 * @method void setIdTeam(int|null $idTeam)
 * @method Job getJob()
 * @method void setJob(Job $job)
 * @method Contract|null getContract()
 * @method void setContract(Contract|null $contract)
 */
#[Setter, Getter]
class Employee extends Helper implements JsonSerializable
{
    private int $id;
    private string $firstName;
    private string $lastName;
    private int $age;
    private string $nationality;
    private int $ovr;
    private int $talent;
    private float $experience;
    private ?float $moral;
    private int $unemployedSeasons;
    private int $marketvalue;
    private ?int $idTeam = null;
    private int $idJob;
    private Job $job;
    private ?int $idContract = null;
    private ?Contract $contract = null;

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->getId(),
            'firstName' => $this->getFirstName(),
            'lastName' => $this->getLastName(),
            'age' => $this->getAge(),
            'nationality' => $this->getNationality(),
            'ovr' => $this->getOvr(),
            'talent' => $this->getTalent(),
            'experience' => $this->getExperience(),
            'moral' => $this->getMoral(),
            'unemployedSeasons' => $this->getUnemployedSeasons(),
            'marketvalue' => $this->getMarketvalue(),
            'idTeam' => $this->getIdTeam(),
            'job' => json_encode($this->getJob()),
            'contract' => json_encode($this->getContract())
        ];
    }
}