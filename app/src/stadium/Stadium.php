<?php


namespace touchdownstars\stadium;

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
 * @method string getDescription()
 * @method void setDescription(string $description)
 * @method array getBuildings()
 * @method void setBuildings(array $buildings)
 */
#[Setter, Getter]
class Stadium extends Helper implements JsonSerializable
{
    private int $id;
    private string $name;
    private string $description;
    private array $buildings;
    private int $idTeam;

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->getId(),
            'name' => $this->getName(),
            'description' => $this->getDescription(),
            'buildings' => $this->getBuildings()
        ];
    }
}