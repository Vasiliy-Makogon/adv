"use strict";

var Krugozor = window.Krugozor || {};
Krugozor.UI = {
    popup: {}
};

/**
 * Метод инъекций, трейтов.
 * Применяется для объектов с "чистыми" данными (например с теми, которые были получены из ответа на ajax запрос).
 * Метод принимает объект данных obj и объект с методами methods и рекурсивно "впрыскивает"
 * в каждый объект объекта obj все методы (функции) объявленные в объекте methods:
 *
 * let o = {
 *     a: {}, b: {}, c: {}
 * };
 *
 * Krugozor.Injection.injection(o, {foo: function() {
 *     console.log(this);
 * }});
 * o.a.foo();
 */
Krugozor.Injection = {
    injection: function (obj, methods) {
        // typeof null - даёт object, поэтому сначала проверяем на null, потом на тип "object".
        if (obj === null || typeof obj !== "object") {
            return;
        }

        for (var j in methods) {
            if (typeof methods[j] === 'function') {
                obj[j] = methods[j];
            }
        }

        for (var i in obj) {
            if (obj.hasOwnProperty(i) && typeof obj[i] == "object") {
                this.injection(obj[i], methods);
            }
        }
    }
};

/**
 * Работа с объектами.
 */
Krugozor.Helper = {
    /**
     * Применяет CSS стили описанные в объекте css к узлу node.
     */
    attachCss: function (node, css) {
        for (let i in css) {
            node.style[i] = css[i];
        }
    },

    /**
     * Считает кол-во элементов в объекте obj.
     * Если obj не указан, то за объект берется this (для вызова через call).
     */
    getCountElements: function (obj) {
        if (obj === undefined) {
            obj = this;
        }

        var size = 0;
        for (var key in obj) {
            if (obj.hasOwnProperty(key)) {
                size++;
            }
        }

        return size;
    },

    isObject: function (obj) {
        return obj != null && obj.constructor.name === "Object";
    },

    /**
     * Проверяет, находится ли значение value в объекте obj.
     * В случае успеха возвращает значение ключа, в противном случае - false.
     * Если obj не указан, то за объект берется this (для вызова через call).
     */
    inObject: function (value, obj) {
        if (obj === undefined) {
            obj = this;
        }

        // Если передан массив или любой другой объект со свойством .length, то проверяем сначала его длинну.
        if (obj.length !== undefined && !obj.length) {
            return false;
        }

        for (var key in obj) {
            if (obj.hasOwnProperty(key)) {
                if (obj[key] == value) {
                    return key;
                }
            }
        }

        return false;
    },

    /**
     * Клонирование объекта obj.
     */
    clone: function (obj) {
        if (obj === null || typeof obj !== "object") {
            return obj;
        }

        var copy = obj.constructor();

        for (var key in obj) {
            if (obj.hasOwnProperty(key)) {
                copy[key] = obj[key];
            }
        }

        return copy;
    }
};

/**
 * Строковые методы.
 */
Krugozor.Helper.String = {
    /**
     * Метод возвращает true если строка пуста, т.е. не содержит символов,
     * или не содержит символов, отличных от пробельных.
     */
    isEmpty: function (value) {
        if (!value) {
            return true;
        }

        for (var j = 0; j < value.length; j++) {
            if (value.charAt(j) != " " && value.charAt(j) != "\n" && value.charAt(j) != "\t" && value.charAt(j) != "\r") {
                return false;
            }
        }

        return true;
    },

    /**
     * Преобразует первый символ строки в верхний регистр.
     */
    ucfirst: function (str) {
        if (!str) {
            return str;
        }

        var f = str.charAt(0).toUpperCase();
        return f + str.substring(1, str.length);
    },
};

/**
 * Браузер и экран.
 */
Krugozor.Browser = {
    /**
     * Содержит true, если включён режим CSS1Compat (IE 6),
     * false - если BackCompat (IE 4.x–IE 5.x).
     */
    CSS1Compat: document.compatMode && document.compatMode === "CSS1Compat",

    /**
     * Принимает ширину и высоту блока, который необходимо поместить по центру страницы.
     * Возвращает объект со свойствами left и top, которые в дальнейшем указываются для CSS
     * свойств top и left:
     *
     * var positions = Krugozor.Browser.getPositionBlockByCenter(200, 100);
     * div.style.top = positions.top + 'px';
     * div.style.left = positions.left + 'px';
     *
     * @param int width ширина блока
     * @param int height высота блока
     * @param bool true  - с учетом скролла страницы, false - без учета
     *             данный аргумент необходимо ставить в true если блок абсолютно позиционируемый,
     *             и false - если фиксированный.
     * @deprecated
     */
    getPositionBlockByCenter: function (width, height, use_scroll) {
        if (use_scroll === undefined) {
            use_scroll = true;
        }

        return {
            left: Math.floor((this.getClientWidth() - width) / 2) + (use_scroll ? this.getScrollLeft() : 0),
            top: Math.floor((this.getClientHeight() - height) / 2) + (use_scroll ? this.getScrollTop() : 0)
        };
    },

    /**
     * Значение прокрутки слева.
     */
    getScrollLeft: function () {
        // window.pageXOffset || docElem.scrollLeft || body.scrollLeft
        return this.CSS1Compat ? document.documentElement.scrollLeft : document.body.scrollLeft;
    },

    /**
     * Значение прокрутки сверху.
     */
    getScrollTop: function () {
        // window.pageYOffset || docElem.scrollTop || body.scrollTop
        return this.CSS1Compat ? document.documentElement.scrollTop : document.body.scrollTop;
    },

    /**
     * Возвращает высоту видимой части окна.
     */
    getClientHeight: function () {
        return this.CSS1Compat ? document.documentElement.clientHeight : document.body.clientHeight;
    },

    /**
     * Возвращает ширину видимой части окна.
     */
    getClientWidth: function () {
        return this.CSS1Compat ? document.documentElement.clientWidth : document.body.clientWidth;
    }
};

