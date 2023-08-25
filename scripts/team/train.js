const TBL_TRAINING = $('#tblTraining');

$(function () {
    let generalTooltipOptions = {
        animation: 'grow',
        delay: '500',
        side: 'bottom',
        maxWidth: '200',
        trigger: 'hover',
        theme: ['tooltipster-light', 'tooltipster-light-customized']
    };
    $('.tooltip-custom').tooltipster(generalTooltipOptions);
});

function countdown(countdownId, countdownDate) {

    function intervalFunc() {
        // today's date and time
        let now = new Date().getTime();

        //distance between now and count down date
        let distance = countdownDate - now;

        //time calculations for minutes and seconds
        let minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
        if (minutes < 10) {
            minutes = '0' + minutes;
        }
        let seconds = Math.floor((distance % (1000 * 60)) / 1000);
        if (seconds < 10) {
            seconds = '0' + seconds;
        }

        document.getElementById(countdownId).innerHTML = minutes + ':' + seconds + ' Minuten';

        if (distance < 0) {
            clearInterval(interval);
            document.getElementById(countdownId).innerHTML = '';
            $('#' + countdownId).addClass('d-none');
            $('#btnRow' + countdownId.substring(1, 4)).removeClass('d-none');
        }
    }

    //update the countdown every i second
    intervalFunc();
    let interval = setInterval(intervalFunc, 1000);
}

function train(playerIds, countdownId, training) {
    let errorField = $('#' + countdownId.substring(1, 4) + 'Error');
    if (!errorField.hasClass('d-none')) {
        errorField.addClass('d-none');
    }

    ajax({
            trainingGroup: countdownId.substring(1, 4),
            training: training
        },
        function (data) {
            if (null != data.isTraining && data.isTraining) {
                $('#' + countdownId).removeClass('d-none');
                $('#btnRow' + countdownId.substring(1, 4)).addClass('d-none');
                let countdownTime = new Date(data.timeToCount * 1000);
                countdown(countdownId, countdownTime);

                playerIds.forEach(function (player) {
                    TBL_TRAINING.bootstrapTable('updateCellByUniqueId', {
                        id: player.rowId,
                        field: 'trainings',
                        value: (player.numberOfTrainings + 1) + ' / 3'
                    });
                });

            } else if (null != data.errorMessage && data.errorMessage.length > 0) {
                errorField.removeClass('d-none');
                errorField.text(data.errorMessage);
            }
        }
    );
}

function setCountDown(countDownId, timeToCount) {
    $('#' + countDownId).removeClass('d-none');
    $('#btnRow' + countDownId.substring(1, 4)).addClass('d-none');
    countdown(countDownId, new Date(timeToCount * 1000));
}

function updateIntensityForTeam(newIntensity) {
    ajax({
            newIntensity: newIntensity
        },
        function (data) {
            if (null != data.intIsUpdated && data.intIsUpdated) {
                location.reload();
            }
        }
    );
}

function updateIntensity(rowId, newIntensity, playerId) {
    ajax({
            newIntensity: newIntensity,
            playerId: playerId
        },
        function (data) {
            if (null != data.intIsUpdated && data.intIsUpdated) {
                let newIntensityBar = null;
                switch (newIntensity) {
                    case 1:
                        newIntensityBar = '<a id="1" href="javascript:updateIntensity(' + rowId + ', 2, ' + playerId + ');">' +
                            '<i class="fas fa-battery-quarter" style="font-size: 30px; color: #00ff00;"></i></a>';
                        break;
                    case 2:
                        newIntensityBar = '<a id="1" href="javascript:updateIntensity(' + rowId + ', 3, ' + playerId + ');">' +
                            '<i class="fas fa-battery-half" style="font-size: 30px; color: #ffff00;"></i></a>';
                        break;
                    case 3:
                        newIntensityBar = '<a id="1" href="javascript:updateIntensity(' + rowId + ', 1, ' + playerId + ');">' +
                            '<i class="fas fa-battery-full" style="font-size: 30px; color: #ff0000;"></i></a>';
                        break;
                }
                TBL_TRAINING.bootstrapTable('updateCellByUniqueId', {
                    id: rowId,
                    field: 'intensity',
                    value: newIntensityBar
                });
            }
        }
    );
}

