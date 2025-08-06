<?php


namespace touchdownstars\coaching;


use JsonSerializable;
use Lombok\Getter;
use Lombok\Helper;
use Lombok\Setter;

/**
 * Class Coachingname
 * @package touchdownstars\coaching
 *
 * @method int getId()
 * @method void setId(int $id)
 * @method int getIdTeam()
 * @method void setIdTeam(int $idTeam)
 * @method int getGameplanNr()
 * @method void setGameplanNr(int $gameplanNr)
 * @method string getName()
 * @method void setName(string $name)
 * @method string getTeamPart()
 * @method void setTeamPart(string $teamPart)
 */
#[Setter, Getter]
class CoachingName extends Helper implements JsonSerializable
{
    private int $id;
    private int $idTeam;
    private int $gameplanNr;
    private string $name;
    private string $teamPart;

    public function __construct(array $properties=array())
    {
        parent::__construct();
        foreach($properties as $key => $value){
            $this->{$key} = $value;
        }
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->getId(),
            'idTeam' => $this->getIdTeam(),
            'gameplanNr' => $this->getGameplanNr(),
            'name' => $this->getName(),
            'teamPart' => $this->getTeamPart()
        ];
    }
}