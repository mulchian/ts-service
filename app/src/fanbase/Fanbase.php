<?php


namespace touchdownstars\fanbase;


use Lombok\Getter;
use Lombok\Setter;

/**
 * Class Fanbase
 * @package touchdownstars\fanbase
 *
 * @method int getId()
 * @method void setId(int $id)
 * @method int getAmount()
 * @method void setAmount(int $amount)
 * @method float|null getSatisfaction()
 * @method void setSatisfaction(float $satisfaction)
 * @method int|null getExpectedWins()
 * @method void setExpectedWins(int $expectedWins)
 */
#[Setter, Getter]
class Fanbase
{
    private int $id;
    private int $amount;
    private ?float $satisfaction;
    private ?int $expectedWins;
    private ?int $idTeam = null;
}