function updateTrainingGroupForTeam(newTrainingGroup) {
    ajax({
            newTrainingGroup: newTrainingGroup
        },
        function (data) {
            if (null != data.tgIsUpdated && data.tgIsUpdated) {
                location.reload();
            }
        }
    );
}

function updateTrainingGroup(rowId, oldTrainingGroup, newTrainingGroup, playerId) {
    ajax({
            oldTrainingGroup: oldTrainingGroup,
            newTrainingGroup: newTrainingGroup,
            playerId: playerId
        },
        function (data) {
            if (null != data) {
                if (null != data.tgIsUpdated && data.tgIsUpdated) {
                    let newTrainingGroupDropDown = '<div class="dropdown"><button class="btn btn-secondary dropdown-toggle" type="button" ' +
                        'data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">' + data.newTrainingGroupName + '</button>' +
                        '<div class="dropdown-menu" aria-labelledby="trainingGroupDropdown">\n' +
                        '<a class="dropdown-item" href="javascript:updateTrainingGroup(' + rowId + ', \'' + newTrainingGroup + '\', \'TE0\', ' + playerId + ');">' + data.trainingGroup0Name + '</a>' +
                        '<a class="dropdown-item" href="javascript:updateTrainingGroup(' + rowId + ', \'' + newTrainingGroup + '\', \'TE1\', ' + playerId + ');">' + data.trainingGroup1Name + '</a>' +
                        '<a class="dropdown-item" href="javascript:updateTrainingGroup(' + rowId + ', \'' + newTrainingGroup + '\', \'TE2\', ' + playerId + ');">' + data.trainingGroup2Name + '</a>' +
                        '<a class="dropdown-item" href="javascript:updateTrainingGroup(' + rowId + ', \'' + newTrainingGroup + '\', \'TE3\', ' + playerId + ');">' + data.trainingGroup3Name + '</a>' +
                        '</div></div>';
                    TBL_TRAINING.bootstrapTable('updateCellByUniqueId', {
                        id: rowId,
                        field: 'trainingGroup',
                        value: newTrainingGroupDropDown
                    });

                    if (undefined !== data.playersToTrainingGroup && null !== data.playersToTrainingGroup) {
                        $('#btn' + newTrainingGroup + 'Fitness').removeClass('disabled')
                            .attr('onClick', 'train(' + data.playersToTrainingGroup + ', \'p' + newTrainingGroup + 'Training\', \'fitness\')');
                        $('#btn' + newTrainingGroup + 'Technik').removeClass('disabled')
                            .attr('onClick', 'train(' + data.playersToTrainingGroup + ', \'p' + newTrainingGroup + 'Training\', \'technique\')');
                        $('#btn' + newTrainingGroup + 'Scrimmage').removeClass('disabled')
                            .attr('onClick', 'train(' + data.playersToTrainingGroup + ', \'p' + newTrainingGroup + 'Training\', \'scrimmage\')');
                    }
                    if (undefined !== data.noPlayerInOldTrainingGroup && null !== data.noPlayerInOldTrainingGroup && data.noPlayerInOldTrainingGroup) {
                        $('#btn' + oldTrainingGroup + 'Fitness').addClass('disabled').removeAttr('onclick');
                        $('#btn' + oldTrainingGroup + 'Technik').addClass('disabled').removeAttr('onclick');
                        $('#btn' + oldTrainingGroup + 'Scrimmage').addClass('disabled').removeAttr('onclick');
                    }
                }
            }
        }
    );
}

function ajax(data, success, error) {
    let trainUrl = window.location.origin + '/ajax/train/train.php';
    $.ajax({
        type: 'post',
        url: trainUrl,
        data: data,
        dataType: 'JSON',
        success: success,
        error: error || function (data) {
            //TODO: LOG-Error in File instead of console.log
            console.log('ajaxError');
            console.log(data.responseText);
        }
    });
}