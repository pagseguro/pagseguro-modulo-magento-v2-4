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

namespace PagSeguro\Payment\Helper;

use Magento\Payment\Model\MethodInterface;
use PagSeguro\Payment\Gateway\Http\Client\Transaction;
use PagSeguro\Payment\Gateway\Http\TransferFactory;
use PagSeguro\Payment\Helper\Data as HelperData;
use PagSeguro\Payment\Helper\Installments as HelperInstallment;

class TwoCard extends \Magento\Payment\Helper\Data
{
    const ROUND_FACTOR = 100;

    /**
     * @var HelperData
     */
    protected $helperData;

    /**
     * @var Transaction
     */
    protected $transaction;

    /**
     * @var TransferFactory
     */
    protected $transferFactory;

    /**
     * @var \Magento\Sales\Model\Order\Payment
     */
    protected $payment;

    /**
     * @var HelperInstallment
     */
    protected $helperInstallment;

    public function __construct(
        HelperData $helperData,
        TransferFactory $transferFactory,
        Transaction $transaction,
        HelperInstallment $helperInstallment
    ) {
        $this->helperData = $helperData;
        $this->transferFactory = $transferFactory;
        $this->transaction = $transaction;
        $this->helperInstallment = $helperInstallment;
    }

    /**
     * @param \Magento\Sales\Model\Order\Payment $payment
    */
    public function secondCardRequest($payment)
    {
        $this->payment = $payment;
        $request = $this->getChargeData();
        $transferObject = $this->transferFactory->create($request);
        return $this->transaction->placeRequest($transferObject);
    }

    /**
     * @return \stdClass[]
     */
    protected function getChargeData()
    {
        $amount = $this->payment->getAdditionalInformation('second_cc_amount');
        $amount = $this->getAmountWithInterest($this->payment->getAdditionalInformation('second_cc_installments'), $amount);

        $incrementId = $this->payment->getOrder()->getIncrementId();
        $chargeData = new \stdClass();
        $chargeData->reference_id = $incrementId;
        $chargeData->description = __(sprintf("Online Purchase - #%s", $incrementId));
        $chargeData->amount = $this->getChargeAmount($amount);
        $chargeData->payment_method = $this->getChargePaymentMethod();

        $chargeData->notification_urls = [
            $this->helperData->getUrlBuilder()->getUrl('pagseguropayment/notification/orders')
        ];

        return ['request' => $chargeData];
    }

    /**
     * @return \stdClass
     */
    protected function getChargePaymentMethod()
    {
        $paymentMethod = new \stdClass();
        $paymentMethod->type = 'CREDIT_CARD';
        $paymentMethod->installments = $this->payment->getAdditionalInformation("second_cc_installments");
        $paymentMethod->capture =  $this->helperData->getConfig('payment_action', $this->payment->getMethod()) == MethodInterface::ACTION_AUTHORIZE ? false : true;
        $paymentMethod->card = $this->getCardData();

        return $paymentMethod;
    }

    protected function getCardData()
    {
        $payment = $this->payment;

        $card = new \stdClass();

        $encrypted = $payment->getAdditionalInformation('second_cc_encrypted');
        if ($encrypted) {
            $card->encrypted = $encrypted;
            return $card;
        }

        $card->security_code = $payment->getAdditionalInformation('second_cc_cid');

        if ($token = $payment->getAdditionalInformation('second_cc_id')) {
            $card->id = $token;
            return $card;
        }

        $card->number = $payment->getAdditionalInformation('second_cc_number');
        $card->exp_month = str_pad( $payment->getAdditionalInformation('second_cc_exp_month'), 2, '0', STR_PAD_LEFT);
        $card->exp_year = $payment->getAdditionalInformation('second_cc_exp_year');

        $saveCard = $payment->getAdditionalInformation('second_cc_save');
        if ($saveCard) {
            $card->store = true;
        }

        $holder = new \stdClass();
        $holder->name = $payment->getAdditionalInformation('second_cc_owner');
        $card->holder = $holder;

        return $card;
    }

    /**
     * @param $amount
     * @return \stdClass
     */
    protected function getChargeAmount($amount)
    {
        $chargeAmount = new \stdClass();
        $chargeAmount->value = (int)($amount * self::ROUND_FACTOR);
        $chargeAmount->currency = "BRL";
        return $chargeAmount;
    }

    /**
     * @param $installment
     * @param $amount
     * @return float|int|mixed
     * @throws \Exception
     */
    protected function getAmountWithInterest($installment, $amount)
    {
        if ($installment > 1) {
            $installmentAmount = $this->helperInstallment->getInstallmentPrice(
                $amount,
                $installment,
                null,
                \PagSeguro\Payment\Model\TwoCreditCard\Ui\ConfigProvider::CODE
            );

            $amount = $installmentAmount * $installment;
        }

        return $amount;
    }
}
