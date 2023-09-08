<?php

use touchdownstars\player\PlayerController;

$table = 'overview';
if (isset($pdo, $log)) {
    $playerController = new PlayerController($pdo, $log);
}

if (isset($_GET['table'])) {
    $table = $_GET['table'];
}

if (isset($_SESSION['team'])) {
    $team = $_SESSION['team'];
}

?>

<div class="panel panel-default opacity">
    <div class="row my-3">
        <div class="col-sm">
            <table id="tblRoster" class="table table-dark" data-toggle="table"
                   data-sortable="true" data-sort-name="pos" data-sort-order="asc" data-unique-id="id"
                   data-sticky-header="true" data-sticky-header-offset-y="1" data-sticky-header-offset-right="15"
                   data-sticky-header-offset-left="15"
                   data-sort-priority='[{"sortName": "pos","sortOrder":"asc"}, {"sortName":"ovr","sortOrder":"desc"},
                   {"sortName":"age","sortOrder":"asc"}, {"sortName":"talent","sortOrder":"desc"}]'>
                <thead class="thead-dark">
                <tr>
                    <th data-field="id" scope="col" data-width="5" data-width-unit="%" data-visible="false">ID</th>
                    <th data-field="pos" scope="col" data-width="5" data-width-unit="%" data-sortable="true"
                        data-sorter="positionSorter">POS</th>
                    <th data-field="ovr" scope="col" data-width="5" data-width-unit="%" data-sortable="true">OVR</th>
                    <th data-field="age" scope="col" data-width="5" data-width-unit="%" data-sortable="true">ALTER</th>
                    <th data-field="player" scope="col" data-width="25" data-width-unit="%" data-sortable="true">SPIELER</th>
                    <th data-field="talent" scope="col" data-width="10" data-width-unit="%" data-sortable="true">TALENT</th>
                    <?php if ($table === 'overview') : ?>
                        <th scope="col" data-width="10" data-width-unit="%" data-sortable="true">ENERGIE</th>
                        <th scope="col" data-width="10" data-width-unit="%" data-sortable="true">SP</th>
                        <th scope="col" data-width="10" data-width-unit="%" data-sortable="true">STATUS</th>
                    <?php else : ?>
                        <th scope="col" data-width="8" data-width-unit="%" data-sortable="true">MORAL</th>
                        <th scope="col" data-width="8" data-width-unit="%" data-sortable="true">GEHALT</th>
                        <th scope="col" data-width="7" data-width-unit="%" data-sortable="true">VERTRAG</th>
                        <th scope="col" data-width="7" data-width-unit="%">AKTION</th>
                    <?php endif; ?>
                </tr>
                </thead>
                <tbody>
                <?php if (!empty($team)) :
                    foreach ($team->getPlayers() as $player):?>
                        <tr>
                            <td <?php echo getPlayerModalDataset($player); ?>><?php echo $player->getId(); ?></td>
                            <td <?php echo getPlayerModalDataset($player); ?>><?php echo $player->getType()->getPosition()->getPosition(); ?></td>
                            <td <?php echo getPlayerModalDataset($player); ?>><?php echo $player->getOVR(); ?></td>
                            <td <?php echo getPlayerModalDataset($player); ?>><?php echo $player->getAge(); ?></td>
                            <td <?php echo getPlayerModalDataset($player); ?>><?php echo $player->getFirstName() . ' ' . $player->getLastName(); ?></td>
                            <td <?php echo getPlayerModalDataset($player); ?>>
                                <?php for ($i = 0; $i < floor($player->getTalent() / 2); $i++) : ?>
                                    <i class="fas fa-star"></i>
                                <?php endfor;
                                if (($player->getTalent() % 2) != 0) :?>
                                    <i class="far fa-star"></i>
                                <?php endif; ?>
                            </td>
                            <?php if ($table === 'overview') : ?>
                                <td <?php echo getPlayerModalDataset($player); ?>><?php echo ($player->getEnergy() * 100) . ' %'; ?></td>
                                <td>
                                    <div class="progress">
                                        <?php $skillWidth = number_format($player->getSkillpoints() - floor($player->getSkillpoints()), 2) * 100; ?>
                                        <div class="progress-bar" role="progressbar" style="width: <?php echo $skillWidth . '%'; ?>; color: #000000"
                                             aria-valuenow="<?php echo $skillWidth; ?>" aria-valuemin="0" aria-valuemax="100">
                                            <small class="progress-bar-title"><?php echo floor($player->getSkillpoints()) . ' SP'; ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo 'Aktiv'; ?></td>
                            <?php else : ?>
                                <td <?php echo getPlayerModalDataset($player); ?>><?php echo ($player->getMoral() * 100) . ' %'; ?></td>
                                <td <?php echo getPlayerModalDataset($player); ?>><?php echo getFormattedCurrency($player->getContract()->getSalary()); ?></td>
                                <td <?php if ($player->getContract()->getEndOfContract() > 2) { echo getPlayerModalDataset($player); } ?>>
                                    <?php
                                    $endOfContract = $player->getContract()->getEndOfContract();
                                    echo $endOfContract . ' Saisons';
                                    if ($endOfContract <= 2) : ?>
                                        <br>
                                        <button id="btnNewContract" type="button" class="btn btn-secondary btn-sm"
                                                onclick="showContract(this)" data-id_player='<?php echo $player->getId(); ?>'
                                                data-ovr='<?php echo $player->getOVR(); ?>'
                                        >
                                            NEUER VERTRAG
                                        </button>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-outline-danger tooltip-custom-interactive"
                                            id="btnReleasePlayer<?php echo $player->getId(); ?>"
                                            data-tooltip-content="#release_tooltip_player_<?php echo $player->getId(); ?>">
                                        ENTLASSEN
                                    </button>
                                    <div class="tooltip-content">
                                        <span id="release_tooltip_player_<?php echo $player->getId(); ?>">
                                            Möchtest du deinen <?php echo $player->getType()->getPosition()->getDescription() . ' roster.php' .
                                                $player->getFirstName() . ' ' . $player->getLastName() ?> wirklich entlassen?<br>
                                            <button type="button" class="btn btn-outline-danger m-1"
                                                    onclick="releasePlayer(this)"
                                                    data-id_player="<?php echo $player->getId(); ?>"
                                                    data-position="<?php echo $player->getType()->getPosition()->getPosition(); ?>"
                                            >Ja</button>
                                            <button type="button" class="btn btn-secondary m-1" onclick="closeTooltip('#btnReleasePlayer<?php echo $player->getId(); ?>')"
                                                    data-id_player="<?php echo $player->getId(); ?>">Abbrechen</button>
                                        </span>
                                    </div>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="/scripts/util/tooltipCustom.js"></script>
<script src="/scripts/team/roster.js"></script>