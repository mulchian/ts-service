<?php

namespace touchdownstars\main;

use DateTime;
use JsonSerializable;
use Lombok\Getter;
use Lombok\Helper;
use Lombok\Setter;

/**
 * class Main
 * @package touchdownstars\main
 * @method int getId()
 * @method void setId(int $id)
 * @method DateTime getFirstGameday()
 * @method void setFirstGameday(DateTime $firstGameday)
 * @method DateTime getLastSeasonday()
 * @method void setLastSeasonday(DateTime $lastSeasonday)
 * @method int getSeason()
 * @method void setSeason(int $season)
 * @method Gameweek getGameweek()
 * @method void setGameweek(Gameweek $gameweek)
 * @method int getGameday()
 * @method void setGameday(int $gameday)
 * @method int getHighestSalaryCap()
 * @method void setHighestSalaryCap(int $highestSalaryCap)
 * @method int getStartBudget()
 * @method void setStartBudget(int $startBudget)
 * @method DateTime getLastChanged()
 * @method void setLastChanged(DateTime $lastChanged)
 */
#[Setter, Getter]
class Main extends Helper implements JsonSerializable
{
    private int $id;
    private DateTime $firstGameday;
    private DateTime $lastSeasonday;
    private int $season;
    private Gameweek $gameweek;
    private int $gameday;
    private int $highestSalaryCap;
    private int $startBudget;
    private DateTime $lastChanged;

    public function __set($name, $value) {
        if ($name == 'firstGamedayString') {
            $this->firstGameday = new DateTime($value);
        } else if ($name == 'lastSeasondayString') {
            $this->lastSeasonday = new DateTime($value);
        } else if ($name == 'lastChangedString') {
            $this->lastChanged = new DateTime($value);
        } else if ($name == 'gameweekValue') {
            $this->gameweek = Gameweek::from($value);
        } else {
            $this->$name = $value;
        }
    }

    public static function fromData(array $data): self
    {
        $instance = new self();
        $instance->setId((int)$data['id']);
        $instance->setFirstGameday(new DateTime($data['firstGameday']));
        $instance->setLastSeasonday(new DateTime($data['lastSeasonday']));
        $instance->setSeason((int)$data['season']);
        $instance->setGameweek(Gameweek::from((int)$data['gameweek']));
        $instance->setGameday((int)$data['gameday']);
        $instance->setHighestSalaryCap((int)$data['highestSalaryCap']);
        $instance->setStartBudget((int)$data['startBudget']);
        $instance->setLastChanged(new DateTime($data['lastChanged']));
        return $instance;
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->getId(),
            'firstGameday' => $this->getFirstGameday()->format('Y-m-d H:i:s'),
            'lastSeasonday' => $this->getLastSeasonday()->format('Y-m-d H:i:s'),
            'season' => $this->getSeason(),
            'gameweek' => $this->getGameweek()->value,
            'gameday' => $this->getGameday(),
            'highestSalaryCap' => $this->getHighestSalaryCap(),
            'startBudget' => $this->getStartBudget(),
            'lastChanged' => $this->getLastChanged()->format('Y-m-d H:i:s')
        ];
    }
}

