<?php

namespace touchdownstars\stadium;


use JsonSerializable;
use Lombok\Getter;
use Lombok\Helper;
use Lombok\Setter;

/**
 * class BuildingEffect
 * @package touchdownstars\stadium
 *
 * @method int getId()
 * @method void setId(int $id)
 * @method string getName()
 * @method void setName(string $name)
 * @method string getDescription()
 * @method void setDescription(string $description)
 * @method float getValue()
 * @method void setValue(float $value)
 */
#[Setter, Getter]
class BuildingEffect extends Helper implements JsonSerializable
{
    private int $id;
    private string $name;
    private string $description;
    private float $value;

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->getId(),
            'name' => $this->getName(),
            'description' => $this->getDescription(),
            'value' => $this->getValue()
        ];
    }
}