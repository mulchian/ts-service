<?php


namespace touchdownstars\player\draft;


use Lombok\Getter;
use Lombok\Helper;
use Lombok\Setter;
use touchdownstars\league\League;

/**
 * Class Draftposition
 * @package touchdownstars\player\draft
 *
 * @method int getId()
 * @method void setId(int $id)
 * @method int getSeason()
 * @method void setSeason(int $season)
 * @method int|null getRound()
 * @method void setRound(int $round)
 * @method int|null getPick()
 * @method void setPick(int $pick)
 * @method bool isDrafted()
 * @method void setDrafted(bool $drafted)
 * @method League getLeague()
 * @method void setLeague(League $league)
 */
#[Setter, Getter]
class Draftposition extends Helper
{
    private int $id;
    private int $season;
    private ?int $round;
    private ?int $pick;
    private bool $drafted = false;
    private League $league;
}