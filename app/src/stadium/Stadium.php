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
 * @method string getDescription()
 * @method void setDescription(string $description)
 * @method array getBuildings()
 * @method void setBuildings(array $buildings)
 */
#[Setter, Getter]
class Stadium extends Helper
{
    private int $id;
    private string $name;
    private string $description;
    private array $buildings;
    private int $idTeam;
}