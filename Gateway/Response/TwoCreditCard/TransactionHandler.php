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

use Magento\Checkout\Model\Session;
use PagSeguro\Payment\Helper\Order as HelperOrder;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Response\HandlerInterface;

class TransactionHandler implements HandlerInterface
{
    /**
     * @var HelperOrder
     */
    private $helperOrder;

    /**
     * @var Session
     */
    private $checkoutSession;

    /**
     * TransactionHandler constructor.
     * @param HelperOrder $helperOrder
     */
    public function __construct(
        HelperOrder $helperOrder,
        Session $checkoutSession
    ) {
        $this->helperOrder = $helperOrder;
        $this->checkoutSession = $checkoutSession;
    }

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
        $this->checkoutSession->unsetData('first_pagseguropayment_installments');
        $this->checkoutSession->unsetData('second_pagseguropayment_installments');

        if (!isset($handlingSubject['payment'])
            || !$handlingSubject['payment'] instanceof PaymentDataObjectInterface
        ) {
            throw new \InvalidArgumentException('Payment data object should be provided');
        }

        /** @var PaymentDataObjectInterface $paymentData */
        $paymentData = $handlingSubject['payment'];
        if (isset($response['transaction']['charges'])) {
            $transaction = $response['transaction']['charges'][0];
        } else {
            $transaction = $response['transaction'];
        }

        if (isset($transaction['error_messages']) || $transaction['status'] > 301) {
            throw new LocalizedException(__('There was an error processing your request.'));
        }

        /** @var $payment \Magento\Sales\Model\Order\Payment */
        $payment = $paymentData->getPayment();

        if (isset($transaction['id'])) {
            $payment->setAdditionalInformation('id', $transaction['id']);
            $payment->setCcTransId($transaction['id']);
            $payment->setLastTransId($transaction['id']);
            $payment->setTransactionId($transaction['id']);
            $payment->setAdditionalInformation('ordered_amount', $transaction['amount']['value']);
        }

        if (isset($transaction['payment_method'])) {
            foreach ($transaction['payment_method'] as $key => $value) {
                if (is_string($value)) {
                    $infoKey = sprintf('payment_method_%s', $key);
                    $payment->setAdditionalInformation($infoKey, $value);
                }
            }
        }

        $payment = $this->helperOrder->updateAdditionalInfo($payment, $transaction);

        $payment->setIsTransactionClosed(false);
    }
}
