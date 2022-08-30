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
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/model/cart/totals-processor/default',
    'Magento_Checkout/js/model/cart/cache',
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
    creditCardData,
    quote,
    defaultTotal,
    cartCache
) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'PagSeguro_Payment/payment/form/twocc',
            firstCreditCardAmount: 0,
            firstCreditCardOwner: '',
            firstCreditCardSave: '',
            firstCreditCardId: '',
            firstCreditCardInstallments: '',
            firstCreditCardEncrypted: '',
            pagseguroPaymentFirstCreditCardNumber: '',
            showFirstCardData: ko.observable(true),
            hasCards: ko.observable(false),
            firstCards: ko.observableArray([]),

            cardOneInstallments: ko.observableArray(['']),
            cardOneHasInstallments: ko.observable(false),

            cardTwoInstallments: ko.observableArray([]),
            cardTwoHasInstallments: ko.observable(false),

            installmentsUrl: '',
            cardsUrl: '',
            showLoadingInstallment: ko.observable(false),

            secondCreditCardAmount: 0,
            secondCreditCardOwner: '',
            secondCreditCardSave: '',
            secondCreditCardId: '',
            secondCreditCardInstallments: '',
            pagseguroPaymentSecondCreditCardNumber: '',
            secondCreditCardEncrypted: '',
            showSecondCardData: ko.observable(true),
            secondCards: ko.observableArray([]),
        },

        /** @inheritdoc */
        initObservable: function () {
            var self = this;

            cartCache.set('totals',null);
            defaultTotal.estimateTotals();

            this._super()
                .observe([
                    'firstCreditCardAmount',
                    'firstCreditCardType',
                    'firstCreditCardExpYear',
                    'firstCreditCardExpMonth',
                    'pagseguroPaymentFirstCreditCardNumber',
                    'firstCreditCardVerificationNumber',
                    'firstSelectedCardType',
                    'firstCreditCardOwner',
                    'firstCreditCardSave',
                    'firstCreditCardId',
                    'firstCreditCardInstallments',
                    'firstCreditCardEncrypted',

                    'secondSelectedCardType',
                    'secondCreditCardAmount',
                    'secondCreditCardType',
                    'secondCreditCardExpYear',
                    'secondCreditCardExpMonth',
                    'pagseguroPaymentSecondCreditCardNumber',
                    'secondCreditCardVerificationNumber',
                    'secondCreditCardOwner',
                    'secondCreditCardSave',
                    'secondCreditCardId',
                    'secondCreditCardInstallments',
                    'secondCreditCardEncrypted'
                ]);

            this.firstCreditCardId.subscribe(function (value) {
                if (typeof value === "undefined" || value === "") {
                    self.showFirstCardData(true);
                } else {
                    self.showFirstCardData(false);
                }
            });

            this.secondCreditCardId.subscribe(function (value) {
                if (typeof value === "undefined" || value === "") {
                    self.showSecondCardData(true);
                } else {
                    self.showSecondCardData(false);
                }
            });

            this.firstCreditCardAmount.subscribe(function (value) {
                self.updateTwoCardAmount(value, 'card_one')
            });

            this.secondCreditCardAmount.subscribe(function (value) {
                self.updateTwoCardAmount(value, 'card_two')
            });

            //Set credit card number to credit card data object
            this.pagseguroPaymentFirstCreditCardNumber.subscribe(function (value) {
                var result;

                self.firstSelectedCardType(null);

                if (value === '' || value === null) {
                    return false;
                }
                result = cardNumberValidator(value);

                if (!result.isPotentiallyValid && !result.isValid) {
                    return false;
                }

                if (result.card !== null) {
                    self.firstSelectedCardType(result.card.type);
                    creditCardData.creditCard = result.card;
                }

                if (result.isValid) {
                    creditCardData.pagseguroPaymentFirstCreditCardNumber = value;
                    self.firstCreditCardType(result.card.type);
                }
            });

            //Set credit card number to credit card data object
            this.pagseguroPaymentSecondCreditCardNumber.subscribe(function (value) {
                var result;

                self.secondSelectedCardType(null);

                if (value === '' || value === null) {
                    return false;
                }
                result = cardNumberValidator(value);

                if (!result.isPotentiallyValid && !result.isValid) {
                    return false;
                }

                if (result.card !== null) {
                    self.secondSelectedCardType(result.card.type);
                    creditCardData.creditCard = result.card;
                }

                if (result.isValid) {
                    creditCardData.pagseguroPaymentSecondCreditCardNumber = value;
                    self.secondCreditCardType(result.card.type);
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
                    'cc_one_cc_cid': this.firstCreditCardVerificationNumber(),
                    'cc_one_cc_type': this.firstCreditCardType(),
                    'cc_one_cc_exp_year': this.firstCreditCardExpYear(),
                    'cc_one_cc_exp_month': this.firstCreditCardExpMonth(),
                    'cc_one_cc_number': this.pagseguroPaymentFirstCreditCardNumber(),
                    'cc_one_cc_owner': this.firstCreditCardOwner(),
                    'cc_one_cc_save': this.firstCreditCardSave(),
                    'cc_one_cc_id': this.firstCreditCardId(),
                    'cc_one_installments': this.firstCreditCardInstallments(),
                    'cc_one_cc_encrypted': this.firstCreditCardEncrypted,
                    'cc_one_cc_amount': this.firstCreditCardAmount(),

                    'cc_two_cc_cid': this.secondCreditCardVerificationNumber(),
                    'cc_two_cc_type': this.secondCreditCardType(),
                    'cc_two_cc_exp_year': this.secondCreditCardExpYear(),
                    'cc_two_cc_exp_month': this.secondCreditCardExpMonth(),
                    'cc_two_cc_number': this.pagseguroPaymentSecondCreditCardNumber(),
                    'cc_two_cc_owner': this.secondCreditCardOwner(),
                    'cc_two_cc_save': this.secondCreditCardSave(),
                    'cc_two_cc_id': this.secondCreditCardId(),
                    'cc_two_installments': this.secondCreditCardInstallments(),
                    'cc_two_cc_encrypted': this.secondCreditCardEncrypted,
                    'cc_two_cc_amount': this.secondCreditCardAmount()
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
                this.encryptCard();
            }

            return ($form.validation() && $form.validation('isValid'));
        },

        /**
         * @returns {boolean|*}
         */
        retrieveInstallmentsUrl: function() {
            try {
                this.installmentsUrl = window.checkoutConfig.payment.ccform.urls[this.getCode()].retrieve_installments;
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
            self.firstCards.removeAll();
            self.secondCards.removeAll();
            new Promise((resolve) => {
                fetch(self.retrieveCardsUrl(), {
                    method: 'GET',
                    cache: 'no-cache',
                    headers: {'Content-Type': 'application/json'}
                }).then((response) => {
                    return response.json();
                }).then(json => {
                    json.forEach(function(card) {
                        self.firstCards.push(card);
                        self.secondCards.push(card);
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
            self.showLoadingInstallment(true)

            new Promise((resolve) => {
                fetch(self.retrieveInstallmentsUrl(), {
                    method: 'POST',
                    cache: 'no-cache',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({'amount': self.firstCreditCardAmount()})
                }).then((response) => {
                    self.cardOneInstallments.removeAll();
                    return response.json();
                }).then(json => {
                    json.forEach(function(installment) {
                        self.cardOneInstallments.push(installment);
                        self.cardOneHasInstallments(true);
                    });
                });
            });

            new Promise((resolve) => {
                fetch(self.retrieveInstallmentsUrl(), {
                    method: 'POST',
                    cache: 'no-cache',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({'amount': self.secondCreditCardAmount()})
                }).then((response) => {
                    self.cardTwoInstallments.removeAll();
                    return response.json();
                }).then(json => {
                    json.forEach(function(installment) {
                        self.cardTwoInstallments.push(installment);
                        self.cardTwoHasInstallments(true);
                    });
                    self.showLoadingInstallment(false)
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

        minInstallmentValue: function() {
            return Number(window.checkoutConfig.payment.ccform.minInstallment[this.getCode()]);
        },

        grandTotal: function() {
            cartCache.set('totals',null);
            defaultTotal.estimateTotals();

            var totals = quote.getTotals()();

            let grandTotal = totals ? Number(totals['grand_total']) : Number(quote['grand_total']);
            let pagseguroInterest = totals ? this.getSegmentTotal(totals) : 0;

            if (pagseguroInterest) {
                grandTotal -= Number(pagseguroInterest);
            }

            return grandTotal;
        },

        getSegmentTotal: function (totals) {
            let pagseguroInterest = 0;
            let sumTotals = 0;

            let discountSegments = totals['total_segments'].filter(function (segment) {
                return segment.code.indexOf('pagseguropayment_interest') !== -1;
            });

            pagseguroInterest = discountSegments.length ? Number(discountSegments[0].value) : 0;

            if (!pagseguroInterest && totals['total_segments'].length < 5) {
                for (let key in totals['total_segments']) {
                    sumTotals += totals['total_segments'][key]['code'] !== 'grand_total' ? totals['total_segments'][key]['value'] : 0
                }
            }

            return pagseguroInterest ? pagseguroInterest : totals['grand_total'] - sumTotals;
        },

        /**
         * Get credit card details
         * @returns {Array}
         */
        getInfo: function () {
            return [
                {
                    'name': 'Credit Card Type', value: this.getCcTypeTitleByCode(this.firstCreditCardType())
                },
                {
                    'name': 'Credit Card Number', value: this.formatDisplayCcNumber(this.pagseguroPaymentFirstCreditCardNumber())
                }
            ];
        },

        encryptCard: function () {
            let firstCardEncrypt = PagSeguro.encryptCard({
                publicKey: window.checkoutConfig.payment.ccform.publicKey[this.getCode()],
                holder: this.firstCreditCardOwner(),
                number: this.pagseguroPaymentFirstCreditCardNumber(),
                expMonth: this.firstCreditCardExpMonth(),
                expYear: this.firstCreditCardExpYear(),
                securityCode: this.firstCreditCardVerificationNumber()
            });
            this.firstCreditCardEncrypted = firstCardEncrypt.encryptedCard;

            let secondCardEncrypt = PagSeguro.encryptCard({
                publicKey: window.checkoutConfig.payment.ccform.publicKey[this.getCode()],
                holder: this.secondCreditCardOwner(),
                number: this.pagseguroPaymentSecondCreditCardNumber(),
                expMonth: this.secondCreditCardExpMonth(),
                expYear: this.secondCreditCardExpYear(),
                securityCode: this.secondCreditCardVerificationNumber()
            });
            this.secondCreditCardEncrypted = secondCardEncrypt.encryptedCard;

        },

        updateTwoCardAmount: function (amount, field) {
            let focusedCardAmountElement = field === 'card_one' ? this.firstCreditCardAmount : this.secondCreditCardAmount;
            let secondCardAmountElement = field === 'card_one' ? this.secondCreditCardAmount : this.firstCreditCardAmount;

            let focusedCardAmount = Number(focusedCardAmountElement());

            if (focusedCardAmount < this.minInstallmentValue()) {
                focusedCardAmount = this.minInstallmentValue();
            }

            let grandTotal = this.grandTotal();
            let maxValue = grandTotal - this.minInstallmentValue();

            if (focusedCardAmountElement() > maxValue) {
                focusedCardAmount = maxValue;
            }

            focusedCardAmount !== Number(focusedCardAmountElement()) ? focusedCardAmountElement(focusedCardAmount.toFixed(2)) : false;

            let secondCardAmount = Number(grandTotal - focusedCardAmountElement());
            secondCardAmount !== Number(secondCardAmountElement()) ? secondCardAmountElement(secondCardAmount.toFixed(2)) : false;

            this.updateInstallmentsValues()
        },
    });
}
);
