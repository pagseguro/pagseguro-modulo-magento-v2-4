define(
    [
        'PagSeguro_Payment/js/view/checkout/summary/interest'
    ],
    function (Component) {
        'use strict';

        return Component.extend({

            /**
             * @override
             */
            isDisplayed: function () {
               return this.getRawValue() > 0;
            }
        });
    }
);
