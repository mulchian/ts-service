const AJAX_LINEUP_URL = window.location.origin + '/ajax/lineup/';
const LINEUP_MODAL = $('#lineupModal');
const DROPZONES = $('.dropzone');
let draggables = $('.draggable');

LINEUP_MODAL.on('show.bs.modal', function (element) {
    let startingTime = Date.now(),
        position,
        lineupPosition;

    if (undefined !== element.relatedTarget && null !== element.relatedTarget) {
        position = element.relatedTarget.dataset.position;
        lineupPosition = element.relatedTarget.dataset.lineup_position;
    } else {
        position = LINEUP_MODAL.data('position');
        lineupPosition = LINEUP_MODAL.data('lineup_position');
    }

    let lineupModalHead = $('#lineupModalHead');
    lineupModalHead.text('Aufstellung ' + lineupPosition);
    lineupModalHead.data('lineupPosition', lineupPosition);

    // Hole Array aller Player für die Position
    $.ajax({
        type: 'POST',
        url: AJAX_LINEUP_URL + 'getLineup.php',
        data: {
            position: position
        },
        dataType: 'JSON',
        success: function (data) {
            console.log(data);
            if (data && data.players) {
                data.players.forEach((dataPlayer) => {
                    let player = JSON.parse(dataPlayer);

                    let talent = '';
                    for (let i = 0; i < Math.floor(player.talent / 2); i++) {
                        talent += '<i class="fas fa-star"></i>';
                    }
                    if (player.talent % 2 !== 0) {
                        talent += '<i class="far fa-star"></i>';
                    }

                    let playerCard =
                        '<div id="' + player.id + '" class="card draggable" draggable="true">\n' +
                        '<div class="card-header">' + player.name + (lineupPosition === 'R' ? ' (' + player.position + ')' : '') + '</div>\n' +
                        '<div class="card-body">\n' +
                        '<div class="card-text">\n' +
                        '<div class="row">\n' +
                        '<div class="col text-warning">' + player.ovr + '</div>\n' +
                        '<div class="col text-warning">' + talent + '</div>\n' +
                        '<div class="col text-warning">' + player.age + '</div>\n' +
                        '</div>\n' +
                        '<div class="row">\n' +
                        '<div class="col"><small>OVR</small></div>\n' +
                        '<div class="col"><small>TALENT</small></div>\n' +
                        '<div class="col"><small>ALTER</small></div>\n' +
                        '</div>\n' +
                        '</div>\n' +
                        '</div>\n' +
                        '</div>\n'

                    if (undefined === player.lineupPosition || null === player.lineupPosition) {
                        $('#listPlayer').append(playerCard);
                    } else if (player.lineupPosition === lineupPosition) {
                        $('#starterDZ').append(playerCard);
                    } else if (player.lineupPosition === lineupPosition + 'b') {
                        $('#backupDZ').append(playerCard);
                    } else if (['RB1', 'RB2', 'MLB1', 'MLB2'].includes(player.lineupPosition) && lineupPosition !== 'R') {
                        $('#starterDZ').append(playerCard);
                    }
                });

                draggables = $('.draggable');
                initializeDragAndDrop();
            }
            if (data && data.position) {
                let position = JSON.parse(data.position);
                if (position.countPlayer) {
                    $('#listPlayer').data('count', position.countPlayer);
                }
                if (position.countStarter) {
                    $('#starterDZ').data('count', position.countStarter);
                }
                if (position.countBackup) {
                    $('#backupDZ').data('count', position.countBackup);
                }
            }
            $('#lblCallingTime').text(((Date.now() - startingTime) / 1000) + ' Sekunden');
        },
        error: function (data) {
            console.log(data);
        }
    });

});

LINEUP_MODAL.on('hide.bs.modal', function () {
    $('#listPlayer').empty();
    $('#starterDZ').empty();
    $('#backupDZ').empty();
});

$('#btnSaveLineup').on('click', function () {
    let lineupPosition = $('#lineupModalHead').data('lineupPosition');
    let starterPlayers = [];
    let backupPlayers = [];
    let listPlayers = [];

    let starters = $('#starterDZ').find('.draggable');
    starters.each(function () {
        let playerID = $(this).attr('id');
        starterPlayers.push(playerID);
    });

    let backups = $('#backupDZ').find('.draggable');
    backups.each(function () {
        let playerID = $(this).attr('id');
        backupPlayers.push(playerID);
    });

    let players = $('#listPlayer').find('.draggable');
    players.each(function () {
        let playerID = $(this).attr('id');
        listPlayers.push(playerID);
    });

    $.ajax({
        type: 'POST',
        url: AJAX_LINEUP_URL + 'saveLineup.php',
        data: {
            starterPlayers: starterPlayers,
            backupPlayers: backupPlayers,
            listPlayers: listPlayers,
            position: lineupPosition
        },
        dataType: 'JSON',
        success: function (data) {
            console.log('ajaxSuccess');
            if (data && data.playersLinedUp) {
                // Aktualisiere Personalübersicht (refresh)
                let row = $('#rowOffense');
                if ($('#btnDefense').hasClass('active')) {
                    row = $('#rowDefense');
                } else if ($('#btnSpecial').hasClass('active')) {
                    row = $('#rowSpecial');
                }

                row.show().load(AJAX_LINEUP_URL + 'getLineupRow.php?position=' + lineupPosition);
                LINEUP_MODAL.modal('hide');
                //location.reload();
            }
        },
        error: function (data) {
            //TODO: LOG-Error in File instead of console.log
            console.log('ajaxError');
            console.log(data.responseText);
        }
    });


});

