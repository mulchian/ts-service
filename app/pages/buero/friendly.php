<?php

use touchdownstars\league\LeagueController;
use touchdownstars\team\TeamController;
use touchdownstars\user\UserController;

if ($_GET['opponent']) {
    $opponentName = $_GET['opponent'];
}

if (isset($pdo, $log)) :
    $teamController = new TeamController($pdo, $log);
    $userController = new UserController($pdo, $log);
    $leagueController = new LeagueController($pdo, $log);

    if (!empty($_SESSION['team'])) :
        $selfTeam = $_SESSION['team'];

        if (isset($opponentName) && $selfTeam->getName() !== $opponentName) {
            $opponentTeam = $teamController->fetchTeam(null, $opponentName);
            $opponentUser = $userController->fetchUserByTeam($opponentTeam);
        }

        $allTeams = $teamController->fetchAllTeams(null, 2);
        $allFriendlies = $leagueController->getAllFriendlies($selfTeam);
        ?>
        <div class="panel panel-default opacity">
            <div class="row justify-content-center">
                <div class="col-4">
                    <div class="card bg-dark text-white mb-3">
                        <div class="card-header">
                            Freundschaftsspiel
                        </div>
                        <div class="card-body">
                            <label for="opponent">Gegner</label>
                            <div class="input-group justify-content-center mb-3">
                                <select id="opponent" class="selectpicker" data-live-search="true">
                                    <?php if (isset($opponentTeam, $opponentUser)): ?>
                                        <option
                                                data-tokens="<?php echo $opponentUser->getUsername(); ?>"><?php echo $opponentTeam->getName(); ?></option>
                                    <?php endif;
                                    foreach ($allTeams as $team): ?>
                                        <?php if ((!isset($opponentTeam) || $team->getName() != $opponentTeam->getName()) && $team->getName() != $selfTeam->getName()): ?>
                                            <option
                                                    data-tokens="<?php echo $team->getUser()->getUsername(); ?>"><?php echo $team->getName(); ?></option>
                                        <?php endif;
                                    endforeach; ?>
                                </select>
                            </div>

                            <label for="cbIsHome">Heim- oder Auswärtsspiel</label>
                            <div class="input-group justify-content-center mb-3">
                                <input id="cbIsHome"
                                       type="checkbox" checked data-toggle="toggle" data-on="Heimspiel"
                                       data-off="Auswärtsspiel"
                                       data-onstyle="secondary">
                            </div>

                            <label for="datetime">Spielzeit</label>
                            <div class="input-group mb-3"
                                 id="dtpicker"
                                 data-td-target-input="nearest"
                                 data-td-target-toggle="nearest">
                                <input id="datetime"
                                       type="text"
                                       class="form-control"
                                       data-td-target="#dtpicker"
                                />
                                <span
                                        class="input-group-text"
                                        data-td-target="#datetimepicker1"
                                        data-td-toggle="datetimepicker"
                                >
                                    <span class="fas fa-calendar"></span>
                                </span>
                            </div>
                            <div id="msgError" class="mb-3 alert alert-danger d-none">
                            </div>
                        </div>
                        <div class="card-footer">
                            <button type="button" class="btn btn-secondary" onclick="inviteForFriendly()">Einladung
                                senden
                            </button>
                        </div>
                    </div>
                    <div>
                        <div class="card bg-dark text-white mb-3">
                            <div class="card-header">
                                Ergebnisse der Freundschaftsspiele
                            </div>
                            <div class="card-body">
                                <button type="button" class="btn btn-secondary" onclick="showFriendlyResults()">Zu den
                                    Ergebnissen
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="card bg-dark text-white">
                        <div class="card-header">
                            anstehende Freundschaftsspiele
                        </div>
                        <div class="card-body">
                            <?php if (empty($allFriendlies)): ?>
                                <p class="card-text">Es sind keine Freundschaftsspiele geplant.</p>
                            <?php else: ?>
                                <table id="tblFriendlies" class="table table-dark" data-toggle="table"
                                       data-sortable="true" data-sort-name="gameTime" data-sort-order="asc"
                                       data-unique-id="id"
                                       data-sticky-header="true" data-sticky-header-offset-y="1"
                                       data-sticky-header-offset-right="15"
                                       data-sticky-header-offset-left="15">
                                    <thead class="thead-dark">
                                    <tr>
                                        <th data-field="id" scope="col" data-width="10" data-width-unit="%"
                                            data-visible="false">ID
                                        </th>
                                        <th data-field="gameTime" scope="col" data-width="20" data-width-unit="%"
                                            data-sortable="true"
                                            data-sorter="gameTimeSorter">ANPFIFF
                                        </th>
                                        <th data-field="home" scope="col" data-width="25" data-width-unit="%"
                                            data-sortable="true">HEIM
                                        </th>
                                        <th data-field="away" scope="col" data-width="25" data-width-unit="%"
                                            data-sortable="true">GAST
                                        </th>
                                        <th data-field="accepted" scope="col" data-width="10" data-width-unit="%"
                                            data-sortable="true">ZUSAGEN
                                        </th>
                                        <th data-field="action" scope="col" data-width="10" data-width-unit="%"
                                            data-sortable="true">AKTION
                                        </th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach ($allFriendlies as $friendly): ?>
                                        <tr>
                                            <td><?php echo $friendly['id']; ?></td>
                                            <td><?php echo (new DateTime($friendly['gameTime']))->format('d.m.Y H:i'); ?></td>
                                            <td><?php echo $friendly['home']; ?></td>
                                            <td><?php echo $friendly['away']; ?></td>
                                            <td><?php
                                                $accepted = 0;
                                                if ($friendly['homeAccepted']) {
                                                    $accepted++;
                                                }
                                                if ($friendly['awayAccepted']) {
                                                    $accepted++;
                                                }
                                                echo $accepted . '/2';
                                                ?></td>
                                            <td>
                                                <?php
                                                $gameTime = (new DateTime($friendly['gameTime']))->modify('-1 hour');
                                                $nowInOneHour = (new DateTime('now'))->modify('+1 hour');
                                                $nowInOneHour = $nowInOneHour->setTime($nowInOneHour->format('H'), 0);
                                                $log->debug('Game time: ' . $gameTime->format('Y-m-d H:i:s'));
                                                $log->debug('Now in one hour: ' . $nowInOneHour->format('Y-m-d H:i:s'));
                                                if ((($friendly['home'] == $selfTeam->getName() && $friendly['homeAccepted']) ||
                                                        ($friendly['away'] == $selfTeam->getName() && $friendly['awayAccepted'])) &&
                                                    ($gameTime > $nowInOneHour)): ?>
                                                    <button type="button"
                                                            class="btn btn-outline-danger tooltip-custom-interactive"
                                                            id="btnDeclineFriendly<?php echo $friendly['id']; ?>"
                                                            data-tooltip-content="#decline_tooltip_<?php echo $friendly['id']; ?>">
                                                        ABSAGEN
                                                    </button>
                                                <?php elseif ($leagueController->isLive($selfTeam)): ?>
                                                    <button type="button" class="btn btn-secondary"
                                                            onclick="goToLive()">LIVE
                                                    </button>
                                                <?php else: ?>
                                                    <div class="btn-group-vertical" role="group">
                                                        <button type="button" class="btn btn-secondary"
                                                                onclick="acceptFriendly(<?php echo $friendly['id']; ?>)">
                                                            ANNEHMEN
                                                        </button>
                                                        <button type="button"
                                                                class="btn btn-outline-danger tooltip-custom-interactive"
                                                                id="btnDeclineFriendly<?php echo $friendly['id']; ?>"
                                                                data-tooltip-content="#decline_tooltip_<?php echo $friendly['id']; ?>">
                                                            ABLEHNEN
                                                        </button>
                                                    </div>
                                                <?php endif; ?>
                                                <div id="declineTooltipContent<?php echo $friendly['id']; ?>"
                                                     class="tooltip-content">
                                                    <span id="decline_tooltip_<?php echo $friendly['id']; ?>">
                                                        Möchtest du das Spiel gegen <?php echo($friendly['home'] == $selfTeam->getName() ? $friendly['away'] : $friendly['home']); ?> wirklich absagen?<br>
                                                        <button type="button" class="btn btn-outline-danger m-1"
                                                                onclick="declineFriendly(<?php echo $friendly['id']; ?>)"
                                                        >Ja</button>
                                                        <button type="button" class="btn btn-secondary m-1"
                                                                onclick="closeTooltip('#btnDeclineFriendly<?php echo $friendly['id']; ?>')">
                                                            Abbrechen
                                                        </button>
                                                    </span>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row justify-content-center">
                <div class="col">

                </div>
            </div>
        </div>

        <script src="/scripts/util/tooltipCustom.js"></script>
        <script src="/scripts/buero/friendly.js"></script>
    <?php
    endif;
endif;
?>