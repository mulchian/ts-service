<?php

use touchdownstars\league\LeagueController;
use touchdownstars\live\GameController;
use touchdownstars\statistics\StatisticsController;
use touchdownstars\team\TeamController;

if (isset($pdo, $log, $_GET['game'], $_SESSION['team'])) :
    $teamController = new TeamController($pdo, $log);
    $gameController = new GameController($pdo, $log);
    $leagueController = new LeagueController($pdo, $log);
    $statisticsController = new StatisticsController($pdo, $log);
    $team = $_SESSION['team'];

    $game = $leagueController->fetchGameById($_GET['game']);

    $isHome = $team->getName() == $game['home'];
    $vsTeamName = $game['home'] == $team->getName() ? $game['away'] : $game['home'];

    $vsTeam = $teamController->fetchTeam(null, $vsTeamName);

    $teams = array($team, $vsTeam);

    $standings = $leagueController->getStandings($game);
    $isLeagueGame = $standings['idLeagueGame'] ?? false;
    $ot = explode(';', $standings['ot']);
    $sum = explode(';', $standings['score']);

    // Statistiken laden - Team-Statistics müssen im Spiel noch geschrieben oder anhand der Player-Statistics berechnet werden
    $teamStatistics = $statisticsController->getTeamStatisticsForGame($game['id'], $team->getStatistics(), $team->getId());
    $vsTeamStatistics = $statisticsController->getTeamStatisticsForGame($game['id'], $vsTeam->getStatistics(), $vsTeam->getId());

    ?>

    <div class="panel panel-default opacity mb-4">
        <div class="row my-2">
            <div class="col-sm-2">
                <div class="card bg-dark text-white">
                    <div class="card-header">
                        <?php
                        $teamStandings = $isLeagueGame ? $leagueController->getSeasonalLeagueStandings($team) : $leagueController->getSeasonalFriendlyStandings($team);
                        echo $team->getAbbreviation() . ' (' . $teamStandings . ')';
                        ?>
                    </div>
                    <div class="card-body my-0 py-2">
                        <div class="row mb-2">
                            <div class="col font-weight-bold"><?php echo $team->getName(); ?></div>
                        </div>
                        <div class="row">
                            <div class="col">
                                <p><small>Overall:</small> <strong><?php echo $team->getOVR(); ?></strong></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-8">
                <table id="tblGCStandings" class="table table-dark" data-toggle="table">
                    <thead>
                    <tr>
                        <th data-field="team" scope="col" data-width="35" data-width-unit="%"
                            data-sortable="false"><?php echo $isLeagueGame ? 'Ligaspiel' : 'Freundschaftsspiel'; ?></th>
                        <th data-field="first" scope="col" data-width="10" data-width-unit="%" data-sortable="false">1st</th>
                        <th data-field="second" scope="col" data-width="10" data-width-unit="%" data-sortable="false">2nd</th>
                        <th data-field="third" scope="col" data-width="10" data-width-unit="%" data-sortable="false">3rd</th>
                        <th data-field="fourth" scope="col" data-width="10" data-width-unit="%" data-sortable="false">4th</th>
                        <th data-field="ot" scope="col" data-width="10" data-width-unit="%" data-sortable="false">OT</th>
                        <th data-field="sum" scope="col" data-width="15" data-width-unit="%" data-sortable="false">Gesamt</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr>
                        <td><?php echo $team->getName(); ?></td>
                        <?php
                        for ($i = 1; $i < 5; $i++) {
                            $points = explode(';', $standings['score' . $i]);
                            echo '<td>' . ($isHome ? $points[0] : $points[1]) . '</td>';
                        }
                        ?>
                        <td><?php echo $isHome ? $ot[0] : $ot[1]; ?></td>
                        <td><?php echo $isHome ? $sum[0] : $sum[1]; ?></td>
                    </tr>
                    <tr>
                        <td><?php echo $vsTeam->getName(); ?></td>
                        <?php
                        for ($i = 1; $i < 5; $i++) {
                            $points = explode(';', $standings['score' . $i]);
                            echo '<td>' . ($isHome ? $points[1] : $points[0]) . '</td>';
                        }
                        ?>
                        <td><?php echo $isHome ? $ot[1] : $ot[0]; ?></td>
                        <td><?php echo $isHome ? $sum[1] : $sum[0]; ?></td>
                    </tr>
                    </tbody>
                </table>
            </div>
            <div class="col-sm-2">
                <div class="card bg-dark text-white">
                    <div class="card-header">
                        <?php
                        $teamStandings = $isLeagueGame ? $leagueController->getSeasonalLeagueStandings($vsTeam) : $leagueController->getSeasonalFriendlyStandings($vsTeam);
                        echo $vsTeam->getAbbreviation() . ' (' . $teamStandings . ')';
                        ?>
                    </div>
                    <div class="card-body my-0 py-2">
                        <div class="row mb-2">
                            <div class="col font-weight-bold"><?php echo $vsTeam->getName(); ?></div>
                        </div>
                        <div class="row">
                            <div class="col">
                                <p><small>Overall:</small> <strong><?php echo $vsTeam->getOVR(); ?></strong></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <nav class="bg-dark">
            <div class="nav nav-tabs" id="nav-tab-gamecenter" role="tablist">
                <a class="nav-item nav-link text-nav-color text-nav-hover-color active" id="nav-boxscore-tab" data-toggle="tab" href="#nav-boxscore"
                   role="tab"
                   aria-controls="nav-general" aria-selected="true">Box Score</a>
                <a class="nav-item nav-link text-nav-color text-nav-hover-color" id="nav-scoring-tab" data-toggle="tab" href="#nav-scoring" role="tab"
                   aria-controls="nav-statistics" aria-selected="false">Scoring</a>
                <a class="nav-item nav-link text-nav-color text-nav-hover-color" id="nav-playByPlay-tab" data-toggle="tab" href="#nav-playByPlay"
                   role="tab" aria-controls="nav-skills"
                   aria-selected="false">Play By Play</a>
                <a class="nav-item nav-link text-nav-color text-nav-hover-color" id="nav-statistics-tab" data-toggle="tab" href="#nav-statistics"
                   role="tab"
                   aria-controls="nav-contract" aria-selected="false">Statistiken</a>
            </div>
        </nav>
        <div class="tab-content" id="nav-tabContent">
            <div class="tab-pane fade show active" id="nav-boxscore" role="tabpanel" aria-labelledby="nav-boxscore-tab">
                <!-- Generelle Team-Statistiken -->
                <div class="row justify-content-center">
                    <div class="col-sm">
                        <table id="tblTeamStatistics" class="table table-dark" data-toggle="table" data-sortable="false"
                               data-sticky-header="true" data-sticky-header-offset-y="15" data-sticky-header-offset-right="20"
                               data-sticky-header-offset-left="20">
                            <thead class="thead-dark">
                            <tr>
                                <th data-field="home" scope="col" data-width="30" data-width-unit="%"><?php echo $game['home']; ?></th>
                                <th data-field="statistic" scope="col" data-width="30" data-width-unit="%">TEAM STATISTIKEN</th>
                                <th data-field="away" scope="col" data-width="30" data-width-unit="%"><?php echo $game['away']; ?></th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr>
                                <td colspan="3" rowspan="2">
                                    <div class="row mx-1 justify-content-center">
                                        <div class="col-1 justify-content-start">
                                            <?php echo $teamStatistics->getOvrYards(); ?>
                                        </div>
                                        <div class="col">
                                            YARDS INSGESAMT
                                        </div>
                                        <div class="col-1 justify-content-end">
                                            <?php echo $vsTeamStatistics->getOvrYards(); ?>
                                        </div>
                                    </div>
                                    <div class="row mx-1 justify-content-center">
                                        <div class="col justify-content-center">
                                            <input type="text" class="js-range-slider" name="sliderOvrYards" id="sliderOvrYards" value=""
                                                   data-type="single" data-min="0"
                                                   data-max="<?php echo($teamStatistics->getOvrYards() + $vsTeamStatistics->getOvrYards()); ?>"
                                                   data-from="<?php echo $vsTeamStatistics->getOvrYards(); ?>" data-step="1" data-from_fixed="true"
                                                   data-hide_min_max="true" data-hide_from_to="true" data-disable="true"/>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <tr></tr>
                            <tr>
                                <td><?php echo $teamStatistics->getPaYds(); ?></td>
                                <td>PASSING YARDS</td>
                                <td><?php echo $vsTeamStatistics->getPaYds(); ?></td>
                            </tr>
                            <tr>
                                <td><?php echo $teamStatistics->getRuYds(); ?></td>
                                <td>RUSHING YARDS</td>
                                <td><?php echo $vsTeamStatistics->getRuYds(); ?></td>
                            </tr>
                            <tr>
                                <td><?php echo $teamStatistics->getYardsPerAtt(); ?></td>
                                <td>YARDS PRO SPIELZUG</td>
                                <td><?php echo $vsTeamStatistics->getYardsPerAtt(); ?></td>
                            </tr>
                            <tr>
                                <td><?php echo $teamStatistics->getFirstDowns(); ?></td>
                                <td>FIRST DOWNS</td>
                                <td><?php echo $teamStatistics->getFirstDowns(); ?></td>
                            </tr>
                            <tr>
                                <td><?php echo ($teamStatistics->getEfficiencyForDown('3rd') * 100) . ' %'; ?></td>
                                <td>EFFIZIENZ DES THIRD DOWN</td>
                                <td><?php echo ($vsTeamStatistics->getEfficiencyForDown('3rd') * 100) . ' %'; ?></td>
                            </tr>
                            <tr>
                                <td><?php echo ($teamStatistics->getEfficiencyForDown('4th') * 100) . ' %'; ?></td>
                                <td>EFFIZIENZ DES FOURTH DOWN</td>
                                <td><?php echo ($vsTeamStatistics->getEfficiencyForDown('4th') * 100) . ' %'; ?></td>
                            </tr>
                            <tr>
                                <td><?php echo($teamStatistics->getRuAtt() + $teamStatistics->getPaAtt()); ?></td>
                                <td>SPIELZÜGE INSGESAMT</td>
                                <td><?php echo($vsTeamStatistics->getRuAtt() + $vsTeamStatistics->getPaAtt()); ?></td>
                            </tr>
                            <tr>
                                <td><?php echo $teamStatistics->getSacks(); ?></td>
                                <td>SACKS</td>
                                <td><?php echo $vsTeamStatistics->getSacks(); ?></td>
                            </tr>
                            <tr>
                                <td><?php echo $teamStatistics->getPunts(); ?></td>
                                <td>PUNTS</td>
                                <td><?php echo $vsTeamStatistics->getPunts(); ?></td>
                            </tr>
                            <tr>
                                <td><?php echo $teamStatistics->getPenaltyYds(); ?></td>
                                <td>PENALTIES (YARDS)</td>
                                <td><?php echo $vsTeamStatistics->getPenaltyYds() ?></td>
                            </tr>
                            <tr>
                                <td><?php echo $teamStatistics->getLostFumbles(); ?></td>
                                <td>LOST FUMBLES</td>
                                <td><?php echo $vsTeamStatistics->getLostFumbles(); ?></td>
                            </tr>
                            <tr>
                                <td><?php echo $teamStatistics->getInterceptions(); ?></td>
                                <td>INTERCEPTIONS</td>
                                <td><?php echo $vsTeamStatistics->getInterceptions(); ?></td>
                            </tr>
                            <tr>
                                <td><?php echo $teamStatistics->getTop(); ?></td>
                                <td>TIME OF POSSESSION</td>
                                <td><?php echo $vsTeamStatistics->getTop(); ?></td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="tab-pane fade" id="nav-scoring" role="tabpanel" aria-labelledby="nav-scoring-tab">
                <?php
                $scoringGameplays = $gameController->getScoringGameplaysForGame($game);
                $quarter = 1;
                $playClock = 900;
                foreach ($scoringGameplays as $gameplay) {
                    if ($gameplay['yardsToTD'] <= 0 || $gameplay['isFG']) {
                        //Scoring
                        $offTeam = $teamController->fetchTeamById($gameplay['idOffTeam']);
                        $defTeam = $teamController->fetchTeamById($gameplay['idDefTeam']);

                        $yardsToTD = $gameplay['startYardsToTD'];
                        if ($yardsToTD < 0) {
                            // Es kam im vorherigen Spielzug zu einem TD, also findet der PAT von der 15 Yards-Linie statt.
                            $yardLine = $defTeam->getAbbreviation() . 15;
                        } else if ($yardsToTD < 50) {
                            $yardLine = $defTeam->getAbbreviation() . $yardsToTD;
                        } elseif ($yardsToTD === 50) {
                            $yardLine = 50;
                        } else {
                            $yardLine = $offTeam->getAbbreviation() . $yardsToTD;
                        }

                        echo '<div class="card bg-dark text-white">' .
                            '<div class="card-header text-left">' . $gameplay['startQuarter'] . ' Quarter</div>' .
                            '<div class="card-body my-0 py-2">' .
                            '<div class="row mb-2">' .
                            '<div class="col-2">' . $offTeam->getName() . '</div>' .
                            '<div class="col-1">' . gmdate('i:s', $gameplay['startPlayClock']) . '</div>' .
                            '<div class="col-1">' . $yardLine . '</div>' .
                            '<div class="col-8 text-right">' . $gameplay['gametext'] . '</div>' .
                            '</div>' .
                            '</div>' .
                            '</div>';
                    }
                }
                ?>
            </div>
            <div class="tab-pane fade" id="nav-playByPlay" role="tabpanel" aria-labelledby="nav-playByPlay-tab">
            </div>
            <div class="tab-pane fade" id="nav-statistics" role="tabpanel" aria-labelledby="nav-statistics-tab">
            </div>
        </div>
    </div>

    <script src="/scripts/league/gamecenter.js"></script>
<?php endif; ?>