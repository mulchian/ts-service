const AJAX_COACHING_URL = window.location.origin + '/ajax/coaching/';

$(document).on('change', '.toggle', function (e) {
    let cb = $(e.target);
    let idMain = cb.prop('id').substring(2);
    let gp = $('#gp' + idMain);
    let idSelect = 'sl' + idMain;

    if (cb.prop('checked')) {
        gp.html('<select id="' + idSelect + '" class="form-control bg-secondary text-white" onchange="saveCoachingFromSelect(this)">\n' +
            '<option value="Inside Run" selected>Inside Run</option>\n' +
            '<option value="Outside Run rechts">Outside Run rechts</option>\n' +
            '<option value="Outside Run links">Outside Run links</option>\n' +
            '</select>');
    } else {
        gp.html('<select id="' + idSelect + '" class="form-control" onchange="saveCoachingFromSelect(this)">\n' +
            '<option value="Screen Pass" selected>Screen Pass</option>\n' +
            '<option value="Short Pass">Short Pass</option>\n' +
            '<option value="Medium Pass">Medium Pass</option>\n' +
            '<option value="Long Pass">Long Pass</option>\n' +
            '</select>');
    }

    saveCoaching(idMain.substring(1), null);
});

function initializeRange(rngName, rating) {

    let rangeSlider = $('#' + rngName);
    let rangeInstance = rangeSlider.data('ionRangeSlider');
    if (rangeInstance) {
        rangeInstance.destroy();
    }

    rangeSlider.ionRangeSlider({
        type: 'single',
        min: 0,
        max: 100,
        from: rating,
        step: 1,
        postfix: " %",
        grid: true,
        onFinish: function (data) {
            saveCoaching(rngName.substring(3), data.from);
        }
    });
}

$(function () {
    initializeStandardRanges();
    initializeCoaching();

    //initialize tooltip
    let gameplanTooltip = generalTooltipOptions;
    gameplanTooltip['content'] = '<label for="inputGameplan">Neuer Name des Gameplans:</label><br>\n' +
        '                        <input type="text" placeholder="Gameplan" id="inputGameplan"\n' +
        '                               class="form-control" autocomplete="off">\n' +
        '                        <button type="button" class="btn btn-outline-danger m-1"\n' +
        '                                onclick="saveGameplanName()">Speichern</button>';
    gameplanTooltip['contentAsHTML'] = true;
    gameplanTooltip['interactive'] = true;
    gameplanTooltip['trigger'] = 'click';
    $('.tooltip-gameplan').tooltipster(gameplanTooltip)
        .on("click", function () {
            $('#inputGameplan').focus();
        });
})

function initializeStandardRanges() {
    let ranges = ['1st', '2nd', '3rd', '4th'];
    ranges.forEach(function (elem) {
        initializeRange('rng' + elem + 'Long', 50);
        initializeRange('rng' + elem + 'Middle', 50);
        initializeRange('rng' + elem + 'Short', 50);
        initializeRange('rng' + elem + 'Run', 50);
        initializeRange('rng' + elem + 'Pass', 50);
    });
}

function initializeCoaching() {
    let gameplanOff = $('#slGameplanOff').children('option:selected').val();
    let gameplanDef = $('#slGameplanDef').children('option:selected').val();

    $.ajax({
        type: 'POST',
        url: AJAX_COACHING_URL + 'getCoaching.php',
        data: {
            allCoachings: true,
            gameplanOff: gameplanOff,
            gameplanDef: gameplanDef
        },
        dataType: 'JSON',
        success: function (data) {
            console.log(data);
            if (data && data.coachings) {
                data.coachings.forEach(function (item) {
                    if (item.teamPart !== 'General') {
                        let rangeInstance = $('#rng' + item.down + item.playrange).data('ionRangeSlider');
                        rangeInstance.update({
                            from: item.rating
                        });
                    }
                });
            }
        },
        error: function (data) {
            console.log(data);
        }
    });
}

function changeTeamPart(teamPart, button) {
    let btn = $(button);
    btn.addClass('active');
    let buttons = btn.parent().find('.active').not(btn);
    buttons.each(function () {
        $(this).removeClass('active');
    });

    let rowOff = $('#rowOffense');
    let rowDef = $('#rowDefense');
    let colGPOff = $('#colGameplanOff');
    let colGPDef = $('#colGameplanDef');
    switch (teamPart) {
        case 'Offense':
            changeVisibility(rowDef, rowOff);
            changeVisibility(colGPDef, colGPOff);
            break;
        default:
            changeVisibility(rowOff, rowDef);
            changeVisibility(colGPOff, colGPDef);
            break;
    }
}

function changeVisibility(toHide, toShow) {
    if (!toHide.hasClass('d-none')) {
        toHide.addClass('d-none');
    }
    if (toShow.hasClass('d-none')) {
        toShow.removeClass('d-none');
    }
}

function saveGameplanName() {
    let gameplanNr,
        slGameplan,
        teamPart = $('#btnGrpTeamPart').find('.active').val(),
        gameplanName = $('#inputGameplan').val();
    if (teamPart.includes('Offense')) {
        slGameplan = $('#slGameplanOff');
    } else {
        slGameplan = $('#slGameplanDef');
    }
    if (slGameplan && gameplanName.length > 0) {
        gameplanNr = slGameplan.children('option:selected').val();

        $.ajax({
            type: 'POST',
            url: AJAX_COACHING_URL + 'saveCoaching.php',
            data: {
                gameplanNr: gameplanNr,
                gameplanName: gameplanName,
                teamPart: teamPart
            },
            dataType: 'JSON',
            success: function (data) {
                console.log(data);
                if (data && data.gameplanNameSaved) {
                    $('#inputGameplan').val('');
                    slGameplan.children('option:selected').html(gameplanName);
                    closeTooltip('#btnChangeNameGP');
                }
            },
            error: function (data) {
                console.log(data);
            }
        });
    }
}

