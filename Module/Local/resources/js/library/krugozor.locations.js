"use strict";

var Krugozor = window.Krugozor || {};

/**
 * Переключение локаций (страны, регионы, города) в select-списках.
 */
Krugozor.Location = {

    /**
     * Типы локаций и их идентификаторы.
     *
     * @var object
     */
    LOCATION_TYPES: {
        1: 'country',
        2: 'region',
        3: 'city'
    },

    /**
     * Объект, содержащий пользовательские локации вида ID локации => ID места, где
     * ID локации - ID локации из массива this.LOCATION_TYPES,
     * ID места - ID места (страна, регион или город) из СУБД
     *
     * @var object
     */
    checked_user_locations: {},

    /**
     * Добавляет пользовательскую локацию в this.checked_user_locations.
     * Фактически, всегда должен быть вызов данного метода для всех 3 локаций.
     *
     * @param location_id ID локации
     * @param place_id ID места
     */
    addCheckedUserLocation: function(location_id, place_id){
        this.checked_user_locations[location_id] = place_id;
    },

    /**
     * Созданные объеты локаций.
     *
     * @var array
     */
    locations: [],

    /**
     * Создаёт или убирает нижестоящие select-списки.
     *
     * @param object locationObj
     * @return void
     */
    _next_step: function(locationObj)
    {
        locationObj.creator.checked_user_locations = {};

        if (locationObj.SelectElement.value > 0) {
            var next_select_id = this.LOCATION_TYPES[locationObj.location_id + 1] ? locationObj.location_id + 1 : null;

            if (next_select_id) {
                for (var i in this.locations) {
                    if (this.locations[i].location_id == next_select_id) {
                        this.locations[i].create(next_select_id, locationObj.SelectElement.value);
                    }
                }

                // Сбрасываем option's всех последующих select-списков.
                i = next_select_id + 1;
                while (this.LOCATION_TYPES[i]) {
                    for (var j in this.locations) {
                        if (this.locations[j].location_id == i) {
                            this.locations[j].create(i, 0);
                        }
                    }
                    i++;
                }
            }
        }
        else
        {
            var i = this.LOCATION_TYPES[locationObj.location_id] ? locationObj.location_id + 1 : null;
            while (this.LOCATION_TYPES[i]) {
                for (var j in this.locations) {
                    if (this.locations[j].location_id == i) {
                        this.locations[j].create(i, 0);
                    }
                }
                i++;
            }
        }
    },

    /**
     * Создаёт и возвращает объект локации.
     *
     * @param object select_attributes аттрибуты HTML-тега SELECT
     * @param option_text текст тега OPTION
     * @return object
     */
    createLocation: function(select_attributes, option_text){
        var SelectElement = document.createElement('SELECT');
        for (var name in select_attributes) {
            SelectElement.setAttribute(name, select_attributes[name]);
        }

        var option = document.createElement('OPTION');
        option.setAttribute('value', '0');
        option.appendChild(document.createTextNode(option_text));

        SelectElement.appendChild(option);

        if (SelectElement.getAttribute('toggle')) {
            SelectElement.style.display = 'none';
        }

        var locationObj = {
            creator: this,
            location_id: null,
            SelectElement: SelectElement,

            /**
             * Заполняет this.SelectElement данными локации location_id, полученными для места place_id.
             *
             * @param location_id
             * @param place_id
             * @returns {Boolean}
             */
            create: function(location_id, place_id){
                if (!location_id || !this.creator.LOCATION_TYPES[location_id]) {
                    return false;
                }

                this.location_id = location_id;

                var _this = this;

                try {
                    var ajax = new Krugozor.Ajax();
                    ajax.setObserverState(function(ajx){
                        _this._addOptionsToSelect(this.locations);
                    }, true);
                    ajax.get("/user/frontend-ajax-get-" + this.creator.LOCATION_TYPES[this.location_id] + "/?id=" + place_id);
                } catch (e) {}
            },

            /**
             * Добавляет option's к списку this.SelectElement.
             *
             * @param object places данные
             * @param object HTMLSelectElement
             */
            _addOptionsToSelect: function(places){
                if (!places || places === {}) {
                    return;
                }

                var first_option = this.SelectElement.firstChild;
                while (this.SelectElement.firstChild){
                    this.SelectElement.removeChild(this.SelectElement.firstChild);
                }
                this.SelectElement.appendChild(first_option);

                for (var i in places){
                    var option = document.createElement('OPTION');
                    option.appendChild(document.createTextNode(places[i][1]));
                    option.setAttribute('value', places[i][0]);

                    if (parseInt(this.creator.checked_user_locations[this.location_id]) == places[i][0]){
                        option.setAttribute('selected', 'selected');
                    }

                    this.SelectElement.appendChild(option);
                }

                if (this.SelectElement.getAttribute('toggle')) {
                    this.SelectElement.style.display = Krugozor.Helper.getCountElements(places) ? 'inline' : 'none';
                }
            }
        };

        var _this = this;
        SelectElement.onchange = function() {
            _this._next_step(locationObj);
        };

        this.locations.push(locationObj);

        return locationObj;
    }
};