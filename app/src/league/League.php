<?php


namespace touchdownstars\league;


use Lombok\Getter;
use Lombok\Helper;
use Lombok\Setter;

/**
 * @class League
 * @package touchdownstars\league
 *
 * @method int getId()
 * @method void setId(int $id)
 * @method string getDescription()
 * @method void setDescription(string $description)
 * @method int getLeagueNumber()
 * @method void setLeagueNumber(int $leagueNumber)
 * @method string getCountry()
 * @method void setCountry(string $country)
 */
#[Setter, Getter]
class League extends Helper
{
    private int $id;
    private string $description;
    private int $leagueNumber;
    private string $country;
}