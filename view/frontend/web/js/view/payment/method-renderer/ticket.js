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

define(
    [
        'Magento_Checkout/js/view/payment/default'
    ],
    function (Component) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'PagSeguro_Payment/payment/form/ticket'
            },

            getCode: function() {
                return 'pagseguropayment_ticket';
            },

            /**
             * Get Ticket Instructions
             * @returns {String}
             */
            getInstructions: function() {
                return '<p>' + window.checkoutConfig.payment.pagseguropayment_ticket.checkout_instructions + '</p>';
            },

            getData: function() {
                return {
                    'method': this.item.method
                };
            }
        });
    }
);
