<?php

namespace touchdownstars\player;

use JsonSerializable;
use Lombok\Getter;
use Lombok\Helper;
use Lombok\Setter;
use Tebru\Gson\Gson;
use touchdownstars\contract\Contract;
use touchdownstars\player\character\Character;
use touchdownstars\player\draft\Draftposition;
use touchdownstars\player\status\Status;
use touchdownstars\player\type\Type;

/**
 * Class Player
 * @package touchdownstars\player
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
 * @method int getHeight()
 * @method void setHeight(int $height)
 * @method int getWeight()
 * @method void setWeight(int $weight)
 * @method int getMarketValue()
 * @method void setMarketValue(int $marketValue)
 * @method float getEnergy()
 * @method void setEnergy(float $energy)
 * @method float getMoral()
 * @method void setMoral(float $moral)
 * @method float getMinContractMoral()
 * @method void setMinContractMoral(float $minContractMoral)
 * @method int getExperience()
 * @method void setExperience(int $experience)
 * @method int getTalent()
 * @method void setTalent(int $talent)
 * @method float getSkillpoints()
 * @method void setSkillpoints(float $skillpoints)
 * @method int getTimeInLeague()
 * @method void setTimeInLeague(int $timeInLeague)
 * @method bool isHallOfFame()
 * @method void setHallOfFame(bool $isHallOfFame)
 * @method string getTrainingGroup()
 * @method void setTrainingGroup(string $trainingGroup)
 * @method int getIntensity()
 * @method void setIntensity(int $intensity)
 * @method int getNumberOfTrainings()
 * @method void setNumberOfTrainings(int $numberOfTrainings)
 * @method string|null getLineupPosition()
 * @method void setLineupPosition(string $lineupPosition)
 * @method array getSkills()
 * @method void setSkills(array $skills)
 * @method int|null getIdTeam()
 * @method void setIdTeam(int $idTeam)
 * @method string|null getTeamName()
 * @method void setTeamName(string $teamName)
 * @method Status getStatus()
 * @method void setStatus(Status $status)
 * @method Character getCharacter()
 * @method void setCharacter(Character $character)
 * @method Contract|null getContract()
 * @method void setContract(Contract $contract)
 * @method Draftposition|null getDraftposition()
 * @method void setDraftposition(Draftposition $draftposition)
 * @method int getIdType()
 * @method void setIdType(int $idType)
 * @method Type getType()
 * @method void setType(Type $type)
 * @method array getStatistics()
 * @method void setStatistics(array $statistics)
 *
 */
#[Setter, Getter]
class Player extends Helper implements JsonSerializable
{
    private int $id;
    private string $firstName;
    private string $lastName;
    private int $age;
    private string $nationality;
    private int $height;
    private int $weight;
    private int $marketValue;
    private float $energy;
    private float $moral = 0.8;
    private float $minContractMoral = 0.75;
    private int $experience;
    private int $talent;
    private float $skillpoints = 0.0;
    private int $timeInLeague;
    private bool $hallOfFame = false;
    private string $trainingGroup;
    private int $intensity;
    private int $numberOfTrainings;
    private ?string $lineupPosition = null;
    private array $skills = array();
    private ?int $idTeam = null;
    private ?string $teamName = null;

    private int $idStatus;
    private Status $status;
    private int $idCharacter;
    private Character $character;
    private ?int $idContract = null;
    private ?Contract $contract = null;
    private ?int $idDraftposition = null;
    private ?Draftposition $draftposition = null;
    private int $idType;
    private Type $type;
    private array $statistics = array();

    private float $ovr = 0.0;

    public function getOVR(): int
    {
        if ($this->ovr == 0) {
            $special = array('K', 'P');
            $pos = $this->getType()->getPosition()->getPosition();
            if (in_array($pos, $special)) {
                $this->ovr = ($pos == 'K' ? $this->skills['kickAccuracy'] : $this->skills['puntAccuracy']) + $this->skills['power'];
                if ($this->ovr > 0) {
                    $this->ovr = intval(floor($this->ovr / 2));
                }
            } else {
                foreach ($this->skills as $skillValue) {
                    $this->ovr += $skillValue;
                }
                if ($this->ovr > 0) {
                    $this->ovr = intval(floor($this->ovr / count($this->skills)));
                }
            }
        }
        return (int)$this->ovr;
    }

    public function getJson(): string
    {
        $gson = Gson::builder()->build();
        return $gson->toJson($this);
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->getId(),
            'firstName' => $this->getFirstName(),
            'lastName' => $this->getLastName(),
            'age' => $this->getAge(),
            'nationality' => $this->getNationality(),
            'height' => $this->getHeight(),
            'weight' => $this->getWeight(),
            'marketValue' => $this->getMarketValue(),
            'energy' => $this->getEnergy(),
            'moral' => $this->getMoral(),
            'minContractMoral' => $this->getMinContractMoral(),
            'experience' => $this->getExperience(),
            'talent' => $this->getTalent(),
            'skillpoints' => $this->getSkillpoints(),
            'timeInLeague' => $this->getTimeInLeague(),
            'hallOfFame' => $this->isHallOfFame(),
            'trainingGroup' => $this->getTrainingGroup(),
            'intensity' => $this->getIntensity(),
            'numberOfTrainings' => $this->getNumberOfTrainings(),
            'lineupPosition' => $this->getLineupPosition(),
            'skills' => $this->getSkills(),
            'ovr' => $this->getOVR(),
            'idTeam' => $this->getIdTeam(),
            'teamName' => $this->getTeamName(),
            'status' => $this->getStatus(),
            'character' => $this->getCharacter(),
            'contract' => $this->getContract(),
            'draftposition' => $this->getDraftposition(),
            'type' => $this->getType(),
            'statistics' => $this->getStatistics()
        ];
    }
}