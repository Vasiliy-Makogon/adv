"use strict";

document.addEventListener("DOMContentLoaded", function () {

    const jsSelectCategoryWrapperTop = document.getElementById('js-select-category-wrapper-top');
    if (jsSelectCategoryWrapperTop) {
        Krugozor.СategorySelectListBuilder.run(jsSelectCategoryWrapperTop, current_category, category_pid, 'category');
    }

    const jsSelectCategoryWrapperBottom = document.getElementById('js-select-category-wrapper-bottom');
    if (jsSelectCategoryWrapperBottom) {
        Krugozor.СategorySelectListBuilder.run(jsSelectCategoryWrapperBottom, current_category, category_pid, 'category');
    }

    // Сделать отдельной функцией, как только возникнет необходимость
    // повторять подобные действия - выбирать все checkbox на странице.
    // @todo: добавить возможность указывать класс
    var label = document.getElementById('js_advert_delete_all');
    if (label) {
        label.addEventListener('click', function (e) {
            var inputs = document.getElementsByTagName('input');
            e.preventDefault();

            for (let input of inputs) {
                if (input.type == 'checkbox') {
                    input.checked = !input.checked;
                }
            }
        });
    }

    // Отправка письма анонимному пользователю с предложением зарегестрироваться
    var invites = document.getElementsByClassName('js-invite-anonymous-user');
    [...invites].forEach(function (item) {
        item.addEventListener('click', function (e) {
            e.preventDefault();

            var ajax = new Krugozor.Ajax();
            ajax.setObserverState(
                function (ajx, xhr) {
                    alert(this.message);
                }, true
            );
            ajax.get(e.currentTarget.getAttribute('href'));
        });
    });

});