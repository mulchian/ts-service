<?php

use touchdownstars\player\Player;

function getPlayerModalDataset(Player $player): string
{
    return 'data-toggle="modal" data-target="#playerModal" data-id_player=\'' . $player->getId() . '\' data-ovr=\'' . $player->getOVR() . '\'';
}

?>

<div class="modal fade" id="playerModal" tabindex="-1" role="dialog" aria-labelledby="playerModalHead" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 id="playerModelHead" class="modal-title">Spielerübersicht</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <!-- Zuerst die Stammdaten des Spielers in den Header, darunter die Tabs. -->
                <div class="row">
                    <div class="col-sm-1">
                        <img src="/resources/pick_six_profile.png" alt="Pick Six Profile" width="48" height="48">
                    </div>
                    <div class="col-sm-3">
                        <h6 id="lblName">Spieler</h6>
                        <label id="lblTalent">Talent</label>
                    </div>
                    <div class="col-sm-2">
                        <h6 id="lblPosition">Position</h6>
                        <label id="lblType">Typ</label>
                    </div>
                    <div class="col-sm-1">
                        <img src="/resources/pick_six_profile.png" alt="Pick Six Profile" width="48" height="48">
                    </div>
                    <div class="col-sm-2">
                        <div class="row">
                            <div class="col text-warning font-weight-bold" id="lblOvr">0</div>
                            <div class="col text-warning font-weight-bold" id="lblAge">0</div>
                        </div>
                        <div class="row mb-2">
                            <div class="col"><small>OVR</small></div>
                            <div class="col"><small>ALTER</small></div>
                        </div>
                    </div>
                    <div class="col-sm-3">
                        <div class="row">
                            <div class="col text-warning font-weight-bold" id="lblEnergy">100 %</div>
                            <div class="col text-warning font-weight-bold" id="lblMoral">100 %</div>
                        </div>
                        <div class="row mb-2">
                            <div class="col"><small>ENERGIE</small></div>
                            <div class="col"><small>MORAL</small></div>
                        </div>
                    </div>
                </div>
                <!-- Tab-Navigation mit Spieler-Daten -->
                <nav>
                    <div class="nav nav-tabs" id="nav-tab-player" role="tablist">
                        <a class="nav-item nav-link active" id="nav-general-tab" data-toggle="tab" href="#nav-general" role="tab"
                           aria-controls="nav-general" aria-selected="true">Allgemein</a>
                        <a class="nav-item nav-link" id="nav-statistics-tab" data-toggle="tab" href="#nav-statistics" role="tab"
                           aria-controls="nav-statistics" aria-selected="false">Statistiken</a>
                        <a class="nav-item nav-link" id="nav-skills-tab" data-toggle="tab" href="#nav-skills" role="tab" aria-controls="nav-skills"
                           aria-selected="false">Skills</a>
                        <a class="nav-item nav-link" id="nav-contract-tab" data-toggle="tab" href="#nav-contract" role="tab"
                           aria-controls="nav-contract" aria-selected="false">Vertrag</a>
                    </div>
                </nav>
                <div class="tab-content" id="nav-tabContent">
                    <div class="tab-pane fade show active" id="nav-general" role="tabpanel" aria-labelledby="nav-general-tab">
                        <ul class="list-group list-group-flush my-3">
                            <li class="list-group-item">
                                <div class="clearfix">
                                    <label class="h6 float-left font-weight-bold">Team</label>
                                    <label id="lblTeamname" class="float-right"></label>
                                </div>
                            </li>
                            <li class="list-group-item">
                                <div class="clearfix">
                                    <label class="h6 float-left font-weight-bold">Status</label>
                                    <label id="lblStatus" class="float-right"></label>
                                </div>
                            </li>
                            <li class="list-group-item">
                                <div class="clearfix">
                                    <p class="h6 float-left font-weight-bold">Draft</p>
                                    <label id="lblDraft" class="float-right text-right"></label>
                                </div>
                            </li>
                            <li class="list-group-item">
                                <div class="clearfix">
                                    <label class="h6 float-left font-weight-bold">Nationalität</label>
                                    <label id="lblNationality" class="float-right"></label>
                                </div>
                            </li>
                            <li class="list-group-item">
                                <div class="clearfix">
                                    <label class="h6 float-left font-weight-bold">Größe</label>
                                    <label id="lblHeight" class="float-right"></label>
                                </div>
                            </li>
                            <li class="list-group-item">
                                <div class="clearfix">
                                    <label class="h6 float-left font-weight-bold">Gewicht</label>
                                    <label id="lblWeight" class="float-right"></label>
                                </div>
                            </li>
                            <li class="list-group-item">
                                <div class="clearfix">
                                    <label class="h6 float-left font-weight-bold">Marktwert</label>
                                    <label id="lblMarketValue" class="float-right"></label>
                                </div>
                            </li>
                            <li class="list-group-item">
                                <div class="clearfix">
                                    <label class="h6 float-left font-weight-bold">Erfahrung</label>
                                    <label id="lblExperience" class="float-right"></label>
                                </div>
                            </li>
                            <li class="list-group-item">
                                <div class="clearfix">
                                    <label class="h6 float-left font-weight-bold">Charakter</label>
                                    <label id="lblCharacter" class="float-right"></label>
                                </div>
                            </li>
                        </ul>
                    </div>
                    <div class="tab-pane fade" id="nav-statistics" role="tabpanel" aria-labelledby="nav-statistics-tab">
                        <div id="rowStatistics" class="row mt-3 justify-content-center">
                        </div>
                        <div id="rowStatisticsError" class="row mt-3 justify-content-center d-none">
                            <div class="col-8">
                                <label id="statisticsError" class="text-danger">Es wurden keine Statistiken zu dem Spieler gefunden.</label>
                            </div>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="nav-skills" role="tabpanel" aria-labelledby="nav-skills-tab">
                        <div id="skillCardGroup" class="card-columns my-3">
                        </div>
                    </div>
                    <div class="tab-pane fade" id="nav-contract" role="tabpanel" aria-labelledby="nav-contract-tab">
                        <div id="rowContract" class="row mt-3 justify-content-center">
                            <div class="col-8">
                                <div class="form-group m-2 pb-2">
                                    <label for="salaryRange">Gehalt</label>
                                    <input type="text" class="js-range-slider" name="salaryRange" id="salaryRange" value=""/>
                                </div>
                                <div class="row">
                                    <div class="col text-warning font-weight-bold" id="lblSigningBonus">0</div>
                                    <div class="col text-warning font-weight-bold" id="lblContractMoral">0</div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col"><small>SIGNING BONUS</small></div>
                                    <div class="col"><small>MORAL</small></div>
                                </div>
                                <div class="row">
                                    <div class="col text-warning font-weight-bold" id="lblCompleteOffer">0</div>
                                    <div class="col text-warning font-weight-bold" id="lblNewSalaryCap">0</div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col"><small>GESAMTANGEBOT</small></div>
                                    <div class="col"><small>NEUES SALARY CAP</small></div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col">
                                        <div class="input-group mb-2">
                                            <div class="input-group-prepend">
                                                <label class="input-group-text" style="background: #6c757d !important;"
                                                       for="slctTimeOfContract">Laufzeit:</label>
                                            </div>
                                            <select class="custom-select" id="slctTimeOfContract">
                                                <option value="1">1 Saison</option>
                                                <option value="2">2 Saisons</option>
                                                <option value="3" selected>3 Saisons</option>
                                                <option value="4">4 Saisons</option>
                                                <option value="5">5 Saisons</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col">
                                        <button id="btnExtendContract" type="button" class="btn btn-primary">Verlängern</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div id="rowContractError" class="row mt-3 justify-content-center d-none">
                            <div class="col-8">
                                <label id="contractError" class="text-danger">Es wurden keine Vertragsdaten zu dem Spieler gefunden.</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="/scripts/contract/contract.js"></script>
<script src="/scripts/player/player.js"></script>