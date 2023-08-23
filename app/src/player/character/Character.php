<?php


namespace touchdownstars\player\character;


use Lombok\Getter;
use Lombok\Helper;

/**
 * Class Character
 * @package touchdownstars\player\character
 *
 * @method int getId()
 * @method string getDescription()
 */
#[Getter]
class Character extends Helper
{
    private int $id;
    private string $description;
}