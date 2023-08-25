let generalTooltipOptions = {
    animation: 'grow',
    delay: '500',
    side: ['right', 'bottom'],
    maxWidth: '200',
    trigger: 'custom',
    theme: ['tooltipster-light', 'tooltipster-light-customized']
};

$(function () {
    $('.tooltip-custom')
        .tooltipster(generalTooltipOptions)
        .on("click", function () {
            $(this).tooltipster('open');
        })
        .on("focus", function () {
            $(this).tooltipster('open');
        })
        .on("blur", function () {
            $(this).tooltipster('close');
        })
    ;

    $('.tooltip-custom-interactive')
        .tooltipster({
        interactive: true,
        animation: 'grow',
        delay: '500',
        delayTouch: '500',
        side: ['left', 'bottom', 'top'],
        maxWidth: '200',
        trigger: 'click',
        theme: ['tooltipster-light', 'tooltipster-light-customized']
    });

    $('.tooltip-right-interactive')
        .tooltipster({
            interactive: true,
            animation: 'grow',
            delay: '500',
            delayTouch: '500',
            side: ['right', 'bottom', 'top'],
            maxWidth: '200',
            trigger: 'click',
            theme: ['tooltipster-light', 'tooltipster-light-customized']
        });
});

function closeTooltip(tooltipedBtn) {
    $(tooltipedBtn).tooltipster('instance').close();
}