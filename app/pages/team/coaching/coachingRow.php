<?php

use touchdownstars\coaching\CoachingController;

if (!isset($team) && isset($_SESSION['team']) && !empty($_SESSION['team'])) {
    $team = $_SESSION['team'];
}

if (isset($pdo, $log) && isset($team) && isset($gameplanNr) && isset($down) && isset($teamPart)) :
    $coachingController = new CoachingController($pdo, $log);

    if (count($team->getCoachings()) == 0) {
        $coachings = $coachingController->fetchAllCoachings($team->getId());
        $team->setCoachings($coachings);
    }

    if ($teamPart == 'Offense') :
        $coachingShort = $coachingController->getCoachingFromTeam($team, $gameplanNr, $teamPart, $down, 'Short');
        $coachingMiddle = $coachingController->getCoachingFromTeam($team, $gameplanNr, $teamPart, $down, 'Middle');
        $coachingLong = $coachingController->getCoachingFromTeam($team, $gameplanNr, $teamPart, $down, 'Long');

        ?>
        <div class="col-4">
            <div class="card text-center bg-dark text-white">
                <h5 class="card-header"><?php echo $down; ?> Long</h5>
                <div class="card-body">
                    <div class="row">
                        <div class="col-6">
                            <?php $coachingGP1 = explode(';', $coachingLong->getGameplay1()); ?>
                            <input id="cbL<?php echo $down; ?>Long" class="mb-2"
                                   type="checkbox" <?php echo $coachingGP1[0] == 'Run' ? 'checked' : '' ?> data-toggle="toggle" data-on="Run"
                                   data-off="Pass"
                                   data-onstyle="secondary">
                            <div id="gpL<?php echo $down; ?>Long" class="m-2">
                                <?php echo getSelectOffBox('slL' . $down . 'Long', $coachingGP1[0], $coachingGP1[1]); ?>
                            </div>
                        </div>
                        <div class="col-6">
                            <?php $coachingGP2 = explode(';', $coachingLong->getGameplay2()); ?>
                            <input id="cbR<?php echo $down; ?>Long" class="mb-2"
                                   type="checkbox" <?php echo $coachingGP2[0] == 'Run' ? 'checked' : '' ?> data-toggle="toggle" data-on="Run"
                                   data-off="Pass"
                                   data-onstyle="secondary">
                            <div id="gpR<?php echo $down; ?>Long" class="m-2">
                                <?php echo getSelectOffBox('slR' . $down . 'Long', $coachingGP2[0], $coachingGP2[1]); ?>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12">
                            <input type="text" class="js-range-slider" name="rng<?php echo $down; ?>Long" id="rng<?php echo $down; ?>Long"
                                   value=""/>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-4">
            <div class="card text-center bg-dark text-white">
                <h5 class="card-header"><?php echo $down; ?> Middle</h5>
                <div class="card-body">
                    <div class="row">
                        <div class="col-6">
                            <?php $coachingGP1 = explode(';', $coachingMiddle->getGameplay1()); ?>
                            <input id="cbL<?php echo $down; ?>Middle" class="mb-2"
                                   type="checkbox" <?php echo $coachingGP1[0] == 'Run' ? 'checked' : '' ?> data-toggle="toggle" data-on="Run"
                                   data-off="Pass"
                                   data-onstyle="secondary">
                            <div id="gpL<?php echo $down; ?>Middle" class="m-2">
                                <?php echo getSelectOffBox('slL' . $down . 'Middle', $coachingGP1[0], $coachingGP1[1]); ?>
                            </div>
                        </div>
                        <div class="col-6">
                            <?php $coachingGP2 = explode(';', $coachingMiddle->getGameplay2()); ?>
                            <input id="cbR<?php echo $down; ?>Middle" class="mb-2"
                                   type="checkbox" <?php echo $coachingGP2[0] == 'Run' ? 'checked' : '' ?> data-toggle="toggle" data-on="Run"
                                   data-off="Pass"
                                   data-onstyle="secondary">
                            <div id="gpR<?php echo $down; ?>Middle" class="m-2">
                                <?php echo getSelectOffBox('slR' . $down . 'Middle', $coachingGP2[0], $coachingGP2[1]); ?>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12">
                            <input type="text" class="js-range-slider" name="rng<?php echo $down; ?>Middle" id="rng<?php echo $down; ?>Middle"
                                   value=""/>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-4">
            <div class="card text-center bg-dark text-white">
                <h5 class="card-header"><?php echo $down; ?> Short</h5>
                <div class="card-body">
                    <div class="row">
                        <div class="col-6">
                            <?php $coachingGP1 = explode(';', $coachingShort->getGameplay1()); ?>
                            <input id="cbL<?php echo $down; ?>Short" class="mb-2"
                                   type="checkbox" <?php echo $coachingGP1[0] == 'Run' ? 'checked' : '' ?> data-toggle="toggle" data-on="Run"
                                   data-off="Pass"
                                   data-onstyle="secondary">
                            <div id="gpL<?php echo $down; ?>Short" class="m-2">
                                <?php echo getSelectOffBox('slL' . $down . 'Short', $coachingGP1[0], $coachingGP1[1]); ?>
                            </div>
                        </div>
                        <div class="col-6">
                            <?php $coachingGP2 = explode(';', $coachingShort->getGameplay2()); ?>
                            <input id="cbR<?php echo $down; ?>Short" class="mb-2"
                                   type="checkbox" <?php echo $coachingGP2[0] == 'Run' ? 'checked' : '' ?> data-toggle="toggle" data-on="Run"
                                   data-off="Pass"
                                   data-onstyle="secondary">
                            <div id="gpR<?php echo $down; ?>Short" class="m-2">
                                <?php echo getSelectOffBox('slR' . $down . 'Short', $coachingGP2[0], $coachingGP2[1]); ?>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12">
                            <input type="text" class="js-range-slider" name="rng<?php echo $down; ?>Short" id="rng<?php echo $down; ?>Short"
                                   value=""/>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php
    elseif ($teamPart == 'Defense'):
        $coachingRun = $coachingController->getCoachingFromTeam($team, $gameplanNr, $teamPart, $down, 'Run');
        $coachingPass = $coachingController->getCoachingFromTeam($team, $gameplanNr, $teamPart, $down, 'Pass');
        ?>
        <div class="col-6">
            <div class="card text-center bg-dark text-white">
                <h5 class="card-header"><?php echo $down; ?> Runspielzug</h5>
                <div class="card-body">
                    <div class="row">
                        <div class="col-6">
                            <div id="gpL<?php echo $down; ?>Run" class="m-2">
                                <?php
                                $coachingGP1 = explode(';', $coachingRun->getGameplay1());
                                echo getSelectDefBox('slL' . $down . 'Run', $coachingGP1[0], $coachingGP1[1]);
                                ?>
                            </div>
                        </div>
                        <div class="col-6">
                            <div id="gpR<?php echo $down; ?>Run" class="m-2">
                                <?php
                                $coachingGP2 = explode(';', $coachingRun->getGameplay2());
                                echo getSelectDefBox('slR' . $down . 'Run', $coachingGP2[0], $coachingGP2[1]);
                                ?>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12">
                            <input type="text" class="js-range-slider" name="rng<?php echo $down; ?>Run" id="rng<?php echo $down; ?>Run" value=""/>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6">
            <div class="card text-center bg-dark text-white">
                <h5 class="card-header"><?php echo $down; ?> Passspielzug</h5>
                <div class="card-body">
                    <div class="row">
                        <div class="col-6">
                            <div id="gpL<?php echo $down; ?>Pass" class="m-2">
                                <?php
                                $coachingGP1 = explode(';', $coachingPass->getGameplay1());
                                echo getSelectDefBox('slL' . $down . 'Pass', $coachingGP1[0], $coachingGP1[1]);
                                ?>
                            </div>
                        </div>
                        <div class="col-6">
                            <div id="gpR<?php echo $down; ?>Pass" class="m-2">
                                <?php
                                $coachingGP2 = explode(';', $coachingPass->getGameplay2());
                                echo getSelectDefBox('slR' . $down . 'Pass', $coachingGP2[0], $coachingGP2[1]);
                                ?>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12">
                            <input type="text" class="js-range-slider" name="rng<?php echo $down; ?>Pass" id="rng<?php echo $down; ?>Pass" value=""/>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php
    elseif ($teamPart == 'General'):
        $general1 = $coachingController->getGeneralCoachingFromTeam($team, $gameplanNr, '1st');
        $general2 = $coachingController->getGeneralCoachingFromTeam($team, $gameplanNr, '2nd');
        ?>
        <div class="card text-center bg-dark text-white">
            <h5 class="card-header">Allgemein</h5>
            <div class="card-body">
                <div class="input-group mb-4">
                    <div class="input-group-prepend coachingInput">
                        <label class="input-group-text" for="inputFGRange">Field Goal Versuch bis</label>
                    </div>
                    <select class="custom-select" id="inputFGRange" onchange="saveGeneralCoaching()">
                        <?php
                        $fgRange = explode(';', $general1->getGameplay1());
                        for ($i = 1; $i < 65; $i++) {
                            if ($i == $fgRange[1]) {
                                echo '<option selected value="' . $i . '">' . $i . ' Yards</option>';
                            }
                            echo '<option value="' . $i . '">' . $i . ' Yards</option>';
                        }
                        ?>
                    </select>
                </div>
                <div class="input-group mb-4">
                    <div class="input-group-prepend coachingInput">
                        <label class="input-group-text" for="input2PtCon">2-Point Conversion</label>
                    </div>
                    <select class="custom-select" id="input2PtCon" onchange="saveGeneralCoaching()">
                        <?php $twoPtCon = explode(';', $general1->getGameplay2()); ?>
                        <option <?php echo $twoPtCon[1] == 0 ? 'selected' : '' ?> value="0">Nie</option>
                        <option <?php echo $twoPtCon[1] == 1 ? 'selected' : '' ?> value="1">Immer</option>
                        <option <?php echo $twoPtCon[1] == 2 ? 'selected' : '' ?> value="2">Ja, bei = 2 Punkte Abstand</option>
                        <option <?php echo $twoPtCon[1] == 5 ? 'selected' : '' ?> value="5">Ja, bei = 5 Punkte Abstand</option>
                        <option <?php echo $twoPtCon[1] == 8 ? 'selected' : '' ?> value="8">Ja, bei >= 8 Punkte Abstand</option>
                    </select>
                </div>
                <div class="input-group mb-4">
                    <div class="input-group-prepend coachingInput">
                        <label class="input-group-text" for="input4thDown">4th Down ausspielen</label>
                    </div>
                    <select class="custom-select" id="input4thDown" onchange="saveGeneralCoaching()">
                        <?php $fourthDown = explode(';', $general2->getGameplay1()); ?>
                        <option <?php echo $fourthDown[1] == 'Nie' ? 'selected' : '' ?> value="Nie">Nie</option>
                        <option <?php echo $fourthDown[1] == 'Immer' ? 'selected' : '' ?> value="Immer">Immer</option>
                        <option <?php echo $fourthDown[1] == 'GegnerischeHaelfte' ? 'selected' : '' ?> value="GegnerischeHaelfte">Immer in der
                            gegnerischen Hälfte
                        </option>
                        <option <?php echo $fourthDown[1] == 'GegnerischeHaelfteInRueckstand' ? 'selected' : '' ?>
                                value="GegnerischeHaelfteInRueckstand">Nur bei Rückstand in der gegnerischen Hälfte
                        </option>
                    </select>
                </div>
                <div class="input-group mb-3">
                    <div class="input-group-prepend coachingInput">
                        <label class="input-group-text" for="inputQBRun">Mit dem QB laufen</label>
                    </div>
                    <select class="custom-select" id="inputQBRun" onchange="saveGeneralCoaching()">
                        <?php $qbRun = explode(';', $general2->getGameplay2()); ?>
                        <option <?php echo $qbRun[1] == 1 ? 'selected' : '' ?> value="1">Ja</option>
                        <option <?php echo $qbRun[1] == 0 ? 'selected' : '' ?> value="0">Nein</option>
                    </select>
                </div>
            </div>
        </div>
    <?php
    endif;
endif;
?>
