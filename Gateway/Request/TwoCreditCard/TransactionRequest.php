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

namespace PagSeguro\Payment\Gateway\Request\TwoCreditCard;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Payment\Model\MethodInterface;
use PagSeguro\Payment\Helper\Data;
use PagSeguro\Payment\Helper\Installments as HelperInstallment;
use PagSeguro\Payment\Gateway\Http\Client\Api;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Payment\Gateway\ConfigInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;

class TransactionRequest implements BuilderInterface
{
    /**
     * @var ConfigInterface
     */
    protected $config;

    /**
     * @var DateTime
     */
    protected $date;

    /**
     * @var Data
     */
    protected $helper;

    /**
     * @var Context
     */
    protected $context;

    /**
     * @var Api
     */
    protected $api;

    /**
     * Event manager
     *
     * @var ManagerInterface
     */
    protected $eventManager;

    /**
     * @var CustomerRepositoryInterface
     */
    protected $customer;

    /**
     * @var \Magento\Sales\Model\Order $order
     */
    protected $order;

    /**
     * @var HelperInstallment
     */
    protected $helperInstallment;

    /**
     * @param Context $context
     * @param Data $helper
     * @param DateTime $date
     * @param ConfigInterface $config
     * @param Api $api
     * @param ManagerInterface $eventManager
     * @param CustomerRepositoryInterface $customer
     * @param HelperInstallment $helperInstallment
     */
    public function __construct(
        Context $context,
        Data $helper,
        DateTime $date,
        ConfigInterface $config,
        Api $api,
        ManagerInterface $eventManager,
        CustomerRepositoryInterface $customer,
        HelperInstallment $helperInstallment
    )
    {
        $this->context = $context;
        $this->helper = $helper;
        $this->date = $date;
        $this->config = $config;
        $this->api = $api;
        $this->eventManager = $eventManager;
        $this->customer = $customer;
        $this->helperInstallment = $helperInstallment;
    }

    /**
     * @param $order
     */
    protected function setOrder($order)
    {
        $this->order = $order;
    }

    /**
     * @return \Magento\Sales\Model\Order
     */
    protected function getOrder()
    {
        return $this->order;
    }

    /**
     * Builds ENV request
     *
     * @param array $buildSubject
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function build(array $buildSubject)
    {
        if (!isset($buildSubject['payment'])
            || !$buildSubject['payment'] instanceof PaymentDataObjectInterface
        ) {
            throw new \InvalidArgumentException('Payment data object should be provided');
        }

        /** @var \Magento\Sales\Model\Order\Payment $payment */
        $payment = $buildSubject['payment']->getPayment();

        /** @var \Magento\Sales\Model\Order $order */
        $order = $payment->getOrder();
        $this->setOrder($order);

        $amount = $payment->getAdditionalInformation('first_cc_amount');
        $amount = $this->getAmountWithInterest($payment->getAdditionalInformation('first_cc_installments'), $amount);

        $request = new \stdClass();
        $request->reference_id = $this->getOrder()->getIncrementId();
        $request->description = __(sprintf("Online Purchase - #%s", $this->getOrder()->getIncrementId()));
        $request->amount = $this->getChargeAmount($amount);
        $request->notification_urls = [
            $this->helper->getUrlBuilder()->getUrl('pagseguropayment/notification/orders')
        ];

        // Payment Method Data
        $request = $this->getPaymentData($request, $payment);

        return ['request' => $request];
    }

    /**
     * @param \stdClass $request
     * @param \Magento\Sales\Model\Order\Payment $payment
     * @param string $buyerId
     *
     * @return \stdClass
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function getPaymentData($request, $payment)
    {
        $method = $payment->getMethod();
        $paymentMethod = new \stdClass();

        $paymentMethod->type = 'CREDIT_CARD';
        $paymentMethod->installments = $payment->getAdditionalInformation('first_cc_installments');
        $paymentMethod->capture = $this->helper->getConfig('payment_action', $method) == MethodInterface::ACTION_AUTHORIZE ? false : true;
        $paymentMethod->card = $this->getCardData($request, $payment);

        $request->payment_method = $paymentMethod;
        return $request;
    }

    /**
     * @param $request
     * @param \Magento\Sales\Model\Order\Payment $payment
     * @return \stdClass
     * @throws LocalizedException
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    protected function getCardData($request, $payment)
    {
        $card = new \stdClass();

        $encrypted = $payment->getAdditionalInformation('first_cc_encrypted');
        if ($encrypted) {
            $card->encrypted = $encrypted;
            return $card;
        }

        $card->security_code = $payment->getCcCid();

        if ($token = $payment->getAdditionalInformation('first_cc_id')) {
            $card->id = $token;
            return $card;
        }

        $card->number = $payment->getCcNumber();
        $card->exp_month = str_pad($payment->getCcExpMonth(), 2, '0', STR_PAD_LEFT);
        $card->exp_year = $payment->getCcExpYear();

        $saveCard = $payment->getAdditionalInformation('first_cc_save');
        if ($saveCard) {
            $card->store = true;
        }

        $holder = new \stdClass();
        $holder->name = $payment->getCcOwner();
        $card->holder = $holder;

        return $card;
    }

    /**
     * @param $amount
     * @return \stdClass
     */
    protected function getChargeAmount($amount)
    {
        $amount = (int)round($amount * Data::ROUND_FACTOR);

        $chargeAmount = new \stdClass();
        $chargeAmount->value = $amount;
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
