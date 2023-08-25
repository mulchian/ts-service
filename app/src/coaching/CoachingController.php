<?php


namespace touchdownstars\coaching;

use PDO;
use Monolog\Logger;
use touchdownstars\team\Team;

class CoachingController
{

    private PDO $pdo;
    private ?Logger $log;

    /**
     * TypeController constructor.
     * @param PDO $pdo
     * @param Logger|null $log
     */
    public function __construct(PDO $pdo, Logger $log = null)
    {
        $this->pdo = $pdo;
        $this->log = $log;
    }

    public function fetchCoaching(int $idTeam, int $gameplanNr, string $teamPart, int $down, string $playrange): Coaching
    {
        $stmt = $this->pdo->prepare('SELECT * from `t_coaching` where idTeam = :idTeam and gameplanNr = :gameplanNr and teamPart = :teamPart and down = :down and playrange = :playrange;');
        $stmt->execute(['idTeam' => $idTeam, 'gameplanNr' => $gameplanNr, 'teamPart' => $teamPart, 'down' => $down, 'playrange' => $playrange]);
        $stmt->setFetchMode(PDO::FETCH_CLASS, 'touchdownstars\\coaching\\Coaching');
        $coaching = $stmt->fetch(PDO::FETCH_CLASS);

        if (!isset($coaching) || !$coaching) {
            $coaching = new Coaching();
            $coaching->setIdTeam($idTeam);
            $coaching->setGameplanNr($gameplanNr);
            $coaching->setTeamPart($teamPart);
            $coaching->setDown($down);
            $coaching->setPlayrange($playrange);
            $coaching->setGameplay1('Run;Inside Run');
            $coaching->setGameplay2('Run;Inside Run');
            $coaching->setRating(50);
        }

        return $coaching;
    }