/**
 * Объект для добавления Overlay-слоя на страницу.
 */
Krugozor.Overlay = {
    overlay: null,

    CSS_OVERLAY: {
        opacity: '0',
        filter: 'alpha(opacity=0)',
        background: 'none repeat scroll 0 0 black',
        cursor: 'auto',
        height: '100%',
        left: '0',
        margin: '0',
        padding: '0',
        position: 'fixed',
        top: '0',
        width: '100%',
        zIndex: '999',
        display: 'block'
    },

    /**
     * Создает объект overlay-слоя без добавления слоя на страницу.
     * Только инициализация.
     */
    createOverlay: function () {
        return Krugozor.Helper.clone(this);
    },

    /**
     * Устанавливает прозрачность overlay-слоя.
     * Значение от 0 до 1
     */
    setOpacity: function (opacity) {
        if (opacity !== undefined) {
            this.CSS_OVERLAY.opacity = opacity;
            this.CSS_OVERLAY.filter = 'alpha(opacity=' + (opacity * 100) + ')';
        }

        return this;
    },

    /**
     * Устанавливает zIndex overlay-слоя.
     */
    setZIndex: function (zIndex) {
        if (zIndex !== undefined) {
            this.CSS_OVERLAY.zIndex = zIndex;
        }

        return this;
    },

    /**
     * Устанавливает на click по overlay функцию func.
     */
    setCallback: function (func) {
        if (func !== undefined) {
            this.overlay.addEventListener('click', func);
        }

        return this;
    },

    /**
     * Добавляет overlay-слой в DOM, как потомка body.
     */
    appendOverlay: function () {
        if (this.overlay !== null) {
            return this;
        }

        this.overlay = document.createElement('DIV');
        Krugozor.Helper.attachCss(this.overlay, this.CSS_OVERLAY);
        document.body.appendChild(this.overlay);

        return this;
    },


    /**
     * Добавляет overlay-слой на страницу и возвращает объект текущего overlay-слоя.
     * Это универсальный метод добавления overlay слоя, который инкапсулирует
     * четыре отдельных метода для более гибкой настройки и добавления overlay-слоев -
     * createOverlay, setOpacity, setCallback и appendOverlay
     */
    addOverlay: function (opacity, func) {
        var o = this.createOverlay();

        if (opacity !== undefined) {
            o.setOpacity(opacity);
        }

        o.appendOverlay();

        if (func !== undefined) {
            o.setCallback(func);
        }

        return o;
    },

    /**
     * Удаляет overlay-слой.
     */
    removeOverlay: function () {
        if (this.overlay) {
            document.body.removeChild(this.overlay);
            this.overlay = null;
        }

        return this;
    }
};

/**
 * Объект для добавления Preloader-изображения на страницу,
 * обычно - в качестве потомка Overlay-слоя.
 */
Krugozor.Preloader = {
    path: null,

    CSS_PRELOADER: {
        zIndex: 1000,
        position: 'fixed',
        visibility: 'hidden',
        left: 0,
        top: 0,
        borderRadius: '50%',
        padding: '10px'
    },

    /**
     * Создает объект preloader.
     * Только инициализация.
     */
    createPreloader: function () {
        return Krugozor.Helper.clone(this);
    },

    /**
     * Устанавливает http-путь изображения прелоадера.
     */
    setImagePath: function (path) {
        this.path = path;

        return this;
    },

    /**
     * Устанавливает zIndex для preloader.
     */
    setZIndex: function (zIndex) {
        if (zIndex !== undefined) {
            this.CSS_PRELOADER.zIndex = zIndex;
        }

        return this;
    },

    /**
     * Добавляет прелоадер в качестве потомка узла object
     */
    addPreloaderTo: function (object) {
        var that = this;

        var img = document.createElement('IMG');
        Krugozor.Helper.attachCss(img, this.CSS_PRELOADER);

        img.onload = function () {
            this.style.left = Math.ceil((Krugozor.Browser.getClientWidth() - parseInt(this.width)) / 2) + 'px';
            this.style.top = Math.ceil((Krugozor.Browser.getClientHeight() - parseInt(this.height)) / 2) + 'px';
            this.style.zIndex = that.CSS_PRELOADER.zIndex;
            img.style.visibility = 'visible';
        };
        img.src = this.path;
        object.appendChild(img);
    }
};