<?php

namespace touchdownstars\penalty;

use Lombok\Getter;
use Lombok\Helper;
use Lombok\Setter;

/**
 * @class Penalty
 * @package touchdownstars\penalty
 *
 * @method int getId()
 * @method void setId(int $id)
 * @method string getGameplay()
 * @method void setGameplay(string $gameplay)
 * @method string|null getTeamPart()
 * @method void setTeamPart(string $teamPart)
 * @method string getTimescale()
 * @method void setTimescale(string $timescale)
 * @method string getPenalty()
 * @method void setPenalty(string $penalty)
 * @method float getChance()
 * @method void setChance(float $chance)
 * @method int getYards()
 * @method void setYards(int $yards)
 * @method bool isFirstDown()
 * @method void setFirstDown(bool $firstDown)
 * @method string getPenaltyText()
 * @method void setPenaltyText(string $penaltyText)
 */
#[Setter, Getter]
class Penalty extends Helper
{
    private int $id;
    private string $gameplay;
    private ?string $teamPart = null;
    private string $timescale;
    private string $penalty;
    private float $chance;
    private int $yards;
    private bool $firstDown = false;
    private string $penaltyText;
}