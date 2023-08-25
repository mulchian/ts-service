<?php

use touchdownstars\league\LeagueController;
use touchdownstars\live\GameController;
use touchdownstars\player\Player;
use touchdownstars\player\PlayerController;
use touchdownstars\team\TeamController;

if (isset($pdo, $log)) :
    $playerController = new PlayerController($pdo, $log);
    $teamController = new TeamController($pdo, $log);
    $leagueController = new LeagueController($pdo, $log);
    $gameController = new GameController($pdo, $log);

    if (isset($_SESSION['team'])) :
        $team = $_SESSION['team'];

        // getting home and away
        if (isset($_SESSION['season']) && isset($_SESSION['gameday'])) :
            // Hole letztes aktuelles Gameplay
            $game = $leagueController->fetchGame($team, $_SESSION['season'], $_SESSION['gameday']);
            $gameplayTime = 15 * ceil(time() / 15);

            $gameplayHistory = $gameController->getGameCalculation($game, $gameplayTime);
            if (null != $gameplayHistory) {
                $playClock = $gameplayHistory['playClock'];
                $quarter = $gameplayHistory['quarter'];
                $offTeamName = $teamController->fetchTeamNameById($gameplayHistory['idOffTeam']);
                $secondRB = $gameplayHistory['secondRB'];
            } else {
                $playClock = 900; // 900 Sekunden = 15 Minuten
                $quarter = 1; // Erstes Viertel zum Start
                $secondRB = false;
            }

            $vsOrAt = $game['home'] == $team->getName() ? 'vs' : 'at';
            $vsTeamName = $game['home'] == $team->getName() ? $game['away'] : $game['home'];

            $vsTeam = $teamController->fetchTeam(null, $vsTeamName);

            include($_SERVER['DOCUMENT_ROOT'] . '/pages/live/game/startingElevenListInfo.php');
            ?>

            <div class="panel panel-default opacity">
                <div id="rowStandings" class="row mb-1 justify-content-end">
                    <div class="col-4 justify-content-center">
                        <div class="card text-white bg-dark">
                            <div id="showTeams" class="card-body justify-content-center">
                                <?php echo $team->getName() . ' game.php' . $vsOrAt . ' ' . $vsTeam->getName(); ?>
                                <br>
                                <?php echo 'Kickoff: ' . date('d.m.Y H:i', $game['gameTime']); ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-8">
                        <div class="card text-white bg-dark">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-2">
                                        <div class="card-text">
                                            <div class="row mb-4">
                                                <div id="quarter" class="col">
                                                    <?php
                                                    echo match ($quarter) {
                                                        1 => $quarter . 'st',
                                                        2 => $quarter . 'nd',
                                                        3 => $quarter . 'rd',
                                                        4 => $quarter . 'th',
                                                        default => $quarter,
                                                    };
                                                    ?>
                                                </div>
                                            </div>
                                            <div class="row mb-4">
                                                <div id="playClock" class="col">
                                                    <?php
                                                    $minutes = floor($playClock / 60);
                                                    $minutes = $minutes < 10 ? '0' . $minutes : $minutes;
                                                    $seconds = $playClock - $minutes * 60;
                                                    $seconds = $seconds < 10 ? '0' . $seconds : $seconds;
                                                    $showTime = $minutes . ':' . $seconds;
                                                    echo $showTime;
                                                    ?>
                                                </div>
                                            </div>
                                            <div class="row mb-4">
                                                <div id="down" class="col">
                                                    <?php
                                                    if (isset($gameplayHistory['down'], $gameplayHistory['yardsToFirstDown'])) {
                                                        echo $gameplayHistory['down'] . ' & ' . $gameplayHistory['yardsToFirstDown'];
                                                    }
                                                    ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-10">
                                        <table id="tblStandings" class="table table-dark" data-toggle="tblStandings">
                                            <thead>
                                            <tr>
                                                <th data-field="team" scope="col" data-width="20" data-width-unit="%" data-sortable="false"></th>
                                                <th data-field="first" scope="col" data-width="16" data-width-unit="%" data-sortable="false">1st</th>
                                                <th data-field="second" scope="col" data-width="16" data-width-unit="%" data-sortable="false">2nd</th>
                                                <th data-field="third" scope="col" data-width="16" data-width-unit="%" data-sortable="false">3rd</th>
                                                <th data-field="fourth" scope="col" data-width="16" data-width-unit="%" data-sortable="false">4th</th>
                                                <th data-field="ot" scope="col" data-width="16" data-width-unit="%" data-sortable="false">OT</th>
                                                <th data-field="sum" scope="col" data-width="16" data-width-unit="%" data-sortable="false">Gesamt</th>
                                            </tr>
                                            </thead>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row mb-4">
                    <div id="colHome" class="col-2">
                        <?php
                        if ($quarter == 1 && $playClock == 900) {
                            // Home = Return-Team
                            $startingPlayers = $gameController->getStartingEleven($team, 'Special');
                            $startingPlayers = array_values(array_filter($startingPlayers, function (Player $player) {
                                return $player->getLineupPosition() == 'R';
                            }));
                        } else {
                            if (isset($offTeamName)) {
                                $teamPart = $offTeamName == $team->getName() ? 'Offense' : 'Defense';
                            } else {
                                $teamPart = $vsOrAt == 'vs' ? 'Offense' : 'Defense';
                            }
                            $startingPlayers = $gameController->getStartingEleven($team, $teamPart, $secondRB);
                        }
                        include($_SERVER['DOCUMENT_ROOT'] . '/pages/live/game/startingElevenList.php');
                        ?>
                    </div>
                    <div id="colLive" class="col-8" style="padding: 0">
                        <canvas id="liveView"></canvas>
                    </div>
                    <div id="colAway" class="col-2">
                        <?php
                        if ($quarter == 1 && $playClock == 900) {
                            // Away = Kicking-Team
                            $startingPlayers = $gameController->getStartingEleven($vsTeam, 'Special');
                            $startingPlayers = array_values(array_filter($startingPlayers, function (Player $player) {
                                return $player->getLineupPosition() == 'K';
                            }));
                        } else {
                            if (isset($offTeam)) {
                                $vsTeamPart = $offTeam->getName() == $team->getName() ? 'Defense' : 'Offense';
                            } else {
                                $vsTeamPart = $vsOrAt == 'vs' ? 'Defense' : 'Offense';
                            }
                            $startingPlayers = $gameController->getStartingEleven($vsTeam, $vsTeamPart, $secondRB);
                        }
                        include($_SERVER['DOCUMENT_ROOT'] . '/pages/live/game/startingElevenList.php');
                        ?>
                    </div>
                </div>
            </div>

            <script nomodule>
                console.info(`Your browser doesn't support native JavaScript modules.`);
            </script>
            <script type="module" src="/app/scriptsipts/live/game.js"></script>

        <?php
        endif;
    endif;
endif; ?>