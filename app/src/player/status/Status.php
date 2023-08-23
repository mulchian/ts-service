<?php


namespace touchdownstars\player\status;


use Lombok\Getter;
use Lombok\Helper;

/**
 * Class Status
 * @package touchdownstars\player\status
 *
 * @method int getId()
 * @method string getDescription()
 */
#[Getter]
class Status extends Helper
{
    private int $id;
    private string $description;
}