<?php


namespace touchdownstars\player\position;


use Lombok\Getter;
use Lombok\Helper;

/**
 * Class Position
 * @package touchdownstars\player\position
 *
 * @method string getPosition()
 * @method string getDescription()
 * @method int getCountStarter()
 * @method int getCountBackup()
 */
#[Getter]
class Position extends Helper
{
    private int $id;
    private string $position;
    private string $description;
    private int $countStarter;
    private int $countBackup;
}