<?php


namespace touchdownstars\team;


use PDO;
use touchdownstars\coaching\CoachingController;
use touchdownstars\employee\EmployeeController;
use touchdownstars\fanbase\Fanbase;
use touchdownstars\fanbase\FanbaseController;
use touchdownstars\league\Conference;
use touchdownstars\league\Division;
use touchdownstars\league\League;
use touchdownstars\league\LeagueController;
use Monolog\Logger;
use touchdownstars\player\Player;
use touchdownstars\player\PlayerController;
use touchdownstars\player\position\PositionController;
use touchdownstars\stadium\Stadium;
use touchdownstars\stadium\StadiumController;
use touchdownstars\statistics\StatisticsController;
use touchdownstars\user\User;
use touchdownstars\user\UserController;

class TeamController
{
    private Logger $log;
    private PDO $pdo;

    private array $offensePositions = array('QB', 'RB', 'FB', 'WR', 'TE', 'RT', 'RG', 'C', 'LG', 'LT');
    private array $defensePositions = array('LE', 'DT', 'NT', 'RE', 'LOLB', 'MLB', 'ROLB', 'CB', 'SS', 'FS');
    private array $specialPositions = array('K', 'P', 'R');

    public function __construct(PDO $pdo, Logger $log)
    {
        $this->pdo = $pdo;
        $this->log = $log;
    }

    public function fetchTeamNameById(int $teamId): ?string
    {
        $team = select($this->pdo, 'SELECT * FROM `t_team` where id = :teamId;', 'touchdownstars\\team\\Team', ['teamId' => $teamId]);
        if ($team) {
            return $team->getName();
        }
        return null;
    }

    public function fetchTeamById(int $teamId): ?Team
    {
        $team = null;
        if (isset($_SESSION['team'])) {
            $team = $_SESSION['team']->getId() == $teamId ? $_SESSION['team'] : null;
        }
        if (isset($_SESSION['team' . $teamId])) {
            $team = $_SESSION['team' . $teamId];
        }
        if (!isset($team)) {
            $team = select($this->pdo, 'SELECT * FROM `t_team` where id = :teamId;', 'touchdownstars\\team\\Team', ['teamId' => $teamId]);
            $team = $this->fetchTeam($team->idUser, $team->getName());
        }

        return $team;
    }

    /**
     * Gibt das zum User oder zum Teamnamen zugehörige Team zurück. (Kann sowohl Active-Teams als auch Bot-Teams zurückgeben)
     * Zu dem Team gibt werden direkt Fanbase, Stadion, Spieler und Mitarbeiter aus der Datenbank geladen.
     * @param int|null $idUser - Id des Users, muss mitgegeben werden.
     * @param string|null $teamName : Default = null
     * @return Team|null
     */
    public function fetchTeam(?int $idUser, ?string $teamName = null): ?Team
    {
        if (!isset($idUser) && !isset($teamName)) {
            return null;
        }

        // search for team in session
        $team = $_SESSION['team_' . $teamName] ?? null;
        if (isset($team)) {
            return $team;
        }

        $team = select($this->pdo, 'SELECT * FROM `t_team` where idUser = :idUser OR name = :teamname;', 'touchdownstars\\team\\Team', ['idUser' => $idUser, 'teamname' => $teamName]);

        if (!empty($team)) {
            // if we only got the idUser, we need to wait for the fetched team to get the teamname
            $fullTeam = $_SESSION['team_' . $team->getName()] ?? null;
            if (isset($fullTeam)) {
                return $fullTeam;
            }

            $coachingController = new CoachingController($this->pdo);

            if (isset($idUser) && $idUser > 0) {
                $fanbase = select($this->pdo, 'SELECT * FROM `t_fanbase` where idTeam = :idTeam;', 'touchdownstars\\fanbase\\Fanbase', ['idTeam' => $team->getId()]);
                if (!empty($fanbase)) {
                    $team->setFanbase($fanbase);
                }

                $stadiumController = new StadiumController($this->pdo);
                $stadium = $stadiumController->fetchStadium($team);
                if (!empty($stadium)) {
                    $team->setStadium($stadium);
                }

                $employeeController = new EmployeeController($this->pdo, $this->log);
                $team->setEmployees($employeeController->fetchEmployeesOfTeam($team));

                $team->setCoachingnames($coachingController->fetchAllCoachingnames($team->getId()));
            }

            $playerController = new PlayerController($this->pdo, $this->log);
            $team->setPlayers($playerController->fetchPlayers($team));

            $leagueController = new LeagueController($this->pdo, $this->log);
            $team->setLeague($leagueController->fetchLeagueForTeam($team));
            $team->setConference($leagueController->fetchConferenceForTeam($team));
            $team->setDivision($leagueController->fetchDivisionForTeam($team));

            $team->setCoachings($coachingController->fetchAllCoachings($team->getId()));

            $statisticsController = new StatisticsController($this->pdo, $this->log);
            $team->setStatistics($statisticsController->fetchStatisticsForTeam($team->getId()));

            $_SESSION['team_' . $team->getName()] = $team;

            return $team;
        } else {
            return null;
        }
    }

