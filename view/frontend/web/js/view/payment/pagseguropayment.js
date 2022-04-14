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

define([
    'uiComponent',
    'Magento_Checkout/js/model/payment/renderer-list'
], function (
        Component,
        rendererList
) {
    'use strict';

    rendererList.push({
        type: 'pagseguropayment_one_cc',
        component: 'PagSeguro_Payment/js/view/payment/method-renderer/onecc'
    });

    rendererList.push({
        type: 'pagseguropayment_two_cc',
        component: 'PagSeguro_Payment/js/view/payment/method-renderer/twocc'
    });

    rendererList.push({
        type: 'pagseguropayment_ticket',
        component: 'PagSeguro_Payment/js/view/payment/method-renderer/ticket'
    });

    rendererList.push({
        type: 'pagseguropayment_pix',
        component: 'PagSeguro_Payment/js/view/payment/method-renderer/pix'
    });

    return Component.extend({});
});
