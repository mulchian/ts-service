<?php


namespace touchdownstars\stadium;

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
 * @method int getLevel()
 * @method void setLevel(int $level)
 * @method int getMaxLevel()
 * @method void setMaxLevel(int $maxLevel)
 * @method string getDescription()
 * @method void setDescription(string $description)
 */
#[Setter, Getter]
class Building extends Helper
{
    private int $id;
    private string $name;
    private int $level;
    private int $maxLevel;
    private string $description;
    // TODO: BuildingEffect implementieren
    // private $buildingEffect;
}