<?php


namespace touchdownstars\player\status;


use JsonSerializable;
use Lombok\Getter;
use Lombok\Helper;

/**
 * Class Status
 * @package touchdownstars\player\status
 *
 * @method int getId()
 * @method string getDescription()
 */
#[Getter]
class Status extends Helper implements JsonSerializable
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