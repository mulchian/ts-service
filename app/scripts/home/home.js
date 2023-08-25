const AJAX_HOME_URL = window.location.origin + '/ajax/home/';
const CALENDAR = $('#calendar');
const DAYS = ['sunday','monday','tuesday','wednesday','thursday','friday','saturday'];

$(function () {
    let dateFormatter = new Intl.DateTimeFormat('de-DE', {day: '2-digit', month: '2-digit', year: 'numeric'});

    $.ajax({
        type: 'POST',
        url: AJAX_HOME_URL + 'getCalendar.php',
        data: {
            isLeagueGame: true
        },
        dataType: 'JSON',
        success: function (data) {
            if (null != data.calendarWeek && null != data.calendarTitles) {
                let titles = data.calendarTitles;
                let columns = [{
                    field: 'monday',
                    title: 'MONTAG<br/>' + dateFormatter.format(new Date(titles.monday))
                }, {
                    field: 'tuesday',
                    title: 'DIENSTAG<br/>' + dateFormatter.format(new Date(titles.tuesday))
                }, {
                    field: 'wednesday',
                    title: 'MITTWOCH<br/>' + dateFormatter.format(new Date(titles.wednesday))
                }, {
                    field: 'thursday',
                    title: 'DONNERSTAG<br/>' + dateFormatter.format(new Date(titles.thursday))
                }, {
                    field: 'friday',
                    title: 'FREITAG<br/>' + dateFormatter.format(new Date(titles.friday))
                }, {
                    field: 'saturday',
                    title: 'SAMSTAG<br/>' + dateFormatter.format(new Date(titles.saturday))
                }, {
                    field: 'sunday',
                    title: 'SONNTAG<br/>' + dateFormatter.format(new Date(titles.sunday))
                }];

                CALENDAR.bootstrapTable({
                    columns: columns,
                    data: [data.calendarWeek]
                });

                CALENDAR.bootstrapTable('refreshOptions', {
                    classes: 'table table-bordered table-dark'
                });

            }
        },
        error: function (data) {
            //TODO: LOG-Error in File instead of console.log
            console.log('ajaxError');
            console.log(data.responseText);
        }
    });
});

function headerStyle(column) {
    let day = DAYS[new Date().getDay()];

    if (column["field"] === day) {
    column["css"] = {color: '#ffc107'};
    }
    return column;
}

CALENDAR.on('click-cell.bs.table', function (field, value, row, $element) {
    // Fires when user click a cell, the parameters contain:
    // field: the field name corresponding to the clicked cell.
    // value: the data value corresponding to the clicked cell.
    // row: the record corresponding to the clicked row.
    // $element: the td element.


});