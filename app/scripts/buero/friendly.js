const AJAX_EVENT_URL = window.location.origin + '/ajax/event/';

$(function () {
    $('#dtpicker').tempusDominus({
        allowInputToggle: true,
        defaultDate: new Date(moment().add(2, 'hours').minutes(0).seconds(0).unix() * 1000),
        localization: {
            locale: 'de',
            format: 'dd.MM.yyyy HH:mm'
        }
    });
})

function gameTimeSorter(a, b) {
    let aa = moment(a, "DD.MM.YYYY HH:mm").unix();
    let bb = moment(b, "DD.MM.YYYY HH:mm").unix();
    return aa - bb;
}

async function inviteForFriendly() {
    let msgError = $('#msgError');
    let error;
    if (!msgError.hasClass('d-none')) {
        msgError.addClass('d-none');
    }

    let opponent = $('#opponent').val();
    let isHome = $('#cbIsHome').prop("checked");
    let dateTime = $('#datetime').val();

    let gameTime = moment(dateTime, "DD-MM-YYYY HH:mm");

    if (gameTime.format('YYYY-MM-DD HH:mm') >= moment().add(2, 'hours').minutes(0).seconds(0).format('YYYY-MM-DD HH:mm')) {
        const response = await fetch(AJAX_EVENT_URL + 'addFriendly.php', {
            method: 'POST',
            body: JSON.stringify({
                home: isHome,
                opponent: opponent,
                gameTime: gameTime.format('YYYY-MM-DD HH:mm:ss')
            })
        });
        const data = await response.json();

        if (data.id) {
            let table = $('#tblFriendlies');
            if (table.length) {
                // Table is there - add the element
                let friendlyRow = [{
                    id: data.id,
                    gameTime: moment(data.gameTime, 'YYYY-MM-DD HH:MM:SS').format('DD.MM.YYYY HH:mm'),
                    home: data.home,
                    away: data.away,
                    accepted: data.accepted,
                    action: '<button type="button" class="btn btn-secondary">ABSAGEN</button>'
                }];

                if (!table.bootstrapTable('getRowByUniqueId', data.id)) {
                    table.bootstrapTable('append', friendlyRow);
                } else {
                    // error message
                    error = 'Das Spiel konnte nicht in der Tabelle gespeichert werden.'
                    error += '<br>Bitte lade die Seite neu und/oder versuche es erneut.';
                }
            } else {
                // reload page for creating the table
                location.reload();
            }
        } else {
            // there was an error
            error = 'Zu der Zeit kann kein Freundschaftsspiel angelegt werden.';
            if (data['timeHasLeagueGame']) {
                error += '<br>Zu der Zeit hast du bereits ein Spiel geplant.';
            } else {
                error += '<br>Die Zeit liegt nicht weit genug in der Zukunft.';
            }
        }

    } else {
        // Fehlermeldung, dass das Spiel mind. 2 volle Stunden später sein muss.
        // Zum Beispiel bei 14:59 darf erst um 16 Uhr gestartet werden.
        error = 'Zu der Zeit kann kein Freundschaftsspiel angelegt werden.<br>Die Zeit liegt nicht weit genug in der Zukunft.';
    }

    if (error && error.length > 0) {
        msgError.html(error);
        if (msgError.hasClass('d-none')) {
            msgError.removeClass('d-none');
        }
    }
}

function goToLive() {
    location.href = location.origin + location.pathname + '?site=live';
}

async function acceptFriendly(rowId) {
    let table = $('#tblFriendlies');
    let row = table.bootstrapTable('getRowByUniqueId', rowId);
    let gameTime = moment(row.gameTime, "DD.MM.YYYY HH:mm").unix();

    const response = await fetch(AJAX_EVENT_URL + 'acceptFriendly.php', {
        method: 'POST',
        body: JSON.stringify({
            home: row.home,
            away: row.away,
            gameTime: gameTime
        })
    });
    const data = await response.json();

    if (data.isAccepted) {
        //reload cause bootstrap table update isn't working with tooltipster
        location.reload();
    }
}

async function declineFriendly(rowId) {
    let table = $('#tblFriendlies');
    let row = table.bootstrapTable('getRowByUniqueId', rowId);
    let gameTime = moment(row.gameTime, "DD.MM.YYYY HH:mm").unix();

    const response = await fetch(AJAX_EVENT_URL + 'declineFriendly.php', {
        method: 'POST',
        body: JSON.stringify({
            rowId: row.id,
            home: row.home,
            away: row.away,
            gameTime: gameTime
        })
    });
    const data = await response.json();

    if (data.isDeclined) {
        table.bootstrapTable('removeByUniqueId', row.id);
    }
}

function showFriendlyResults() {
    //refresh page to show the results
    location.href = location.origin + location.pathname + '?site=league&do=friendlyResult';
}


