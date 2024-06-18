"use strict";

document.addEventListener("DOMContentLoaded", function () {

    let textareaCollections = document.getElementsByTagName('textarea');
    [...textareaCollections].forEach(function (item) {
        Krugozor.Forms.resizeTextarea(item);
    });

    let captchaCodeField = document.querySelector('input[name="captcha_code"]');
    if (captchaCodeField) {
        captchaCodeField.addEventListener('input', function (e) {
            Krugozor.Forms.filterDigit(e.target);
        });
    }

    // Показать/скрыть пароль
    let hideShowPassCharsDivs = document.querySelectorAll('.hide_show_pass_chars');
    if (hideShowPassCharsDivs.length) {
        hideShowPassCharsDivs.forEach(function (div) {
            let image = div.querySelector('img');
            if (image) {
                image.addEventListener('click', function (e) {
                    Krugozor.Forms.hidePassChars(div);
                });
            }
        });
    }

});