<?php


namespace touchdownstars\league;


use Lombok\Getter;
use Lombok\Helper;
use Lombok\Setter;

/**
 * Class Conference
 * @package touchdownstars\league
 *
 * @method int getId()
 * @method void setId(int $id)
 * @method string getDescription()
 * @method void setDescription(string $description)
 */
#[Setter, Getter]
class Conference extends Helper
{
    private int $id;
    private string $description;
}