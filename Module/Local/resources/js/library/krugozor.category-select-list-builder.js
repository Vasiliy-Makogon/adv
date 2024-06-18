"use strict";

var Krugozor = window.Krugozor || {};

Krugozor.СategorySelectListBuilder = {
    element: null,
    selectName: null,
    currentAdvertType: null,

    /**
     * @param element ID DOM элемента-обёртки, в котором будут созданы select-списки
     * @param current_category ID текущей категории объявления
     * @param category_pid PID текущей категории объявления
     * @param selectName имя select-списков, который будет создаваться
     * @param currentAdvertType тип текущего объявления
     */
    run: function(element, current_category, category_pid, selectName, currentAdvertType) {
        const o = Krugozor.Helper.clone(this);
        o.advertTypesAddedFlag = false;
        o.element = element;
        o.selectName = selectName;
        o.currentAdvertType = currentAdvertType;

        o.doListTopLevel(current_category, category_pid);
        if (current_category) {
            o.doListBottomLevel(current_category);
        }
    },

    doListTopLevel: function (current_category, category_pid) {
        const ajax = new Krugozor.Ajax();
        const _this = this;

        ajax.setObserverState(function(ajx, xhr) {
            if (!Krugozor.Helper.isObject(this) || !Krugozor.Helper.getCountElements(this)) {
                return;
            }

            _this.element.insertBefore(
                _this.createSelectList(this, current_category),
                _this.element.querySelector('select:first-child')
            );

            if (this[0].grandparent_id) {
                _this.doListTopLevel(this[0].parent_id, this[0].grandparent_id);
            }
        }, true);

        ajax.get('/category/frontend-ajax-get-child-category/id/' + category_pid);
    },

    doListBottomLevel: function (current_category) {
        const ajax = new Krugozor.Ajax();
        const _this = this;

        ajax.setObserverState(function(ajx, xhr) {
            if (!Krugozor.Helper.isObject(this) || !Krugozor.Helper.getCountElements(this)) {
                return;
            }

            _this.element.appendChild(
                _this.createSelectList(this, current_category)
            );
        }, true);

        ajax.get('/category/frontend-ajax-get-child-category/id/' + current_category);
    },

    createSelectList: function(data, selectedValue) {
        const list = document.createElement('SELECT');
        list.setAttribute('name', this.selectName);

        let option = document.createElement('OPTION');

        option.appendChild(
            document.createTextNode(
                data[0].parent_id == 0
                ? 'Выберите категорию'
                : 'Выберите категорию, если это возможно по смыслу'
            )
        );

        option.setAttribute('value', data[0].parent_id);
        option.setAttribute('parent_id', 0);
        option.setAttribute('data-haschilds', 0);
        option.advert_types = data[0].advert_types;
        list.appendChild(option);

        let _this = this;
        list.addEventListener('change', function () {
            while (this.nextSibling) {
                this.nextSibling.remove();
            }

            if (this.selectedIndex && parseInt(this.options[this.selectedIndex].dataset.haschilds)) {
                _this.doListBottomLevel(this.value);
            }

            let advertTypes = null;
            if (this.selectedIndex) {
                advertTypes = this.options[this.selectedIndex].advert_types;
            } else if (this.previousSibling) {
                advertTypes = this.previousSibling.options[this.previousSibling.selectedIndex].advert_types;
            }

            _this.addAdvertTypes(advertTypes, false);
        }, false);

        for (let k in data) {
            let option = document.createElement('OPTION');
            option.appendChild(document.createTextNode(data[k].name));
            option.setAttribute('value', data[k].id);
            option.setAttribute('parent_id', data[k].parent_id);
            option.setAttribute('data-haschilds', data[k].haschilds);
            option.advert_types = data[k].advert_types;

            if (selectedValue == data[k].id) {
                option.setAttribute('selected', 'selected');

                if (!_this.advertTypesAddedFlag) {
                    _this.advertTypesAddedFlag = true;
                    _this.addAdvertTypes(data[k].advert_types, this.currentAdvertType);
                }
            }

            list.appendChild(option);
        }

        return list;
    },

    /**
     * @param advertTypes список типов объявлений
     * @param checkedValue значение, которое необходимо сделать checked
     */
    addAdvertTypes: function (advertTypes, checkedValue) {
        const js_category_radiobuttons = document.getElementById('js_category_radiobuttons');

        let advertTypesHtml = '';
        const advertTypesCount = Krugozor.Helper.getCountElements(advertTypes);

        if (advertTypes && advertTypesCount) {
            for (let i in advertTypes) {
                const chacked = checkedValue == advertTypes[i]['key'] || advertTypesCount == 1
                    ? 'checked="checked"'
                    : '';

                advertTypesHtml +=
                    '<input ' + chacked + ' type="radio" name="advert[type]" value="' +
                    advertTypes[i]['key'] + '" id="advert_type_' + advertTypes[i]['key'] + '">' +
                    '<label for="advert_type_' + advertTypes[i]['key'] + '">' + advertTypes[i]['value'] + '</label>';
            }
        }

        if (js_category_radiobuttons) {
            js_category_radiobuttons.innerHTML = advertTypesHtml
                ||
            '<span class="select_category_notification">Уточните категорию, выбрав в списке выше необходмый пункт</span>';
        }
    }
};