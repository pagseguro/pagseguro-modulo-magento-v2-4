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

namespace PagSeguro\Payment\Gateway\Response;

use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Response\HandlerInterface;
use PagSeguro\Payment\Gateway\Http\Client\Api;
use PagSeguro\Payment\Helper\Card as HelperCard;

class OneCreditCardHandler implements HandlerInterface
{
    /**
     * @var HelperCard
     */
    private $helperCard;

    public function __construct(HelperCard $helperCard)
    {
        $this->helperCard = $helperCard;
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
        if (!isset($handlingSubject['payment'])
            || !$handlingSubject['payment'] instanceof PaymentDataObjectInterface
        ) {
            throw new \InvalidArgumentException('Payment data object should be provided');
        }

        /** @var PaymentDataObjectInterface $paymentData */
        $paymentData = $handlingSubject['payment'];
        $transaction = $response['transaction'];

        /** @var $payment \Magento\Sales\Model\Order\Payment */
        $payment = $paymentData->getPayment();

        if (isset($transaction['status']) && $transaction['status'] == Api::STATUS_DECLINED) {
            throw new LocalizedException(__('The transaction was not authorized, check your credit card data and try again'));
        }

        if (isset($transaction['id'])) {
            $this->setAuthInformation($payment, $transaction);
        }

        if (isset($transaction['payment_method']) && isset($transaction['payment_method']['card'])) {
            $this->setCardInformation($payment, $transaction['payment_method']['card']);
        }
    }

    /**
     * @param $payment
     * @param $response
     */
    protected function setAuthInformation($payment, $response)
    {
        if (isset($response['id'])) {
            $tid = $response['id'];
            $payment->setAdditionalInformation('tid', $tid);
            $payment->setAdditionalInformation('authorizer_id', $tid);
            $payment->setTransactionAdditionalInfo('tid', $tid);
            $payment->setTransactionAdditionalInfo('authorizer_id', $tid);
            $payment->setLastTransId($tid);
        }

        if (isset($response['payment_response'])) {
            $authCode = $response['payment_response']['reference'];
            $payment->setAdditionalInformation('nsu', $authCode);
            $payment->setAdditionalInformation('authorization_nsu', $authCode);
            $payment->setTransactionAdditionalInfo('nsu', $authCode);
            $payment->setTransactionAdditionalInfo('authorization_nsu', $authCode);
        }
    }

    /**
     * @param $payment
     * @param $card
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    protected function setCardInformation($payment, $card)
    {
        if (isset($card['id'])) {
            $payment->setAdditionalInformation('card_id', $card['id']);
        }

        if (isset($card['brand'])) {
            $payment->setCcType($card['brand']);
        }

        if (isset($card['store']) && $card['store']) {
            $this->helperCard->createCard($card['id'], $payment);
        }
    }
}
