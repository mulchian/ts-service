<?php

namespace touchdownstars\statistics;

use Lombok\Getter;
use Lombok\Helper;
use Lombok\Setter;


/**
 * class StatisticsTeam
 * @package touchdownstars\statistics
 * @method int getId()
 * @method void setId(int $id)
 * @method int getSeason()
 * @method void setSeason(int $season)
 * @method int getGameId()
 * @method void setGameId(int $gameId)
 * @method int getPaAtt()
 * @method void setPaAtt(int $paAtt)
 * @method int getPaYds()
 * @method void setPaYds(int $paYds)
 * @method int getPaTd()
 * @method void setPaTd(int $paTd)
 * @method int getRuAtt()
 * @method void setRuAtt(int $ruAtt)
 * @method int getRuYds()
 * @method void setRuYds(int $ruYds)
 * @method int getRuTd()
 * @method void setRuTd(int $ruTd)
 * @method int getFirstDowns()
 * @method void setFirstDowns(int $firstDowns)
 * @method int getFirstDownsComp()
 * @method void setFirstDownsComp(int $firstDownsComp)
 * @method int getSecondDowns()
 * @method void setSecondDowns(int $secondDowns)
 * @method int getSecondDownsComp()
 * @method void setSecondDownsComp(int $secondDownsComp)
 * @method int getThirdDowns()
 * @method void setThirdDowns(int $thirdDowns)
 * @method int getThirdDownsComp()
 * @method void setThirdDownsComp(int $thirdDownsComp)
 * @method int getFourthDowns()
 * @method void setFourthDowns(int $fourthDowns)
 * @method int getFourthDownsComp()
 * @method void setFourthDownsComp(int $fourthDownsComp)
 * @method int getPenalties()
 * @method void setPenalties(int $penalties)
 * @method int getPenaltyYds()
 * @method void setPenaltyYds(int $penaltyYds)
 * @method int getSacks()
 * @method void setSacks(int $sacks)
 * @method int getPunts()
 * @method void setPunts(int $punts)
 * @method int getFumbles()
 * @method void setFumbles(int $fumbles)
 * @method int getLostFumbles()
 * @method void setLostFumbles(int $lostFumbles)
 * @method int getInterceptions()
 * @method void setInterceptions(int $interceptions)
 * @method int getTimeOfPossession()
 * @method void setTimeOfPossession(int $top)
 * @method int getIdTeam()
 * @method void setIdTeam(int $idTeam)
 */
#[Setter, Getter]
class StatisticsTeam extends Helper
{
    private int $id;
    private int $season;
    private int $gameId;

    private int $paAtt = 0;
    private int $paYds = 0;
    private int $paTd = 0;
    private int $ruAtt = 0;
    private int $ruYds = 0;
    private int $ruTd = 0;

    private int $firstDowns = 0;
    private int $firstDownsComp = 0;
    private int $secondDowns = 0;
    private int $secondDownsComp = 0;
    private int $thirdDowns = 0;
    private int $thirdDownsComp = 0;
    private int $fourthDowns = 0;
    private int $fourthDownsComp = 0;

    private int $penalties = 0;
    private int $penaltyYds = 0;

    private int $sacks = 0; // Erfolgreiche Sacks der Defense
    private int $punts = 0;
    private int $fumbles = 0;
    private int $lostFumbles = 0;
    private int $interceptions = 0;

    /**
     * @int $top - Time of Possession in seconds
     */
    private int $timeOfPossession = 0;
    private int $idTeam;

    public function getOvrYards(): int
    {
        return $this->getPaYds() + $this->getRuYds();
    }

    public function getYardsPerAtt(): float
    {
        if ($this->getPaAtt() + $this->getRuAtt() == 0) {
            return 0;
        }
        return floor($this->getOvrYards() / ($this->getPaAtt() + $this->getRuAtt()));
    }

    public function getEfficiencyForDown(string $down): int
    {
        return match ($down) {
            "1st" => $this->getFirstDownsComp() / ($this->getFirstDowns() > 0 ? $this->getFirstDowns() : 1),
            "2nd" => $this->getSecondDownsComp() / ($this->getSecondDowns() > 0 ? $this->getSecondDowns() : 1),
            "3rd" => $this->getThirdDownsComp() / ($this->getThirdDowns() > 0 ? $this->getThirdDowns() : 1),
            "4th" => $this->getFourthDownsComp() / ($this->getFourthDowns() > 0 ? $this->getFourthDowns() : 1),
            default => 0
        };
    }
}