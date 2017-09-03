/* global document, GplCart, jQuery */
(function (document, GplCart, $) {

    "use strict";

    /**
     * Filter rows
     * @returns {undefined}
     */
    GplCart.onload.moduleTranslatorFilter = function () {
        var num, input, tr;
        $('input[name^="filter"]').on('keyup', function () {
            input = $(this).val().toLowerCase();
            num = $(this).attr('name').match(/\[(.*?)\]/)[1];
            $('input[name^="strings"][name$="[' + num + ']"]').each(function () {
                tr = $(this).closest('tr');
                if ($(this).val().toLowerCase().indexOf(input) >= 0) {
                    tr.show();
                } else {
                    tr.hide();
                }
            });
        });
    };

    /**
     * Highlight rows
     * @returns {undefined}
     */
    GplCart.onload.moduleTranslatorHighlight = function () {
        var tr;
        $(document).on('keyup', 'input[name^="strings"]', function () {
            tr = $(this).closest('tr');
            tr.removeClass('bg-success bg-warning');
            if ($(this).val() === '') {
                tr.addClass('bg-warning');
            } else {
                tr.addClass('bg-success');
            }
        });
    };

})(document, GplCart, jQuery);

