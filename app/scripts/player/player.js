const AJAX_PLAYER_URL = window.location.origin + '/ajax/player/';
const PLAYER_MODAL = $('#playerModal');
let btnExtendPlayerContract = $('#btnExtendContract');

let skillpoints = 0;
let currencyFormat = new Intl.NumberFormat('de-DE', {
    style: 'currency',
    currency: 'EUR',
    minimumFractionDigits: 0
});

PLAYER_MODAL.on('show.bs.modal', function (element) {
    let startingTime = Date.now(),
        ovr = 0,
        idPlayer;

    if (undefined !== element.relatedTarget && null !== element.relatedTarget) {
        idPlayer = element.relatedTarget.dataset.id_player;
        ovr = element.relatedTarget.dataset.ovr || 0;
    } else {
        idPlayer = PLAYER_MODAL.data('idPlayer');
        ovr = PLAYER_MODAL.data('ovr') || 0;
    }

    // Hole Player-Json und speichere diese ins Session-Team für spätere Zugriffe
    $.ajax({
        type: 'POST',
        url: AJAX_PLAYER_URL + 'getPlayer.php',
        data: {
            idPlayer: idPlayer
        },
        dataType: 'JSON',
        success: function (data) {
            if (data && data.player) {
                let player = JSON.parse(data.player);

                // Allgemeine Werte
                $('#lblName').text(player.first_name + ' ' + player.last_name);
                $('#lblPosition').text(player.type.position.description);
                $('#lblType').text(player.type.description);
                $('#lblOvr').text(ovr);
                $('#lblAge').text(player.age);
                $('#lblEnergy').text((player.energy * 100) + ' %');
                $('#lblMoral').text((player.moral * 100) + ' %');

                let talent = '';
                for (let i = 0; i < Math.floor(player.talent / 2); i++) {
                    talent += '<i class="fas fa-star"></i>';
                }
                if (player.talent % 2 !== 0) {
                    talent += '<i class="far fa-star"></i>';
                }
                $('#lblTalent').html(talent);

                // Navigation füllen
                createNavGeneral(player, data.team);

                if (undefined !== player.statistics && null !== player.statistics) {
                    createNavStatistics(player);
                    $('#rowStatisticsError').remove();
                } else {
                    $('#rowStatistics').remove();
                    let rowContractError = $('#rowStatisticsError')
                    if (rowContractError.hasClass('d-none')) {
                        rowContractError.removeClass('d-none');
                    }
                }

                createNavSkills(player, data.skillNames);

                if (undefined !== player.contract && null !== player.contract) {
                    createNavContract(player);
                    $('#rowContractError').remove();
                } else {
                    $('#rowContract').remove();
                    let rowContractError = $('#rowContractError')
                    if (rowContractError.hasClass('d-none')) {
                        rowContractError.removeClass('d-none');
                    }
                }

                $('#lblCallingTime').text(((Date.now() - startingTime) / 1000) + ' Sekunden');
            }
        },
        error: function (data) {
            console.log(data);
        }
    });
});

function updateSkill(idPlayer, skillName, skillValue, skillpointsDecimal) {
    let playerUrl = window.location.href.substring(0, window.location.href.lastIndexOf('/pages') + 1) + 'ajax/train/train.php';
    $.ajax({
        type: 'post',
        url: playerUrl,
        dataType: 'JSON',
        data: {
            idPlayer: idPlayer,
            skillName: skillName
        },
        success: function (data) {
            if (null != data.skillIsUpdated && data.skillIsUpdated) {
                skillpoints -= 1;
                skillpointsDecimal -= 1;
                $('#lblSkillpoints').text(skillpoints + ' SP');

                let skillWidth = (skillpointsDecimal - skillpoints).toFixed(2) * 100;
                let skillProgress = '<div class="progress">\n' +
                    '<div class="progress-bar" role="progressbar" style="width:' + skillWidth + '%;"\n' +
                    'aria-valuenow="' + skillWidth + '" aria-valuemin="0" aria-valuemax="100"> \n' +
                    '<small class="progress-bar-title">' + skillWidth + '%</small>' +
                    '</div>\n</div>\n'

                TBL_TRAINING.bootstrapTable('updateCellByUniqueId', {
                    id: idPlayer,
                    field: 'skillpoints',
                    value: skillProgress
                });

                $('#lblSkill' + skillName.replace(' ', '')).text(skillValue + 1);

                if (skillpoints < 1) {
                    $('.btnUpdateSkill').remove();
                }
            }
        },
        error: function (data) {
            //TODO: LOG-Error in File instead of console.log
            console.log('ajaxError');
            console.log(data.responseText);
        }
    });
}

PLAYER_MODAL.on('hide.bs.modal', function () {
    $('#lblName').text('');
    $('#lblPosition').text('');
    $('#lblType').text('');
    $('#lblOvr').text('');
    $('#lblAge').text('');
    $('#lblEnergy').text('');
    $('#lblMoral').text('');
    $('#lblTalent').html('');
    $('#lblTeamname').text('');
    $('#lblDraft').text('');
    $('#lblStatus').text('');
    $('#lblNationality').text('');
    $('#lblHeight').text('');
    $('#lblWeight').text('');
    $('#lblMarketValue').text('');
    $('#lblExperience').text('');
    $('#lblCharacter').text('');
});