    /**
     * Holt alle Teams, denen ein User zugeordnet ist zum mitgegebenen Land.
     * @param string|null $leagueCountry - Land der Liga | Wird null mitgegeben, wird nicht aufs Land gefiltert
     * @param int $teamSelectionNum - 0 => alle Teams, 1 => alle Bot-Teams, 2 => alle aktiven Teams
     * @return array - Alle aktiven Teams oder leeren Array, wenn keine Teams gefunden wurden
     */
    public function fetchAllTeams(?string $leagueCountry, int $teamSelectionNum = 0): array
    {
        $whereOrAnd = ' WHERE ';
        $selectTeams = 'SELECT tt.* FROM `t_team` tt 
            JOIN `t_team_to_league` tttl ON tt.id = tttl.idTeam JOIN `t_league` tl ON tl.id = tttl.idLeague';

        if (null != $leagueCountry) {
            $selectTeams .= $whereOrAnd . 'tl.country = :country';
            $params = ['country' => $leagueCountry];
            $whereOrAnd = ' AND ';
        }

        if ($teamSelectionNum == 1) {
            // alle Bot-Teams
            $selectTeams .= $whereOrAnd . 'tt.idUser is null;';
        } elseif ($teamSelectionNum == 2) {
            // alle aktiven (Spieler-) Teams
            $selectTeams .= $whereOrAnd . 'tt.idUser is not null;';
        }

        $selectStmt = $this->pdo->prepare($selectTeams);
        $selectStmt->execute($params ?? null);
        $selectStmt->setFetchMode(PDO::FETCH_CLASS, 'touchdownstars\\team\\Team');
        $resultTeams = $selectStmt->fetchAll(PDO::FETCH_CLASS, 'touchdownstars\\team\\Team');

        if (isset($resultTeams) & !empty($resultTeams)) {
            if ($teamSelectionNum == 2) {
                $userController = new UserController($this->pdo, $this->log);
                foreach ($resultTeams as $team) {
                    $this->log->debug('Team: ' . $team->getName());
                    $team->setUser($userController->fetchUserById($team->idUser));
                }
            }
            $leagueController = new LeagueController($this->pdo, $this->log);
            foreach ($resultTeams as $team) {
                $team->setLeague($leagueController->fetchLeagueForTeam($team));
                $team->setConference($leagueController->fetchConferenceForTeam($team));
                $team->setDivision($leagueController->fetchDivisionForTeam($team));
            }
            return $resultTeams;
        }
        return array();
    }

    public function fetchTeamOfPlayer(int $idPlayer): ?Team
    {
        $team = select($this->pdo, 'SELECT tt.* FROM `t_team` tt LEFT JOIN `t_player` tp on tt.id = tp.idTeam where tp.id = :idPlayer;', 'touchdownstars\\team\\Team', ['idPlayer' => $idPlayer]);

        if (!empty($team)) {
            return $team;
        }

        return null;
    }

    /**
     * Erstellt ein neues Bot-Team. Überprüft vorher, ob zum Teamnamen bereits ein Team besteht.
     * Budget und SalaryCap ist 0. Ein Bot-Team bekommt keine Fanbase oder Stadion.
     * @param string $teamName
     * @param string $abbreviation
     * @param array|null $leagueTeams
     * @param League $league
     * @return Team|null - Das erstellte Team oder null, falls bereits ein Team mit dem Teamnamen besteht.
     */
    public function registerNewBotTeam(string $teamName, string $abbreviation, ?array $leagueTeams, League $league): ?Team
    {
        // Registriere neues Bot-Team.
        // Teamname muss einmalig sein.
        $team = $this->fetchTeam(null, $teamName);

        if (!$team) {
            $offPositions = array('TE', 'FB');
            $defPositions = array('NT', 'MLB');
            $offKey = array_rand($offPositions);
            $defKey = array_rand($defPositions);
            $insertNewTeam = 'INSERT INTO `t_team` (name, abbreviation, budget, salaryCap, lineupOff, lineupDef) values (:teamname, :abbreviation, :budget, :salaryCap, :lineupOff, :lineupDef);';
            $insertStmt = $this->pdo->prepare($insertNewTeam);
            $insertStmt->execute([
                'teamname' => $teamName,
                'abbreviation' => $abbreviation,
                'budget' => 0,
                'salaryCap' => 0,
                'lineupOff' => $offPositions[$offKey],
                'lineupDef' => $defPositions[$defKey]
            ]);

            $team = select($this->pdo, 'SELECT * FROM `t_team` where name = :teamname;', 'touchdownstars\\team\\Team', ['teamname' => $teamName]);

            //Bot-Team wird in die nächste freie Division platziert
            if (!isset($leagueTeams)) {
                $country = $league->getCountry();
                $leagueTeams = array_filter($this->fetchAllTeams($country), function (Team $value) use ($country, $league) {
                    return $value->getLeague()->getCountry() == $country && $value->getLeague()->getLeagueNumber() == $league->getLeagueNumber();
                });
            }
            $this->setTeamInDivision($team, $leagueTeams, $league);

            $team = $this->fetchTeam(null, $teamName);

            $team->setPlayers($this->createPlayers($team, true));

            $this->updateLineup($team);
            $this->updateLineup($team, 'b');

            $coachingController = new CoachingController($this->pdo);
            $coachingController->createBotCoaching($team);
            $team->setCoachings($coachingController->fetchAllCoachings($team->getId()));

            return $team;
        }
        return null;
    }

