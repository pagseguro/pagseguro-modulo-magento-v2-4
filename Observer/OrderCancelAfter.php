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

use PagSeguro\Payment\Gateway\Http\Client\Api;
use PagSeguro\Payment\Helper\Data;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;

class OrderCancelAfter implements ObserverInterface
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
     * OrderCancelOrder constructor.
     * @param Api $api
     * @param Data $helper
     */
    public function __construct(
        Api $api,
        Data $helper
    )
    {
        $this->api = $api;
        $this->helper = $helper;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return OrderCancelAfter
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        try {
            /* @var \Magento\Sales\Model\Order $order */
            $order = $observer->getEvent()->getData('order');

            /** @var \Magento\Sales\Model\Order\Payment $payment */
            $payment = $order->getPayment();

            $paymentMethod = $payment->getMethod();
            if (
                $this->helper->getConfig('refund_on_cancel', $paymentMethod)
                && $payment->getAdditionalInformation('captured')
            ) {
                $amount = $payment->getAdditionalInformation('captured_amount');
                $refundAmount = $amount - $payment->getAdditionalInformation('refunded_amount');

                if ($refundAmount) {
                    $value = new \stdClass();
                    $value->value = $refundAmount;

                    $request = new \stdClass();
                    $request->amount = $value;

                    $this->api->transaction()->cancelCharge($payment->getAdditionalInformation('id'), $request);
                }
            }
        } catch (LocalizedException $e) {
            $this->helper->getLogger()->critical($e->getMessage());
        }

        return $this;
    }
}

