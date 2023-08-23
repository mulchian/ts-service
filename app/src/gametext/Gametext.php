<?php


namespace touchdownstars\gametext;


use Lombok\Getter;
use Lombok\Helper;
use Lombok\Setter;

/**
 * Class Gametext
 * @package touchdownstars\gametext
 *
 * @method int getId()
 * @method void setId(int $id)
 * @method string getLanguage()
 * @method void setLanguage(string $language)
 * @method string|null getGameplay()
 * @method void setGameplay(string $gameplay)
 * @method string|null getSituation()
 * @method void setSituation(string $situation)
 * @method string|null getTextName()
 * @method void setTextName(string $textName)
 * @method string|null getTriggeringPosition()
 * @method void setTriggeringPosition(string $triggeringPosition)
 * @method int|null getPlayrangeVon()
 * @method void setPlayrangeVon(int $playrangeVon)
 * @method int|null getPlayrangeBis()
 * @method void setPlayrangeBis(int $playrangeBis)
 * @method bool isTd()
 * @method void setTd(bool $td)
 * @method string getText()
 * @method void setText(string $text)
 */
#[Setter, Getter]
class Gametext extends Helper
{
    private int $id;
    private string $language;
    private ?string $gameplay;
    private ?string $situation;
    private ?string $textName;
    private ?string $triggeringPosition;
    private ?int $playrangeVon;
    private ?int $playrangeBis;
    private bool $td = false;
    private string $text;
}