function createNavGeneral(player, team) {
    if (team) {
        $('#lblTeamname').text(team);
    } else {
        $('#lblTeamname').text('Keinem Team zugeordnet.').addClass('font-italic');
    }
    if (player.draftposition && player.draftposition.round && player.draftposition.pick) {
        $('#lblDraft').html('Saison: ' + player.draftposition.season + '<br>' + 'Runde: ' + player.draftposition.round + ' Pick: ' + player.draftposition.pick);
    } else {
        $('#lblDraft').text('Free Agent').addClass('font-italic');
    }

    $('#lblStatus').text(player.status.description);
    $('#lblNationality').text(player.nationality);
    $('#lblHeight').text(player.height + ' cm');
    $('#lblWeight').text(player.weight + ' kg');
    $('#lblMarketValue').text(currencyFormat.format(player.marketValue));
    $('#lblExperience').text(player.experience + (player.experience === 1 ? ' Saison' : ' Saisons'));
    $('#lblCharacter').text(player.character.description);
}

function createNavStatistics(player) {

}

function createNavSkills(player, skillNames) {
    let skillCards = '';

    skillpoints = Math.floor(player.skillpoints);
    let skillWidth = (player.skillpoints - skillpoints).toFixed(2) * 100;
    skillCards += '<div class="card">\n' +
        '<div class="card-header">\n' +
        '<span class="d-inline-block">Skillpoints</span>\n' +
        '</div>\n' +
        '<div class="card-body">\n' +
        '<label id="lblSkillpoints" class="card-text font-weight-bold">' + skillpoints + ' SP</label>\n' +
        '<div class="progress">\n' +
        '<div class="progress-bar" role="progressbar" style="width:' + skillWidth + '%;" \n' +
        'aria-valuenow="' + skillWidth + '" aria-valuemin="0" aria-valuemax="100"> \n' +
        '<small class="progress-bar-title">' + skillWidth + '%</small>' +
        '</div>\n</div>\n' +
        '</div>\n</div>\n'

    Object.keys(player.skills).forEach(function (key) {
        skillWidth = (player.skills[key] - Math.floor(player.skills[key])).toFixed(2) * 100;
        skillCards += '<div class="card">\n' +
            '<div class="card-header">\n' +
            '<span class="d-inline-block">' + skillNames[key] + '</span>\n';
        if (skillpoints > 0) {
            skillCards += '<button type="button" class="btn btn-sm btn-secondary d-inline-block float-right btnUpdateSkill" ' +
                'onclick="updateSkill(' + player.id + ', \'' + key + '\', ' + Math.floor(player.skills[key]) + ', ' + player.skillpoints + ')">' +
                '<i class="fas fa-plus"></i></button>\n';
        }
        skillCards += '</div>\n' +
            '<div class="card-body">\n' +
            '<label id="lblSkill'+ key + '" class="card-text font-weight-bold">' + Math.floor(player.skills[key]) + '</label>\n' +
            '<div class="progress">\n' +
            '<div class="progress-bar" role="progressbar" style="width:' + skillWidth + '%;" \n' +
            'aria-valuenow="' + skillWidth + '" aria-valuemin="0" aria-valuemax="100"> \n' +
            '</div>\n</div>\n' +
            '</div>\n</div>\n'
    })

    $('#skillCardGroup').html(skillCards);
}

function createNavContract(player) {
    btnExtendPlayerContract.data('idPlayer', player.id);
    calcStartContractValues(player.contract.salary, player.marketValue);
    calcSalaryRange(player.marketValue);
    if (player.contract.end_of_contract >= 1) {
        $("#slctTimeOfContract option[value='1']").remove();
    }
    if (player.contract.end_of_contract >= 2) {
        $("#slctTimeOfContract option[value='2']").remove();
    }
    if (player.contract.end_of_contract >= 3) {
        if (!btnExtendPlayerContract.hasClass('disabled')) {
            btnExtendPlayerContract.addClass('disabled');
        }
        btnExtendPlayerContract.removeAttr('onclick');
    } else {
        if (btnExtendPlayerContract.hasClass('disabled')) {
            btnExtendPlayerContract.removeClass('disabled');
        }
        btnExtendPlayerContract.attr("onClick", 'extendPlayerContract()');
    }
}

function extendPlayerContract() {
    if (Math.random() < calcProbability()) {
        let idPlayer = btnExtendPlayerContract.data('idPlayer');
        let timeOfContract = $('#slctTimeOfContract option:selected').val();
        if (null !== idPlayer && null !== timeOfContract) {
            // selektierten Mitarbeiter einstellen
            $.ajax({
                type: 'POST',
                url: AJAX_PLAYER_URL + 'extendPlayerContract.php',
                data: {
                    idPlayer: idPlayer,
                    salary: salary,
                    timeOfContract: timeOfContract
                },
                dataType: 'JSON',
                success: function(data) {
                    if (data && data.contractIsUpdated && data.contractIsUpdated) {
                        // Aktualisiere Personalübersicht (refresh)
                        location.reload();
                    }
                },
                error: function(data) {
                    //TODO: LOG-Error in File instead of console.log
                    console.log('ajaxError');
                    console.log(data.responseText);
                }
            });
        }
    } else {
        // Verhandlung fortsetzen
        // Gehalt muss mindestens 5% höher als Angebot sein.
        updateSalaryRange();
    }
}

