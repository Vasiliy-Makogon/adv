document.addEventListener("DOMContentLoaded", function () {

    for (const resetButton of document.getElementsByClassName('js_reset_button')) {
        resetButton.addEventListener('click', function (e) {
            (Krugozor.Forms.Checker(resetButton.form)).clear();
            resetButton.form.submit();
        });
    }

});