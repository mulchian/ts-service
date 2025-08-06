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
class Coaching extends Helper implements JsonSerializable
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
            'teamPart' => $this->getTeamPart(),
            'down' => $this->getDown(),
            'playrange' => $this->getPlayrange(),
            'gameplay1' => $this->getGameplay1(),
            'gameplay2' => $this->getGameplay2(),
            'rating' => $this->getRating()
        ];
    }
}