<?php

use touchdownstars\team\TeamController;

if (isset($pdo, $log, $team, $teamPart)):
    $teamController = new TeamController($pdo, $log);
    $players = $teamController->getStartingPlayers($team);

    if (strcmp($teamPart, 'Offense') == 0):
        ?>
        <div class="col">
            <div class="row m-2 justify-content-center">
                <div class="col-sm-3" id="cardFB">
                    <div class="card text-center <?php echo $team->getLineupOff() == 'FB' ? 'bg-dark' : 'bg-secondary'; ?> text-white">
                        <h5 class="card-header">Fullback</h5>
                        <div class="card-body">
                            <?php echo getPlayerInfo($players, 'FB', 'FB'); ?>
                        </div>
                    </div>
                </div>
                <div class="col-sm-3">
                    <div class="card text-center bg-dark text-white">
                        <h5 class="card-header">Quarterback</h5>
                        <div class="card-body">
                            <?php echo getPlayerInfo($players, 'QB', 'QB'); ?>
                        </div>
                    </div>
                </div>
                <div class="col-sm-3">
                    <div class="card text-center bg-dark text-white">
                        <h5 class="card-header">Runningback</h5>
                        <div class="card-body">
                            <?php echo getPlayerInfo($players, 'RB', 'RB1'); ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row m-2 justify-content-center">
                <div class="col-sm-3" id="cardTE">
                    <div class="card text-center <?php echo $team->getLineupOff() == 'TE' ? 'bg-dark' : 'bg-secondary'; ?> text-white">
                        <h5 class="card-header">Tight End</h5>
                        <div class="card-body">
                            <?php echo getPlayerInfo($players, 'TE', 'TE'); ?>
                        </div>
                    </div>
                </div>
                <div class="col-sm-3">
                    <div class="card text-center bg-dark text-white">
                        <h5 class="card-header">Wide Receiver</h5>
                        <div class="card-body">
                            <?php echo getPlayerInfo($players, 'WR', 'WR'); ?>
                        </div>
                    </div>
                </div>
                <div class="col-sm-3">
                    <div class="card text-center bg-dark text-white">
                        <h5 class="card-header">Wide Receiver</h5>
                        <div class="card-body">
                            <?php echo getPlayerInfo($players, 'WR', 'WR', 1); ?>
                        </div>
                    </div>
                </div>
                <div class="col-sm-3">
                    <div class="card text-center bg-dark text-white">
                        <h5 class="card-header">Wide Receiver</h5>
                        <div class="card-body">
                            <?php echo getPlayerInfo($players, 'WR', 'WR', 2); ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row m-2 justify-content-center">
                <div class="col-sm-2" style="width: 20%;flex: 0 0 20%;max-width: 20%;">
                    <div class="card text-center bg-dark text-white">
                        <h5 class="card-header">Right Tackle</h5>
                        <div class="card-body">
                            <?php echo getPlayerInfo($players, 'OT', 'RT'); ?>
                        </div>
                    </div>
                </div>
                <div class="col-sm-2" style="width: 20%;flex: 0 0 20%;max-width: 20%;">
                    <div class="card text-center bg-dark text-white">
                        <h5 class="card-header">Right Guard</h5>
                        <div class="card-body">
                            <?php echo getPlayerInfo($players, 'OG', 'RG'); ?>
                        </div>
                    </div>
                </div>
                <div class="col-sm-2" style="width: 20%;flex: 0 0 20%;max-width: 20%;">
                    <div class="card text-center bg-dark text-white">
                        <h5 class="card-header">Center</h5>
                        <div class="card-body">
                            <?php echo getPlayerInfo($players, 'C', 'C'); ?>
                        </div>
                    </div>
                </div>
                <div class="col-sm-2" style="width: 20%;flex: 0 0 20%;max-width: 20%;">
                    <div class="card text-center bg-dark text-white">
                        <h5 class="card-header">Left Guard</h5>
                        <div class="card-body">
                            <?php echo getPlayerInfo($players, 'OG', 'LG'); ?>
                        </div>
                    </div>
                </div>
                <div class="col-sm-2" style="width: 20%;flex: 0 0 20%;max-width: 20%;">
                    <div class="card text-center bg-dark text-white">
                        <h5 class="card-header">Left Tackle</h5>
                        <div class="card-body">
                            <?php echo getPlayerInfo($players, 'OT', 'LT'); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php elseif (strcmp($teamPart, 'Defense') == 0): ?>
        <div class="col">
            <div class="row m-2 justify-content-center">
                <div class="col-sm-3">
                    <div class="card text-center bg-dark text-white">
                        <h5 class="card-header">Left End</h5>
                        <div class="card-body">
                            <?php echo getPlayerInfo($players, 'DE', 'LE'); ?>
                        </div>
                    </div>
                </div>
                <div class="col-sm-3">
                    <div class="card text-center bg-dark text-white">
                        <h5 class="card-header">Defensive Tackle</h5>
                        <div class="card-body">
                            <?php echo getPlayerInfo($players, 'DT', 'DT'); ?>
                        </div>
                    </div>
                </div>
                <div class="col-sm-3" id="cardNT">
                    <div class="card text-center <?php echo $team->getLineupDef() == 'NT' ? 'bg-dark' : 'bg-secondary'; ?> text-white">
                        <h5 class="card-header">Nose Tackle</h5>
                        <div class="card-body">
                            <?php echo getPlayerInfo($players, 'DT', 'NT'); ?>
                        </div>
                    </div>
                </div>
                <div class="col-sm-3">
                    <div class="card text-center bg-dark text-white">
                        <h5 class="card-header">Right End</h5>
                        <div class="card-body">
                            <?php echo getPlayerInfo($players, 'DE', 'RE'); ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row m-2 justify-content-center">
                <div class="col-sm-3">
                    <div class="card text-center bg-dark text-white">
                        <h5 class="card-header">Left Outside Linebacker</h5>
                        <div class="card-body">
                            <?php echo getPlayerInfo($players, 'OLB', 'LOLB'); ?>
                        </div>
                    </div>
                </div>
                <div class="col-sm-3">
                    <div class="card text-center bg-dark text-white">
                        <h5 class="card-header">Middle Linebacker</h5>
                        <div class="card-body">
                            <?php echo getPlayerInfo($players, 'MLB', 'MLB1'); ?>
                        </div>
                    </div>
                </div>
                <div class="col-sm-3" id="cardMLB">
                    <div class="card text-center <?php echo $team->getLineupDef() == 'MLB' ? 'bg-dark' : 'bg-secondary'; ?> text-white">
                        <h5 class="card-header">Middle Linebacker</h5>
                        <div class="card-body">
                            <?php echo getPlayerInfo($players, 'MLB', 'MLB2'); ?>
                        </div>
                    </div>
                </div>
                <div class="col-sm-3">
                    <div class="card text-center bg-dark text-white">
                        <h5 class="card-header">Right Outside Linebacker</h5>
                        <div class="card-body">
                            <?php echo getPlayerInfo($players, 'OLB', 'ROLB'); ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row m-2 justify-content-center">
                <div class="col-sm-3">
                    <div class="card text-center bg-dark text-white">
                        <h5 class="card-header">Cornerback</h5>
                        <div class="card-body">
                            <?php echo getPlayerInfo($players, 'CB', 'CB'); ?>
                        </div>
                    </div>
                </div>
                <div class="col-sm-3">
                    <div class="card text-center bg-dark text-white">
                        <h5 class="card-header">Strong Safety</h5>
                        <div class="card-body">
                            <?php echo getPlayerInfo($players, 'SS', 'SS'); ?>
                        </div>
                    </div>
                </div>
                <div class="col-sm-3">
                    <div class="card text-center bg-dark text-white">
                        <h5 class="card-header">Free Safety</h5>
                        <div class="card-body">
                            <?php echo getPlayerInfo($players, 'FS', 'FS'); ?>
                        </div>
                    </div>
                </div>
                <div class="col-sm-3">
                    <div class="card text-center bg-dark text-white">
                        <h5 class="card-header">Cornerback</h5>
                        <div class="card-body">
                            <?php echo getPlayerInfo($players, 'CB', 'CB', 1); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="col">
            <div class="row m-2 justify-content-center">
                <div class="col-sm-3">
                    <div class="card text-center bg-dark text-white">
                        <h5 class="card-header">Kicker</h5>
                        <div class="card-body">
                            <?php echo getPlayerInfo($players, 'K', 'K'); ?>
                        </div>
                    </div>
                </div>
                <div class="col-sm-3">
                    <div class="card text-center bg-dark text-white">
                        <h5 class="card-header">Punter</h5>
                        <div class="card-body">
                            <?php echo getPlayerInfo($players, 'P', 'P'); ?>
                        </div>
                    </div>
                </div>
                <div class="col-sm-3">
                    <div class="card text-center bg-dark text-white">
                        <h5 class="card-header">Returner</h5>
                        <div class="card-body">
                            <?php echo getPlayerInfo($players, 'R', 'R'); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php
    endif;
endif;
?>