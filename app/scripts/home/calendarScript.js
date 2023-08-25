const CALENDAR = $('#calendar');

$(function () {
    // Build calendar
    let calendarJson = [];
    let dateFormatter = new Intl.DateTimeFormat('de-DE', {day: '2-digit', month: '2-digit', year: 'numeric'});


    let today = new Date;
    let monday = new Date(today.setDate(today.getDate() - today.getDay() + 1));
    let tuesday = new Date(today.setDate(today.getDate() - today.getDay() + 2));
    let wednesday = new Date(today.setDate(today.getDate() - today.getDay() + 3));
    let thursday = new Date(today.setDate(today.getDate() - today.getDay() + 4));
    let friday = new Date(today.setDate(today.getDate() - today.getDay() + 5));
    let saturday = new Date(today.setDate(today.getDate() - today.getDay() + 6));
    let sunday = new Date(today.setDate(today.getDate() - today.getDay() + 7));

    let columns = [{
            field: 'monday',
            title: 'MONTAG<br/>' + dateFormatter.format(monday)
        }, {
            field: 'tuesday',
            title: 'DIENSTAG<br/>' + dateFormatter.format(tuesday)
        }, {
            field: 'wednesday',
            title: 'MITTWOCH<br/>' + dateFormatter.format(wednesday)
        }, {
            field: 'thursday',
            title: 'DONNERSTAG<br/>' + dateFormatter.format(thursday)
        }, {
            field: 'friday',
            title: 'FREITAG<br/>' + dateFormatter.format(friday)
        }, {
            field: 'saturday',
            title: 'SAMSTAG<br/>' + dateFormatter.format(saturday)
        }, {
            field: 'sunday',
            title: 'SONNTAG<br/>' + dateFormatter.format(sunday)
        }
    ];

    let week = {
        monday: 'Spiel 1',
        tuesday: 'Spiel 2',
        wednesday: 'Spiel 3',
        thursday: 'Spiel 4',
        friday: 'Free?!',
        saturday: 'Noch ein Beitrag',
        sunday: 'Und Ende'
    };
    calendarJson.push(week);

    CALENDAR.bootstrapTable({
        columns: columns,
        data: calendarJson
    });

    CALENDAR.bootstrapTable('refreshOptions', {
        classes: 'table table-bordered table-dark'
    });
});

CALENDAR.on('click-cell.bs.table', function (field, value, row, $element) {
    // Fires when user click a cell, the parameters contain:
    // field: the field name corresponding to the clicked cell.
    // value: the data value corresponding to the clicked cell.
    // row: the record corresponding to the clicked row.
    // $element: the td element.


});