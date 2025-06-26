define([
    "Magento_Checkout/js/view/payment/default",
    "mage/url",
    "Magento_Checkout/js/model/quote",
    "ko",
], function (Component, url, quote, ko) {
    "use strict";

    return Component.extend({
        defaults: {
            template: "Nicepay_NicePayment/payment/invoice",
            redirectAfterPlaceOrder: false,
            cardNo: ko.observable(""),
            cardExpYymm: ko.observable(""),
            cardCvv: ko.observable(""),
            cardHolderNm: ko.observable(""),
        },

        initialize: function () {
            this._super();

            // additionalInfo is an associative object { BMRI: {...}, BBBA: {...}, ... }
            this.paymentConfig = window.checkoutConfig.payment[this.getCode()];

            return this;
        },

        getCode: function () {
            return "card";
        },

        getMethod: function () {
            return "CARD";
        },

        getTest: function () {
            return "1";
        },

        getDescription: function () {
            return window.checkoutConfig.payment.card.description;
        },

        getTestDescription: function () {
            return {
                prefix: "Note: ",
                content: "Card payment sandbox environment",
            };
        },

        isPlaceOrderActionAllowed: function () {
            return (
                this.cardNo() !== null &&
                this.cardExpYymm() !== null &&
                this.cardCvv() !== null &&
                this.cardHolderNm() !== null
            );
        },

        afterPlaceOrder: function () {
            console.log("afterPlaceOrder called");
            var cardNo = this.cardNo() || "";
            var cardCvv = this.cardCvv() || "";
            var cardExpYymm = this.cardExpYymm() || "";
            var cardHolderNm = this.cardHolderNm() || "";

            var redirectUrl = url.build(
                "nicepay/nicepayment/registration?payMethod=01&cardNo=" +
                    cardNo +
                    "&cardExpYymm=" +
                    cardExpYymm +
                    "&cardCvv=" +
                    cardCvv +
                    "&cardHolderNm=" +
                    cardHolderNm
            );

            window.location.replace(redirectUrl);
        },

        placeOrder: function (data, event) {
            console.log("placeOrder called");
            if (event) {
                event.preventDefault();
            }

            if (!this.validate()) {
                return;
            }

            // Call parent's placeOrder, no Promise chaining
            this._super(data, event);

            // Trigger redirect manually
            // this.afterPlaceOrder();
        },

        validate: function () {
            var billingAddress = quote.billingAddress();
            this.messageContainer.clear();

            // Add validation if needed, e.g. ensure bank selected
            if (this.getCode() === "card" && !this.cardNo()) {
                alert("Please Fill Card Number!");
                return false;
            }

            if (this.getCode() === "card" && !this.cardCvv()) {
                alert("Please Fill Card CVV!");
                return false;
            }

            if (this.getCode() === "card" && !this.cardExpYymm()) {
                alert("Please Fill Card Expiry Year And Month!");
                return false;
            }

            if (this.getCode() === "card" && !this.cardHolderNm()) {
                alert("Please Fill Card Holder Name!");
                return false;
            }

            if (!billingAddress) {
                this.messageContainer.addErrorMessage({
                    message: "Please enter your billing address",
                });
                return false;
            }
            return true;
        },
    });
});
