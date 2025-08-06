<?php

include('lineupInfo.php');

if (isset($_SESSION['team'])) :
    $team = $_SESSION['team'];
    ?>
    <div class="panel panel-default opacity">
        <div class="row">
            <div class="col-1 col-sm-2 align-self-start">
                <button id="btnAutoLineup" type="button" class="btn btn-dark tooltip-custom-interactive"
                        data-tooltip-content="#autoLineupTooltip">Auto-Aufstellung
                </button>
                <div class="tooltip-content">
                        <span id="autoLineupTooltip">
                            Möchtest du dein Team automatisch aufstellen? Dabei gehen alle Einstellungen verloren.<br>
                            <button type="button" class="btn btn-outline-danger m-1"
                                    onclick="autoLineup()">Ja</button>
                            <button type="button" class="btn btn-secondary m-1" onclick="closeTooltip('#btnAutoLineup')">Abbrechen</button>
                        </span>
                </div>
            </div>
            <div class="col-2 col-sm-3 align-self-start">
                <div class="btn-group" role="group">
                    <button id="btnOffense" type="button" class="btn btn-dark active">Offensive</button>
                    <button id="btnDefense" type="button" class="btn btn-dark">Defensive</button>
                    <button id="btnSpecial" type="button" class="btn btn-dark">Special Teams</button>
                </div>
            </div>
            <div class="col-2 col-sm-2 align-self-start">
                <div id="switchOffense" class="btn-group" role="group">
                    <button id="btnTE" type="button" class="btn btn-dark <?php echo $team->getLineupOff() == 'TE' ? 'active' : ''; ?>"
                            onclick="changePosition('TE', this)">TE
                    </button>
                    <button id="btnFB" type="button" class="btn btn-dark <?php echo $team->getLineupOff() == 'FB' ? 'active' : ''; ?>"
                            onclick="changePosition('FB', this)">FB
                    </button>
                </div>
                <div id="switchDefense" class="btn-group d-none" role="group">
                    <button id="btnNT" type="button" class="btn btn-dark <?php echo $team->getLineupDef() == 'NT' ? 'active' : ''; ?>"
                            onclick="changePosition('NT', this)">NT
                    </button>
                    <button id="btnMLB" type="button" class="btn btn-dark <?php echo $team->getLineupDef() == 'MLB' ? 'active' : ''; ?>"
                            onclick="changePosition('MLB', this)">MLB
                    </button>
                </div>
            </div>
        </div>
        <div id="rowOffense" class="row">
            <?php
            $teamPart = 'offense';
            include('lineupRow.php');
            ?>
        </div>
        <div id="rowDefense" class="row d-none">
            <?php
            $teamPart = 'defense';
            include('lineupRow.php');
            ?>
        </div>
        <div id="rowSpecial" class="row d-none">
            <?php
            $teamPart = 'special';
            include('lineupRow.php');
            ?>
        </div>
    </div>
    <script src="/scripts/util/tooltipCustom.js"></script>
    <script src="/scripts/team/lineup.js"></script>
<?php endif; ?>



