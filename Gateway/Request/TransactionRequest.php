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

namespace PagSeguro\Payment\Gateway\Request;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Payment\Model\MethodInterface;
use PagSeguro\Payment\Helper\Data;
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
     * @param Context $context
     * @param Data $helper
     * @param DateTime $date
     * @param ConfigInterface $config
     * @param Api $api
     * @param ManagerInterface $eventManager
     * @param CustomerRepositoryInterface $customer
     */
    public function __construct(
        Context $context,
        Data $helper,
        DateTime $date,
        ConfigInterface $config,
        Api $api,
        ManagerInterface $eventManager,
        CustomerRepositoryInterface $customer
    )
    {
        $this->context = $context;
        $this->helper = $helper;
        $this->date = $date;
        $this->config = $config;
        $this->api = $api;
        $this->eventManager = $eventManager;
        $this->customer = $customer;
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

        /** @var \Magento\Sales\Model\Order\Payment\Interceptor $payment */
        $payment = $buildSubject['payment']->getPayment();

        /** @var \Magento\Sales\Model\Order $order */
        $order = $payment->getOrder();

        $this->setOrder($order);

        $amount = (int)round($buildSubject['amount'] * Data::ROUND_FACTOR);

        $request = new \stdClass();

        $method = $payment->getMethod();
        $request->reference_id = $this->getOrder()->getIncrementId();
        $request->customer = $this->getCustomerData();
        $request->items = $this->getItemsData($amount);

        if ($method == \PagSeguro\Payment\Model\Pix\Ui\ConfigProvider::CODE) {
        $request->qr_codes = $this->getQRCodesData($amount, $payment);
        } else {
            $request->charges = [$this->getChargeData($request, $payment, $amount)];
        }

        $request->notification_urls = [
            $this->helper->getUrlBuilder()->getUrl('pagseguropayment/notification/order')
        ];

        return ['request' => $request];
    }

    /**
     * @param \stdClass $request
     * @param \Magento\Sales\Model\Order\Payment\Interceptor $payment
     * @param float $amount
     * @param string $buyerId
     *
     * @return \stdClass
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function getChargeData($request, $payment, $amount)
    {
        $charge = new \stdClass();
        $charge->reference_id = $this->getOrder()->getIncrementId();
        $charge->description = __(sprintf("Online Purchase - #%s", $this->getOrder()->getIncrementId()));
        $charge->amount = $this->getChargeAmount($amount);
        // Payment Method Data
        $charge->payment_method = $this->getPaymentData($request, $payment, $amount);
        $charge->notification_urls = [
            $this->helper->getUrlBuilder()->getUrl('pagseguropayment/notification/order')
        ];
        return $charge;
    }

    /**
     * @param \stdClass $request
     * @param \Magento\Sales\Model\Order\Payment\Interceptor $payment
     * @param float $amount
     * @param string $buyerId
     *
     * @return \stdClass
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function getPaymentData($request, $payment, $amount)
    {
        $method = $payment->getMethod();

        $paymentMethod = new \stdClass();
        if ($method == \PagSeguro\Payment\Model\Ticket\Ui\ConfigProvider::CODE) {

            $paymentMethod->type = 'BOLETO';
            $paymentMethod->boleto = $this->ticketData($payment);

        } else if ($method == \PagSeguro\Payment\Model\OneCreditCard\Ui\ConfigProvider::CODE) {

            $paymentMethod->type = 'CREDIT_CARD';
            $paymentMethod->installments = $payment->getAdditionalInformation('installments');
            $paymentMethod->capture = $this->helper->getConfig('payment_action', $method) == MethodInterface::ACTION_AUTHORIZE ? false : true;
            $paymentMethod->card = $this->getCardData($request, $payment);

        }

        return $paymentMethod;
    }

    /**
     * @param $request
     * @param \Magento\Sales\Model\Order\Payment\Interceptor $payment
     * @return \stdClass
     * @throws LocalizedException
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    protected function getCardData($request, $payment)
    {
        $card = new \stdClass();

        $encrypted = $payment->getAdditionalInformation('cc_encrypted');
        if ($encrypted) {
            $card->encrypted = $encrypted;
            return $card;
        }
    }

    /**
     * @param $amount
     * @return \stdClass
     */
    protected function getChargeAmount($amount)
    {
        $chargeAmount = new \stdClass();
        $chargeAmount->value = $amount;
        $chargeAmount->currency = "BRL";
        return $chargeAmount;
    }

    /**
     * @param \Magento\Sales\Model\Order\Payment\Interceptor $payment
     * @return object|boolean
     * @throws
     */
    protected function ticketData($payment)
    {
        $expirationDays = $payment->getMethodInstance()->getConfigData('expiration_days') ?: 1;
        $date = $this->date->gmtDate('Y-m-d', strtotime(sprintf('+ %d days', $expirationDays)));

        $ticket = new \stdClass();
        $ticket->due_date = $date;
        $ticket->holder = $this->getHolderData();
        $ticket->instruction_lines = $this->getInstructionLines();

        return $ticket;
    }

    /**
     * @return \stdClass
     */
    protected function getInstructionLines()
    {
        $instructionLines = new \stdClass();
        $instructionLines->line_1 = $this->helper->getConfig('line_one', 'pagseguropayment_ticket');
        $instructionLines->line_2 = $this->helper->getConfig('line_two', 'pagseguropayment_ticket');
        return $instructionLines;
    }

    /**
     * @return \stdClass
     */
    protected function getHolderData()
    {
        $taxvat = $this->getOrder()->getCustomerTaxvat() ?? $this->getCustomerTaxvat();
        $holder = new \stdClass();
        $holder->tax_id = $this->helper->digits($taxvat);
        $holder->name = $this->getCustomerName($holder->tax_id);
        $holder->email = $this->getOrder()->getCustomerEmail();
        $holder->address = $this->getHolderAddress();
        return $holder;
    }

    /**
     * @return \stdClass
     */
    protected function getCustomerData()
    {
        $taxvat = $this->getOrder()->getCustomerTaxvat() ?? $this->getCustomerTaxvat();
        $customer = new \stdClass();
        $customer->tax_id = $this->helper->digits($taxvat);
        $customer->name = $this->getCustomerName($customer->tax_id);
        $customer->email = $this->getOrder()->getCustomerEmail();
        return $customer;
    }

    /**
     * @return \stdClass
     */
    protected function getItemsData($amount)
    {
        $items = new \stdClass();
        $items->name = __(sprintf("Online Purchase - #%s", $this->getOrder()->getIncrementId()));;
        $items->quantity = 1;
        $items->unit_amount = str_replace('.', '', $amount);
        return [$items];
    }

    /**
     * @return \stdClass
     */
    protected function getQRCodesData($amount, $payment)
    {
        $qrCodes = new \stdClass();

        $qrCodes->amount = [
            'value' => $amount
        ];
        $expirationType = $payment->getMethodInstance()->getConfigData('expiration_type') ?: 'days';
        $expirationQty = $payment->getMethodInstance()->getConfigData('expiration_quantity') ?: 1;

        $qrCodes->expiration_date = $this->date->date('Y-m-d\TH:i:s-03:00', strtotime('+ ' . $expirationQty . ' ' . $expirationType));

        return [$qrCodes];
    }

    /**
     * @return \stdClass
     */
    protected function getHolderAddress()
    {
        $billingAddress = $this->getOrder()->getBillingAddress();

        $fullStreet = $billingAddress->getStreet();
        $street = $this->helper->getGeneralConfig('street', 'address');
        $streetNumber = $this->helper->getGeneralConfig('number', 'address');
        $neighborhood = $this->helper->getGeneralConfig('district', 'address');

        $address = new \stdClass();
        $address->country = $billingAddress->getCountryId();
        $address->region = $billingAddress->getRegion();
        $address->region_code = $billingAddress->getRegionCode();
        $address->city = $billingAddress->getCity();
        $address->postal_code = $this->helper->digits($billingAddress->getPostcode());
        $address->street = $this->helper->formattedString($fullStreet[$street] ?? 'N/A');
        $address->number = $fullStreet[$streetNumber] ?? 'N/A';
        $address->locality = $fullStreet[$neighborhood] ?? 'N/A';
        return $address;
    }

    /**
     * @return string|null
     * @throws LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function getCustomerTaxvat()
    {
        $customer = false;
        if ($customerId = $this->getOrder()->getCustomerId()) {
            $customer = $this->customer->getById($customerId);
            $customerTaxvat = $customer ? $customer->getTaxvat() : null;
        }
        return $customerTaxvat ?? $this->getOrder()->getBillingAddress()->getVatId();
    }

    /**
     * @param $customerTaxvat
     * @return mixed|string
     * @throws LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function getCustomerName($customerTaxvat)
    {
        $customerId = $this->getOrder()->getCustomerId();
        $companyAttributeCode = $this->helper->getGeneralConfig('company_attribute');

        if ($customerId && strlen($customerTaxvat) == 14) {
            /** @var \Magento\Customer\Model\Data\Customer $customerData */
            $customerData = $this->customer->getById($customerId);
            if ($customerData && $customerData->getId()) {
                $companyAttribute = $customerData->getCustomAttribute($companyAttributeCode);

                if ($companyAttribute) {
                    return $companyAttribute->getValue();
                }
            }
        }

        return $this->getOrder()->getCustomerName();
    }
}
