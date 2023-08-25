<?php


?>

<div class="modal fade" id="lineupModal" tabindex="-1" role="dialog" aria-labelledby="lineupModalHead" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 id="lineupModalHead" class="modal-title">Aufstellung</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col">
                        <h5>Spieler</h5>
                        <div id="listPlayer" class="dropzone">
                        </div>
                    </div>
                    <div id="lineupOffense" class="col">
                        <h5>Starter</h5>
                        <div id="starterDZ" class="dropzone">
                        </div>
                        <h5>Back Ups</h5>
                        <div id="backupDZ" class="dropzone">
                        </div>
                    </div>
                </div>
                <div class="row justify-content-end">
                    <div class="col-2 col-sm-3 align-self-end">
                        <button id="btnSaveLineup" type="button" class="btn btn-primary">Speichern</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>