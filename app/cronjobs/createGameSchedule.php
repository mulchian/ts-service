<?php
// Saisonaler Cronjob (alle 4 Wochen)
// Erstellung des Spielplans

use Faker\Factory;
use touchdownstars\league\LeagueController;
use touchdownstars\main\MainController;
use touchdownstars\team\Team;
use touchdownstars\team\TeamController;

$logFile = 'createGameSchedule';
include('../init.php');

// For Debugging:
//ini_set("xdebug.var_display_max_children", -1);
//ini_set("xdebug.var_display_max_data", -1);
//ini_set("xdebug.var_display_max_depth", -1);

$localeArray = array('en_US', 'en_GB', 'en_AU', 'nl_NL', 'de_DE', 'de_CH', 'de_AT');
$botTeamNames = array('Berlin', 'Hamburg', 'München', 'Köln', 'Frankfurt am Main', 'Stuttgart', 'Düsseldorf',
    'Leipzig', 'Dortmund', 'Essen', 'Bremen', 'Dresden', 'Hannover', 'Nürnberg', 'Duisburg', 'Bochum', 'Wuppertal',
    'Bielefeld', 'Bonn', 'Münster', 'Karlsruhe', 'Mannheim', 'Augsburg', 'Wiesbaden', 'Mönchengladbach', 'Gelsenkirchen', 'Braunschweig',
    'Kiel', 'Aachen', 'Chemnitz', 'Halle (Saale)', 'Magdeburg', 'Freiburg im Breisgau', 'Krefeld', 'Lübeck', 'Mainz', 'Erfurt', 'Oberhausen',
    'Rostock', 'Kassel', 'Hagen', 'Saarbrücken', 'Hamm', 'Potsdam', 'Ludwigshafen am Rhein', 'Mülheim an der Ruhr', 'Oldenburg',
    'Osnabrück', 'Leverkusen', 'Heidelberg', 'Solingen', 'Darmstadt', 'Herne', 'Neuss', 'Regensburg', 'Paderborn', 'Ingolstadt', 'Offenbach am Main',
    'Würzburg', 'Fürth', 'Ulm', 'Heilbronn', 'Pforzheim', 'Wolfsburg', 'Göttingen', 'Bottrop', 'Reutlingen', 'Koblenz', 'Bremerhaven', 'Recklinghausen',
    'Bergisch Gladbach', 'Erlangen', 'Jena', 'Remscheid', 'Trier', 'Salzgitter', 'Moers', 'Siegen', 'Hildesheim', 'Cottbus', 'Gütersloh',
    'Kaiserslautern', 'Witten', 'Hanau', 'Schwerin', 'Gera', 'Esslingen am Neckar', 'Ludwigsburg', 'Iserlohn', 'Düren', 'Tübingen', 'Zwickau',
    'Flensburg', 'Gießen', 'Ratingen', 'Lünen', 'Villingen-Schwenningen', 'Konstanz', 'Marl', 'Worms');
