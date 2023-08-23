const AJAX_LIVE_URL = window.location.origin + '/ajax/live/';

function recalculate(gameId, isHome) {
    $.ajax({
        type: 'POST',
        url: AJAX_LIVE_URL + 'getCalcResult.php',
        data: {
            gameId: gameId
        },
        dataType: 'JSON',
        success: function (data) {
            if (data.recalculationFinished && data.result) {
                let result = data.result.split(';');
                $('#result' + gameId).text(result[0] + ' : ' + result[1]);
                let diff = result[0] - result[1];
                let resultCond, textColor;
                if ((isHome && diff > 0) || (!isHome && diff < 0)) {
                    resultCond = 'S';
                    textColor = 'text-success';
                } else if (diff === 0) {
                    resultCond = 'U';
                    textColor = 'text-warning';
                } else {
                    resultCond = 'N';
                    textColor = 'text-danger';
                }
                $('#resultSymbol' + gameId).html('<h2 class="my-0 py-0' + textColor + '">' + resultCond + '</h2>');
            }
        }
    });
}
