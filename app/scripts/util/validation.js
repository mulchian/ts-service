$('document').ready(function () {
    let fields = document.getElementsByTagName("input");
    for (let i = 0; i < fields.length; i++) {
        addEvent(fields[i], ["change", "blur", "input"], function (e, target) {
            instantValidation(target);
        });
    }
});


function addEvent(node, types, callback) {
    types.forEach(function (type) {
        if (node.addEventListener) {
            node.addEventListener(type, function (e) {
                callback(e, e.target);
            }, false);
        } else if (node.attachEvent) {
            node.attachEvent('on' + type, function (e) {
                callback(e, e.srcElement);
            });
        }
    });
}

function shouldBeValidated(field) {
    return field.getAttribute("pattern") || field.getAttribute("required");
}

function instantValidation(field) {
    if (shouldBeValidated(field)) {
        let invalid =
            (field.getAttribute("required") && !field.value) ||
            (field.getAttribute("pattern") &&
                field.value &&
                !new RegExp(field.getAttribute("pattern")).test(field.value));

        if (field.id.includes('Repeat')) {
            let controlField = $('#' + field.id.substr(0, field.id.indexOf('Repeat')));
            invalid = controlField.val() !== field.value;
        }
        if (invalid) {
            field.classList.add("is-invalid");
            field.classList.remove("is-valid");
        } else {
            field.classList.add("is-valid");
            field.classList.remove("is-invalid");
        }
    }
}



