"use strict";

document.addEventListener("DOMContentLoaded", function () {

    let textareaCollections = document.getElementsByTagName('textarea');
    [...textareaCollections].forEach(function (item) {
        Krugozor.Forms.resizeTextarea(item);
    });

});