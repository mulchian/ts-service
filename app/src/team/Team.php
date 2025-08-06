<?php


namespace touchdownstars\team;


use JsonSerializable;
use Lombok\Getter;
use Lombok\Helper;
use Lombok\Setter;
use touchdownstars\fanbase\Fanbase;
use touchdownstars\league\Conference;
use touchdownstars\league\Division;
use touchdownstars\league\League;
use touchdownstars\stadium\Stadium;
use touchdownstars\user\User;

/**
 * class Team
 * @package touchdownstars\team
 * @method int getId()
 * @method void setId(int $id)
 * @method string getName()
 * @method void setName(string $name)
 * @method string getAbbreviation()
 * @method void setAbbreviation(string $abbreviation)
 * @method int getBudget()
 * @method void setBudget(int $budget)
 * @method int getSalaryCap()
 * @method void setSalaryCap(int $salaryCap)
 * @method int getCredits()
 * @method void setCredits(int $credits)
 * @method int getChemie()
 * @method void setChemie(int $chemie)
 * @method int getGameplanGeneral()
 * @method void setGameplanGeneral(int $gameplanGeneral)
 * @method int getGameplanOff()
 * @method void setGameplanOff(int $gameplanOff)
 * @method int getGameplanDef()
 * @method void setGameplanDef(int $gameplanDef)
 * @method string getLineupOff()
 * @method void setLineupOff(string $lineupOff)
 * @method string getLineupDef()
 * @method void setLineupDef(string $lineupDef)
 * @method Fanbase getFanbase()
 * @method void setFanbase(Fanbase $fanbase)
 * @method int getIdUser()
 * @method void setIdUser(int $idUser)
 * @method User getUser()
 * @method void setUser(User $user)
 * @method Stadium getStadium()
 * @method void setStadium(Stadium $stadium)
 * @method array getPlayers()
 * @method void setPlayers(array $players)
 * @method array getEmployees()
 * @method void setEmployees(array $employees)
 * @method League getLeague()
 * @method void setLeague(League $league)
 * @method Conference getConference()
 * @method void setConference(Conference $conference)
 * @method Division getDivision()
 * @method void setDivision(Division $division)
 * @method array getCoachings()
 * @method void setCoachings(array $coachings)
 * @method array getCoachingNames()
 * @method void setCoachingNames(array $coachingNames)
 * @method array getTeamPicture()
 * @method void setTeamPicture(array $teamPicture)
 * @method array getStatistics()
 * @method void setStatistics(array $statistics)
 */
#[Setter, Getter]
class Team extends Helper implements JsonSerializable
{

    private int $id;
    private string $name;
    private string $abbreviation;
    private int $budget;
    private int $salaryCap;
    private int $credits;
    private int $chemie;
    private int $gameplanGeneral;
    private int $gameplanOff;
    private int $gameplanDef;
    private string $lineupOff;
    private string $lineupDef;
    private ?int $idFanbase = null;
    private ?Fanbase $fanbase = null;
    private int $idUser;
    private User $user;
    private int $idStadium;
    private Stadium $stadium;
    private array $players;
    private array $employees;
    private int $idLeague;
    private League $league;
    private int $idConference;
    private Conference $conference;
    private int $idDivision;
    private Division $division;
    private array $coachings;
    private ?array $coachingNames = null;
    private ?array $teamPicture = null;
    /**
     * @var array $statistics - class StatisticsTeam with the statistics of the team
     */
    private array $statistics = array();

    private int $ovr = 0;

    public function getOVR(): int
    {
        if ($this->ovr == 0) {
            foreach ($this->players as $player) {
                $this->ovr += $player->getOVR();
            }
            if ($this->ovr > 0) {
                $this->ovr = intval(floor($this->ovr / count($this->players)));
            }
        }
        return $this->ovr;
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->getId(),
            'name' => $this->getName(),
            'abbreviation' => $this->getAbbreviation(),
            'budget' => $this->getBudget(),
            'salaryCap' => $this->getSalaryCap(),
            'credits' => $this->getCredits(),
            'chemie' => $this->getChemie(),
            'gameplanGeneral' => $this->getGameplanGeneral(),
            'gameplanOff' => $this->getGameplanOff(),
            'gameplanDef' => $this->getGameplanDef(),
            'lineupOff' => $this->getLineupOff(),
            'lineupDef' => $this->getLineupDef(),
            'fanbase' => $this->getFanbase(),
            'user' => $this->getUser(),
            'stadium' => $this->getStadium(),
            'players' => $this->getPlayers(),
            'employees' => $this->getEmployees(),
            'league' => $this->getLeague(),
            'conference' => $this->getConference(),
            'division' => $this->getDivision(),
            'coachings' => $this->getCoachings(),
            'coachingNames' => $this->getCoachingNames(),
            'teamPicture' => $this->getTeamPicture(),
            'statistics' => $this->getStatistics(),
            'ovr' => $this->getOVR()
        ];
    }
}