<?php


namespace touchdownstars\league;


use Lombok\Getter;
use Lombok\Helper;
use Lombok\Setter;

/**
 * @class Division
 * @package touchdownstars\league
 *
 * @method int getId()
 * @method void setId(int $id)
 * @method string getDescription()
 * @method void setDescription(string $description)
 */
#[Setter, Getter]
class Division extends Helper
{
    private int $id;
    private string $description;
}