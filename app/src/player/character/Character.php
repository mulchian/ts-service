<?php


namespace touchdownstars\player\character;


use JsonSerializable;
use Lombok\Getter;
use Lombok\Helper;

/**
 * Class Character
 * @package touchdownstars\player\character
 *
 * @method int getId()
 * @method string getDescription()
 */
#[Getter]
class Character extends Helper implements JsonSerializable
{
    private int $id;
    private string $description;

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->getId(),
            'description' => $this->getDescription()
        ];
    }
}