    /**
     * Erstellt ein neues Team. Überprüft vorher, ob der User bereits ein Team hat/hatte.
     * Legt zu dem Team eine Start-Fanbase und ein Start-Stadion mit Gebäuden an.
     * @param User $user
     * @param String $teamName
     * @param String $abbreviation
     * @param string $conferenceName
     * @param string $country - Default => 'Deutschland'
     * @return Team|null - Das erstellte Team oder null, falls bereits ein Team mit dem Teamnamen besteht.
     */
    public function registerNewTeam(User $user, string $teamName, string $abbreviation, string $conferenceName, string $country = 'Deutschland'): ?Team
    {
        // Registriere neues Team.
        // Teamname muss einmalig sein.
        $team = $this->fetchTeam($user->getId(), $teamName);

        if (!$team) {
            // Lösche Bot-Team, in der höchst möglichen Liga.
            // Es muss zusätzlich der Spielplan angepasst werden, also muss der neue Teamname den des Bot-Teams ersetzen.
            $botTeam = $this->searchBotTeamForDelete($conferenceName, $country);

            // Wird kein Bot-Team gefunden, kann auch kein Team angelegt werden.
            if (isset($botTeam) && !empty($botTeam)) {
                $league = $botTeam->getLeague();

                // Erstelle Team und speichere es in der Datenbank
                $team = new Team();
                $team->setName($teamName);
                $team->setAbbreviation($abbreviation);
                $team->setBudget($this->getStartBudget());
                $team->setSalaryCap($this->getStartSalaryCap($league));
                $team->setUser($user);

                $team->setGameplanOff(1);
                $team->setGameplanDef(1);
                $team->setLineupOff('TE');
                $team->setLineupDef('NT');

                $team->setId($this->saveTeam($team));

                // Erstelle Fanbase und speichere diese im Team
                $fanbase = new Fanbase();
                $fanbase->setAmount(5000);
                $fanbaseController = new FanbaseController($this->pdo);
                $fanbase = $fanbaseController->saveFanbase($team, $fanbase);
                $team->setFanbase($fanbase);

                // Erstelle Stadion und speichere es inklusive der Startgebäude
                $stadium = new Stadium();
                $stadium->setName($team->getName() . ' Stadion');
                $stadium->setDescription('Das ist das Stadion von ' . $team->getName());
                $stadiumController = new StadiumController($this->pdo);
                $stadium->setId($stadiumController->saveStadium($team, $stadium));
                $stadiumWithBuildings = $stadiumController->saveBuildingsToStadium($stadium);
                $team->setStadium($stadiumWithBuildings);

                // Wird ein freies Bot-Team gefunden, kann der Spielplan des Bot-Teams für das neue Team geändert und das Bot-Team gelöscht werden.
                $botTeamPlayers = $botTeam->getPlayers();
                if ($this->deleteTeam($botTeam, true)) {
                    $playerController = new PlayerController($this->pdo, $this->log);
                    foreach ($botTeamPlayers as $botTeamPlayer) {
                        $playerController->deletePlayer($botTeamPlayer);
                    }
                    $leagueController = new LeagueController($this->pdo, $this->log);
                    $leagueController->updateGameSchedule($botTeam->getName(), $team->getName());
                }

                $allTeams = $this->fetchAllTeams($country);
                $leagueTeams = array_filter($allTeams, function (Team $value) use ($country, $league) {
                    return $value->getLeague()->getCountry() == $country && $value->getLeague()->getLeagueNumber() == $league->getLeagueNumber();
                });
                // Registriere Team in der gewünschten Conference der League
                $team = $this->setTeamInDivision($team, $leagueTeams, $league, $conferenceName);

                $team->setPlayers($this->createPlayers($team));

                // automatische Aufstellung
                $this->updateLineup($team);
                $this->updateLineup($team, 'b');

                // Standard-Coachings speichern
                $coachingController = new CoachingController($this->pdo);
                $coachingController->createBotCoaching($team);
                $team->setCoachings($coachingController->fetchAllCoachings($team->getId()));

                // Die Gehälter der Start-Contracts der Spieler vom saisonalen Salary Cap abziehen.
                $salaryCost = 0;
                foreach ($team->getPlayers() as $player) {
                    $salaryCost += $player->getContract()->getSalary() + $player->getContract()->getSigningBonus();
                }
                $team->setSalaryCap($team->getSalaryCap() - $salaryCost);
                $this->saveTeam($team);
            } else {
                error_log('Es wurde kein Bot-Team gefunden, um das neue Team ' . $teamName . ' in der Liga zu registrieren.');
            }

            if ($team) {
                return $team;
            }
        }
        return null;
    }

