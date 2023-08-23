const AJAX_PERSONAL_URL = window.location.origin + '/ajax/personal/';
const PERSONAL_MODAL = $('#personalModal');
const CONTRACT_MODAL = $('#contractModal');
let tblPersonal = $('#tblNewPersonal');
let btnEinstellen = $('#btnModalEinstellen');
let btnVerhandeln = $('#btnModalVerhandeln');
let btnVerlaengern = $('#btnModalVerlaengern');
let errorInModal = $('#errorInModal');

let currencyFormat = new Intl.NumberFormat('de-DE', {
    style: 'currency',
    currency: 'EUR',
    minimumFractionDigits: 0
});

let generalTooltipOptions = {
    animation: 'grow',
    delay: '500',
    delayTouch: '500',
    side: ['right', 'bottom'],
    maxWidth: '200',
    trigger: 'click',
    theme: ['tooltipster-light', 'tooltipster-light-customized']
};

$(function () {
    let tooltipCustomOptions = generalTooltipOptions;
    tooltipCustomOptions['interactive'] = true;
    $('.tooltip-custom').tooltipster(tooltipCustomOptions);
});

async function releaseEmployee(element) {
    let jobname = element.dataset.jobname;
    let idEmployee = element.dataset.id_employee;
    let countEmployees = element.dataset.count_employees || 0;
    let buildingLevel = element.dataset.building_level || 0;
    let salary = element.dataset.salary;

    const response = await fetch(AJAX_PERSONAL_URL + 'releaseEmployee.php', {
        method: 'POST',
        body: JSON.stringify({
            idEmployee: idEmployee,
            jobname: jobname
        })
    });
    const data = await response.json();

    if (data && data.employeeIsReleased && data.correctedSalary) {
        let cardBody = $('#btn' + jobname.replace(" ", "") + 'Entlassen').parent();
        let disabled = (buildingLevel <= (countEmployees - 1)) ? 'disabled' : '';

        cardBody.html('<div class="card-body">' +
            '<p class="card-text">\n' +
            '<button type="button" class="btn btn-secondary"\n' +
            'id="btn' + jobname.replace(" ", "") + 'Einstellen"\n' +
            'data-toggle="modal" data-target="#personalModal"\n' +
            'data-jobname="' + jobname + '" ' + disabled + '>' + 'EINSTELLEN' + '</button>\n' +
            '</p>' +
            '</div>');

        $('#lblSalaryCap').text('Salary Cap: ' + currencyFormat.format(data.correctedSalary));
        $('#lblPersonalAnzahl').text((countEmployees - 1) + ' / ' + buildingLevel + ' Mitarbeiter');
        let lblEmployeeSalary = $('#lblEmployeeSalary');
        let teamSalary = lblEmployeeSalary.text();
        teamSalary = parseInt(teamSalary.substring(0, teamSalary.length - 2).replace('.', ''));
        lblEmployeeSalary.text(currencyFormat.format(teamSalary - salary));
    }
}

function closeTooltip() {
    $('.tooltip-custom').tooltipster('instance').close();
}

async function negotiateContract(url, idEmployee) {
    if (Math.random() < calcProbability()) {
        // Chance zu Moral als Prozent, um hier zu sein.
        // Ist die Moral 0,75 ist die Chance 50 %, dass wir hier landen.
        let timeOfContract = $('#slctTimeOfContract option:selected').val();
        if (null !== idEmployee && null !== timeOfContract) {
            // selektierten Mitarbeiter einstellen
            const response = await fetch(url, {
                method: 'POST',
                body: JSON.stringify({
                    idEmployee: idEmployee,
                    salary: salary,
                    timeOfContract: timeOfContract
                })
            });
            const data = await response.json();
            if ((data.employeeIsInTeam && data.employeeIsInTeam) || (data.contractIsUpdated && data.contractIsUpdated)) {
                // Aktualisiere Personalübersicht (refresh)
                location.reload();
            }
        }
    } else {
        // Verhandlungen fortsetzen
        // Gehalt muss mindestens 5 % höher als Angebot sein.
        updateSalaryRange();
    }
}

async function getUnemployedEmployees(jobName) {
    const response = await fetch(AJAX_PERSONAL_URL + 'getUnemployedEmployees.php?'
        + new URLSearchParams({
            jobName: jobName
        }), {
        method: 'GET'
    });
    return await response.json();
}

btnEinstellen.on('click', function () {
    negotiateContract(AJAX_PERSONAL_URL + 'addEmployeeToTeam.php', $(this).data('idEmployee'));
});

btnVerlaengern.on("click", function () {
    negotiateContract(AJAX_PERSONAL_URL + 'extendEmployeeContract.php', $(this).data('idEmployee'));
});

