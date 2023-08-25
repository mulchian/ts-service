const AJAX_LEAGUE_URL = window.location.origin + '/ajax/league/';

$(function () {
    const params = new Proxy(new URLSearchParams(window.location.search), {
        get: (searchParams, prop) => searchParams.get(prop),
    });
    let gameId = params.game;

    setSliderOvrYards(300, 100);
    // $.ajax({
    //     type: 'POST',
    //     url: AJAX_LEAGUE_URL + 'getGameStatistics.php',
    //     data: {
    //         gameId: gameId
    //     },
    //     dataType: 'JSON',
    //     success: function (data) {
    //
    //     }
    // });
})

function setSliderOvrYards(ovrYards, ovrYardsHome) {
    let ovrYardsSlider = $('#sliderOvrYards');
    let ovrYardsInstance = ovrYardsSlider.data('ionRangeSlider');
    if (ovrYardsInstance) {
        ovrYardsInstance.destroy();
    }
    ovrYardsSlider.ionRangeSlider({
        type: 'single',
        min: 0,
        max: ovrYards,
        from: ovrYardsHome,
        step: 1,
        from_fixed: true,
        hide_min_max: true,
        hide_from_to: true,
        disable: true
    });
}