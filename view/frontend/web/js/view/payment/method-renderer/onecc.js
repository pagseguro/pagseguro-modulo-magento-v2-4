/**
 * PagSeguro
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to PagSeguro so we can send you a copy immediately.
 *
 * @category   PagSeguro
 * @package    PagSeguro_Payment
 * @author     PagSeguro
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
/*browser:true*/
/*global define*/

define([
    'underscore',
    'ko',
    'jquery',
    'mage/translate',
    'Magento_SalesRule/js/action/set-coupon-code',
    'Magento_SalesRule/js/action/cancel-coupon',
    'Magento_Customer/js/model/customer',
    'Magento_Payment/js/view/payment/cc-form',
    'PagSeguro_Payment/js/model/credit-card-validation/credit-card-number-validator',
    'Magento_Payment/js/model/credit-card-validation/credit-card-data',
    'Magento_Payment/js/model/credit-card-validation/validator',
    'Magento_Checkout/js/model/payment/additional-validators',
    'mage/mage',
    'mage/validation',
    'pagseguropayment/validation'
],
function (
    _,
    ko,
    $,
    $t,
    setCouponCodeAction,
    cancelCouponCodeAction,
    customer,
    Component,
    cardNumberValidator,
    creditCardData
) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'PagSeguro_Payment/payment/form/onecc',
            creditCardOwner: '',
            creditCardSave: '',
            creditCardId: '',
            creditCardInstallments: '',
            pagseguroPaymentCreditCardNumber: '',
            showCardData: ko.observable(true),
            hasCards: ko.observable(false),
            cards: ko.observableArray([]),
            installments: ko.observableArray([]),
            hasInstallments: ko.observable(false),
            installmentsUrl: '',
            cardsUrl: '',
            creditCardEncrypted: ''
        },

        /** @inheritdoc */
        initObservable: function () {
            var self = this;
            this._super()
                .observe([
                    'creditCardType',
                    'creditCardExpYear',
                    'creditCardExpMonth',
                    'pagseguroPaymentCreditCardNumber',
                    'creditCardVerificationNumber',
                    'selectedCardType',
                    'creditCardOwner',
                    'creditCardSave',
                    'creditCardId',
                    'creditCardInstallments',
                    'creditCardEncrypted'
                ]);

            setCouponCodeAction.registerSuccessCallback(function() {
                self.updateInstallmentsValues();
            });

            cancelCouponCodeAction.registerSuccessCallback(function() {
                self.updateInstallmentsValues();
            });

            this.creditCardId.subscribe(function (value) {
                if (typeof value === "undefined" || value === "") {
                    self.showCardData(true);
                } else {
                    self.showCardData(false);
                }
            });

            //Set credit card number to credit card data object
            this.pagseguroPaymentCreditCardNumber.subscribe(function (value) {
                var result;

                self.selectedCardType(null);

                if (value === '' || value === null) {
                    return false;
                }
                result = cardNumberValidator(value);

                if (!result.isPotentiallyValid && !result.isValid) {
                    return false;
                }

                if (result.card !== null) {
                    self.selectedCardType(result.card.type);
                    creditCardData.creditCard = result.card;
                }

                if (result.isValid) {
                    creditCardData.pagseguroPaymentCreditCardNumber = value;
                    self.creditCardType(result.card.type);
                    self.updateInstallmentsValues();
                }
            });

            this.updateCardsValues();

            return this;
        },

        getCode: function() {
            return this.item.method;
        },

        /**
         * Get data
         * @returns {Object}
         */
        getData: function () {

            return {
                'method': this.item.method,
                'additional_data': {
                    'cc_cid': this.creditCardVerificationNumber(),
                    'cc_type': this.creditCardType(),
                    'cc_exp_year': this.creditCardExpYear(),
                    'cc_exp_month': this.creditCardExpMonth(),
                    'cc_number': this.pagseguroPaymentCreditCardNumber(),
                    'cc_owner': this.creditCardOwner(),
                    'cc_save': this.creditCardSave(),
                    'cc_id': this.creditCardId(),
                    'installments': this.creditCardInstallments(),
                    'cc_encrypted': this.creditCardEncrypted
                }
            };
        },

        /**
         * Check if payment is active
         *
         * @returns {Boolean}
         */
        isActive: function() {
            return this.getCode() === this.isChecked();
        },

        /**
         * @return {Boolean}
         */
        validate: function () {
            const $form = $('#' + 'form_' + this.getCode());

            if (($form.validation() && $form.validation('isValid'))) {
                console.log('validate');
                this.encryptCard();
            }

            return ($form.validation() && $form.validation('isValid'));
        },

        /**
         * @returns {boolean|*}
         */
        retrieveInstallmentsUrl: function() {
            try {
                this.installmentsUrl = window.checkoutConfig.payment.ccform.urls[this.getCode()].retrieve_installments + '?creditCardType=' + this.creditCardType();
                return this.installmentsUrl;
            } catch (e) {
                console.log('Installments URL not defined');
            }
            return false;
        },

        /**
         * @returns {boolean|*}
         */
        retrieveCardsUrl: function() {
            try {
                this.cardsUrl = window.checkoutConfig.payment.ccform.urls[this.getCode()].cards;
                return this.cardsUrl;
            } catch (e) {
                console.log('Cards URL not defined');
            }
            return false;
        },

        updateCardsValues: function() {
            var self = this;
            self.cards.removeAll();
            new Promise((resolve) => {
                fetch(self.retrieveCardsUrl(), {
                    method: 'GET',
                    cache: 'no-cache',
                    headers: {'Content-Type': 'application/json'},
                }).then((response) => {
                    return response.json();
                }).then(json => {
                    json.forEach(function(card) {
                        self.cards.push(card);
                        self.hasCards(true);
                    });
                });
            });

        },

        isLoggedIn: function() {
            return customer.isLoggedIn();
        },

        updateInstallmentsValues: function() {
            var self = this;
            self.installments.removeAll();
            new Promise((resolve) => {
                fetch(self.retrieveInstallmentsUrl(), {
                    method: 'GET',
                    cache: 'no-cache',
                    headers: {'Content-Type': 'application/json'},
                }).then((response) => {
                    return response.json();
                }).then(json => {
                    json.forEach(function(installment) {
                        console.log(installment)
                        self.installments.push(installment);
                        self.hasInstallments(true);
                    });
                });
            });

            return true;
        },

        canSave: function() {
            return window.checkoutConfig.payment.ccform.canSave[this.getCode()];
        },

        canEncrypt: function() {
            return window.checkoutConfig.payment.ccform.canEncrypt[this.getCode()];
        },

        /**
         * Get credit card details
         * @returns {Array}
         */
        getInfo: function () {
            return [
                {
                    'name': 'Credit Card Type', value: this.getCcTypeTitleByCode(this.creditCardType())
                },
                {
                    'name': 'Credit Card Number', value: this.formatDisplayCcNumber(this.pagseguroPaymentCreditCardNumber())
                }
            ];
        },

        encryptCard: function () {
            let pagseguroEncrypt = PagSeguro.encryptCard({
                publicKey: window.checkoutConfig.payment.ccform.publicKey[this.getCode()],
                holder: this.creditCardOwner(),
                number: this.pagseguroPaymentCreditCardNumber(),
                expMonth: this.creditCardExpMonth(),
                expYear: this.creditCardExpYear(),
                securityCode: this.creditCardVerificationNumber()
            });
            console.log(pagseguroEncrypt)
            this.creditCardEncrypted = pagseguroEncrypt.encryptedCard;
            console.log(this.creditCardEncrypted)
        },
    });
}
);
