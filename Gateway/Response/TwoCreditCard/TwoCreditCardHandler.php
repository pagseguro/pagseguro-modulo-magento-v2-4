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
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Response\HandlerInterface;
use PagSeguro\Payment\Gateway\Http\Client\Api;
use PagSeguro\Payment\Gateway\Http\Client\Api\Transaction;
use PagSeguro\Payment\Helper\Card as HelperCard;
use PagSeguro\Payment\Helper\TwoCard as HelperTwoCard;

class TwoCreditCardHandler implements HandlerInterface
{
    /**
     * @var HelperCard
     */
    private $helperCard;

    /**
     * @var HelperTwoCard
     */
    private $helperTwoCard;

    /**
     * @var Api
     */
    protected $api;

    /**
     * @var Session
     */
    private $checkoutSession;

    /**
     * TwoCreditCardHandler constructor.
     * @param HelperCard $helperCard
     * @param HelperTwoCard $helperTwoCard
     * @param Api $api
     * @param Session $checkoutSession
     */
    public function __construct(
        HelperCard $helperCard,
        HelperTwoCard $helperTwoCard,
        Api $api,
        Session $checkoutSession
    ) {
        $this->helperCard = $helperCard;
        $this->helperTwoCard = $helperTwoCard;
        $this->api = $api;
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
        $this->api->logRequest('PRIMEIRA TRANSAÇÃO');
        $this->api->logRequest($transaction);

        /** @var $payment \Magento\Sales\Model\Order\Payment */
        $payment = $paymentData->getPayment();
        $this->api->logRequest($payment);

        if (isset($transaction['status']) && $transaction['status'] !== Api::STATUS_PAID) {
            $message = $transaction['payment_response']['message'];
            throw new LocalizedException(__($message));
        }

        if (isset($transaction['status']) && $transaction['status'] === Api::STATUS_PAID) {
            $secondCcResponse = $this->helperTwoCard->secondCardRequest($payment);

            if (isset($secondCcResponse['transaction']['charges'])) {
                $secondCcTransaction = $secondCcResponse['transaction']['charges'][0];
            } else {
                $secondCcTransaction = $secondCcResponse['transaction'];
            }
            $this->api->logRequest('SEGUNDA TRANSAÇÃO');
            $this->api->logRequest($secondCcTransaction);

            if (isset($secondCcTransaction['error_messages']) || isset($secondCcTransaction['status']) && $secondCcTransaction['status'] !== Api::STATUS_DECLINED) {
                $canceledTransaction = $this->api->transaction()->cancelCharge(
                    $transaction['id'],
                    $this->getAmountData($transaction['amount']['summary']['total'])
                );

                $this->api->logRequest($this->getAmountData($transaction['amount']['summary']['total']));
                $this->api->logResponse($canceledTransaction);

                throw new LocalizedException(__('The transaction for second card was not authorized, check your credit card data and try again'));
            } else {
                $this->setSecondCcAdditionalInformation($payment, $secondCcTransaction);
                $this->setSecondCardInformation($payment, $secondCcTransaction['payment_method']['card']);
            }

            if ($transaction['status'] === Api::STATUS_PAID && $secondCcTransaction['status'] === Api::STATUS_PAID) {
                $payment->setAdditionalInformation('status', Api::STATUS_PAID);
            }

        }

        if (isset($transaction['id'])) {
            $this->setFirstCcAdditionalInformation($payment, $transaction);
        }

        if (isset($transaction['payment_method']) && isset($transaction['payment_method']['card'])) {
            $this->setCardInformation($payment, $transaction['payment_method']['card']);
        }

    }

    /**
     * @param $payment
     * @param $response
     */
    protected function setFirstCcAdditionalInformation($payment, $response)
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

        if (isset($response['status'])) {
            $payment->setAdditionalInformation('first_cc_status', $response['status']);
        }

        if (isset($response['amount']['value'])) {
            $payment->setAdditionalInformation('first_cc_amount_with_interest', $response['amount']['value'] / HelperTwoCard::ROUND_FACTOR);
        }
    }

    /**
     * @param $payment
     * @param $response
     */
    protected function setSecondCcAdditionalInformation($payment, $response)
    {
        if (isset($response['id'])) {
            $tid = $response['id'];
            $payment->setAdditionalInformation('second_cc_tid', $tid);
            $payment->setAdditionalInformation('second_cc_authorizer_id', $tid);
        }

        if (isset($response['payment_response'])) {
            $authCode = $response['payment_response']['reference'];
            $payment->setAdditionalInformation('second_cc_nsu', $authCode);
            $payment->setAdditionalInformation('second_cc_authorization_nsu', $authCode);
        }

        if (isset($response['status'])) {
            $payment->setAdditionalInformation('second_cc_status', $response['status']);
        }

        if (isset($response['amount']['value'])) {
            $payment->setAdditionalInformation('second_cc_amount_with_interest', $response['amount']['value'] / HelperTwoCard::ROUND_FACTOR);
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
            $payment->setAdditionalInformation('first_card_id', $card['id']);
        }

        if (isset($card['brand'])) {
            $payment->setCcType($card['brand']);
        }

        if (isset($card['store']) && $card['store']) {
            $this->helperCard->createCard($card['id'], $payment);
        }
    }

    /**
     * @param $payment
     * @param $card
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    protected function setSecondCardInformation($payment, $card)
    {
        if (isset($card['id'])) {
            $payment->setAdditionalInformation('second_cc_id', $card['id']);
        }

        if (isset($card['brand'])) {
            $payment->setAdditionalInformation('second_cc_type', $card['brand']);
        }

        if (isset($card['store']) && $card['store']) {
            $this->helperCard->createSecondCard($card['id'], $payment);
        }
    }

    /**
     * @param $amount
     * @return \stdClass
     */
    protected function getAmountData($amount)
    {
        $value = new \stdClass();
        $value->value = (int) round($amount);

        $request = new \stdClass();
        $request->amount = $value;
        return $request;
    }
}
