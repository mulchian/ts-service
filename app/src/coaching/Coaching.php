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
 * @method string getTeamPart()
 * @method void setTeamPart(string $teamPart)
 * @method string getDown()
 * @method void setDown(string $down)
 * @method string getPlayrange()
 * @method void setPlayrange(string $playrange)
 * @method string getGameplay1()
 * @method void setGameplay1(string $gameplay1)
 * @method string getGameplay2()
 * @method void setGameplay2(string $gameplay2)
 * @method int getRating()
 * @method void setRating(int $rating)
 */
#[Setter, Getter]
class Coaching extends Helper
{
    private int $id;
    private int $idTeam;
    private int $gameplanNr;
    private string $teamPart;
    private string $down;
    private string $playrange;
    private string $gameplay1;
    private string $gameplay2;
    private int $rating;
}