    /**
     * Speichert das Team in der Datenbank.
     * Falls es noch nicht in der Datenbank gespeichert wurde, wird es erstmalig eingefügt, ansonsten wir es aktualisiert.
     * @param Team $team
     * @return int - lastInsertId: ID des neu eingefügten Teams oder des aktualisierten Teams. 0 bei Fehler.
     */
    public
    function saveTeam(Team $team): int
    {
        $selectStmt = $this->pdo->prepare('SELECT id from `t_team` where name = :name;');
        $selectStmt->execute(['name' => $team->getName()]);
        $id = $selectStmt->fetch(PDO::FETCH_ASSOC)['id'];

        $saveTeam = 'INSERT INTO `t_team` (id, name, abbreviation, budget, salaryCap, credits, idUser) values (:id, :teamname, :abbreviation, :budget, :salaryCap, :credits, :idUser) 
                            ON DUPLICATE KEY UPDATE name = :newTeamname, abbreviation = :newAbbreviation, budget = :newBudget, salaryCap = :newSalaryCap, credits = :newCredits;';
        $saveStmt = $this->pdo->prepare($saveTeam);
        $saveStmt->execute([
            'id' => $id ?? null,
            'teamname' => $team->getName(),
            'abbreviation' => $team->getAbbreviation(),
            'budget' => $team->getBudget(),
            'salaryCap' => $team->getSalaryCap(),
            'credits' => $team->getCredits(),
            'idUser' => $team->getUser()->getId(),
            'newTeamname' => $team->getName(),
            'newAbbreviation' => $team->getAbbreviation(),
            'newBudget' => $team->getBudget(),
            'newSalaryCap' => $team->getSalaryCap(),
            'newCredits' => $team->getCredits()
        ]);

        $_SESSION['team_' . $team->getName()] = $team;

        return $this->pdo->lastInsertId();
    }

    private function deleteTeam(Team $team, bool $isBotTeam = false): bool
    {
        $deleteTeam = 'DELETE FROM `t_team` WHERE id = :idTeam AND name = :name ';
        if ($isBotTeam) {
            $deleteTeam .= 'AND idUser is null;';
        } else {
            $deleteTeam .= ';';
        }

        $deleteStmt = $this->pdo->prepare($deleteTeam);
        $deleteStmt->execute(['idTeam' => $team->getId(), 'name' => $team->getName()]);

        if ($deleteStmt->rowCount() == 1) {
            return true;
        }

        return false;
    }

    public
    function setTeamInDivision(Team $team, array $leagueTeams, League $league, string $conferenceName = null): Team
    {
        $leagueController = new LeagueController($this->pdo, $this->log);

        $conferences = $this->getConferences($leagueTeams);
        foreach ($conferences as $conferenceKey => $conferenceTeams) {
            if (isset($conferenceName) && !empty($conferenceName)) {
                $conference = $leagueController->fetchConference($conferenceName);
            } else {
                $conference = $leagueController->fetchConference($conferenceKey);
            }
            if (count($conferenceTeams) == 0 || count($conferenceTeams) % 16 != 0) {
                if (count($conferenceTeams) != 0) {
                    // Conference hat Teams, also können die Teams in Divisions gefiltert werden.
                    $divisions = $this->getDivisions($conferenceTeams);
                    foreach ($divisions as $divisionKey => $divisionTeams) {
                        if (count($divisionTeams) == 0 || count($divisionTeams) % 4 != 0) {
                            $division = $leagueController->fetchDivision($divisionKey);
                            $this->saveTeamToLeague($team, $league, $conference, $division);
                            $team->setLeague($league);
                            $team->setConference($conference);
                            $team->setDivision($division);
                            break;
                        }
                    }
                } else {
                    // Conference hat keine Teams, also wird das Bot-Team in 'Division North' gesteckt
                    $division = $leagueController->fetchDivision('Division North');
                    $this->saveTeamToLeague($team, $league, $conference, $division);
                    $team->setLeague($league);
                    $team->setConference($conference);
                    $team->setDivision($division);
                    break;
                }
            }
        }

        return $team;
    }

    public
    function saveTeamToLeague(Team $team, League $league, Conference $conference, Division $division): int
    {
        $selectStmt = $this->pdo->prepare('SELECT id from `t_team_to_league` where idTeam = :idTeam');
        $selectStmt->execute(['idTeam' => $team->getId()]);
        $id = $selectStmt->fetch(PDO::FETCH_ASSOC)['id'];

        $saveTeamToLeague = 'INSERT INTO `t_team_to_league` (id, idTeam, idLeague, idConference, idDivision) VALUES (:id, :idTeam, :idLeague, :idConference, :idDivision)
                                ON DUPLICATE KEY UPDATE idLeague = :newIdLeague, idConference = :newIdConference, idDivision = :newIdDivision';
        $saveStmt = $this->pdo->prepare($saveTeamToLeague);
        $saveStmt->execute([
            'id' => $id ?? null,
            'idTeam' => $team->getId(),
            'idLeague' => $league->getId(),
            'idConference' => $conference->getId(),
            'idDivision' => $division->getId(),
            'newIdLeague' => $league->getId(),
            'newIdConference' => $conference->getId(),
            'newIdDivision' => $division->getId()
        ]);

        return $this->pdo->lastInsertId();
    }

    /**
     * Gibt das StartBudget zurück.
     * @return int
     */
    private
    function getStartBudget(): int
    {
        $selectStmt = $this->pdo->prepare('SELECT startBudget FROM `t_main` WHERE id = 1');
        $selectStmt->execute();
        $startBudget = $selectStmt->fetch(PDO::FETCH_ASSOC)['startBudget'];
        if (isset($startBudget) && $startBudget) {
            return $startBudget;
        } else {
            return 50000000;
        }
    }

