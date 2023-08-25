<?php

use touchdownstars\league\LeagueController;
use touchdownstars\live\GameController;
use touchdownstars\team\TeamController;

if (isset($pdo, $log, $_SESSION['team'], $_SESSION['season'])) :
    $start = hrtime(true);
    $log->debug('schedule.php started');
    $teamController = new TeamController($pdo, $log);
    $leagueController = new LeagueController($pdo, $log);
    $gameController = new GameController($pdo, $log);
    $team = $_SESSION['team'];

    $games = $leagueController->fetchGames($team, $_SESSION['season'], $isLeague ?? false);
    $log->debug('time from start to fetched games: ' . ((hrtime(true) - $start) / 1e+6) . ' ms');
    usort($games, function (array $game1, array $game2) {
        return $game1['gameTime'] <=> $game2['gameTime'];
    })
    ?>

    <div class="panel panel-default opacity">
        <?php
        foreach ($games as $game) :
            $result = null;
            $recalculate = false;
            // $log->debug('Game: ' . print_r($game, true));
            $isHome = $team->getName() == $game['home'];
            $vsOrAt = $isHome ? 'vs' : '@';
            $vsTeamName = $game['home'] == $team->getName() ? $game['away'] : $game['home'];

            $vsTeam = $teamController->fetchTeam(null, $vsTeamName);

            if (isset($game['result'])) {
                $points = explode(';', $game['result']);
                $result = $points[0] . ' : ' . $points[1];
            } else {
                if (!isset($game['gameDay']) && ($game['gameTime'] > time() + 1200 && $game['gameTime'] < time() + 3600)) {
                    $result = 'Loading...';
                    $recalculate = true;
                }
            }
            ?>
            <div class="row my-2">
                <div class="col">
                    <div class="card bg-dark text-white">
                        <div class="card-header">
                            <?php
                            if ($isLeague ?? false) {
                                echo 'Regular Season Spiel ' . $game['gameDay'];
                            } else {
                                echo 'Freundschaftsspiel vom ' . date('d.m.Y H:i', $game['gameTime']);
                            }
                            ?>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-sm-8">
                                    <div class="row my-0 py-0 align-middle">
                                        <div class="col-5 text-right">
                                            <?php echo $game['home'] . ' (' . ($isHome ? $team->getOVR() : $vsTeam->getOVR()) . ')'; ?>
                                        </div>
                                        <div class="col-2 text-warning" id="<?php echo 'result' . $game['id']; ?>">
                                            <?php echo $result ?? $vsOrAt; ?>
                                        </div>
                                        <div class="col-5 text-left">
                                            <?php echo $game['away'] . ' (' . ($isHome ? $vsTeam->getOVR() : $team->getOVR()) . ')'; ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-2" id="<?php echo 'resultSymbol' . $game['id']; ?>">
                                    <?php
                                    if (isset($game['result'], $points)) :
                                        $diff = $points[0] - $points[1];
                                        if (($isHome && $diff > 0) || (!$isHome && $diff < 0)) {
                                            $resultCond = 'S';
                                            $textColor = 'text-success';
                                        } elseif ($diff === 0) {
                                            $resultCond = 'U';
                                            $textColor = 'text-warning';
                                        } else {
                                            $resultCond = 'N';
                                            $textColor = 'text-danger';
                                        }
                                        ?>
                                        <h2 class="my-0 py-0 <?php echo $textColor; ?>"><?php echo $resultCond; ?></h2>
                                    <?php endif; ?>
                                </div>
                                <div class="col-sm-2">
                                    <?php if (isset($game['result'])) : ?>
                                        <a role="button" class="btn btn-secondary"
                                           href="/app/index.php?site=league&do=gamecenter&game=<?php echo $game['id']; ?>">
                                            Gamecenter
                                        </a>
                                    <?php elseif ($recalculate) : ?>
                                        <button class="btn btn-secondary" type="button" id="btnRecalculate"
                                                onclick="recalculate(<?php echo $game['id']; ?>, <?php echo $isHome ? 'true' : 'false'; ?>)">
                                            Nachsimulieren
                                        </button>
                                    <?php else: ?>
                                        <a role="button" class="btn btn-secondary"
                                           href="/app/index.php?site=league&do=matchup&game=<?php echo $game['id']; ?>">
                                            Matchup
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <script src="/app/scriptsipts/league/schedule.js"></script>
    <?php
    $log->debug('schedule.php finished after ' . ((hrtime(true) - $start) / 1e+6) . ' ms');
endif;
?>