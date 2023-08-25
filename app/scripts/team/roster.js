const TBL_ROSTER = $('#tblRoster');

function showContract(element) {
    LINEUP_MODAL.data('idPlayer', element.dataset.id_player);
    LINEUP_MODAL.data('ovr', element.dataset.ovr);
    LINEUP_MODAL.modal('show');
    $('#nav-tab-player  a[href="#nav-contract"]').tab('show');
}

function releasePlayer(element) {
    let idPlayer = element.dataset.id_player;
    let position = element.dataset.position;

    $.ajax({
        type: 'POST',
        url: AJAX_PLAYER_URL + 'releasePlayer.php',
        data: {
            idPlayer: idPlayer,
            position: position
        },
        dataType: 'JSON',
        success: function(data) {
            console.log('ajaxSuccess');
            if (data && data.playerIsReleased && data.correctedSalary) {
                TBL_ROSTER.bootstrapTable('removeByUniqueId', idPlayer);

                $('#lblSalaryCap').text('Salary Cap: ' + currencyFormat.format(data.correctedSalary));
            }
        },
        error: function(data) {
            //TODO: LOG-Error in File instead of console.log
            console.log('ajaxError');
            console.log(data.responseText);
        }
    });
}