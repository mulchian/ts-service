<?php


namespace touchdownstars\player\position;


use JsonSerializable;
use Lombok\Getter;
use Lombok\Helper;

/**
 * Class Position
 * @package touchdownstars\player\position
 *
 * @method int getId()
 * @method string getPosition()
 * @method string getDescription()
 * @method int getCountStarter()
 * @method int getCountBackup()
 */
#[Getter]
class Position extends Helper implements JsonSerializable
{
    private int $id;
    private string $position;
    private string $description;
    private int $countStarter;
    private int $countBackup;

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->getId(),
            'position' => $this->getPosition(),
            'description' => $this->getDescription(),
            'countStarter' => $this->getCountStarter(),
            'countBackup' => $this->getCountBackup()
        ];
    }
}