    public function fetchAllCoachings(int $idTeam): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM `t_coaching` WHERE idTeam = :idTeam;');
        $stmt->execute(['idTeam' => $idTeam]);
        $stmt->setFetchMode(PDO::FETCH_CLASS, 'touchdownstars\\coaching\\Coaching');
        return $stmt->fetchAll(PDO::FETCH_CLASS, 'touchdownstars\\coaching\\Coaching');
    }

    public function fetchAllCoachingnames(int $idTeam): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM `t_coachingname` WHERE idTeam = :idTeam;');
        $stmt->execute(['idTeam' => $idTeam]);
        $stmt->setFetchMode(PDO::FETCH_CLASS, 'touchdownstars\\coaching\\Coachingname');
        return $stmt->fetchAll(PDO::FETCH_CLASS, 'touchdownstars\\coaching\\Coachingname');
    }

    /**
     * Speichert das Coaching in der Datenbank (Tabelle t_coaching).
     * Falls es noch nicht in der Datenbank gespeichert wurde, wird es erstmalig eingefügt, ansonsten wir es aktualisiert.
     * @param Team $team
     * @param Coaching $coaching
     * @return int - lastInsertId: ID des neu eingefügten Coachings oder des aktualisierten Coachings. 0 bei Fehler.
     */
    public
    function saveCoaching(Team $team, Coaching $coaching): int
    {
        $selectStmt = $this->pdo->prepare('SELECT id from `t_coaching` where idTeam = :idTeam and gameplanNr = :gameplanNr and teamPart = :teamPart and down = :down and playrange = :playrange;');
        $selectStmt->execute(['idTeam' => $team->getId(), 'gameplanNr' => $coaching->getGameplanNr(), 'teamPart' => $coaching->getTeamPart(), 'down' => $coaching->getDown(), 'playrange' => $coaching->getPlayrange()]);
        $id = $selectStmt->fetch(PDO::FETCH_ASSOC)['id'];

        $saveCoaching = 'INSERT INTO `t_coaching` (id, idTeam, gameplanNr, teamPart, down, playrange, gameplay1, gameplay2, rating) values (:id, :idTeam, :gameplanNr, :teamPart, :down, :playrange, :gameplay1, :gameplay2, :rating) 
                            ON DUPLICATE KEY UPDATE gameplay1 = :newGameplay1, gameplay2 = :newGameplay2, rating = :newRating;';
        $saveStmt = $this->pdo->prepare($saveCoaching);
        $saveStmt->execute([
            'id' => $id ?? null,
            'idTeam' => $team->getId(),
            'gameplanNr' => $coaching->getGameplanNr(),
            'teamPart' => $coaching->getTeamPart(),
            'down' => $coaching->getDown(),
            'playrange' => $coaching->getPlayrange(),
            'gameplay1' => $coaching->getGameplay1(),
            'gameplay2' => $coaching->getGameplay2(),
            'rating' => $coaching->getRating(),
            'newGameplay1' => $coaching->getGameplay1(),
            'newGameplay2' => $coaching->getGameplay2(),
            'newRating' => $coaching->getRating()
        ]);

        return $this->pdo->lastInsertId();
    }

    /**
     * Speichert den Coachingname in der Datenbank (Tabelle t_coachingname).
     * Falls dieser noch nicht in der Datenbank gespeichert wurde, wird er erstmalig eingefügt, ansonsten wir er aktualisiert.
     * @param Team $team
     * @param Coachingname $newCoachingname
     * @return int - lastInsertId: ID des neu eingefügten Coachingname oder des aktualisierten Coachingname. 0 bei Fehler.
     */
    public
    function saveCoachingname(Team $team, Coachingname $newCoachingname): int
    {
        $selectStmt = $this->pdo->prepare('SELECT id from `t_coachingname` where idTeam = :idTeam and gameplanNr = :gameplanNr and teamPart = :teamPart;');
        $selectStmt->execute(['idTeam' => $team->getId(), 'gameplanNr' => $newCoachingname->getGameplanNr(), 'teamPart' => $newCoachingname->getTeamPart()]);
        $id = $selectStmt->fetch(PDO::FETCH_ASSOC)['id'];

        $saveCoachingname = 'INSERT INTO `t_coachingname` (id, idTeam, gameplanNr, teamPart, gameplanName) values (:id, :idTeam, :gameplanNr, :teamPart, :gameplanName) 
                            ON DUPLICATE KEY UPDATE gameplanName = :newGameplanName;';
        $saveStmt = $this->pdo->prepare($saveCoachingname);
        $saveStmt->execute([
            'id' => $id ?? null,
            'idTeam' => $team->getId(),
            'gameplanNr' => $newCoachingname->getGameplanNr(),
            'teamPart' => $newCoachingname->getTeamPart(),
            'gameplanName' => $newCoachingname->getGameplanName(),
            'newGameplanName' => $newCoachingname->getGameplanName()
        ]);

        return $this->pdo->lastInsertId();
    }

    public function getCoachingFromTeam(Team $team, int $gameplanNr, string $teamPart, string $down, string $playrange): Coaching
    {
        $coaching = array_values(array_filter($team->getCoachings(), function (Coaching $coaching) use ($gameplanNr, $teamPart, $down, $playrange) {
            return $coaching->getGameplanNr() == $gameplanNr && $coaching->getTeamPart() == $teamPart && $coaching->getDown() == $down && $coaching->getPlayrange() == $playrange;
        }))[0];

        if (!isset($coaching) || !$coaching) {
            $coaching = new Coaching();
            $coaching->setIdTeam($team->getId());
            $coaching->setGameplanNr($gameplanNr);
            $coaching->setTeamPart($teamPart);
            $coaching->setDown($down);
            $coaching->setPlayrange($playrange);
            if ($teamPart == 'Offense') {
                $coaching->setGameplay1('Run;Inside Run');
                $coaching->setGameplay2('Run;Inside Run');
            } else {
                $coaching->setGameplay1($playrange == 'Run' ? 'Run;Box' : 'Pass;Coverage');
                $coaching->setGameplay2($playrange == 'Run' ? 'Run;Box' : 'Pass;Coverage');
            }
            $coaching->setRating(50);
        }

        return $coaching;
    }

    public function getGeneralCoachingFromTeam(Team $team, int $gameplanNr, string $down): Coaching
    {
        $coaching = array_values(array_filter($team->getCoachings(), function (Coaching $coaching) use ($gameplanNr, $down) {
            return $coaching->getGameplanNr() == $gameplanNr && $coaching->getTeamPart() == 'General' && $coaching->getDown() == $down && $coaching->getPlayrange() == 'General';
        }))[0];

        if (!isset($coaching) || !$coaching) {
            $coaching = new Coaching();
            $coaching->setIdTeam($team->getId());
            $coaching->setGameplanNr($gameplanNr);
            $coaching->setTeamPart('General');
            $coaching->setDown($down);
            $coaching->setPlayrange('General');
            if ($down == '1st') {
                $coaching->setGameplay1('FGRange;30');
                $coaching->setGameplay2('2PtCon;0');
            } else {
                $coaching->setGameplay1('4thDown;Nie');
                $coaching->setGameplay2('QBRun;0');
            }
            $coaching->setRating(50);
        }

        return $coaching;
    }

    public function getRatingsForDown(Team $team, int $gameplanNr, string $teamPart, string $down): array
    {
        $coachings = array_values(array_filter($team->getCoachings(), function (Coaching $coaching) use ($gameplanNr, $teamPart, $down) {
            return $coaching->getGameplanNr() == $gameplanNr && $coaching->getTeamPart() == $teamPart && $coaching->getDown() == $down;
        }));

        $ratings = array();
        foreach ($coachings as $coaching) {
            $ratings[$coaching->getPlayrange()] = $coaching->getRating();
        }

        return $ratings;
    }

    public function createBotCoaching(Team $team): void
    {
        $botOffense = [
            '1stShort' => 'Pass;Medium Pass,Run;Inside Run',
            '1stMiddle' => 'Pass;Medium Pass,Run;Inside Run',
            '1stLong' => 'Pass;Medium Pass,Run;Outside Run links',
            '2ndShort' => 'Pass;Short Pass,Run;Outside Run links',
            '2ndMiddle' => 'Pass;Screen Pass,Run;Inside Run',
            '2ndLong' => 'Pass;Short Pass,Run;Outside Run rechts',
            '3rdShort' => 'Pass;Short Pass,Run;Inside Run',
            '3rdMiddle' => 'Pass;Medium Pass,Run;Outside Run rechts',
            '3rdLong' => 'Pass;Long Pass,Pass;Medium Pass',
            '4thShort' => 'Pass;Short Pass,Run;Inside Run',
            '4thMiddle' => 'Pass;Long Pass,Pass;Medium Pass',
            '4thLong' => 'Pass;Long Pass,Pass;Medium Pass'
        ];

        $coaching = new Coaching();
        $coaching->setIdTeam($team->getId());
        $coaching->setGameplanNr(1);
        $coaching->setRating(50);
        foreach (['Offense', 'Defense'] as $teamPart) {
            $coaching->setTeamPart($teamPart);
            foreach (['1st', '2nd', '3rd', '4th'] as $down) {
                $coaching->setDown($down);
                if ($teamPart == 'Offense') {
                    foreach (['Short', 'Middle', 'Long'] as $playrange) {
                        $coaching->setPlayrange($playrange);

                        $gameplay = explode(',', $botOffense[$down . $playrange]);
                        $coaching->setGameplay1($gameplay[0]);
                        $coaching->setGameplay2($gameplay[1]);

                        $this->saveCoaching($team, $coaching);
                    }
                } else {
                    foreach (['Run', 'Pass'] as $playrange) {
                        $coaching->setPlayrange($playrange);
                        $coaching->setGameplay1($playrange . ';Auf Reaktion');
                        $coaching->setGameplay2($playrange . ';Auf Reaktion');

                        $this->saveCoaching($team, $coaching);
                    }
                }
            }
        }

        $general = new Coaching();
        $general->setIdTeam($team->getId());
        $general->setGameplanNr(1);
        $general->setTeamPart('General');
        $general->setPlayrange('General');
        $general->setRating(50);

        $general->setDown('1st');
        $general->setGameplay1('FGRange;30');
        $general->setGameplay2('2PtCon;0');
        $this->saveCoaching($team, $general);

        $general->setDown('2nd');
        $general->setGameplay1('4thDown;Nie');
        $general->setGameplay2('QBRun;0');
        $this->saveCoaching($team, $general);
    }
}