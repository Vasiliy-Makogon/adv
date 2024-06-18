"use strict";

var Krugozor = window.Krugozor || {};

Krugozor.Forms = {

    resizeTextarea: function (element) {
        let resizeFunction = function (element) {
            element.style.height = "auto"
            element.style.height = element.scrollHeight + "px"
        };

        element.addEventListener('input', function (e) {
            resizeFunction(e.target);
        });
        resizeFunction(element);
    },

    /**
     * Вырезает все не числовые символы из поля ввода.
     */
    filterDigit: function (o) {
        let newstr = '';
        const str = o.value;
        const len = o.value.length;
        let k = 0;

        for (let i = 0; i < len; i++) {
            let chr = str.substring(i, i + 1);

            if (/[0-9]/.test(chr)) {
                newstr = newstr + chr;
            } else {
                if (!k) {
                    k = 1;
                }
            }
        }

        o.value = newstr;
        o.focus();
    },

    /**
     * @param div
     */
    hidePassChars: function (div) {
        let image = div.querySelector('img');
        let input = div.querySelector('input');

        if (!image || !input) {
            return;
        }

        input.type = input.type == 'password' ? 'text' : 'password';
        image.src = input.type == 'password' ? '/svg/local/icon-eye-close.svg' : '/svg/local/icon-eye.svg';
    }
};

Krugozor.Forms.Checker = function (form) {
    this.form = form;

    this.error_messages = {
        'empty_input_fields': "Заполнены не все поля формы"
    };

    this.text_fields = [];
    this.text_fields_types = ["text", "password", "email", "url", "textarea", "search", "tel"];

    /**
     * Метод сканирует поля формы и помещает в массив ссылки на текстовые
     * области типа text, password и textarea.
     *
     * @param void
     * @return array массив со ссылками на текстовые поля
     */
    this.getTextFields = function () {
        this.text_fields = [];

        for (var i = 0; i < this.form.elements.length; i++) {
            if (this.form.elements[i].type && Krugozor.Helper.inObject(this.form.elements[i].type.toLowerCase(), this.text_fields_types)) {
                this.text_fields.push(this.form.elements[i]);
            }
        }

        return this;
    };
    this.getTextFields();

    /**
     * Метод устанавливает фокус на незаполненные текстовые поля формы.
     *
     * @param void
     * @return void
     */
    this.putFocus = function () {
        for (var i = 0; i < this.text_fields.length; i++) {
            if (this.text_fields[i].value && !Krugozor.Helper.String.isEmpty(this.text_fields[i].value)) {
                continue;
            } else {
                this.text_fields[i].focus();
                break;
            }
        }
    };

    /**
     * Метод проходит по форме.
     * Если хотя бы одно поле пустое (не содержит данных или содержит проблы и пр. не word-символы),
     * то функция возвращает false.
     * В качестве аргументов метода можно указать список имён или ID полей,
     * на которых действие функции не должны распростроняться.
     *
     * @param void
     * @return boolean
     */
    this.checkTextFieldsOnEmpty = function () {
        for (var i = 0; i < this.text_fields.length; i++) {
            if (arguments.length && (
                Krugozor.Helper.inObject.call(arguments, this.text_fields[i].name) !== false
                ||
                Krugozor.Helper.inObject.call(arguments, this.text_fields[i].id) !== false)
            ) {
                continue;
            }

            if (!this.text_fields[i].value.length || Krugozor.Helper.String.isEmpty(this.text_fields[i].value)) {
                alert(this.error_messages['empty_input_fields']);
                this.text_fields[i].focus();
                return false;
            }
        }

        return true;
    };

    /**
     * Очищает форму.
     * Текстовые поля любого рода очищаются, значение select становится в 0-й элемент option,
     * с radio и checkbox снимается выделение.
     */
    this.clear = function () {
        for (let element of form.elements) {
            switch (element.tagName.toLowerCase()) {
                case 'input':
                    if (this.text_fields_types.includes(element.type.toLowerCase())) {
                        element.setAttribute('value', '');
                    } else if (['checkbox', 'radio'].includes(element.type.toLowerCase())) {
                        element.checked = false;
                    }
                    break;

                case 'select':
                    for (let option of element.options) {
                        if (option.hasAttribute('selected')) {
                            option.removeAttribute('selected');
                        }
                    }
                    break;
            }
        }
    };

    return this;
};