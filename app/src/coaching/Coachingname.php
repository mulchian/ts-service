<?php


namespace touchdownstars\coaching;


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
 * @method string getGameplanName()
 * @method void setGameplanName(string $gameplanName)
 * @method string getTeamPart()
 * @method void setTeamPart(string $teamPart)
 */
#[Setter, Getter]
class Coachingname extends Helper
{
    private int $id;
    private int $idTeam;
    private int $gameplanNr;
    private string $gameplanName;
    private string $teamPart;
}