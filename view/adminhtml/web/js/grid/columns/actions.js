define(["Magento_Ui/js/grid/columns/actions", "mage/template"], function (
    Actions,
    mageTemplate
) {
    "use strict";

    return Actions.extend({
        defaults: {
            bodyTmpl: "Nicepay_NicePayment/grid/cells/actions",
            fieldClass: {
                actions: true,
            },
        },

        getAction: function (action, index) {
            return {
                callback: action.callback,
                href: action.href,
                label: action.label,
                confirm: action.confirm,
                visible: true,
            };
        },
    });
});
