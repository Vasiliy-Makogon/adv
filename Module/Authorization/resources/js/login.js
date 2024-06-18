"use strict";

var Autologin = {
    checkbox: null,
    block_with_prompt: null,
    block_active_prompt: null,
    hidden_field: null,

    set: function (id_checkbox, id_block_with_prompt, id_block_active_prompt, id_hidden) {
        if (id_checkbox) {
            this.checkbox = document.getElementById(id_checkbox);
        }

        this.block_with_prompt = document.getElementById(id_block_with_prompt);
        this.block_active_prompt = document.getElementById(id_block_active_prompt);
        this.hidden_field = document.getElementById(id_hidden);

        var _this = this;
        this.checkbox.addEventListener('click', function (e) {
            _this.block_with_prompt.style.display = e.currentTarget.checked ? 'block' : 'none';
        });

        this.block_active_prompt.addEventListener('click', function (e) {
            var str = 0;
            if (str = prompt('Введите, на какое количество дней необходимо запомнить пароль на этом компьютере', '')) {
                if (isNaN(str)) {
                    return false;
                } else if (Math.round(str) > 365 || Math.round(str) <= 0) {
                    return false;
                } else {
                    _this.block_active_prompt.firstChild.nodeValue = Math.round(str);
                    _this.hidden_field.value = Math.round(str);
                }
            }
        });
    }
};

document.addEventListener("DOMContentLoaded", function () {
    // Если это страница не авторизированного пользователя
    if (document.getElementById('autologin')) {
        Autologin.set('autologin', 'change_cookie_days', 'CookieDays', 'ml_autologin');
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

    let authForm = document.querySelector('form[name="auth_form"]');
    if (authForm) {
        let checker = Krugozor.Forms.Checker(authForm);
        checker.putFocus();
        authForm.addEventListener('submit', function (e) {
            if (!checker.checkTextFieldsOnEmpty()) {
                e.preventDefault();
            }
        });
    }
});