    /**
     * Gibt das Start-SalaryCap für neue Teams zurück.
     * Das SalaryCap ist angepasst an die Ligen.
     * @param League $league
     * @return int
     */
    private
    function getStartSalaryCap(League $league): int
    {
        // Höchstes SalaryCap (Liga 1) kommt aus Datenbank zur einfacheren Anpassung.
        // Anpassung des SalaryCaps an die Liga (Liga 1 150 Mio | Liga 2 140 Mio | Liga 3 130 Mio etc.)
        $selectStmt = $this->pdo->prepare('SELECT highestSalaryCap FROM `t_main` WHERE id = 1');
        $selectStmt->execute();
        $highestSalaryCap = $selectStmt->fetch(PDO::FETCH_ASSOC)['highestSalaryCap'];

        $leagueCap = ($league->getLeagueNumber() - 1) * 10000;
        if (isset($highestSalaryCap) && $highestSalaryCap) {
            $salaryCap = $highestSalaryCap - $leagueCap;
        } else {
            $salaryCap = 150000000 - $leagueCap;
        }

        return $salaryCap;
    }

    public
    function updateSalaryCap(Team $team, int $remainingDays, int $salary): int
    {
        $salaryCap = $team->getSalaryCap();
        // Berechnung: Salary / 28 Spieltage * restliche Spieltage
        return $salaryCap + floor($salary / 28) * $remainingDays;
    }

    /**
     * Berechnet die durchschnittliche Moral aller aktiven Spieler und gibt diese zurück.
     * Team-Moral = Moral aller Starting-Spieler addiert und geteilt durch die Anzahl der Starting-Spieler.
     * @param array $activePlayers
     * @return int - durchschnittliche Team-Moral
     */
    public
    function getAverageMoral(array $activePlayers): int
    {
        $avgMoral = 0;
        foreach ($activePlayers as $player) {
            $avgMoral += $player->getMoral();
        }
        return $avgMoral / count($activePlayers);
    }

    /**
     * Erstellt 47 Spieler für ein neu registriertes Team.
     * @param Team $team - neu erstelltes Team
     * @param bool $isBotTeam - Default: false - true, wenn ein Bot-Team erstellt werden soll.
     * @return array - Array der erstellten Spieler für das Team
     */
    private
    function createPlayers(Team $team, bool $isBotTeam = false): array
    {
        $minimalPositions = [
            'QB' => 2,
            'RB' => 2,
            'WR' => 5,
            'OT' => 3,
            'CB' => 5,
            'MLB' => 3,
            'DE' => 4,
            'OLB' => 4,
            'C' => 2,
            'DT' => 4,
            'TE' => 1,
            'OG' => 3,
            'SS' => 2,
            'FS' => 2,
            'FB' => 1,
            'K' => 1,
            'P' => 1,
            'R' => 2
        ];

        $returners = ['WR', 'RB', 'CB', 'SS', 'FS'];

        $players = array();
        $playerController = new PlayerController($this->pdo, $this->log);
        $positionController = new PositionController($this->pdo);
        foreach (array_keys($minimalPositions) as $positionAbb) {
            if ($positionAbb !== 'R') {
                $position = $positionController->fetchPosition($positionAbb);
            } else {
                $position = $positionController->fetchPosition($returners[rand(0, (count($returners) - 1))]);
            }
            for ($i = 0; $i < $minimalPositions[$positionAbb]; $i++) {
                $player = $playerController->createNewPlayer($team, $position, true, $isBotTeam);
                if (isset($player)) {
                    $players[] = $player;
                }
            }
        }

        return $players;
    }

    /**
     * Gibt die Trainingsgruppe mit dem vom Spieler gewählten Namen zurück.
     * Default-Namen sind Trainingsgruppe 1, 2, 3
     * @param Team $team - Team zu dem die Trainingsgruppe selektiert wird
     * @param $trainingGroup - Trainingsgruppe Enum('TE1', 'TE2', 'TE3')
     * @return string - Name der Trainingsgruppe oder 'Keine'
     */
    public function getTrainingGroup(Team $team, $trainingGroup): string
    {
        $selectStmt = $this->pdo->prepare('SELECT name FROM `t_team_to_traininggroup` where idTeam = :idTeam and trainingGroup = :trainingGroup');
        $selectStmt->execute(['idTeam' => $team->getId(), 'trainingGroup' => $trainingGroup]);
        $nameResult = $selectStmt->fetch(PDO::FETCH_ASSOC);
        $name = $nameResult['name'];

        if (!$name) {
            $name = match ($trainingGroup) {
                'TE1' => 'Trainingsgruppe 1',
                'TE2' => 'Trainingsgruppe 2',
                'TE3' => 'Trainingsgruppe 3',
                default => 'Keine',
            };
        }

        return $name;
    }

    /**
     * Gibt die Trainingszeit der Trainingsgruppe des Teams zurück.
     * @param Team $team - Team zu dem die Trainingszeit selektiert wird.
     * @param string $trainingGroup - Trainingsgruppe Enum('TE1', 'TE2', 'TE3') zu der die Trainingszeit selektiert wird.
     * @return int|null - Gibt den Datenbankeintrag der Trainingszeit zurück. Null, wenn kein Datenbankeintrag besteht.
     */
    public function getTimeToCount(Team $team, string $trainingGroup): ?int
    {
        $selectStmt = $this->pdo->prepare('SELECT trainingTime FROM `t_team_to_traininggroup` where idTeam = :idTeam and trainingGroup = :trainingGroup');
        $selectStmt->execute(['idTeam' => $team->getId(), 'trainingGroup' => $trainingGroup]);
        $timeResult = $selectStmt->fetch(PDO::FETCH_ASSOC);
        return $timeResult['trainingTime'];
    }

