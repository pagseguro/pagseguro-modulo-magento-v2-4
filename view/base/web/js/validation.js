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

/*global define*/
define([
    'jquery',
    'PagSeguro_Payment/js/model/credit-card-validation/credit-card-number-validator',
    'mage/translate',
    'validation'
], function ($, creditCardNumberValidator, $t) {
    'use strict';

    /**
     * Javascript object with credit card types
     * 0 - regexp for card number
     * 1 - regexp for cvn
     * 2 - check or not credit card number trough Luhn algorithm by
     */
    var creditCartTypes = {
        'SO': [
            new RegExp('^(6334[5-9]([0-9]{11}|[0-9]{13,14}))|(6767([0-9]{12}|[0-9]{14,15}))$'),
            new RegExp('^([0-9]{3}|[0-9]{4})?$'),
            true
        ],
        'SM': [
            new RegExp('(^(5[0678])[0-9]{11,18}$)|(^(6[^05])[0-9]{11,18}$)|' +
                '(^(601)[^1][0-9]{9,16}$)|(^(6011)[0-9]{9,11}$)|(^(6011)[0-9]{13,16}$)|' +
                '(^(65)[0-9]{11,13}$)|(^(65)[0-9]{15,18}$)|(^(49030)[2-9]([0-9]{10}$|[0-9]{12,13}$))|' +
                '(^(49033)[5-9]([0-9]{10}$|[0-9]{12,13}$))|(^(49110)[1-2]([0-9]{10}$|[0-9]{12,13}$))|' +
                '(^(49117)[4-9]([0-9]{10}$|[0-9]{12,13}$))|(^(49118)[0-2]([0-9]{10}$|[0-9]{12,13}$))|' +
                '(^(4936)([0-9]{12}$|[0-9]{14,15}$))'), new RegExp('^([0-9]{3}|[0-9]{4})?$'),
            true
        ],
        'VI': [new RegExp('^4[0-9]{12}([0-9]{3})?$'), new RegExp('^[0-9]{3}$'), true],
        'MC': [
            new RegExp('^(?:5[1-5][0-9]{2}|222[1-9]|22[3-9][0-9]|2[3-6][0-9]{2}|27[01][0-9]|2720)[0-9]{12}$'),
            new RegExp('^[0-9]{3}$'),
            true
        ],
        'AE': [new RegExp('^3[47][0-9]{13}$'), new RegExp('^[0-9]{4}$'), true],
        'AU': [new RegExp('^5078$'), new RegExp('^[0-9]{4}$'), true],
        'DI': [new RegExp('^(6011(0|[2-4]|74|7[7-9]|8[6-9]|9)|6(4[4-9]|5))\\d*$'), new RegExp('^[0-9]{3}$'), true],
        'JCB': [new RegExp('^35(2[8-9]|[3-8])\\d*$'), new RegExp('^[0-9]{3}$'), true],
        'DN': [new RegExp('^(3(0[0-5]|095|6|[8-9]))\\d*$'), new RegExp('^[0-9]{3}$'), true],
        'UN': [
            new RegExp('^(622(1(2[6-9]|[3-9])|[3-8]|9([[0-1]|2[0-5]))|62[4-6]|628([2-8]))\\d*?$'),
            new RegExp('^[0-9]{3}$'),
            true
        ],
        'MI': [new RegExp('^(5018|5020|5038|5893|6304|6759|6761|6762|6763)\\d*$'), new RegExp('^[0-9]{3}$'), true],
        'MD': [new RegExp('^6759(?!24|38|40|6[3-9]|70|76)|676770|676774\\d*$'), new RegExp('^[0-9]{3}$'), true],
        'HC': [new RegExp('^((606282)|(637095)|(637568)|(637599)|(637609)|(637612))\\d*$'), new RegExp('^[0-9]{3}$'), true],
        'ELO': [new RegExp('^((509091)|(636368)|(636297)|(504175)|(438935)|(40117[8-9])|(45763[1-2])|(6505)|' +
            '(457393)|(431274)|(50990[0-2])|(5099[7-9][0-9])|(50996[4-9])|(509[1-8][0-9][0-9])|' +
            '(5090(0[0-2]|0[4-9]|1[2-9]|[24589][0-9]|3[1-9]|6[0-46-9]|7[0-24-9]))|' +
            '(5067(0[0-24-8]|1[0-24-9]|2[014-9]|3[0-379]|4[0-9]|5[0-3]|6[0-5]|7[0-8]))|' +
            '(6504(0[5-9]|1[0-9]|2[0-9]|3[0-9]))|' +
            '(6504(8[5-9]|9[0-9])|6505(0[0-9]|1[0-9]|2[0-9]|3[0-8]))|' +
            '(6505(4[1-9]|5[0-9]|6[0-9]|7[0-9]|8[0-9]|9[0-8]))|' +
            '(6507(0[0-9]|1[0-8]))|(65072[0-7])|(6509(0[1-9]|1[0-9]|20))|' +
            '(6516(5[2-9]|6[0-9]|7[0-9]))|(6550(0[0-9]|1[0-9]))|' +
            '(6550(2[1-9]|3[0-9]|4[0-9]|5[0-8])))\\d*$'), new RegExp('^[0-9]{3}$'), true]
    };

    $.validator.addMethod(
        'validate-pagseguro-cc-type',
        function (value, element, params) {
            var ccType;

            if (value && params) {
                ccType = $(params).val();
                value = value.replace(/\s/g, '').replace(/\-/g, '');

                if (creditCartTypes[ccType] && creditCartTypes[ccType][0]) {
                    return creditCartTypes[ccType][0].test(value);
                } else if (creditCartTypes[ccType] && !creditCartTypes[ccType][0]) {
                    return true;
                }
            }

            return false;
        },
        $t('Credit card number does not match credit card type.')
    );

    $.validator.addMethod(
        'validate-pagseguro-card-type',
        function (number, item, allowedTypes) {
            var cardInfo,
                i,
                l;

            if (!creditCardNumberValidator(number).isValid) {
                return false;
            }

            cardInfo = creditCardNumberValidator(number).card;

            for (i = 0, l = allowedTypes.length; i < l; i++) {
                if (cardInfo.title == allowedTypes[i].type) { //eslint-disable-line eqeqeq
                    return true;
                }
            }

            return false;
        },
        $t('Please enter a valid credit card type number.')
    );

    $.validator.addMethod(
        'validate-pagseguro-card-number',
        function (number) {
            return creditCardNumberValidator(number).isValid;
        },
        $t('Please enter a valid credit card type number.')
    );
});
