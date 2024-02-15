<?php

use touchdownstars\employee\Employee;
use touchdownstars\employee\EmployeeController;
use touchdownstars\employee\job\JobController;
use touchdownstars\stadium\Building;
use touchdownstars\stadium\StadiumController;
use touchdownstars\team\TeamController;


if (isset($pdo, $log)) :
    $stadiumController = new StadiumController($pdo);
    $teamController = new TeamController($pdo, $log);
    $employeeController = new EmployeeController($pdo, $log);
    $jobController = new JobController($pdo);

    if (!empty($_SESSION['team'])) :
        $team = $_SESSION['team'];

        $bueroGebaeude = array_values(array_filter($team->getStadium()->getBuildings(), function (Building $building) {
            return $building->getName() == 'Bürogebäude';
        }))[0];
        if (!isset($bueroGebaeude)) {
            $bueroGebaeude = $stadiumController->getBuildingWithName($team->getStadium(), 'Bürogebäude');
        }

        if (count($team->getEmployees()) == $employeeController->countTeamEmployees($team)) {
            $employees = $team->getEmployees();
        } else {
            $employees = $employeeController->fetchEmployeesOfTeam($team);
        }


        ?>

        <div class="panel panel-default opacity">
            <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING']); ?>"
                  method="post">
                <?php if (isset($team) && !empty($team)): ?>
                    <div class="card-columns my-3" id="personalCards">
                        <?php if (isset($bueroGebaeude) && !empty($bueroGebaeude)): ?>
                            <div class="card text-center bg-dark text-white">
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo $bueroGebaeude->getName(); ?></h5>
                                    <div class="card-text">
                                        <div class="row">
                                            <div class="col">Stufe:</div>
                                            <div class="col">
                                                <?php echo $bueroGebaeude->getLevel() . ' / ' . $bueroGebaeude->getMaxLevel(); ?>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col">Personal:</div>
                                            <div class="col"
                                                 id="lblPersonalAnzahl"><?php echo count($employees) . ' / ' . $bueroGebaeude->getLevel() . ' Mitarbeiter'; ?></div>
                                        </div>
                                        <div class="row">
                                            <div class="col">Gehalt:</div>
                                            <div class="col"
                                                 id="lblEmployeeSalary"><?php echo getFormattedCurrency($employeeController->calcEmployeeSalary($team)); ?></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                        <?php foreach ($jobController->fetchJobs() as $job): ?>
                            <div class="card text-center bg-dark text-white">
                                <div class="card-header"><?php echo $job->getName(); ?></div>
                                <div class="card-body">
                                    <?php if ($teamController->hasEmployee($team, $job->getName())):
                                        $employee = array_values(array_filter($employees, function (Employee $value) use ($job) {
                                            return $value->getJob()->getName() == $job->getName();
                                        }))[0];
                                        ?>
                                        <h6 class="card-title"><?php echo $employee->getFirstName() . ' ' . $employee->getLastName(); ?></h6>
                                        <div class="card-text">
                                            <div class="row">
                                                <div class="col text-warning"><?php echo $employee->getOvr(); ?></div>
                                                <div class="col text-warning"><?php echo $employee->getAge(); ?></div>
                                            </div>
                                            <div class="row">
                                                <div class="col"><small>OVR</small></div>
                                                <div class="col"><small>ALTER</small></div>
                                            </div>
                                            <div class="row">
                                                <div class="col text-warning"><?php echo getFormattedCurrency($employee->getContract()->getSalary()); ?></div>
                                                <div class="col text-warning"><?php echo $employee->getContract()->getEndOfContract(); ?></div>
                                            </div>
                                            <div class="row">
                                                <div class="col"><small>GEHALT</small></div>
                                                <div class="col"><small>VERTRAG</small></div>
                                            </div>
                                        </div>
                                        <button type="button"
                                                class="btn btn-secondary m-2 <?php echo $employee->getContract()->getEndOfContract() > 2 ? 'disabled' : '' ?>"
                                                id="btn<?php echo str_replace(' ', '', $job->getName()); ?>Verlaengern"
                                            <?php echo $employee->getContract()->getEndOfContract() > 2 ? '' : 'onclick="extendContract(this)"'; ?>
                                            <?php echo $employee->getContract()->getEndOfContract() > 2 ? '' : 'data-employee=\'' . $employee->getJson() . '\''; ?>>
                                            NEUER VERTRAG
                                        </button>
                                        <button type="button" class="btn btn-outline-danger m-2 tooltip-custom"
                                                id="btn<?php echo str_replace(' ', '', $job->getName()); ?>Entlassen"
                                                data-tooltip-content="<?php echo '#release_tooltip_' . str_replace(' ', '', $job->getName()); ?>">
                                            ENTLASSEN
                                        </button>
                                        <div class="tooltip-content">
                                    <span id="<?php echo 'release_tooltip_' . str_replace(' ', '', $job->getName()); ?>">
                                        Möchtest du deinen <?php echo $employee->getJob()->getName() . ' ' . $employee->getFirstName() . ' ' . $employee->getLastName() ?>
                                        wirklich entlassen?<br>
                                        <button type="button" class="btn btn-outline-danger m-1"
                                                onclick="releaseEmployee(this)"
                                                data-jobname="<?php echo $job->getName(); ?>"
                                                data-id_employee="<?php echo $employee->getId(); ?>"
                                                data-count_employees="<?php echo count($employees); ?>"
                                                data-building_level="<?php echo $bueroGebaeude->getLevel(); ?>"
                                                data-salary="<?php echo $employee->getContract()->getSalary(); ?>"
                                        >Ja</button>
                                        <button type="button" class="btn btn-secondary m-1" onclick="closeTooltip()">Abbrechen</button>
                                    </span>
                                        </div>
                                    <?php else: ?>
                                        <p class="card-text">
                                            <button type="button" class="btn btn-secondary"
                                                    id="btn<?php echo str_replace(' ', '', $job->getName()); ?>Einstellen"
                                                    data-toggle="modal" data-target="#personalModal"
                                                    data-jobname="<?php echo $job->getName(); ?>"
                                                <?php if ($bueroGebaeude->getLevel() <= count($employees)) {
                                                    echo 'disabled';
                                                } ?>
                                            >
                                                EINSTELLEN
                                            </button>
                                        </p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </form>
        </div>

        <div class="modal fade" id="personalModal" tabindex="-1" role="dialog" aria-labelledby="personalModalHead"
             aria-hidden="true">
            <div class="modal-dialog modal-xl" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 id="personalModalHead" class="modal-title tooltip-personalModal">Personal einstellen!</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div id="modalLoadingSpinner">
                            <div class="spinner-grow" role="status">
                                <span class="sr-only">Loading...</span>
                            </div>
                        </div>
                        <table id="tblNewPersonal" data-toggle="tblNewPersonal" data-sortable="true"
                               data-sort-order="asc" data-single-select="true" data-click-to-select="true"
                               data-sort-name="id">
                            <thead>
                            <tr>
                                <th data-field="id" scope="col" data-sortable="true">ID</th>
                                <th data-field="state" scope="col" data-width="5" data-width-unit="%"
                                    data-sortable="false"
                                    data-checkbox="true"></th>
                                <th data-field="jobName" scope="col" data-width="10" data-width-unit="%"
                                    data-sortable="false">
                                    BERUF
                                </th>
                                <th data-field="name" scope="col" data-width="15" data-width-unit="%"
                                    data-sortable="true">
                                    NAME
                                </th>
                                <th data-field="age" scope="col" data-width="5" data-width-unit="%"
                                    data-sortable="true">ALTER
                                </th>
                                <th data-field="nationality" scope="col" data-width="10" data-width-unit="%"
                                    data-sortable="true">
                                    NATIONALITÄT
                                </th>
                                <th data-field="talent" scope="col" data-width="10" data-width-unit="%"
                                    data-sortable="true">
                                    TALENT
                                </th>
                                <th data-field="experience" scope="col" data-width="8" data-width-unit="%"
                                    data-sortable="true">
                                    ERFAHRUNG
                                </th>
                                <th data-field="ovr" scope="col" data-width="5" data-width-unit="%"
                                    data-sortable="true">OVR
                                </th>
                                <th data-field="salary" scope="col" data-width="10" data-width-unit="%"
                                    data-sortable="true">
                                    GEHALT
                                </th>
                                <th data-field="marketValue" scope="col" data-width="10" data-width-unit="%"
                                    data-sortable="true">
                                    MARKTWERT
                                </th>
                            </tr>
                            </thead>
                        </table>
                        <p id="errorInModal" class="card-text text-danger d-none"></p>
                    </div>
                    <div class="modal-footer justify-content-between">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Abbrechen</button>
                        <button id="btnModalVerhandeln" type="button" class="btn btn-primary"
                                disabled>Verhandeln
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="contractModal" tabindex="-1" role="dialog" aria-labelledby="contractModalHead"
             aria-hidden="true">
            <div class="modal-dialog modal-sm" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 id="contractModalHead" class="modal-title">Vertrag verhandeln</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <p class="h6" id="lblJobName">Job</p>
                        <p class="h6" id="lblEmployeeName">Mitarbeiter</p>
                        <div class="row">
                            <div class="col text-warning font-weight-bold" id="lblOvr">0</div>
                            <div class="col text-warning font-weight-bold" id="lblAge">0</div>
                        </div>
                        <div class="row mb-2">
                            <div class="col"><small>OVR</small></div>
                            <div class="col"><small>ALTER</small></div>
                        </div>
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
                    <div class="modal-footer justify-content-between">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Abbrechen</button>
                        <button id="btnModalEinstellen" type="button" class="btn btn-primary" style="display: none">Einstellen</button>
                        <button id="btnModalVerlaengern" type="button" class="btn btn-primary" style="display: none">Verlängern
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Script for personal.php -->
        <script src="/scripts/contract/contract.js"></script>
        <script src="/scripts/buero/personal.js"></script>

    <?php endif; endif; ?>