function initializeDragAndDrop() {
    draggables.each(function () {
        $(this)
            .on('dragstart', () => {
                $(this).addClass('dragging');
            })
            .on('dragend', () => {
                $(this).removeClass('dragging');
            })
    });

    DROPZONES.each(function () {
        $(this)
            .on('dragover', e => {
                e.preventDefault();
                let count = $(this).data('count');
                let draggable = $('.dragging');
                let afterElement = getDragAfterElement($(this), e.clientY);
                if ($(this).children().length < count) {
                    if (null === afterElement || undefined === afterElement) {
                        $(this).append(draggable);
                    } else {
                        draggable.insertBefore(afterElement);
                    }
                }
            })
    });
}

function getDragAfterElement(dropzone, y) {
    let draggableElements = [...dropzone.find('.draggable:not(.dragging)')];

    return draggableElements.reduce((closest, child) => {
        let box = child.getBoundingClientRect();
        let offset = y - box.top - (box.height / 2);
        if (offset < 0 && offset > closest.offset) {
            return {offset: offset, element: child};
        } else {
            return closest;
        }
    }, {offset: Number.NEGATIVE_INFINITY}).element;
}

function autoLineup() {
    $.ajax({
        type: 'POST',
        url: AJAX_LINEUP_URL + 'autoLineup.php',
        dataType: 'JSON',
        success: function (data) {
            console.log('ajaxSuccess');
            if (data && data.playersLinedUp) {
                // Aktualisiere Personalübersicht (refresh)
                // location.reload();
                $('#rowOffense').show().load(AJAX_LINEUP_URL + 'getLineupRow.php?position=QB');
                $('#rowDefense').show().load(AJAX_LINEUP_URL + 'getLineupRow.php?position=DT');
                $('#rowSpecial').show().load(AJAX_LINEUP_URL + 'getLineupRow.php?position=K');
                closeTooltip('#btnAutoLineup');
            }
        },
        error: function (data) {
            //TODO: LOG-Error in File instead of console.log
            console.log('ajaxError');
            console.log(data.responseText);
        }
    });
}

$('#btnOffense').on('click', function () {
    $(this).addClass('active');
    let buttons = $(this).parent().find('.active').not($(this));
    buttons.each(function () {
        $(this).removeClass('active');
    });

    makeVisible($('#switchOffense'));
    makeVisible($('#rowOffense'));
    makeInvisible($('#switchDefense'));
    makeInvisible($('#rowDefense'));
    makeInvisible($('#rowSpecial'));
});
$('#btnDefense').on('click', function () {
    $(this).addClass('active');
    let buttons = $(this).parent().find('.active').not($(this));
    buttons.each(function () {
        $(this).removeClass('active');
    });

    makeVisible($('#switchDefense'));
    makeVisible($('#rowDefense'));
    makeInvisible($('#switchOffense'));
    makeInvisible($('#rowOffense'));
    makeInvisible($('#rowSpecial'));
});
$('#btnSpecial').on('click', function () {
    $(this).addClass('active');
    let buttons = $(this).parent().find('.active').not($(this));
    buttons.each(function () {
        $(this).removeClass('active');
    });

    makeVisible($('#rowSpecial'));
    makeInvisible($('#switchOffense'));
    makeInvisible($('#switchDefense'));
    makeInvisible($('#rowOffense'));
    makeInvisible($('#rowDefense'));
});

function changePosition(position, button) {
    let btn = $(button);
    btn.addClass('active');
    let buttons = btn.parent().find('.active').not(btn);
    buttons.each(function () {
        $(this).removeClass('active');
    });

    let positions = {
        'TE': 'FB',
        'FB': 'TE',
        'NT': 'MLB',
        'MLB': 'NT'
    };

    //Setzen der Flag am Team für die korrekte Darstellung
    $.ajax({
        type: 'POST',
        url: AJAX_LINEUP_URL + 'setLineupFlag.php',
        data: {
            position: position
        },
        dataType: 'JSON',
        success: function (data) {
            console.log('ajaxSuccess');
            if (data && data.lineupFlagSet) {
                let card = $('#card' + position).children();
                let oppositeCard = $('#card' + positions[position]).children();
                changeClass(card, 'add', 'bg-dark');
                changeClass(card, 'remove', 'bg-secondary');
                changeClass(oppositeCard, 'remove', 'bg-dark');
                changeClass(oppositeCard, 'add', 'bg-secondary');
            }
        },
        error: function (data) {
            //TODO: LOG-Error in File instead of console.log
            console.log('ajaxError');
            console.log(data.responseText);
        }
    });
}

function changeClass(field, changeOption, clazz) {
    switch (changeOption) {
        case 'add':
            if (!field.hasClass(clazz)) {
                field.addClass(clazz);
            }
            break;
        default:
            if (field.hasClass(clazz)) {
                field.removeClass(clazz);
            }
            break;
    }
}

function makeVisible(field) {
    if (field.hasClass('d-none')) {
        field.removeClass('d-none');
    }
}

function makeInvisible(field) {
    if (!field.hasClass('d-none')) {
        field.addClass('d-none');
    }
}