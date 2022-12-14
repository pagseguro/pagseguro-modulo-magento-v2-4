<?php
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

namespace PagSeguro\Payment\Observer;

use Magento\Payment\Model\MethodInterface;
use Magento\Sales\Model\Order\Invoice;
use PagSeguro\Payment\Gateway\Http\Client\Api;
use Magento\Checkout\Model\Session;
use PagSeguro\Payment\Helper\Data;
use PagSeguro\Payment\Helper\Order as HelperOrder;
use Magento\Framework\Event\ObserverInterface;

class CheckoutSubmitAllAfter implements ObserverInterface
{
    /**
     * @var Api
     */
    protected $api;

    /**
     * @var Data
     */
    private $helper;

    /**
     * @var HelperOrder
     */
    protected $helperOrder;

    /**
     * @var Session
     */
    protected $checkoutSession;

    /**
     * OrderCancelOrder constructor.
     * @param Api $api
     * @param HelperOrder $helperOrder
     * @param Data $helper
     */
    public function __construct(
        Api $api,
        HelperOrder $helperOrder,
        Data $helper
    ) {
        $this->api = $api;
        $this->helperOrder = $helperOrder;
        $this->helper = $helper;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return CheckoutSubmitAllAfter
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        try {
            /* @var \Magento\Sales\Model\Order $order */
            $order = $observer->getEvent()->getData('order');

            /** @var \Magento\Sales\Model\Order\Payment $payment */
            $payment = $order->getPayment();

            if ($paymentAction = $this->helper->getConfig('payment_action', $payment->getMethod())) {
                if ($payment->getMethod() === \PagSeguro\Payment\Model\OneCreditCard\Ui\ConfigProvider::CODE) {
                    if ($paymentAction === MethodInterface::ACTION_ORDER) {
                        $status = $payment->getAdditionalInformation('status');
                        if ($status === Api::STATUS_PAID) {
                            $this->helperOrder->invoiceOrder($order, $payment,Invoice::CAPTURE_OFFLINE);
                        } else if ($status === Api::STATUS_CANCELED) {
                            $this->helperOrder->cancelOrder($order, $payment);
                        }
                    }
                }

                if ($payment->getMethod() === \PagSeguro\Payment\Model\TwoCreditCard\Ui\ConfigProvider::CODE) {
                    if ($paymentAction === MethodInterface::ACTION_ORDER) {
                        $firstCcStatus = $payment->getAdditionalInformation('first_cc_status');
                        $secondCcStatus = $payment->getAdditionalInformation('second_cc_status');

                        if ($firstCcStatus === Api::STATUS_PAID && $secondCcStatus === Api::STATUS_PAID) {
                            $this->helperOrder->invoiceOrder($order, $payment,Invoice::CAPTURE_OFFLINE);
                        } else if ($firstCcStatus === Api::STATUS_CANCELED || $secondCcStatus === Api::STATUS_CANCELED) {
                            $this->helperOrder->cancelOrder($order, $payment);
                        }
                    }
                }
            }

        } catch (\Exception $e) {
            $this->helper->getLogger()->critical($e->getMessage());
        }

        return $this;
    }
}

