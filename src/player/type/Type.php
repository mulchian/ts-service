<?php

namespace touchdownstars\player\type;


use Lombok\Getter;
use Lombok\Helper;
use Lombok\Setter;
use touchdownstars\player\position\Position;

/**
 * Class Type
 * @package touchdownstars\player\type
 *
 * @method int getId()
 * @method void setId(int $id)
 * @method Position getPosition()
 * @method void setPosition(Position $position)
 * @method string getDescription()
 * @method void setDescription(string $description)
 * @method int getMinHeight()
 * @method void setMinHeight(int $minHeight)
 * @method int getMaxHeight()
 * @method void setMaxHeight(int $maxHeight)
 * @method int getMinWeight()
 * @method void setMinWeight(int $minWeight)
 * @method int getMaxWeight()
 * @method void setMaxWeight(int $maxWeight)
 * @method string getAssignedTeamPart()
 * @method void setAssignedTeamPart(string $assignedTeamPart)
 */
#[Setter, Getter]
class Type extends Helper
{
    private int $id;
    private Position $position;
    private string $description;
    private int $minHeight;
    private int $maxHeight;
    private int $minWeight;
    private int $maxWeight;
    private string $assignedTeamPart;

    public function __construct(int $id, Position $position, string $description, int $minHeight, int $maxHeight, int $minWeight, int $maxWeight, string $assignedTeamPart)
    {
        parent::__construct();
        $this->id = $id;
        $this->position = $position;
        $this->description = $description;
        $this->minHeight = $minHeight;
        $this->maxHeight = $maxHeight;
        $this->minWeight = $minWeight;
        $this->maxWeight = $maxWeight;
        $this->assignedTeamPart = $assignedTeamPart;
    }
}