function saveGameplan(elem) {
    let slGameplan = $(elem);
    let gameplan = slGameplan.prop('id').substring(2);
    let gameplanNr = slGameplan.children('option:selected').val();

    $.ajax({
        type: 'POST',
        url: AJAX_COACHING_URL + 'saveCoaching.php',
        data: {
            gameplanNr: gameplanNr,
            gameplan: gameplan
        },
        dataType: 'JSON',
        success: function (data) {
            console.log(data);
            if (data && data.gameplanSaved) {
                let rangeSlider, rangeInstance;
                let ranges = ['Long', 'Middle', 'Short', 'Run', 'Pass'];

                let downs = ['1st', '2nd', '3rd', '4th'];
                downs.forEach(function (down) {
                    ranges.forEach(function (range) {
                        rangeSlider = $('#rng' + down + range);
                        rangeInstance = rangeSlider.data('ionRangeSlider');
                        rangeInstance.update({
                            from: 50
                        });
                    });
                });

                initializeCoaching();
            }
        },
        error: function (data) {
            console.log(data);
        }
    });
}

function saveCoachingFromSelect(elem) {
    let select = $(elem);
    saveCoaching(select.prop('id').substring(3), null);
}

function saveCoaching(elemName, dataFrom) {
    let teamPart = $('#btnGrpTeamPart').find('.active').val();
    let down = elemName.substring(0, 3);
    let playrange = elemName.substring(3);

    let gameplanNr, cbOption1, cbOption2;
    if (teamPart === 'Offense') {
        gameplanNr = $('#slGameplanOff').children('option:selected').val();
        cbOption1 = $('#cbL' + elemName).prop('checked') ? 'Run' : 'Pass';
        cbOption2 = $('#cbR' + elemName).prop('checked') ? 'Run' : 'Pass';
    } else {
        gameplanNr = $('#slGameplanDef').children('option:selected').val();
        cbOption1 = playrange;
        cbOption2 = playrange;
    }

    let gameplay1 = $('#slL' + elemName).children('option:selected').val();
    let gameplay2 = $('#slR' + elemName).children('option:selected').val();

    let rating = dataFrom || $('#rng' + elemName).data().from;

    // Speichere Run/Pass für das Down
    $.ajax({
        type: 'POST',
        url: AJAX_COACHING_URL + 'saveCoaching.php',
        data: {
            gameplanNr: gameplanNr,
            teamPart: teamPart,
            down: down,
            playrange: playrange,
            gameplay1: cbOption1 + ';' + gameplay1,
            gameplay2: cbOption2 + ';' + gameplay2,
            rating: rating
        },
        dataType: 'JSON',
        success: function (data) {
            console.log(data);
            if (data && data.coachingSaved) {
                $('#row' + down + teamPart.substring(0, 3)).show().load(AJAX_COACHING_URL + 'getCoachingRow.php', {
                    gameplanNr: gameplanNr,
                    down: down,
                    teamPart: teamPart
                }, function () {
                    if (teamPart === 'Offense') {
                        ['Long', 'Middle', 'Short'].forEach(function (elem) {
                            $('#cbL' + down + elem).bootstrapToggle();
                            $('#cbR' + down + elem).bootstrapToggle();
                        });
                        initializeRange('rng' + down + 'Long', data.ratings.Long || 50);
                        initializeRange('rng' + down + 'Middle', data.ratings.Middle || 50);
                        initializeRange('rng' + down + 'Short', data.ratings.Short || 50);
                    } else {
                        initializeRange('rng' + down + 'Run', data.ratings.Run || 50);
                        initializeRange('rng' + down + 'Pass', data.ratings.Pass || 50);
                    }
                });
            }
        },
        error: function (data) {
            console.log(data);
        }
    });
}

function saveGeneralCoaching() {
    let gameplanNr = $('#slGameplanOff').children('option:selected').val();
    let teamPart = 'General';
    let playrange = teamPart;

    let fgRange = $('#inputFGRange').children('option:selected').val();
    let twoPtCon = $('#input2PtCon').children('option:selected').val();
    let fourthDown = $('#input4thDown').children('option:selected').val();
    let qbRun = $('#inputQBRun').children('option:selected').val();

    let data = {
        gameplanNr: gameplanNr,
        general1: {
            gameplanNr: gameplanNr,
            teamPart: teamPart,
            down: '1st',
            playrange: playrange,
            gameplay1: 'FGRange;' + fgRange,
            gameplay2: '2PtCon' + ';' + twoPtCon,
            rating: 0
        },
        general2: {
            gameplanNr: gameplanNr,
            teamPart: teamPart,
            down: '2nd',
            playrange: playrange,
            gameplay1: '4thDown;' + fourthDown,
            gameplay2: 'QBRun;' + qbRun,
            rating: 0
        }
    }

    // Speichere Run/Pass für das Down
    $.ajax({
        type: 'POST',
        url: AJAX_COACHING_URL + 'saveCoaching.php',
        data: data,
        dataType: 'JSON',
        success: function (data) {
            console.log(data);
            if (data && data.coachingSaved) {
                $('#colGeneral').show().load(AJAX_COACHING_URL + 'getCoachingRow.php', {
                    gameplanNr: gameplanNr,
                    down: '1st',
                    teamPart: teamPart
                });
            }
        },
        error: function (data) {
            console.log(data);
        }
    });
}