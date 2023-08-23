$(function () {
    let passwordTooltipOptions = generalTooltipOptions;
    passwordTooltipOptions['distance'] = 50;
    passwordTooltipOptions['content'] = 'Das Passwort muss aus <u>mind. 8 Zeichen</u> bestehen und 3 der 4 folgenden Kriterien erfüllen:<br>\n' +
        'mind. 1 Großbuchstabe, mind. 1 Kleinbuchstabe, mind. 1 Zahl oder 1 Sonderzeichen';
    passwordTooltipOptions['contentAsHTML'] = true;
    $('.tooltip-password').tooltipster(passwordTooltipOptions)
        .on("click", function () {
            $(this).tooltipster('open');
        })
        .on("focus", function () {
            $(this).tooltipster('open');
        })
        .on("blur", function () {
            $(this).tooltipster('close');
        });

    let passwordFields = [
        [
            $('#showPassword'),
            $('#password'),
            $('#showPassword i')
        ],
        [
            $('#showPasswordRepeat'),
            $('#passwordRepeat'),
            $('#showPasswordRepeat i')
        ]
    ];

    for (let i = 0; i < passwordFields.length; i++) {
        passwordFields[i][0].click(function (event) {
            event.preventDefault();
            if (passwordFields[i][1].attr("type") === "text") {
                passwordFields[i][1].attr('type', 'password');
                passwordFields[i][2].addClass("fa-eye");
                passwordFields[i][2].removeClass("fa-eye-slash");
            } else if (passwordFields[i][1].attr("type") === "password") {
                passwordFields[i][1].attr('type', 'text');
                passwordFields[i][2].removeClass("fa-eye");
                passwordFields[i][2].addClass("fa-eye-slash");
            }
        });
    }
});





