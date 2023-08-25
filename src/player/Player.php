<?php

namespace touchdownstars\player;

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
 * @method int getMarketvalue()
 * @method void setMarketvalue(int $marketvalue)
 * @method float getEnergy()
 * @method void setEnergy(float $energy)
 * @method float getMoral()
 * @method void setMoral(float $moral)
 * @method int getExperience()
 * @method void setExperience(int $experience)
 * @method int getTalent()
 * @method void setTalent(int $talent)
 * @method float getSkillpoints()
 * @method void setSkillpoints(float $skillpoints)
 * @method int getTimeInLeague()
 * @method void setTimeInLeague(int $timeInLeague)
 * @method bool isHallOfFame()
 * @method void setHallOfFame(bool $hallOfFame)
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
class Player extends Helper
{
    private int $id;
    private string $firstName;
    private string $lastName;
    private int $age;
    private string $nationality;
    private int $height;
    private int $weight;
    private int $marketvalue;
    private float $energy;
    private float $moral = 0.8;
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

    /**
     * @var Status $status - class Status with the actual players status
     */
    private Status $status;
    /**
     * @var Character $character - class Character with the players character
     */
    private Character $character;
    /**
     * @var Contract|null $contract - class Contract with the players contract
     */
    private ?Contract $contract = null;
    /**
     * @var Draftposition|null $draftposition - class Draftposition with the players position in his draft
     */
    private ?Draftposition $draftposition = null;
    private int $idType;
    /**
     * @var Type $type - class Type with the players playing type
     */
    private Type $type;
    /**
     * @var array $statisticsPlayer - class StatisticsPlayer with the statistics of the player
     */
    private array $statistics = array();

    private int $ovr = 0;

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
        return $this->ovr;
    }

    public function getJson(): string
    {
        $gson = Gson::builder()->build();
        return $gson->toJson($this);
    }
}