$botTeamSymbols = array('Hawks', 'Bears', 'Knights', 'Warriors', 'Grizzlys', 'Tigers', 'Jaguars', 'Panthers', 'Lions',
    'Crocodiles', 'Ducks', 'Wolves', 'Sharks', 'Dolphins', 'Bulldogs', 'Greyhounds', 'Sabres', 'Coyotes', 'Falcons',
    'Hedgehogs', 'Spiders', 'Venom', 'Cranes', 'Hornets', 'Bees', 'Raptors', 'Seagulls', 'Eagles', 'Hippos', 'Bulls',
    'Gorillas', 'Zebras', 'Alligators', 'Blackhawks', 'Thunderbirds', 'Seahawks', 'Rhinos', 'Scorpions', 'Huskies',
    'Cobras', 'Griffins', 'Dragons', 'Wildcats', 'BattleHawks', 'Vipers', 'Stallions', 'Wilddogs', 'Buffalos', 'Razorbacks',
    'Pirates', 'Buccaneers', 'Knights', 'Cowboys', 'Vikings', 'Celtics', 'Shamrocks', 'Sea Devils', 'Devils', 'Vampires',
    'Hurricanes', 'Twisters', 'Flyers', 'Spartans', 'Rebels', 'Outlaws', 'Dockers', 'Patriots', 'Crusaders', 'Thunder',
    'Lightning', 'Firefighters', 'Sheriffs', 'Steelers', 'Miners', 'Farmers', 'Renegades', 'Lumberjacks', 'Titans', 'Colts',
    'Horsemen', 'Monarchs', 'Galaxy', 'Phantoms', 'Diamonds', 'Rangers', 'Bulldozer', 'Comets', 'Royals', 'Gamblers',
    'Mercenaries', 'Maniacs', 'Ambassadors', 'Defenders', 'Roughnecks', 'Guardians', 'Wranglers', 'Breakers', 'Blitz',
    'Gold', 'Express', 'Generals', 'Invaders', 'Maulers', 'Stars', 'Suns', 'Bandits', 'Federals', 'Hotshots', 'Commanders',
    'Iron', 'Apollos', 'Legends', 'Pioneers', 'Badgers', 'Red Wings', 'Jets', 'Giants', 'Demons', 'Barons', 'Gladiators', 'Sentinels'
);
$gameDays = array(1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16);

