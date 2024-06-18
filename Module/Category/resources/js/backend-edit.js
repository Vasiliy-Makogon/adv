"use strict";

document.addEventListener("DOMContentLoaded", function () {

    // Событие на текстовое поле "ключевые слова".
    document.getElementById('js-category-keywords').addEventListener('keyup', function (e) {
        var value = e.target.value;
        value = value.toLowerCase();
        e.target.value = value.replace(/(\r?\n)+/, ', ');
    });

});