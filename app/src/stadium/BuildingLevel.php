<?php

namespace touchdownstars\stadium;

use JsonSerializable;
use Lombok\Getter;
use Lombok\Helper;
use Lombok\Setter;

/**
 * class BuildingLevel
 * @package touchdownstars\stadium
 *
 * @method int getId()
 * @method void setId(int $id)
 * @method int getLevel()
 * @method void setLevel(int $level)
 * @method int getBuildTime()
 * @method void setBuildTime(int $buildTime)
 * @method int getPrice()
 * @method void setPrice(int $price)
 * @method string getDescription()
 * @method void setDescription(string $description)
 * @method array getBuildingEffects()
 * @method void setBuildingEffects(array $buildingEffects)
 * @method BuildingLevel|null getRequiredBuilding()
 * @method void setRequiredBuilding(?BuildingLevel $requiredBuilding)
 */
#[Getter, Setter]
class BuildingLevel extends Helper implements JsonSerializable
{
    private int $id;
    private int $level;
    private int $buildTime;
    private int $price;
    private string $description;
    private array $buildingEffects = [];
    private ?BuildingLevel $requiredBuilding = null;

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->getId(),
            'level' => $this->getLevel(),
            'buildTime' => $this->getBuildTime(),
            'price' => $this->getPrice(),
            'description' => $this->getDescription(),
            'buildingEffects' => $this->getBuildingEffects(),
            'requiredBuilding' => $this->getRequiredBuilding()
        ];
    }
}