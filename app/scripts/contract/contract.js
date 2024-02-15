let salary = 0,
    minSalary = 0,
    maxSalary = 0,
    moral = 0,
    timeOfContract = 3,
    signingBonus = 0;

function calcProbability() {
    let probability = 0.5;
    if (moral <= 0.8) {
        probability += moral - 0.75;
    } else if (moral < 0.9) {
        probability += 0.05 + (((moral * 100) % 10) * 2 / 100);
    } else if (moral < 0.98) {
        probability += 0.26 + (((moral * 100) % 10) * 3 / 100);
    } else {
        probability = moral;
    }
    return probability;
}

function updateSalaryRange() {
    let newMoral = moral + 0.05;
    minSalary = Math.floor(maxSalary * newMoral);
    if (salary < minSalary) {
        moral = minSalary / (salary / moral);
        salary = minSalary;
    }
    if (minSalary > maxSalary) {
        minSalary = maxSalary;
    }
    let salaryRange = $('#salaryRange').data('ionRangeSlider');
    salaryRange.update({
        min: minSalary
    });
    $('#lblContractMoral').text(Math.round(newMoral * 100) + ' %');
}

function calcSalaryRange(marketValue) {
    let salaryCap = $('#lblSalaryCap').data('salary_cap');
    let newSalaryCap = salaryCap - (maxSalary + signingBonus);
    let step = Math.floor(maxSalary * 0.01);

    let salaryRange = $('#salaryRange');
    let rangeInstance = salaryRange.data('ionRangeSlider');
    if (rangeInstance) {
        rangeInstance.destroy();
    }

    salaryRange.ionRangeSlider({
        type: 'single',
        min: minSalary,
        max: maxSalary,
        from: salary,
        step: step,
        postfix: " €",
        onStart: function (data) {
            data.from = salary;
            saveSalaryRange(data, signingBonus, newSalaryCap);
        },
        onChange: function (data) {
            newSalaryCap = salaryCap - (data.from + signingBonus);
            saveSalaryRange(data, signingBonus, newSalaryCap);
        },
        onFinish: function (data) {
            newSalaryCap = salaryCap - (data.from + signingBonus);
            saveSalaryRange(data, signingBonus, newSalaryCap);
        }
    });

    $('#slctTimeOfContract').change(function () {
        timeOfContract = $(this).children('option:selected').val();
        signingBonus = Math.floor(marketValue * (0.05 * timeOfContract)) * timeOfContract;
        let completeOffer = salary + signingBonus;
        $('#lblSigningBonus').text(currencyFormat.format(signingBonus));
        $('#lblCompleteOffer').text(currencyFormat.format(completeOffer));
        newSalaryCap = salaryCap - completeOffer;
        $('#lblNewSalaryCap').text(currencyFormat.format(newSalaryCap));
    });
}

function saveSalaryRange(data, signingBonus, newSalaryCap) {
    salary = data.from;
    moral = Math.round((data.from / data.max) * 100) / 100;
    $('#lblContractMoral').text(moral * 100 + ' %');
    $('#lblCompleteOffer').text(currencyFormat.format(data.from + signingBonus));
    $('#lblNewSalaryCap').text(currencyFormat.format(newSalaryCap));
}

function calcStartContractValues(actualSalary, marketValue) {
    salary = actualSalary;
    timeOfContract = $('#slctTimeOfContract option:selected').val();
    signingBonus = Math.floor(marketValue * (0.05 * timeOfContract)) *  timeOfContract;
    $('#lblSigningBonus').text(currencyFormat.format(signingBonus));
    maxSalary = Math.floor(marketValue * 20 / 100);
    minSalary = Math.floor(maxSalary * 0.75);
}