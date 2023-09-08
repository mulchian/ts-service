<?php

use touchdownstars\player\skill\SkillController;
use touchdownstars\player\PlayerController;
use touchdownstars\team\TeamController;

if (isset($pdo, $log)):
    $playerController = new PlayerController($pdo, $log);
    $teamController = new TeamController($pdo, $log);
    $skillController = new SkillController($pdo, $log);

    if (isset($_SESSION['team'])) :
        $team = $_SESSION['team'];
        if (isset($_SESSION['TE1TrainingTime']) && !empty($_SESSION['TE1TrainingTime'])) {
            $TE1TimeToCount = $_SESSION['TE1TrainingTime'];
        } else {
            // wenn kein TimeToCount in Session, suche in DB
            $TE1TimeToCount = $teamController->getTimeToCount($team, 'TE1');
        }
        if (isset($_SESSION['TE2TrainingTime']) && !empty($_SESSION['TE2TrainingTime'])) {
            $TE2TimeToCount = $_SESSION['TE2TrainingTime'];
        } else {
            // wenn kein TimeToCount in Session, suche in DB
            $TE2TimeToCount = $teamController->getTimeToCount($team, 'TE2');
        }
        if (isset($_SESSION['TE3TrainingTime']) && !empty($_SESSION['TE3TrainingTime'])) {
            $TE3TimeToCount = $_SESSION['TE3TrainingTime'];
        } else {
            // wenn kein TimeToCount in Session, suche in DB
            $TE3TimeToCount = $teamController->getTimeToCount($team, 'TE3');
        }

        $isTE1Training = false;
        $isTE2Training = false;
        $isTE3Training = false;
        if (isset($TE1TimeToCount) && !empty($TE1TimeToCount)) {
            $phpTrainingTime = $TE1TimeToCount;
            if (isset($phpTrainingTime) && $phpTrainingTime > time()) {
                $isTE1Training = true;
            }
        }
        if (isset($TE2TimeToCount) && !empty($TE2TimeToCount)) {
            $phpTrainingTime = $TE2TimeToCount;
            if (isset($phpTrainingTime) && $phpTrainingTime > time()) {
                $isTE2Training = true;
            }
        }
        if (isset($TE3TimeToCount) && !empty($TE3TimeToCount)) {
            $now = time();
            if ($TE3TimeToCount > $now) {
                $isTE3Training = true;
            }
        }
        ?>

        <div class="panel panel-default opacity">
            <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING']); ?>" method="post">
                <div class="row my-2 justify-content-center" id="trainingRow">
                    <div class="col-sm-auto d-flex align-items-stretch">
                        <div class="card text-center bg-dark text-white">
                            <div class="card-header">
                                <?php if (isset($team) && !empty($team)) :
                                    echo $teamController->getTrainingGroup($team, 'TE1');
                                else :
                                    echo 'Trainingsgruppe 1';
                                endif; ?>
                            </div>
                            <div class="card-body">
                                <label id="TE1Error" class="card-text text-danger small d-none"
                                       style="margin-bottom: .9rem;word-wrap: break-word;max-width: 200px"></label>
                                <label id="pTE1Training" class="card-text d-none" style="margin-bottom: .9rem;"></label>
                                <?php if ($isTE1Training) : ?>
                                    <script type="text/javascript">
                                        $(function () {
                                            setCountDown('pTE1Training', <?php echo $TE1TimeToCount; ?>);
                                        });
                                    </script>
                                <?php endif; ?>
                                <div id="btnRowTE1" class="row <?php echo $isTE1Training ? 'd-none' : ''; ?>">
                                    <div class="col-sm">
                                        <button type="button" id="btnTE1Fitness" class="btn btn-secondary tooltip-custom
                                        <?php echo $teamController->trainingGroupHasPlayer($team, 'TE1') ? '' : 'disabled'; ?>"
                                                data-tooltip-content="#fitness_tooltip"
                                            <?php echo $teamController->trainingGroupHasPlayer($team, 'TE1') ? 'onclick="train(' .
                                                htmlspecialchars(json_encode($teamController->getPlayersToTrainingGroup($team, 'TE1'))) .
                                                ', \'pTE1Training\', \'fitness\')"' : ''; ?>>
                                            <i class="fa fa-dumbbell"></i></button>
                                    </div>
                                    <div class="col-sm">
                                        <button type="button" id="btnTE1Technik" class="btn btn-secondary tooltip-custom
                                        <?php echo $teamController->trainingGroupHasPlayer($team, 'TE1') ? '' : 'disabled'; ?>"
                                                data-tooltip-content="#technique_tooltip"
                                            <?php echo $teamController->trainingGroupHasPlayer($team, 'TE1') ? 'onclick="train(' .
                                                htmlspecialchars(json_encode($teamController->getPlayersToTrainingGroup($team, 'TE1'))) .
                                                ', \'pTE1Training\', \'technique\')"' : ''; ?>>
                                            <i class="fa fa-football-ball"></i></button>
                                    </div>
                                    <div class="col-sm">
                                        <button type="button" id="btnTE1Scrimmage" class="btn btn-secondary tooltip-custom
                                        <?php echo $teamController->trainingGroupHasPlayer($team, 'TE1') ? '' : 'disabled'; ?>"
                                                data-tooltip-content="#scrimmage_tooltip"
                                            <?php echo $teamController->trainingGroupHasPlayer($team, 'TE1') ? 'onclick="train(' .
                                                htmlspecialchars(json_encode($teamController->getPlayersToTrainingGroup($team, 'TE1'))) .
                                                ', \'pTE1Training\', \'scrimmage\')"' : ''; ?>>
                                            <i class="fa fa-users"></i></button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-auto d-flex align-items-stretch">
                        <div class="card text-center bg-dark text-white">
                            <div class="card-header">
                                <?php if (isset($team) && !empty($team)) :
                                    echo $teamController->getTrainingGroup($team, 'TE2');
                                else :
                                    echo 'Trainingsgruppe 2';
                                endif; ?>
                            </div>
                            <div class="card-body">
                                <label id="TE2Error" class="card-text text-danger small d-none"
                                       style="margin-bottom: .9rem;word-wrap: break-word;max-width: 200px"></label>
                                <label id="pTE2Training" class="card-text d-none" style="margin-bottom: .9rem;"></label>
                                <?php if ($isTE2Training) : ?>
                                    <script type="text/javascript">
                                        $(function () {
                                            setCountDown('pTE2Training', <?php echo $TE2TimeToCount; ?>);
                                        });
                                    </script>
                                <?php endif; ?>
                                <div id="btnRowTE2" class="row <?php echo $isTE2Training ? 'd-none' : ''; ?>">
                                    <div class="col-sm">
                                        <!-- Verbessert die physischen Fähigkeiten deiner Spieler. -->
                                        <button type="button" id="btnTE2Fitness" class="btn btn-secondary tooltip-custom
                                        <?php echo $teamController->trainingGroupHasPlayer($team, 'TE2') ? '' : 'disabled'; ?>"
                                                data-tooltip-content="#fitness_tooltip"
                                            <?php echo $teamController->trainingGroupHasPlayer($team, 'TE2') ? 'onclick="train(' .
                                                htmlspecialchars(json_encode($teamController->getPlayersToTrainingGroup($team, 'TE2'))) .
                                                ', \'pTE2Training\', \'fitness\')"' : ''; ?>>
                                            <i class="fa fa-dumbbell"></i></button>
                                    </div>
                                    <div class="col-sm">
                                        <!-- Verbessert die technischen Fähigkeiten deiner Spieler. -->
                                        <button type="button" id="btnTE2Technik" class="btn btn-secondary tooltip-custom
                                        <?php echo $teamController->trainingGroupHasPlayer($team, 'TE2') ? '' : 'disabled'; ?>"
                                                data-tooltip-content="#technique_tooltip"
                                            <?php echo $teamController->trainingGroupHasPlayer($team, 'TE2') ? 'onclick="train(' .
                                                htmlspecialchars(json_encode($teamController->getPlayersToTrainingGroup($team, 'TE2'))) .
                                                ', \'pTE2Training\', \'technique\')"' : ''; ?>>
                                            <i class="fa fa-football-ball"></i></button>
                                    </div>
                                    <div class="col-sm">
                                        <!-- Verbessert die Team Chemie und bringt Erfahrungspunkte. -->
                                        <button type="button" id="btnTE2Scrimmage" class="btn btn-secondary tooltip-custom
                                        <?php echo $teamController->trainingGroupHasPlayer($team, 'TE2') ? '' : 'disabled'; ?>"
                                                data-tooltip-content="#scrimmage_tooltip"
                                            <?php echo $teamController->trainingGroupHasPlayer($team, 'TE2') ? 'onclick="train(' .
                                                htmlspecialchars(json_encode($teamController->getPlayersToTrainingGroup($team, 'TE2'))) .
                                                ', \'pTE2Training\', \'scrimmage\')"' : ''; ?>>
                                            <i class="fa fa-users"></i></button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-auto d-flex align-items-stretch">
                        <div class="card text-center bg-dark text-white">
                            <div class="card-header">
                                <?php if (isset($team) && !empty($team)) :
                                    echo $teamController->getTrainingGroup($team, 'TE3');
                                else :
                                    echo 'Trainingsgruppe 3';
                                endif; ?>
                            </div>
                            <div class="card-body">
                                <label id="TE3Error" class="card-text text-danger small d-none"
                                       style="margin-bottom: .9rem;word-wrap: break-word;max-width: 200px"></label>
                                <label id="pTE3Training" class="card-text d-none" style="margin-bottom: .9rem;"></label>
                                <?php if ($isTE3Training) : ?>
                                    <script type="text/javascript">
                                        $(function () {
                                            setCountDown('pTE3Training', <?php echo $TE3TimeToCount; ?>);
                                        });
                                    </script>
                                <?php endif; ?>
                                <div id="btnRowTE3" class="row <?php echo $isTE3Training ? 'd-none' : ''; ?>">
                                    <div class="col-sm">
                                        <button type="button" id="btnTE3Fitness" class="btn btn-secondary tooltip-custom
                                        <?php echo $teamController->trainingGroupHasPlayer($team, 'TE3') ? '' : 'disabled'; ?>"
                                                data-tooltip-content="#fitness_tooltip"
                                            <?php echo $teamController->trainingGroupHasPlayer($team, 'TE3') ? 'onclick="train(' .
                                                htmlspecialchars(json_encode($teamController->getPlayersToTrainingGroup($team, 'TE3'))) .
                                                ', \'pTE3Training\', \'fitness\')"' : ''; ?>>
                                            <i class="fa fa-dumbbell"></i></button>
                                    </div>
                                    <div class="col-sm">
                                        <button type="button" id="btnTE3Technik" class="btn btn-secondary tooltip-custom
                                        <?php echo $teamController->trainingGroupHasPlayer($team, 'TE3') ? '' : 'disabled'; ?>"
                                                data-tooltip-content="#technique_tooltip"
                                            <?php echo $teamController->trainingGroupHasPlayer($team, 'TE3') ? 'onclick="train(' .
                                                htmlspecialchars(json_encode($teamController->getPlayersToTrainingGroup($team, 'TE3'))) .
                                                ', \'pTE3Training\', \'technique\')"' : ''; ?>>
                                            <i class="fa fa-football-ball"></i></button>
                                    </div>
                                    <div class="col-sm">
                                        <button type="button" id="btnTE3Scrimmage" class="btn btn-secondary tooltip-custom
                                        <?php echo $teamController->trainingGroupHasPlayer($team, 'TE3') ? '' : 'disabled'; ?>"
                                                data-tooltip-content="#scrimmage_tooltip"
                                            <?php echo $teamController->trainingGroupHasPlayer($team, 'TE3') ? 'onclick="train(' .
                                                htmlspecialchars(json_encode($teamController->getPlayersToTrainingGroup($team, 'TE3'))) .
                                                ', \'pTE3Training\', \'scrimmage\')"' : ''; ?>>
                                            <i class="fa fa-users"></i></button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-auto d-flex align-items-stretch">
                        <div class="card text-center bg-dark text-white">
                            <div class="card-header">Intensität</div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-sm">
                                        <a id="1"
                                           href="javascript:updateIntensityForTeam(1);">
                                            <i class="fas fa-battery-quarter" style="font-size: 30px; color: #00ff00;"></i>
                                        </a>
                                    </div>
                                    <div class="col-sm">
                                        <a id="2"
                                           href="javascript:updateIntensityForTeam(2);">
                                            <i class="fas fa-battery-half" style="font-size: 30px; color: #ffff00;"></i>
                                        </a>
                                    </div>
                                    <div class="col-sm">
                                        <a id="3"
                                           href="javascript:updateIntensityForTeam(3);">
                                            <i class="fas fa-battery-full" style="font-size: 30px; color: #ff0000;"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-auto d-flex align-items-stretch">
                        <div class="card text-center bg-dark text-white">
                            <div class="card-header">Trainingsgruppe</div>
                            <div class="card-body">
                                <div class="dropdown">
                                    <button class="btn btn-secondary dropdown-toggle" type="button"
                                            id="trainingGroupDropdown" data-toggle="dropdown" aria-haspopup="true"
                                            aria-expanded="false">
                                        <?php echo $teamController->getTrainingGroup($team, $teamController->getTrainingGroup($team, "TE1")); ?>
                                    </button>
                                    <div class="dropdown-menu" aria-labelledby="trainingGroupDropdown">
                                        <a class="dropdown-item"
                                           href="javascript:updateTrainingGroupForTeam('TE0')"><?php echo $teamController->getTrainingGroup($team, 'TE0'); ?> </a>
                                        <a class="dropdown-item"
                                           href="javascript:updateTrainingGroupForTeam('TE1')"><?php echo $teamController->getTrainingGroup($team, 'TE1'); ?></a>
                                        <a class="dropdown-item"
                                           href="javascript:updateTrainingGroupForTeam('TE2')"><?php echo $teamController->getTrainingGroup($team, 'TE2'); ?></a>
                                        <a class="dropdown-item"
                                           href="javascript:updateTrainingGroupForTeam('TE3')"><?php echo $teamController->getTrainingGroup($team, 'TE3'); ?></a>

                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="tooltip-content">
            <span id="fitness_tooltip">
                <u><strong>Fitness</strong></u><br>Verbessert die physischen Fähigkeiten deiner Spieler.
            </span>
                    <span id="technique_tooltip">
                <u><strong>Technik</strong></u><br>Verbessert die technischen Fähigkeiten deiner Spieler.
            </span>
                    <span id="scrimmage_tooltip">
                <u><strong>Scrimmage</strong></u><br>Verbessert die Team Chemie und bringt Erfahrungspunkte.
            </span>
                </div>
                <div class="row my-3">
                    <div class="col-sm">
                        <table id="tblTraining" class="table table-dark" data-toggle="table" data-sortable="true"
                               data-sort-name="pos" data-sort-order="asc" data-unique-id="id" data-sticky-header="true"
                               data-sticky-header-offset-y="1" data-sticky-header-offset-right="15" data-sticky-header-offset-left="15">
                            <thead class="thead-dark">
                            <tr>
                                <th data-field="id" scope="col" data-width="5" data-width-unit="%" data-visible="false">ID</th>
                                <th data-field="pos" scope="col" data-width="5" data-width-unit="%" data-sortable="true" data-sorter="positionSorter">
                                    POS
                                </th>
                                <th data-field="ovr" scope="col" data-width="5" data-width-unit="%" data-sortable="true">OVR</th>
                                <th data-field="age" scope="col" data-width="5" data-width-unit="%" data-sortable="true"
                                    class="d-none d-lg-table-cell">
                                    ALTER
                                </th>
                                <th data-field="player" scope="col" data-width="20" data-width-unit="%" data-sortable="true">SPIELER</th>
                                <th data-field="talent" scope="col" data-width="12" data-width-unit="%" data-sortable="true"
                                    class="d-none d-lg-table-cell">
                                    TALENT
                                </th>
                                <th data-field="energy" scope="col" data-width="8" data-width-unit="%" data-sortable="true"
                                    class=".d-none .d-sm-block">
                                    ENERGIE
                                </th>
                                <th data-field="skillpoints" scope="col" data-width="8" data-width-unit="%" data-sortable="true">SP</th>
                                <th data-field="intensity" scope="col" data-width="6" data-width-unit="%" data-sortable="true"
                                    data-sorter="intensitySorter">
                                    INTENSITÄT
                                </th>
                                <th data-field="trainings" scope="col" data-width="5" data-width-unit="%" data-sortable="true">TRAININGS</th>
                                <th data-field="trainingGroup" scope="col" data-width="7" data-width-unit="%" data-sortable="true">TRAININGSGRUPPE
                                </th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php if (isset($team) && !empty($team)) :
                                foreach ($team->getPlayers() as $player):?>
                                    <tr>
                                        <td <?php echo getPlayerModalDataset($player); ?>>
                                            <?php echo $player->getId(); ?>
                                        </td>
                                        <td <?php echo getPlayerModalDataset($player); ?>>
                                            <?php echo $player->getType()->getPosition()->getPosition(); ?>
                                        </td>
                                        <td <?php echo getPlayerModalDataset($player); ?>>
                                            <?php echo $player->getOVR(); ?>
                                        </td>
                                        <td <?php echo getPlayerModalDataset($player); ?>>
                                            <?php echo $player->getAge(); ?>
                                        </td>
                                        <td <?php echo getPlayerModalDataset($player); ?>>
                                            <?php echo $player->getFirstName() . ' training.php' . $player->getLastName(); ?>
                                        </td>
                                        <td <?php echo getPlayerModalDataset($player); ?>>
                                            <?php for ($i = 0; $i < floor($player->getTalent() / 2); $i++) : ?>
                                                <i class="fas fa-star"></i>
                                            <?php endfor;
                                            if (($player->getTalent() % 2) != 0) :?>
                                                <i class="far fa-star"></i>
                                            <?php endif; ?>
                                        </td>
                                        <td <?php echo getPlayerModalDataset($player); ?>>
                                            <?php echo ($player->getEnergy() * 100) . ' %'; ?>
                                        </td>
                                        <td data-toggle="modal" data-target="#playerModal" data-modal-target="skills">
                                            <div class="progress">
                                                <?php $skillWidth = number_format($player->getSkillpoints() - floor($player->getSkillpoints()), 2) * 100; ?>
                                                <div class="progress-bar" role="progressbar" style="width: <?php echo $skillWidth . '%'; ?>;"
                                                     aria-valuenow="<?php echo $skillWidth; ?>" aria-valuemin="0" aria-valuemax="100">
                                                    <small class="progress-bar-title"><?php echo floor($player->getSkillpoints()) . ' SP'; ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if ($player->getIntensity() == 1) : ?>
                                                <a id="1"
                                                   href="<?php echo "javascript:updateIntensity(" . $player->getId() . ", 2, " . $player->getId() . ");"; ?>">
                                                    <i class="fas fa-battery-quarter" style="font-size: 30px; color: #00ff00;"></i>
                                                </a>
                                            <?php elseif ($player->getIntensity() == 2) : ?>
                                                <a id="2"
                                                   href="<?php echo "javascript:updateIntensity(" . $player->getId() . ", 3, " . $player->getId() . ");"; ?>">
                                                    <i class="fas fa-battery-half" style="font-size: 30px; color: #ffff00;"></i>
                                                </a>
                                            <?php elseif ($player->getIntensity() == 3) : ?>
                                                <a id="3"
                                                   href="<?php echo "javascript:updateIntensity(" . $player->getId() . ", 1, " . $player->getId() . ");"; ?>">
                                                    <i class="fas fa-battery-full" style="font-size: 30px; color: #ff0000;"></i>
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo $player->getNumberOfTrainings() . ' / 3'; ?></td>
                                        <td>
                                            <div class="dropdown">
                                                <button class="btn btn-secondary dropdown-toggle" type="button"
                                                        data-toggle="dropdown" aria-haspopup="true"
                                                        aria-expanded="false">
                                                    <?php echo $teamController->getTrainingGroup($team, $player->getTrainingGroup()); ?>
                                                </button>
                                                <div class="dropdown-menu" aria-labelledby="trainingGroupDropdown">
                                                    <?php
                                                    echo "<a class=\"dropdown-item\" href=\"javascript:updateTrainingGroup(" . $player->getId() . ", '" . $player->getTrainingGroup() . "', 'TE0', " . $player->getId() . ");\">" . $teamController->getTrainingGroup($team, "TE0") . "</a>\n";
                                                    echo "<a class=\"dropdown-item\" href=\"javascript:updateTrainingGroup(" . $player->getId() . ", '" . $player->getTrainingGroup() . "', 'TE1', " . $player->getId() . ");\">" . $teamController->getTrainingGroup($team, "TE1") . "</a>\n";
                                                    echo "<a class=\"dropdown-item\" href=\"javascript:updateTrainingGroup(" . $player->getId() . ", '" . $player->getTrainingGroup() . "', 'TE2', " . $player->getId() . ");\">" . $teamController->getTrainingGroup($team, "TE2") . "</a>\n";
                                                    echo "<a class=\"dropdown-item\" href=\"javascript:updateTrainingGroup(" . $player->getId() . ", '" . $player->getTrainingGroup() . "', 'TE3', " . $player->getId() . ");\">" . $teamController->getTrainingGroup($team, "TE3") . "</a>\n";
                                                    ?>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </form>
        </div>

        <script src="/scripts/team/train.js"></script>

    <?php
    endif;
endif;
?>