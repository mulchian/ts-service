<?php


namespace touchdownstars\employee\job;


use JsonSerializable;
use Lombok\Getter;
use Lombok\Helper;
use Lombok\Setter;

/**
 * Class Job
 * @package touchdownstars\employee\job
 *
 * @method int getId()
 * @method void setId(int $id)
 * @method string getName()
 * @method void setName(string $name)
 * @method string getDescription()
 * @method void setDescription(string $description)
 */
#[Setter, Getter]
class Job extends Helper implements JsonSerializable
{
    private int $id;
    private string $name;
    private string $description;

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->getId(),
            'name' => $this->getName(),
            'description' => $this->getDescription()
        ];
    }
}