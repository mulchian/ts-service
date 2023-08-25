<?php

use touchdownstars\coaching\Coachingname;
use touchdownstars\team\TeamController;

if (isset($pdo, $log)) :
    $teamController = new TeamController($pdo, $log);

    if (isset($_SESSION['team'])) :
        $team = $_SESSION['team'];
        $gameplanOff = $team->getGameplanOff();
        $gameplanDef = $team->getGameplanDef();

        $coachingNames = $team->getCoachingnames();
        ?>
        <div class="panel panel-default opacity">
            <div class="row mb-4">
                <div class="col-2 col-sm-3 align-self-start">
                    <div id="btnGrpTeamPart" class="btn-group" role="group">
                        <button id="btnOffense" type="button" class="btn btn-dark active" value="Offense" onclick="changeTeamPart('Offense', this)">
                            Offensive
                        </button>
                        <button id="btnDefense" type="button" class="btn btn-dark" value="Defense" onclick="changeTeamPart('Defense', this)">
                            Defensive
                        </button>
                    </div>
                </div>
                <div id="colGameplanOff" class="col-2 col-sm-3 align-self-start">
                    <div class="input-group mb-3">
                        <div class="input-group-prepend">
                            <label class="input-group-text" for="slGameplanOff">Offense Gameplan</label>
                        </div>
                        <select id="slGameplanOff" class="custom-select" onchange="saveGameplan(this)">
                            <?php showGameplanSelectOptions($coachingNames, 'Offense', $gameplanOff); ?>
                        </select>
                        <div class="input-group-append">
                            <button id="btnChangeNameGPOff" type="button" class="btn btn-dark tooltip-gameplan">
                                <i class="fas fa-pen"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div id="colGameplanDef" class="col-2 col-sm-3 align-self-start d-none">
                    <div class="input-group mb-3">
                        <div class="input-group-prepend">
                            <label class="input-group-text" for="slGameplanDef">Defense Gameplan</label>
                        </div>
                        <select id="slGameplanDef" class="custom-select" onchange="saveGameplan(this)">
                            <?php showGameplanSelectOptions($coachingNames, 'Defense', $gameplanDef); ?>
                        </select>
                        <div class="input-group-append">
                            <button id="btnChangeNameGPDef" type="button" class="btn btn-dark tooltip-gameplan">
                                <i class="fas fa-pen"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div id="rowOffense" class="row mb-4">
                <div id="colGeneral" class="col-3">
                    <?php
                    $gameplanNr = $gameplanOff;
                    $teamPart = 'General';
                    $down = '1st';
                    include('coachingRow.php'); ?>
                </div>
                <div class="col-9">
                    <?php
                    $teamPart = 'Offense';
                    $down = '1st'; ?>
                    <div id="row<?php echo $down; ?>Off" class="row mb-4">
                        <?php include('coachingRow.php'); ?>
                    </div>
                    <?php $down = '2nd'; ?>
                    <div id="row<?php echo $down; ?>Off" class="row mb-4">
                        <?php include('coachingRow.php'); ?>
                    </div>
                    <?php $down = '3rd'; ?>
                    <div id="row<?php echo $down; ?>Off" class="row mb-4">
                        <?php include('coachingRow.php'); ?>
                    </div>
                    <?php $down = '4th'; ?>
                    <div id="row<?php echo $down; ?>Off" class="row mb-4">
                        <?php include('coachingRow.php'); ?>
                    </div>
                </div>
            </div>
            <div id="rowDefense" class="row mb-4 d-none">
                <div class="col-6">
                    <?php
                    $gameplanNr = $gameplanDef;
                    $teamPart = 'Defense';
                    $down = '1st'; ?>
                    <div id="row<?php echo $down; ?>Def" class="row mb-4 justify-content-center">
                        <?php include('coachingRow.php'); ?>
                    </div>
                    <?php $down = '3rd'; ?>
                    <div id="row<?php echo $down; ?>Def" class="row mb-4 justify-content-center">
                        <?php include('coachingRow.php'); ?>
                    </div>
                </div>
                <div class="col-6">
                    <?php $down = '2nd'; ?>
                    <div id="row<?php echo $down; ?>Def" class="row mb-4 justify-content-center">
                        <?php include('coachingRow.php'); ?>
                    </div>
                    <?php $down = '4th'; ?>
                    <div id="row<?php echo $down; ?>Def" class="row mb-4 justify-content-center">
                        <?php include('coachingRow.php'); ?>
                    </div>
                </div>
            </div>
        </div>
        <script src="/app/scriptsipts/team/coaching.js"></script>
        <script src="/app/scriptsipts/util/tooltipCustom.js"></script>
    <?php
    endif;
endif;
?>

<?php
function showGameplanSelectOptions(?array $coachingNames, string $teamPart, int $mainGameplanNr) {
    for ($i = 1; $i <= 5; $i++) {
        if (isset($coachingNames)) {
            $coachingname = array_filter($coachingNames, function (Coachingname $name) use ($i, $teamPart) {
                return $name->getGameplanNr() == $i && $name->getTeamPart() == $teamPart;
            })[0];
        } else {
            $coachingname = null;
        }
        echo '<option ' . ($mainGameplanNr == $i ? 'selected' : '') . ' value="' . $i . '">';
        echo (isset($coachingname) && $coachingname->getGameplanNr() == $i ? $coachingname->getGameplanName() : ('Gameplan ' . $i));
        echo '</option>';
    }
}
?>
