<?php


namespace touchdownstars\stadium;

use DateTime;
use DateTimeZone;
use JsonSerializable;
use Lombok\Getter;
use Lombok\Helper;
use Lombok\Setter;

/**
 * class Stadium
 * @package touchdownstars\stadium
 *
 * @method int getId()
 * @method void setId(int $id)
 * @method string getName()
 * @method void setName(string $name)
 * @method string getTeaser()
 * @method void setTeaser(string $teaser)
 * @method int getLevel()
 * @method void setLevel(int $level)
 * @method int getMaxLevel()
 * @method void setMaxLevel(int $maxLevel)
 * @method string getDescription()
 * @method void setDescription(string $description)
 * @method DateTime|null getUpgradeTime()
 * @method void setUpgradeTime(?DateTime $upgradeTime)
 * @method array getBuildingLevels()
 * @method void setBuildingLevels(array $buildingLevels)
 */
#[Setter, Getter]
class Building extends Helper implements JsonSerializable
{
    private int $id;
    private string $name;
    private string $teaser;
    private int $level;
    private int $maxLevel;
    private string $description;
    private ?DateTime $upgradeTime = null;
    private array $buildingLevels = [];

    public function __set(string $name, $value): void
    {
        if ($name === 'upgradeTimeString') {
            $this->upgradeTime = new DateTime($value, new DateTimeZone('Europe/Berlin'));
        } else {
            $this->$name = $value;
        }
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->getId(),
            'name' => $this->getName(),
            'teaser' => $this->getTeaser(),
            'level' => $this->getLevel(),
            'maxLevel' => $this->getMaxLevel(),
            'description' => $this->getDescription(),
            'maintenanceCost' => $this->getMaintenanceCost(),
            'buildingLevels' => $this->getBuildingLevels()
        ];
    }
}