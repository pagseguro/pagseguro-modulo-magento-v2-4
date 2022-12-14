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

namespace PagSeguro\Payment\Gateway\Response\TwoCreditCard;

use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Response\HandlerInterface;
use PagSeguro\Payment\Gateway\Http\Client\Api;

class RefundHandler implements HandlerInterface
{
    /**
     * Handles transaction id
     *
     * @param array $handlingSubject
     * @param array $response
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function handle(array $handlingSubject, array $response)
    {
        if (!isset($handlingSubject['payment'])
            || !$handlingSubject['payment'] instanceof PaymentDataObjectInterface
        ) {
            throw new \InvalidArgumentException('Payment data object should be provided');
        }

        /** @var PaymentDataObjectInterface $paymentData */
        $paymentData = $handlingSubject['payment'];
        $transaction = $response['transaction'];

        if (isset($transaction['error_messages'])) {
            throw new LocalizedException(__('There was an error with your payment data'));
        }

        /** @var $payment \Magento\Sales\Model\Order\Payment */
        $payment = $paymentData->getPayment();

        $status = Api::STATUS_CANCELED;
        if (
            $transaction['first_cc']['status'] === Api::STATUS_CANCELED
            || $transaction['second_cc']['status'] === Api::STATUS_CANCELED
        ) {
            $status = Api::STATUS_CANCELED;
        }

        unset($transaction['status']);
        $payment->setAdditionalInformation('status', $status);

        foreach ($transaction as $key => $charge) {
            if (isset($charge['status'])) {
                if ($charge['status'] === Api::STATUS_CANCELED) {
                    $payment->setAdditionalInformation($key . '_status', $charge['status']);
                    $payment->setAdditionalInformation($key . '_refunded', true);
                    $payment->setAdditionalInformation($key . '_refunded_amount', $charge['amount']['summary']['refunded']);
                    $payment->setAdditionalInformation($key . '_refunded_date', date('Y-m-d h:i:s'));
                    $payment->setShouldCloseParentTransaction(true);
                }
            }
        }

        $payment->setAdditionalInformation('voided', true);
    }
}