if (isset($pdo, $log)) {
    $leagueController = new LeagueController($pdo, $log);
    $teamController = new TeamController($pdo, $log);
    $mainController = new MainController($pdo, $log);

//TODO: Teams & User deaktivieren, die zwei Saisons inaktiv sind oder den Haken für 'Zum Saisonende Löschen?' gesetzt haben

// TODO: aktive Teams (Teams mit idUser) aufsteigen lassen
// Conference der Teams bleibt beim Aufstieg gleich
// Je Conference 16 Teams = 32 Teams pro Liga
// Nur Conference bei Auf- und Abstiegen betrachten
// Division zufällig umverteilbar (je nach Auf- und Abstiegen)


// Aktuelle Season aus der Datenbank holen und um 1 erhöhen.
    $arrSeasonAndGameday = $mainController->fetchSeasonAndGameday();
    $season = $arrSeasonAndGameday['season'];

// TODO: Nach der Closed Beta - Alle Countries aus der Datenbank holen
    $country = 'Deutschland';
    $leagues = $leagueController->fetchAllLeagues($country);
    $allTeams = $teamController->fetchAllTeams($country);
    $activeTeams = $teamController->fetchAllTeams($country, 2);
    $botTeams = $teamController->fetchAllTeams($country, 1);

    if ($season > 1 && !empty($allTeams)) {
        // Wenn Saison > 1 und aktive Teams bestehen Auf- und Abstiege simulieren.
        $leagueTeams = array();
        foreach ($leagues as $league) {
            // alle Teams nutzen
            // Teste, ob zwei Bot-Teams in der Conference sind, dann nur Aufstiege auf die Position der Bots
            // Teste, ob ein Bot-Team in der Conference ist, dann nur ein Abstieg und zwei Aufstiege (ein Aufstieg für den Bot)
            // Ansonsten Abstieg von den letzten zwei Teams und Aufstieg aus der unteren Liga.
            $leagueTeams[] = array_filter($allTeams, function (Team $value) use ($country, $league) {
                return $value->getLeague()->getCountry() == $country && $value->getLeague()->getLeagueNumber() == $league->getLeagueNumber();
            });


        }
    }

// Bot-Teams (Teams ohne idUser) erstellen und die Ligen auffüllen
// faker -> city + feste englische Wörter als Symbol | z.B. Seattle Seahawks, Baltimore Ravens, Berlin Bears, Dortmund Knights
    if ((!empty($allTeams) && count($allTeams) % 32 != 0) || count($allTeams) == 0) {
        foreach ($leagues as $league) {
            $leagueTeams = array_filter($allTeams, function (Team $value) use ($country, $league) {
                return $value->getLeague()->getCountry() == $country && $value->getLeague()->getLeagueNumber() == $league->getLeagueNumber();
            });
            if (count($leagueTeams) == 0 || count($leagueTeams) % 32 != 0) {
                // Erstelle Bot-Teams für diese Liga
                while (count($leagueTeams) < 32) {
                    /* Randomize the country and at the same time the Person */
                    $randLocaleIdx = array_rand($localeArray);
                    $faker = Factory::create($localeArray[$randLocaleIdx]);
                    do {
                        $city = $botTeamNames[array_rand($botTeamNames)];
                        $symbol = $botTeamSymbols[array_rand($botTeamSymbols)];
                        $teamName = $city . ' ' . $symbol;
                        $teamNameExists = $teamController->fetchTeam(null, $teamName) != null;
                    } while ($teamNameExists);
                    $abbreviation = 'B' . $teamName[0] . $symbol[0];
                    $botTeam = $teamController->registerNewBotTeam($teamName, $abbreviation, $leagueTeams, $league);
                    $botTeamIsSet = false;

                    $leagueTeams[] = $botTeam;
                    $allTeams[] = $botTeam;
                }
            }
        }
    }

// DivisionGames => 6x Spiel innerhalb der Division (je 2 Spiele gegen deine 3 Konkurrenten) (3x Heim- und 3x Auswärtsspiel)
// ConferenceGames => 8x Spiel gegen Zufallsgegner aus den jeweils anderen Divisionen innerhalb der Conference. (4x Heim- und 4x Auswärtsspiel)
// InterConferenceGames => 2x Spiel gegen Team aus der anderen Conference (1x Heim- und 1x Auswärtsspiel)
// Insgesamt 16 Spiele pro Team in der Regular Season
    if (isset($allTeams) && count($allTeams) % 32 != 0) {
        $allTeams = $teamController->fetchAllTeams($country);
    }

    if (!empty($allTeams) && count($allTeams) % 32 == 0) {
        foreach ($leagues as $league) {
            $leagueTeams = array_values(array_filter($allTeams, function (Team $value) use ($country, $league) {
                return $value->getLeague()->getCountry() == $country && $value->getLeague()->getLeagueNumber() == $league->getLeagueNumber();
            }));

            // Einzelne Spielpläne erstellen
            $divisionGames = $leagueController->createDivisionGames($leagueTeams);
            $conferenceGames = $leagueController->createConferenceGames($leagueTeams);
            $interConferenceGames = $leagueController->createInterConferenceGames($leagueTeams);

            // Games zu einem Spielplan zusammenführen
            $allGames = array();
            foreach ($divisionGames as $divisionGame) {
                $allGames[] = $divisionGame;
            }
            foreach ($conferenceGames as $conferenceGame) {
                $conferenceGame['gameday'] = $conferenceGame['gameday'] + 6;
                $allGames[] = $conferenceGame;
            }
            foreach ($interConferenceGames as $interConferenceGame) {
                $interConferenceGame['gameday'] = $interConferenceGame['gameday'] + 14;
                $allGames[] = $interConferenceGame;
            }

            // Sortiere den Spielplan nach Spieltagen
            usort($allGames, function ($game1, $game2) {
                return $game1['gameday'] <=> $game2['gameday'];
            });

            // Shuffle Gamedays und ersetze die alten Spieltage durch die neuen zufälligen
            shuffle($gameDays);

            $shuffledGames = array();
            foreach ($allGames as $game) {
                $game['gameday'] = $gameDays[$game['gameday'] - 1];
                $shuffledGames[] = $game;
            }

            $allGames = $shuffledGames;

            // Sortiere den Spielplan nach zufälligen Spieltagen
            usort($allGames, function ($game1, $game2) {
                return $game1['gameday'] <=> $game2['gameday'];
            });

            // Spielplan in der Datenbank speichern mit Saison-Nummer als zusätzlichen Identifier
            $leagueController->saveGameSchedule($season, $allGames, $league);
        }
    }
}
