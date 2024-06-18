"use strict";

document.addEventListener("DOMContentLoaded", function () {
    document.body.onselectstart = function () {
        return false;
    };
});