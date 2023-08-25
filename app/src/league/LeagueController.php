<?php


namespace touchdownstars\league;


use PDO;
use Monolog\Logger;
use touchdownstars\team\Team;
use touchdownstars\team\TeamController;

class LeagueController
{
    private PDO $pdo;
    private Logger $log;

    public function __construct(PDO $pdo, Logger $log)
    {
        $this->pdo = $pdo;
        $this->log = $log;
    }

    public function isLive(Team $team): bool
    {
        $selectStmt = $this->pdo->prepare('SELECT gameDay, gameTime, homeAccepted, awayAccepted, result FROM `t_event` 
                                                             WHERE (home = :home OR away = :away) AND gameTime > 1200 
                                                               AND unix_timestamp() > (gameTime - 1200) 
                                                               AND unix_timestamp() < (gameTime + 3600)
                                                               AND result IS NULL');
        $selectStmt->execute([
            'home' => $team->getName(),
            'away' => $team->getName()
        ]);
        $result = $selectStmt->fetch(PDO::FETCH_ASSOC);
        $this->log->debug('isLive - Result: ' . print_r($result, true));
        if (isset($result['gameTime']) && (isset($result['gameday']) || ($result['homeAccepted'] == 1 && $result['awayAccepted'] == 1)) &&
            (!isset($result['result']) || (($result['gameTime'] > time() - 1200)))) {
            return true;
        }

        return false;
    }

    public function hasGameAtGivenTime(Team $team, int $gameTime): bool
    {
        $selectStmt = $this->pdo->prepare('SELECT gameDay, gameTime, homeAccepted, awayAccepted 
                                                    FROM `t_event` 
                                                    WHERE (home = :home OR away = :away) 
                                                    AND gameTime > 3600 AND :now1 <= (gameTime - 3600) AND :now2 >= (gameTime + 3600)');
        $selectStmt->execute([
            'home' => $team->getName(),
            'away' => $team->getName(),
            'now1' => $gameTime,
            'now2' => $gameTime
        ]);
        $result = $selectStmt->fetch(PDO::FETCH_ASSOC);
        $this->log->debug('hasGameAtGivenTime - Result: ' . print_r($result, true));
        if (isset($result['gameTime']) && (isset($result['gameday']) || ($result['homeAccepted'] == 1 && $result['awayAccepted'] == 1))) {
            return true;
        }

        return false;
    }

    /**
     * Gibt die Conference zum Namen bzw der Beschreibung zurück.
     * @param string $description
     * @return Conference
     */
    public function fetchConference(string $description): Conference
    {
        return select($this->pdo, 'SELECT tc.* FROM `t_conference` tc where tc.description = :description;',
            'touchdownstars\\league\\Conference', ['description' => $description]);
    }

    /**
     * Gibt die Division zum Namen bzw zur Beschreibung zurück.
     * @param string $description
     * @return Division
     */
    public function fetchDivision(string $description): Division
    {
        return select($this->pdo, 'SELECT td.* FROM `t_division` td where td.description = :description;',
            'touchdownstars\\league\\Division', ['description' => $description]);
    }

    /**
     * Gibt die League des mitgegebenen Teams zurück.
     * @param Team $team
     * @return League
     */
    public function fetchLeagueForTeam(Team $team): League
    {
        return select($this->pdo, 'SELECT tl.* FROM `t_league` tl join `t_team_to_league` tttl on tl.id = tttl.idLeague where tttl.idTeam = :idTeam;',
            'touchdownstars\\league\\League', ['idTeam' => $team->getId()]);
    }

    /**
     * Gibt die Conference des mitgegebenen Teams zurück.
     * @param Team $team
     * @return Conference
     */
    public function fetchConferenceForTeam(Team $team): Conference
    {
        return select($this->pdo, 'SELECT tc.* FROM `t_conference` tc join `t_team_to_league` tttl on tc.id = tttl.idConference where tttl.idTeam = :idTeam;',
            'touchdownstars\\league\\Conference', ['idTeam' => $team->getId()]);
    }

    /**
     * Gibt die Division des mitgegebenen Teams zurück.
     * @param Team $team
     * @return Division
     */
    public function fetchDivisionForTeam(Team $team): Division
    {
        return select($this->pdo, 'SELECT td.* FROM `t_division` td join `t_team_to_league` tttl on td.id = tttl.idDivision where idTeam = :idTeam;',
            'touchdownstars\\league\\Division', ['idTeam' => $team->getId()]);
    }

    /**
     * Gibt alle in der Datenbank hinterlegten Ligen zum mitgegebenen Land zurück.
     * @param string $country - Default: 'Deutschland'
     * @return array - Liste von Ligen
     */
    public function fetchAllLeagues(string $country = 'Deutschland'): array
    {
        $selectStmt = $this->pdo->prepare('SELECT * FROM `t_league` WHERE country = :country;');
        $selectStmt->execute(['country' => $country]);
        $selectStmt->setFetchMode(PDO::FETCH_CLASS, 'touchdownstars\\league\\League');
        $leagues = $selectStmt->fetchAll(PDO::FETCH_CLASS, 'touchdownstars\\league\\League');

        if (!empty($leagues)) {
            return $leagues;
        }

        return array();
    }

    public function getAllFriendlies(Team $team): array
    {
        $selectStmt = $this->pdo->prepare('SELECT id, gameTime, season, home, away, homeAccepted, awayAccepted FROM `t_event` 
                                                                    WHERE idLeague is null AND (home = :home OR away = :away) and gameTime > unix_timestamp()');
        $selectStmt->execute([
            'home' => $team->getName(),
            'away' => $team->getName()
        ]);
        $result = $selectStmt->fetchAll(PDO::FETCH_ASSOC) ?? array();
        $this->log->debug('Freundschaftsspiele: ' . print_r($result, true));
        return $result;
    }

    public function saveFriendly(int $gameTime, int $season, string $home, string $away, bool $homeAccepted, bool $awayAccepted): int
    {
        // Prüfung +-1 Stunden kein anderer Spielstart (Season oder Friendly)
        $selectStmt = $this->pdo->prepare('SELECT id FROM `t_event` 
                WHERE (home = :home1 or away = :home2 or home = :away1 or away = :away2) 
                and :gameTime1 + 3600 > gameTime and :gameTime2 - 3600 < gameTime');
        $selectStmt->execute([
            'home1' => $home,
            'home2' => $home,
            'away1' => $away,
            'away2' => $away,
            'gameTime1' => $gameTime,
            'gameTime2' => $gameTime
        ]);
        $result = $selectStmt->fetch(PDO::FETCH_ASSOC);
        $id = $result ? $result['id'] : null;

        if (!isset($id)) {
            $saveStmt = $this->pdo->prepare('INSERT INTO `t_event` (gameTime, season, home, away, homeAccepted, awayAccepted) 
                                                VALUES (:gameTime, :season, :home, :away, :homeAccepted, :awayAccepted)');
            $saveStmt->execute([
                'gameTime' => $gameTime,
                'season' => $season,
                'home' => $home,
                'away' => $away,
                'homeAccepted' => $homeAccepted,
                'awayAccepted' => $awayAccepted
            ]);
            $id = $this->pdo->lastInsertId();
        } else {
            $this->log->debug('Zu dem Zeitpunkt ' . date('d.m.Y H:i', $gameTime) . ' hat eines der Teams ' . $home . ' und ' . $away . ' bereits ein Spiel.');
        }

        return $id;
    }

    public function acceptFriendly(int $gameTime, string $home, string $away, bool $homeAccepted, bool $awayAccepted): int
    {
        $selectStmt = $this->pdo->prepare('SELECT id, homeAccepted, awayAccepted FROM `t_event` 
                WHERE ((home = :home1 and away = :away1) OR (home = :home2 AND away = :away2)) and gameTime = :gameTime');
        $selectStmt->execute([
            'home1' => $home,
            'away1' => $away,
            'home2' => $away,
            'away2' => $home,
            'gameTime' => $gameTime
        ]);
        $result = $selectStmt->fetch(PDO::FETCH_ASSOC);
        $this->log->debug('Fetched Friendly: ' . print_r($result, true));
        $id = $result['id'];

        if (isset($id)) {
            $saveStmt = $this->pdo->prepare('UPDATE `t_event` SET homeAccepted = :homeAccepted, awayAccepted = :awayAccepted WHERE id = :id');
            $saveStmt->execute([
                'homeAccepted' => $result['homeAccepted'] ?: $homeAccepted,
                'awayAccepted' => $result['awayAccepted'] ?: $awayAccepted,
                'id' => $id
            ]);
        } else {
            $this->log->debug('Es konnte kein Spiel um ' . date('d.m.Y H:i', $gameTime) . ' Uhr zwischen den Teams ' . $home . ' und ' . $away . ' gefunden werden.');
        }

        return $id;
    }

    public function declineFriendly(int $id, int $gameTime, string $home, string $away): bool
    {

        $selectStmt = $this->pdo->prepare('SELECT id FROM `t_event` 
                WHERE id = :id or (((home = :home1 and away = :away1) OR (home = :home2 AND away = :away2)) and gameTime = :gameTime)');
        $selectStmt->execute([
            'id' => $id,
            'home1' => $home,
            'away1' => $away,
            'home2' => $away,
            'away2' => $home,
            'gameTime' => $gameTime
        ]);
        $result = $selectStmt->fetch(PDO::FETCH_ASSOC);
        $dbId = $result['id'];

        $this->log->debug('ID: ' . $id . ' | DB-ID: ' . $dbId);

        if ($id == $dbId) {
            // Friendly existiert in der DB und kann gelöscht werden
            $result = $this->pdo->exec('DELETE FROM `t_event` WHERE id = ' . $dbId);
            if ($result && $result >= 1) {
                return true;
            }
        }

        return false;
    }

    /**
     * Löscht die Friendlies aus der DB, die nicht gestartet wurden
     * @return bool - true, wenn Datensätze gelöscht wurden - false, wenn nicht
     */
    public function deleteUnplayedFriendlies(): bool
    {
        $selectStmt = $this->pdo->prepare('SELECT te.id FROM `t_gameplay_standings` tgs 
                RIGHT OUTER JOIN `t_event` te ON te.id = tgs.idFriendlyGame 
                WHERE te.gameTime < :now AND te.idLeague is null AND tgs.idFriendlyGame is null;');
        $selectStmt->execute(['now' => strtotime('-3 hours')]);
        $result = $selectStmt->fetchAll(PDO::FETCH_ASSOC);
        $ids = array_column($result, 'id');
        $this->log->debug('Unplayed Friendlies: ' . print_r($ids, true));

        if (count($ids) > 0) {
            $result = $this->pdo->exec('DELETE FROM `t_event` WHERE id IN (' . implode(',', $ids) . ')');
            if ($result && $result >= 1) {
                $this->log->debug($result . ' ungespielte Freundschaftsspiele gelöscht.');
                return true;
            }
        }
        return false;
    }

    /**
     * @param array $game
     * @return void
     */
    public function saveFinalScoreToEvent(array $game): void
    {
        $standings = $this->getStandings($game);

        $saveStmt = $this->pdo->prepare('UPDATE `t_event` SET result = :newResult WHERE id = :id;');

        $saveStmt->execute([
            'newResult' => $standings['score'] ?? null,
            'id' => $game['id']
        ]);
    }

    /**
     * Speichert den erstellten Spielplan für die Saison und Liga in der Datenbank.
     * Aktualisiert, wenn die Spiele schon erstellt worden, den Spielstand in der Datenbank.
     * @param int $season
     * @param array $allGames
     * @param League $league
     */
    public function saveGameSchedule(int $season, array $allGames, League $league): void
    {
        $saveStmt = $this->pdo->prepare('INSERT INTO `t_event` (id, gameTime, season, gameday, home, away, result, idLeague) VALUES (:id, :gameTime, :season, :gameday, :home, :away, :result, :idLeague)
            ON DUPLICATE KEY UPDATE result = :newResult;');

        $gameday = 1;
        $gameTime = strtotime('next monday 20:30');

        foreach ($allGames as $game) {
            $selectStmt = $this->pdo->prepare('SELECT id from `t_event` where season = :season and gameDay = :gameday and home = :home and away = :away;');
            $selectStmt->execute(['season' => $season, 'gameday' => $game['gameday'], 'home' => $game['home'], 'away' => $game['away']]);
            $id = $selectStmt->fetch(PDO::FETCH_ASSOC)['id'];

            if ($game['gameday'] > $gameday) {
                $gameday = $game['gameday'];
                $gameTime = $gameTime + (24 * 60 * 60);
            }

            $saveStmt->execute([
                'id' => $id ?? null,
                'gameTime' => $gameTime,
                'season' => $season,
                'gameday' => $gameday,
                'home' => $game['home'],
                'away' => $game['away'],
                'result' => $game['result'] ?? null,
                'idLeague' => $league->getId(),
                'newResult' => $game['result'] ?? null
            ]);
        }
    }

    /**
     * Aktualisiert den Spielplan, wenn ein neues User-Team ein Bot-Team ersetzt.
     * @param string $oldTeamName
     * @param string $newTeamName
     * @return bool - Sind die Changes Home und Away gleich, kommt true zurück, ansonsten false
     */
    public function updateGameSchedule(string $oldTeamName, string $newTeamName): bool
    {
        $updateHomeStmt = $this->pdo->prepare('UPDATE `t_event` SET home = :newHome WHERE home = :oldHome;');
        $updateHomeStmt->execute(['newHome' => $newTeamName, 'oldHome' => $oldTeamName]);
        $homeTeamChanges = $this->pdo->lastInsertId();

        $updateAwayStmt = $this->pdo->prepare('UPDATE `t_event` SET away = :newAway WHERE away = :oldAway;');
        $updateAwayStmt->execute(['newAway' => $newTeamName, 'oldAway' => $oldTeamName]);
        $awayTeamChanges = $this->pdo->lastInsertId();

        if ($homeTeamChanges == 0 || $awayTeamChanges == 0) {
            return false;
        } elseif ($homeTeamChanges == $awayTeamChanges) {
            return true;
        } else {
            return false;
        }
    }

    public function fetchGame(Team $team, string $season, string $gameDay): array
    {
        $selectStmt = $this->pdo->prepare('SELECT id, home, away, gameTime, idLeague FROM `t_event` WHERE (home = :home or away = :away) 
                                       AND ((season = :season and gameDay = :gameDay) 
                                       OR (gameTime > 1200 AND unix_timestamp() > (gameTime - 1200) AND unix_timestamp() < (gameTime + 3600)))
                                       AND result is null;');
        $selectStmt->execute(['home' => $team->getName(), 'away' => $team->getName(), 'season' => $season, 'gameDay' => $gameDay]);
        $result = $selectStmt->fetch(PDO::FETCH_ASSOC);
        $this->log->debug('fetched game: ' . print_r($result, true));
        if (isset($result) && $result) {
            return $result;
        }
        return array();
    }

    public function fetchGameById(int $idGame): array
    {
        $selectStmt = $this->pdo->prepare('SELECT id, season, gameDay, home, away, result from `t_event` where id = :idGame;');
        $selectStmt->execute(['idGame' => $idGame]);
        return $selectStmt->fetch(PDO::FETCH_ASSOC);
    }

    public function fetchAllLiveGames(): array
    {
        // gameTime > 0 cause league games don't have one right now
        $selectStmt = $this->pdo->prepare('SELECT * FROM `t_event` WHERE gameTime > 0 AND unix_timestamp() >= gameTime AND result is null');
        $selectStmt->execute();
        $result = $selectStmt->fetchAll(PDO::FETCH_ASSOC);

        $liveGames = array();
        foreach ($result as $game) {
            $this->log->debug('live game: ' . print_r($game, true));
            if (isset($game['gameTime']) && (isset($game['gameday']) || ($game['homeAccepted'] == 1 && $game['awayAccepted'] == 1)) && !isset($game['result'])) {
                $liveGames[] = $game;
            }
        }

        return $liveGames;
    }

    public function fetchGames(Team $team, string $season, bool $isLeague = true): array
    {
        if ($isLeague) {
            $selectStmt = $this->pdo->prepare('SELECT id, gameTime, gameDay, home, away, result from `t_event` where season = :season and (home = :home or away = :away) and idLeague is not null;');
        } else {
            $selectStmt = $this->pdo->prepare('SELECT id, gameTime, gameDay, home, away, result from `t_event` where season = :season and (home = :home or away = :away) and idLeague is null;');
        }
        $selectStmt->execute(['season' => $season, 'home' => $team->getName(), 'away' => $team->getName()]);
        return $selectStmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Erstellt die Spiele für die Division.
     * @param array $teams - Ligateams - werden in Conference und Division aufgeteilt.
     * @return array
     */
    public function createDivisionGames(array $teams): array
    {
        $teamController = new TeamController($this->pdo, $this->log);

        $conferences = $teamController->getConferences($teams);
        $divisions = array();
        foreach ($conferences as $conferenceTeams) {
            $divisions[] = $teamController->getDivisions($conferenceTeams);
        }

        $games = array();
        for ($i = 0; $i < count($divisions); $i++) {
            foreach ($divisions[$i] as $division) {
                $teams = $division;
                $games[] = $this->createGames($teams);
            }
        }

        $allGames = array();
        for ($i = 0; $i < count($games); $i++) {
            for ($j = 0; $j < count($games[$i]); $j++) {
                $allGames[] = $games[$i][$j];
            }
        }

        return $allGames;
    }

    private function createGames(array $teams): array
    {
        $teamNum = count($teams) - 1;
        $games = array();

        for ($i = 1; $i < count($teams); $i++) {
            // erstes Spiel ist Team 1 gegen das letzte Team des Arrays (count($teams))
            $home = count($teams);
            $away = $i;
            // Ist i ungerade, spielt das Team an Stelle i auswärts
            if (($i % 2) != 0) {
                $temp = $away;
                $away = $home;
                $home = $temp;
            }

            $games[] = array('home' => $teams[$home - 1]->getName(),
                'away' => $teams[$away - 1]->getName(),
                'gameday' => $i);

            for ($j = 1; $j <= ((count($teams) / 2) - 1); $j++) {

                if (($i - $j) < 0) {
                    $away = $teamNum + ($i - $j);
                } else {
                    $away = ($i - $j) % $teamNum;
                    $away = ($away == 0) ? $teamNum : $away;
                }

                $home = ($i + $j) % $teamNum;
                $home = ($home == 0) ? $teamNum : $home;

                // Home or Away?
                if (($j % 2) == 0) {
                    $temp = $away;
                    $away = $home;
                    $home = $temp;
                }

                $games[] = array('home' => $teams[$home - 1]->getName(), 'away' => $teams[$away - 1]->getName(), 'gameday' => $i);
            }
        }

        // Generiere die Rückrundenspiele
        $gamesNum = count($games);
        for ($i = 0; $i < $gamesNum; $i++) {
            $games[] = array(
                'home' => $games[$i]['away'],
                'away' => $games[$i]['home'],
                'gameday' => $games[$i]['gameday'] + $teamNum
            );
        }

        return $games;
    }

    /**
     *
     * @param array $teams
     * @return array
     */
    public function createConferenceGames(array $teams): array
    {
        $teamController = new TeamController($this->pdo, $this->log);

        $conferences = $teamController->getConferences($teams);

        $games = array();
        foreach ($conferences as $conferenceTeams) {

            usort($conferenceTeams, function (Team $team1, Team $team2) {
                return $team1->getDivision()->getDescription() <=> $team2->getDivision()->getDescription();
            });

            // Hole die conferenceGames anhand der Spielplan-Vorlage
            $createdGames = $this->getConferenceGamesForTeams($conferenceTeams);

            // Es gibt 64 Spiele (8 Spieltage a 8 Spiele) je Conference
            // Per Zufall 8 der 12 vorhandenen Spieltage auswählen
            // Dabei werden die ungerade Zahlen 1, 3, 5, 7, 9, 11 ausgesucht und dazu passend die folgende gerade Zahl
            // Ansonsten passen Heim- und Auswärtsspiele nicht
            $gameDays = [1, 3, 5, 7, 9, 11];
            shuffle($gameDays);
            $gameDays = array_slice($gameDays, 0, 4);
            // Gerade Gamedays zu den ungeraden hinzufügen
            $allGameDays = array();
            foreach ($gameDays as $gameDay) {
                $allGameDays[] = $gameDay;
                $allGameDays[] = $gameDay + 1;
            }

            // createdGames kürzen auf die 8 zufälligen Gamedays
            $createdGames = array_values(array_filter($createdGames, function ($game) use ($allGameDays) {
                return in_array($game['gameday'], $allGameDays);
            }));

            // Spieltage in createdGames auf 1 - 8 ändern
            $currentGameday = 0;
            $count = 0;
            $gameDays = range(1, 8);
            foreach ($createdGames as $key => $createdGame) {
                if ($createdGame['gameday'] > $currentGameday) {
                    $currentGameday = $createdGame['gameday'];
                    $count++;
                }
                $createdGames[$key]['gameday'] = $gameDays[$count - 1];
            }

            shuffle($gameDays);
            $shuffledGames = array();
            foreach ($createdGames as $game) {
                $game['gameday'] = $gameDays[$game['gameday'] - 1];
                $shuffledGames[] = $game;
            }

            $games[] = $shuffledGames;
        }

        $allGames = array();
        for ($i = 0; $i < count($games); $i++) {
            for ($j = 0; $j < count($games[$i]); $j++) {
                $allGames[] = $games[$i][$j];
            }
        }

        return $allGames;
    }

    private function getConferenceGamesForTeams(array $teams): array
    {
        $conferenceGames = [
            array('home' => 1, 'away' => 16, 'gameday' => 1),
            array('home' => 2, 'away' => 15, 'gameday' => 1),
            array('home' => 3, 'away' => 14, 'gameday' => 1),
            array('home' => 13, 'away' => 4, 'gameday' => 1),
            array('home' => 12, 'away' => 5, 'gameday' => 1),
            array('home' => 6, 'away' => 11, 'gameday' => 1),
            array('home' => 7, 'away' => 10, 'gameday' => 1),
            array('home' => 8, 'away' => 9, 'gameday' => 1),
            array('home' => 16, 'away' => 6, 'gameday' => 2),
            array('home' => 14, 'away' => 7, 'gameday' => 2),
            array('home' => 4, 'away' => 8, 'gameday' => 2),
            array('home' => 9, 'away' => 3, 'gameday' => 2),
            array('home' => 10, 'away' => 2, 'gameday' => 2),
            array('home' => 11, 'away' => 1, 'gameday' => 2),
            array('home' => 15, 'away' => 12, 'gameday' => 2),
            array('home' => 5, 'away' => 13, 'gameday' => 2),
            array('home' => 5, 'away' => 16, 'gameday' => 3),
            array('home' => 6, 'away' => 4, 'gameday' => 3),
            array('home' => 7, 'away' => 3, 'gameday' => 3),
            array('home' => 8, 'away' => 2, 'gameday' => 3),
            array('home' => 1, 'away' => 9, 'gameday' => 3),
            array('home' => 10, 'away' => 15, 'gameday' => 3),
            array('home' => 11, 'away' => 14, 'gameday' => 3),
            array('home' => 12, 'away' => 13, 'gameday' => 3),
            array('home' => 16, 'away' => 7, 'gameday' => 4),
            array('home' => 15, 'away' => 8, 'gameday' => 4),
            array('home' => 9, 'away' => 5, 'gameday' => 4),
            array('home' => 4, 'away' => 10, 'gameday' => 4),
            array('home' => 3, 'away' => 11, 'gameday' => 4),
            array('home' => 2, 'away' => 12, 'gameday' => 4),
            array('home' => 13, 'away' => 1, 'gameday' => 4),
            array('home' => 14, 'away' => 6, 'gameday' => 4),
            array('home' => 16, 'away' => 8, 'gameday' => 5),
            array('home' => 9, 'away' => 7, 'gameday' => 5),
            array('home' => 10, 'away' => 6, 'gameday' => 5),
            array('home' => 11, 'away' => 5, 'gameday' => 5),
            array('home' => 4, 'away' => 12, 'gameday' => 5),
            array('home' => 3, 'away' => 13, 'gameday' => 5),
            array('home' => 2, 'away' => 14, 'gameday' => 5),
            array('home' => 1, 'away' => 15, 'gameday' => 5),
            array('home' => 12, 'away' => 16, 'gameday' => 6),
            array('home' => 13, 'away' => 11, 'gameday' => 6),
            array('home' => 14, 'away' => 10, 'gameday' => 6),
            array('home' => 15, 'away' => 9, 'gameday' => 6),
            array('home' => 8, 'away' => 1, 'gameday' => 6),
            array('home' => 7, 'away' => 2, 'gameday' => 6),
            array('home' => 6, 'away' => 3, 'gameday' => 6),
            array('home' => 5, 'away' => 4, 'gameday' => 6),
            array('home' => 1, 'away' => 12, 'gameday' => 7),
            array('home' => 13, 'away' => 2, 'gameday' => 7),
            array('home' => 15, 'away' => 3, 'gameday' => 7),
            array('home' => 4, 'away' => 7, 'gameday' => 7),
            array('home' => 5, 'away' => 10, 'gameday' => 7),
            array('home' => 6, 'away' => 9, 'gameday' => 7),
            array('home' => 8, 'away' => 14, 'gameday' => 7),
            array('home' => 16, 'away' => 11, 'gameday' => 7),
            array('home' => 14, 'away' => 1, 'gameday' => 8),
            array('home' => 2, 'away' => 16, 'gameday' => 8),
            array('home' => 3, 'away' => 5, 'gameday' => 8),
            array('home' => 9, 'away' => 4, 'gameday' => 8),
            array('home' => 12, 'away' => 6, 'gameday' => 8),
            array('home' => 7, 'away' => 15, 'gameday' => 8),
            array('home' => 11, 'away' => 8, 'gameday' => 8),
            array('home' => 10, 'away' => 13, 'gameday' => 8),
            array('home' => 1, 'away' => 5, 'gameday' => 9),
            array('home' => 2, 'away' => 6, 'gameday' => 9),
            array('home' => 8, 'away' => 3, 'gameday' => 9),
            array('home' => 4, 'away' => 15, 'gameday' => 9),
            array('home' => 11, 'away' => 7, 'gameday' => 9),
            array('home' => 13, 'away' => 9, 'gameday' => 9),
            array('home' => 16, 'away' => 10, 'gameday' => 9),
            array('home' => 12, 'away' => 14, 'gameday' => 9),
            array('home' => 7, 'away' => 1, 'gameday' => 10),
            array('home' => 5, 'away' => 2, 'gameday' => 10),
            array('home' => 3, 'away' => 12, 'gameday' => 10),
            array('home' => 14, 'away' => 4, 'gameday' => 10),
            array('home' => 6, 'away' => 13, 'gameday' => 10),
            array('home' => 10, 'away' => 8, 'gameday' => 10),
            array('home' => 9, 'away' => 16, 'gameday' => 10),
            array('home' => 15, 'away' => 11, 'gameday' => 10),
            array('home' => 1, 'away' => 10, 'gameday' => 11),
            array('home' => 2, 'away' => 9, 'gameday' => 11),
            array('home' => 3, 'away' => 16, 'gameday' => 11),
            array('home' => 4, 'away' => 11, 'gameday' => 11),
            array('home' => 14, 'away' => 5, 'gameday' => 11),
            array('home' => 15, 'away' => 6, 'gameday' => 11),
            array('home' => 13, 'away' => 7, 'gameday' => 11),
            array('home' => 12, 'away' => 8, 'gameday' => 11),
            array('home' => 6, 'away' => 1, 'gameday' => 12),
            array('home' => 11, 'away' => 2, 'gameday' => 12),
            array('home' => 10, 'away' => 3, 'gameday' => 12),
            array('home' => 16, 'away' => 4, 'gameday' => 12),
            array('home' => 5, 'away' => 15, 'gameday' => 12),
            array('home' => 7, 'away' => 12, 'gameday' => 12),
            array('home' => 8, 'away' => 13, 'gameday' => 12),
            array('home' => 9, 'away' => 14, 'gameday' => 12)
        ];

        for ($i = 1; $i <= count($teams); $i++) {
            foreach ($conferenceGames as $key => $conferenceGame) {
                if ($conferenceGame['home'] == $i) {
                    $conferenceGames[$key]['home'] = $teams[$i - 1]->getName();
                }
                if ($conferenceGame['away'] == $i) {
                    $conferenceGames[$key]['away'] = $teams[$i - 1]->getName();
                }
            }
        }

        return $conferenceGames;
    }

    public function createInterConferenceGames(array $teams): array
    {
        $teamController = new TeamController($this->pdo, $this->log);

        $conferences = $teamController->getConferences($teams);

        $games = array();

        $northernTeams = $conferences['Conference North'];
        $southernTeams = $conferences['Conference South'];

        shuffle($northernTeams);
        shuffle($southernTeams);
        for ($i = 0; $i < count($northernTeams); $i++) {
            $games[] = array(
                'home' => $northernTeams[$i]->getName(),
                'away' => $southernTeams[$i]->getName(),
                'gameday' => 1
            );
        }

        shuffle($northernTeams);
        shuffle($southernTeams);
        for ($i = 0; $i < count($northernTeams); $i++) {
            $games[] = array(
                'home' => $southernTeams[$i]->getName(),
                'away' => $northernTeams[$i]->getName(),
                'gameday' => 2
            );
        }

        return $games;
    }

    public function getSeasonalFriendlyStandings(Team $team): string
    {
        if (isset($_SESSION['seasonalFriendlyStandings' . $team->getId()])) {
            $standings = $_SESSION['seasonalFriendlyStandings' . $team->getId()];
        } else {
            $select = 'SELECT * FROM `t_gameplay_standings` st 
                LEFT JOIN `t_event` sc ON st.idFriendlyGame = sc.id
                WHERE (home = :home OR away = :away) 
                  and idFriendlyGame is not null 
                  and season = :season;';
            $selectStmt = $this->pdo->prepare($select);
            $selectStmt->execute(['home' => $team->getName(), 'away' => $team->getName(), 'season' => $_SESSION['season']]);
            $standings = $selectStmt->fetchAll(PDO::FETCH_ASSOC);
            $_SESSION['seasonalFriendlyStandings' . $team->getId()] = $standings;
        }

        return $this->getSeasonalStandings($standings, $team->getName());
    }

    private function getSeasonalStandings(array $standings, string $teamName): string
    {
        $wins = 0;
        $losses = 0;
        $ties = 0;
        foreach ($standings as $standing) {
            $isHome = $standing['home'] === $teamName;
            $points = explode(';', $standing['score']);
            $diff = $points[0] - $points[1];

            // $this->log->debug('isHome: ' . print_r($isHome, true));
            // $this->log->debug('Points: ' . print_r($points, true));
            // $this->log->debug('Differenz: ' . $diff);

            if (($isHome && $diff > 0) || (!$isHome && $diff < 0)) {
                $wins++;
            } elseif ($diff == 0) {
                $ties++;
            } else {
                $losses++;
            }
        }

        $seasonalStanding = $wins . '-' . $losses . ($ties > 0 ? '-' . $ties : '');
        $this->log->debug('Seasonal-Standing: ' . $seasonalStanding);
        return $seasonalStanding;
    }

    public function getSeasonalLeagueStandings(Team $team): string
    {
        if (isset($_SESSION['seasonalStandings' . $team->getId()])) {
            $standings = $_SESSION['seasonalStandings' . $team->getId()];
        } else {
            $select = 'SELECT * FROM `t_gameplay_standings` st 
                LEFT JOIN `t_event` sc ON st.idLeagueGame = sc.id
                WHERE (home = :home OR away = :away) 
                  and idLeagueGame is not null 
                  and season = :season;';
            $selectStmt = $this->pdo->prepare($select);
            $selectStmt->execute(['home' => $team->getName(), 'away' => $team->getName(), 'season' => $_SESSION['season']]);
            $standings = $selectStmt->fetchAll(PDO::FETCH_ASSOC);
            $_SESSION['seasonalStandings' . $team->getId()] = $standings;
        }

        return $this->getSeasonalStandings($standings, $team->getName());
    }

    public function updateScore(array $game, Team $scoringTeam, string $quarter, int $points): void
    {
        $homeScored = $game['home'] == $scoringTeam->getName();

        //Hol aktuelle Standings
        $standings = $this->getStandings($game);

        $this->log->debug('Standings: ' . print_r($standings, true));

        $standings['score'] = $this->addPointsToScore($standings['score'], $points, $homeScored);
        if ($quarter === 'OT') {
            $standings[$quarter] = $this->addPointsToScore($standings[$quarter], $points, $homeScored);
        } else {
            $standings['score' . $quarter] = $this->addPointsToScore($standings['score' . $quarter], $points, $homeScored);
        }

        $this->saveScore($game, $standings);
    }

    public function getStandings(array $game): array
    {
        if (isset($_SESSION['standings' . $game['id']])) {
            $standings = $_SESSION['standings' . $game['id']];
        } else {
            $selectStmt = $this->pdo->prepare('SELECT * FROM `t_gameplay_standings` WHERE idLeagueGame = :idLeagueGame OR idFriendlyGame = :idFriendlyGame;');
            $selectStmt->execute(['idLeagueGame' => $game['id'], 'idFriendlyGame' => $game['id']]);
            $standings = $selectStmt->fetch(PDO::FETCH_ASSOC);
            if (isset($standings) && $standings && count($standings) > 0) {
                $_SESSION['standings' . $game['id']] = $standings;
            }
        }
        if ($standings) {
            return $standings;
        }
        return array();
    }

    private function addPointsToScore(string $score, int $points, bool $homeScored): string
    {
        $ovrScore = explode(';', $score);
        $homeScore = $ovrScore[0];
        $awayScore = $ovrScore[1];
        if ($homeScored) {
            $homeScore += $points;
        } else {
            $awayScore += $points;
        }
        return $homeScore . ';' . $awayScore;
    }

    /**
     * Speichere den mitgegebenen Score (standings) in der Datenbank und gibt diesen zurück
     * @param array $game
     * @param array|null $standings
     * @return array - standings mit den Scores aus jedem Viertel und der OT
     */
    public function saveScore(array $game, ?array $standings): array
    {
        $selectStmt = $this->pdo->prepare('SELECT id from `t_gameplay_standings` where idLeagueGame = :idLeagueGame OR idFriendlyGame = :idFriendlyGame;');
        $selectStmt->execute(['idLeagueGame' => $game['id'], 'idFriendlyGame' => $game['id']]);
        $id = $selectStmt->fetch(PDO::FETCH_ASSOC)['id'];

        $saveStmt = 'INSERT INTO `t_gameplay_standings` (id, idLeagueGame, idFriendlyGame) 
                        VALUE (:id, :idLeagueGame, :idFriendlyGame)
                        ON DUPLICATE KEY UPDATE score = :newScore, score1 = :newScore1, score2 = :newScore2, score3 = :newScore3, score4 = :newScore4, ot = :newScoreOt';
        $saveStartScore = $this->pdo->prepare($saveStmt);
        $isLeagueGame = $game['isLeagueGame'] ?? false;

        $saveStartScore->execute([
            'id' => $id ?? null,
            'idLeagueGame' => $isLeagueGame ? $game['id'] : null,
            'idFriendlyGame' => $isLeagueGame ? null : $game['id'],
            'newScore' => isset($standings) ? $standings['score'] : '0;0',
            'newScore1' => isset($standings) ? $standings['score1'] : '0;0',
            'newScore2' => isset($standings) ? $standings['score2'] : '0;0',
            'newScore3' => isset($standings) ? $standings['score3'] : '0;0',
            'newScore4' => isset($standings) ? $standings['score4'] : '0;0',
            'newScoreOt' => isset($standings) ? $standings['ot'] : '0;0'
        ]);

        if (isset($standings) && count($standings) > 0) {
            $_SESSION['standings' . $game['id']] = $standings;
        } else {
            $standings['id'] = $this->pdo->lastInsertId();
            $standings['idLeagueGame'] = $isLeagueGame ? $game['id'] : null;
            $standings['idFriendlyGame'] = $isLeagueGame ? null : $game['id'];
            $standings['score'] = '0;0';
            $standings['score1'] = '0;0';
            $standings['score2'] = '0;0';
            $standings['score3'] = '0;0';
            $standings['score4'] = '0;0';
            $standings['ot'] = '0;0';
        }

        if ($standings) {
            return $standings;
        }
        return array();
    }
}