PERSONAL_MODAL.on('check.bs.table', function () {
    if (btnVerhandeln.prop('disabled')) {
        btnVerhandeln.prop('disabled', false);
    }
});

PERSONAL_MODAL.on('uncheck.bs.table', function () {
    if (!btnVerhandeln.prop('disabled')) {
        btnVerhandeln.prop('disabled', true);
    }
});

PERSONAL_MODAL.on('show.bs.modal', function (event) {
    let btnOpenModal = $(event.relatedTarget);
    let jobName = btnOpenModal.data('jobname');

    let modalHead = $('#personalModalHead');
    modalHead.text(jobName + ' einstellen');

    errorInModal.hide();
    tblPersonal.hide();
    btnVerhandeln.show();

    $('#modalLoadingSpinner').show();

    getUnemployedEmployees(jobName).then(data => {
        if (null != data.employees && null != data.job) {
            let employees = data.employees;
            let job = JSON.parse(data.job);

            generalTooltipOptions['trigger'] = 'custom';
            generalTooltipOptions['triggerOpen'] = {
                mouseenter: true,
                tap: true
            };
            generalTooltipOptions['triggerClose'] = {
                mouseleave: true,
                tap: true,
                scroll: true
            };
            let tooltipster = modalHead.tooltipster(generalTooltipOptions);
            tooltipster.tooltipster('content', job.description);

            let personalDataObject = [];

            employees.forEach(function (item) {
                item = JSON.parse(item);
                let talent = '';
                for (let i = 0; i < Math.floor(item.talent / 2); i++) {
                    talent += '<i class="fas fa-star"></i>';
                }
                if ((item.talent % 2) !== 0) {
                    talent += '<i class="far fa-star" ></i>';
                }

                let employee;
                employee = {
                    id: item.id,
                    name: item.firstName + ' ' + item.lastName,
                    age: item.age,
                    nationality: item.nationality,
                    talent: talent,
                    experience: item.experience,
                    ovr: item.ovr,
                    salary: currencyFormat.format(Math.floor(item.marketvalue * 20 / 100)),
                    jobName: JSON.parse(item.job).name,
                    marketvalue: item.marketvalue
                };

                personalDataObject.push(employee);
            });

            tblPersonal.bootstrapTable();
            tblPersonal.bootstrapTable('load', personalDataObject);

            tblPersonal.bootstrapTable('hideColumn', 'id');
            tblPersonal.bootstrapTable('hideColumn', 'marketvalue');
            tblPersonal.show();
        } else {
            if (null != data.errorMessage) {
                console.log('errorMessage');
                btnVerhandeln.hide();
                errorInModal.show();
                errorInModal.text(data.errorMessage);
            }
        }
        $('#modalLoadingSpinner').hide();
    });
});

btnVerhandeln.on("click", function () {
    let selection = tblPersonal.bootstrapTable('getSelections');
    let employee = selection[0];
    if (null != employee) {
        console.log(employee);

        let salary = employee.salary.replace('.', '');
        salary = parseInt(salary.substring(0, salary.length - 2));
        calcStartContractValues(salary, employee.marketvalue);

        CONTRACT_MODAL.on('show.bs.modal', function () {
            extendContractModal(employee);
        });
        CONTRACT_MODAL.modal('show');

        btnEinstellen.show();
    }
});

function extendContractModal(employee) {
    $('#lblJobName').text(employee.jobName);
    $('#lblEmployeeName').text(employee.name);
    $('#lblOvr').text(employee.ovr);
    $('#lblAge').text(employee.age);

    btnVerlaengern.data('idEmployee', employee.id);
    btnEinstellen.data('idEmployee', employee.id);
    calcSalaryRange(employee.marketvalue);
}

CONTRACT_MODAL.on('hide.bs.modal', function () {
    btnEinstellen.hide();
    btnVerlaengern.hide();
});

function extendContract(element) {
    let employee = element.dataset.employee;
    if (null != employee) {
        employee = JSON.parse(employee);
        employee.jobName = employee.job.name;
        employee.name = employee.first_name + ' ' + employee.last_name;

        calcStartContractValues(Math.floor(employee.marketvalue * 20 / 100), employee.marketvalue);

        CONTRACT_MODAL.on('show.bs.modal', function () {
            extendContractModal(employee, signingBonus, timeOfContract);
        });
        CONTRACT_MODAL.modal('show');

        if (employee.contract.end_of_contract >= 1) {
            $("#slctTimeOfContract option[value='1']").remove();
        }
        if (employee.contract.end_of_contract >= 2) {
            $("#slctTimeOfContract option[value='2']").remove();
        }
        btnVerlaengern.show();
    }
}