    /**
     * Überprüft, ob mindestens ein Spieler des Teams in der mitgegebenen Trainingsgruppe ist.
     * @param Team $team
     * @param string $trainingGroup
     * @return bool
     */
    public function trainingGroupHasPlayer(Team $team, string $trainingGroup): bool
    {
        foreach ($team->getPlayers() as $player) {
            if ($player->getTrainingGroup() == $trainingGroup) {
                return true;
            }
        }
        return false;
    }

    /**
     * Gibt alle Spieler-IDs aus dem Team zu der mitgegebenen Trainingsgruppe als Array zurück.
     * @param Team $team
     * @param string $trainingGroup -
     * @return array - Liste mit den Spieler-IDs der Trainingsgruppe
     */
    public function getPlayersToTrainingGroup(Team $team, string $trainingGroup): array
    {
        $playerIds = array();
        foreach ($team->getPlayers() as $player) {
            if ($player->getTrainingGroup() == $trainingGroup) {
                $playerIds[] = ['rowId' => $player->getId(), 'numberOfTrainings' => $player->getNumberOfTrainings()];
            }
        }
        return $playerIds;
    }

    /**
     * Speichert die mitgegebene Trainingszeit in der Datenbank zu dem Team und der Trainingsgruppe.
     * Falls kein Eintrag besteht, wird ein neuer angelegt, ansonsten wird der vorhandene aktualisiert.
     * @param Team $team - Team zu dem die Zeit gespeichert werden soll.
     * @param string $trainingGroup - Trainingsgruppe Enum('TE1', 'TE2', 'TE3') zu der die Trainingszeit gespeichert wird.
     * @param int $timeToCount - Zeitstempel, der in der Datenbank gespeichert werden soll.
     * @return int - lastInsertId: ID des neu eingefügten Stadions oder des aktualisierten Stadions. 0 bei Fehler.
     */
    public function saveTimeToCount(Team $team, string $trainingGroup, int $timeToCount): int
    {
        $selectStmt = $this->pdo->prepare('SELECT id from `t_team_to_traininggroup` where idTeam = :idTeam and trainingGroup = :trainingGroup;');
        $selectStmt->execute(['idTeam' => $team->getId(), 'trainingGroup' => $trainingGroup]);
        $id = $selectStmt->fetch(PDO::FETCH_ASSOC)['id'];

        $saveStmt = $this->pdo->prepare('INSERT INTO `t_team_to_traininggroup` (id, idTeam, trainingGroup, trainingTime) 
            VALUES (:id, :idTeam, :trainingGroup, :trainingTime) ON DUPLICATE KEY UPDATE trainingTime = :newTrainingTime;');
        $saveStmt->execute([
            'id' => $id ?? null,
            'idTeam' => $team->getId(),
            'trainingGroup' => $trainingGroup,
            'trainingTime' => $timeToCount,
            'newTrainingTime' => $timeToCount
        ]);

        return $this->pdo->lastInsertId();
    }

    /**
     * Speichert den vom Spieler eingegebenen Namen der Trainingsgruppe.
     * Falls kein Eintrag besteht, wird ein neuer angelegt, ansonsten wird der vorhandene aktualisiert.
     * @param Team $team - Team zu dem der Name der Trainingsgruppe gespeichert werden soll.
     * @param string $trainingGroup - Trainingsgruppe Enum('TE1', 'TE2', 'TE3') zu der der neue Name gespeichert wird.
     * @param string $trainingGroupName - Der neue Name für die Trainingsgruppe
     * @return int - lastInsertId: ID des neu eingefügten Stadions oder des aktualisierten Stadions. 0 bei Fehler.
     */
    public function saveTrainingGroupName(Team $team, string $trainingGroup, string $trainingGroupName): int
    {
        $selectStmt = $this->pdo->prepare('SELECT id from `t_team_to_traininggroup` where idTeam = :idTeam and trainingGroup = :trainingGroup;');
        $selectStmt->execute(['idTeam' => $team->getId(), 'trainingGroup' => $trainingGroup]);
        $id = $selectStmt->fetch(PDO::FETCH_ASSOC)['id'];

        $saveStmt = $this->pdo->prepare('INSERT INTO `t_team_to_traininggroup` (id, idTeam, trainingGroup, name) 
            VALUES (:id, :idTeam, :trainingGroup, :name) ON DUPLICATE KEY UPDATE name = :newName');
        $saveStmt->execute([
            'id' => $id ?? null,
            'idTeam' => $team->getId(),
            'trainingGroup' => $trainingGroup,
            'name' => $trainingGroupName,
            'newName' => $trainingGroupName
        ]);

        return $this->pdo->lastInsertId();
    }

    /**
     * Speichert den Haupt-Gameplan für Offense und Defense.
     * @param Team $team - Team zu dem der Haupt-Gameplan gespeichert werden soll.
     * @param string $gameplan - der Teampart für den Gameplan (Offense oder Defense)
     * @param int $gameplanNr - die zu speichernde Gameplan-Nr
     */
    public function updateGameplan(Team $team, string $gameplan, int $gameplanNr): void
    {
        if ($gameplan == 'GameplanOff') {
            $team->setGameplanOff($gameplanNr);
            $updateStmt = $this->pdo->prepare('UPDATE `t_team` SET gameplanOff = :gameplan where id = :idTeam;');
        } else {
            $team->setGameplanDef($gameplanNr);
            $updateStmt = $this->pdo->prepare('UPDATE `t_team` SET gameplanDef = :gameplan where id = :idTeam;');
        }
        $updateStmt->execute(['gameplan' => $gameplanNr, 'idTeam' => $team->getId()]);
    }

    /**
     * Gibt zurück, ob das Team einen Mitarbeiter passend zum Job-Namen angestellt hat oder nicht.
     * @param Team $team - Team, in dem nach dem Mitarbeiter gesucht wird.
     * @param string $jobName - Jobname nach dem im Team gesucht werden soll.
     * @return bool - true, wenn das Team einen Mitarbeiter mit dem Jobnamen angestellt hat, ansonsten false
     */
    public function hasEmployee(Team $team, string $jobName): bool
    {
        foreach ($team->getEmployees() as $employee) {
            if ($employee->getJob()->getName() == $jobName) {
                return true;
            }
        }
        return false;
    }

    /**
     * Gibt zurück, ob das Team einen Spieler passend zur Spieler-ID hat oder nicht.
     * @param Team $team - Team, in dem nach dem Mitarbeiter gesucht wird.
     * @param int $idPlayer - Player-ID, nach der im Team gesucht werden soll.
     * @return bool - true, wenn das Team einen Spieler mit mit der Spieler-ID hat, ansonsten false
     */
    public function hasPlayer(Team $team, int $idPlayer): bool
    {
        foreach ($team->getPlayers() as $player) {
            if ($player->getId() == $idPlayer) {
                return true;
            }
        }
        return false;
    }

    public function getConferences(array $leagueTeams): array
    {
        $conferences = array();
        $conferences['Conference North'] = array_values(array_filter($leagueTeams, function (Team $value) {
            return $value->getConference()->getDescription() == 'Conference North';
        }));
        $conferences['Conference South'] = array_values(array_filter($leagueTeams, function (Team $value) {
            return $value->getConference()->getDescription() == 'Conference South';
        }));
        return $conferences;
    }

    public function getDivisions(array $conferenceTeams): array
    {
        $divisions = array();
        $divisions['Division North'] = array_values(array_filter($conferenceTeams, function (Team $value) {
            return $value->getDivision()->getDescription() == 'Division North';
        }));
        $divisions['Division East'] = array_values(array_filter($conferenceTeams, function (Team $value) {
            return $value->getDivision()->getDescription() == 'Division East';
        }));
        $divisions['Division West'] = array_values(array_filter($conferenceTeams, function (Team $value) {
            return $value->getDivision()->getDescription() == 'Division West';
        }));
        $divisions['Division South'] = array_values(array_filter($conferenceTeams, function (Team $value) {
            return $value->getDivision()->getDescription() == 'Division South';
        }));
        return $divisions;
    }

    public function getRecommendedConference(string $country): string
    {
        $activeTeams = $this->fetchAllTeams($country, 2);
        $conferences = $this->getConferences($activeTeams);

        if (!empty($conferences)) {
            if (count($conferences['Conference North']) <= count($conferences['Conference South'])) {
                return 'Conference North';
            } else {
                return 'Conference South';
            }
        } else {
            return 'Conference North';
        }
    }

    private function searchBotTeamForDelete(string $conferenceName, string $country): ?Team
    {
        $firstAvailableBotTeam = $this->searchFirstAvailableBotTeam($conferenceName, $country);

        if (null == $firstAvailableBotTeam) {
            // Keine freie Conference nach Wunsch des Spielers gefunden.
            // Erneuter Versuch mit der anderen Conference
            if ($conferenceName == 'Conference North') {
                $conferenceName = 'Conference South';
            } else {
                $conferenceName = 'Conference North';
            }
            $firstAvailableBotTeam = $this->searchFirstAvailableBotTeam($conferenceName, $country);
        }

        return $firstAvailableBotTeam;
    }

    private function searchFirstAvailableBotTeam(string $conferenceName, string $country): ?Team
    {
        $leagueController = new LeagueController($this->pdo, $this->log);
        $leagues = $leagueController->fetchAllLeagues($country);
        $allTeams = $this->fetchAllTeams($country, 1);

        usort($leagues, function (League $league1, League $league2) {
            return $league1->getLeagueNumber() <=> $league2->getLeagueNumber();
        });

        foreach ($leagues as $league) {
            $leagueTeams = array_values(array_filter($allTeams, function (Team $value) use ($country, $league) {
                return $value->getLeague()->getCountry() == $country && $value->getLeague()->getLeagueNumber() == $league->getLeagueNumber();
            }));

            $conferences = $this->getConferences($leagueTeams);
            // suche das erste Bot-Team und lösche es
            if (count($conferences[$conferenceName]) > 0) {
                // Bot-Team gefunden wird zum Löschen zurückgegeben
                return $conferences[$conferenceName][0];
            }
        }
        return null;
    }

    /**
     * Holt die elf aufgestellten Spieler für den Spielzug
     * @param Team $team
     * @param string|null $teamPart - Teil des Teams: Offense, Defense oder Special (Default)
     * @return array
     */
    public function getStartingPlayers(Team $team, string $teamPart = null): array
    {
        $startingPlayers = array_values(array_filter($team->getPlayers(), function (Player $player) {
            return null != $player->getLineupPosition();
        }));

        if (null != $teamPart) {
            $startingPlayers = array_values(array_filter($startingPlayers, function (Player $player) use ($teamPart) {
                $positions = match ($teamPart) {
                    'Offense' => $this->offensePositions,
                    'Defense' => $this->defensePositions,
                    default => $this->specialPositions,
                };
                if (str_contains($player->getLineupPosition(), 'RB')) {
                    $lineupPosition = 'RB';
                } elseif (str_contains($player->getLineupPosition(), 'MLB')) {
                    $lineupPosition = 'MLB';
                } else {
                    $lineupPosition = $player->getLineupPosition();
                }

                return in_array($lineupPosition, $positions);
            }));
        }

        return $startingPlayers;
    }

    public function getPlayerIdsToLineupPosition(Team $team, string $lineupPosition): array
    {
        $playerIds = array();
        foreach ($team->getPlayers() as $player) {
            if ($player->getLineupPosition() == $lineupPosition) {
                $playerIds[] = $player->getId();
            }
        }
        return $playerIds;
    }

    public function getTeamPartToPosition(string $lineupPosition): ?string
    {
        if (in_array($lineupPosition, $this->offensePositions)) {
            return 'Offense';
        } else if (in_array($lineupPosition, $this->defensePositions)) {
            return 'Defense';
        } else if (in_array($lineupPosition, $this->specialPositions)) {
            return 'Special';
        } else {
            return null;
        }
    }

    public function updateLineupFlag(Team $team, string $lineupPosition): Team
    {
        $teamPart = $this->getTeamPartToPosition($lineupPosition);
        $query = 'UPDATE `t_team` SET lineup' . substr($teamPart, 0, 3) . ' = :lineupPosition where id = :idTeam;';

        $updateStmt = $this->pdo->prepare($query);
        $updateStmt->execute(['lineupPosition' => $lineupPosition, 'idTeam' => $team->getId()]);

        if ($teamPart == 'Offense') {
            $team->setLineupOff($lineupPosition);
        } else {
            $team->setLineupDef($lineupPosition);
        }

        return $team;
    }

    public function updateLineup(Team $team, string $backup = ''): Team
    {
        $startTime = microtime(true);
        $this->log->debug('start auto lineup');

        $positionController = new PositionController($this->pdo);
        $playerController = new PlayerController($this->pdo, $this->log);
        $positions = array_merge($this->offensePositions, $this->defensePositions, $this->specialPositions);
        $specialPos = array('RT' => 'OT', 'RG' => 'OG', 'LG' => 'OG', 'LT' => 'OT', 'LE' => 'DE', 'RE' => 'DE', 'LOLB' => 'OLB', 'ROLB' => 'OLB', 'NT' => 'DT');

        $startingPlayers = $this->getStartingPlayers($team);
        if ('' == $backup) {
            foreach ($startingPlayers as $player) {
                $playerController->updateLineupPosition($player->getId(), null);
                $player->setLineupPosition(null);
            }
        }

        foreach ($positions as $pos) {
            $lineupPos = $pos;
            if (in_array($pos, array_keys($specialPos))) {
                $pos = $specialPos[$pos];
            }

            if ($lineupPos == 'R') {
                $returnPos = array('RB', 'WR', 'CB', 'SS', 'FS');
                $positionalPlayer = array_values(array_filter($team->getPlayers(), function (Player $player) use ($returnPos) {
                    return in_array($player->getType()->getPosition()->getPosition(), $returnPos);
                }));
            } else {
                $positionalPlayer = array_values(array_filter($team->getPlayers(), function (Player $player) use ($pos) {
                    return $player->getType()->getPosition()->getPosition() == $pos;
                }));
            }

            usort($positionalPlayer, function (Player $player1, Player $player2) {
                return $player1->getOVR() <= $player2->getOVR();
            });

            //Logging
            $this->log->debug('Anzahl Positional-Player ' . $pos . ': ' . count($positionalPlayer));
            $positionalPlayerString = '';
            foreach ($positionalPlayer as $player) {
                $positionalPlayerString .= $player->getId() . ' | ' . $player->getType()->getPosition()->getPosition() . ' | ' . $player->getLineupPosition() . ' | ' . $player->getOVR() . ' | ';
            }
            $this->log->debug($pos . ': ' . $positionalPlayerString);

            $position = $positionController->fetchPosition($lineupPos);
            $positionCount = $backup == '' ? $position->getCountStarter() : $position->getCountBackup();
            $i = 0;
            foreach ($positionalPlayer as $player) {
                $playerLineUp = $player->getLineupPosition();
                if ($i < $positionCount && !isset($playerLineUp)) {
                    if (($pos == 'RB' || $pos == 'MLB') && '' == $backup) {
                        $lineupPos = $pos . ($i + 1);
                    }
                    // Starter festlegen
                    $this->log->debug('Setze Lineupposition ' . $lineupPos . $backup . ' zu Spieler ' . $player->getId() . ' | ' . $player->getType()->getPosition()->getPosition());
                    $playerController->updateLineupPosition($player->getId(), $lineupPos . $backup);
                    $player->setLineupPosition($lineupPos . $backup);
                    $i++;
                }
            }
        }

        $differenceTime = round((microtime(true) - $startTime), 3);
        $this->log->debug('finish auto lineup in ' . $differenceTime);
        return $team;
    }
}