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

use _HumbugBoxe8a38a0636f4\Nette\Neon\Exception;
use PagSeguro\Payment\Gateway\Http\Client\Api;
use PagSeguro\Payment\Helper\Data as HelperData;
use Magento\Framework\App\Config\Initial;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\View\LayoutFactory;
use Magento\Payment\Model\Config;
use Magento\Sales\Model\Order\Invoice;
use Magento\Payment\Model\Method\Factory;
use Magento\Sales\Model\Order\CreditmemoFactory;
use Magento\Sales\Model\Order\InvoiceRepository;
use Magento\Framework\DB\TransactionFactory;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\OrderRepository;
use Magento\Sales\Model\Service\CreditmemoService;
use Magento\Store\Model\App\Emulation;
use Magento\Sales\Model\ResourceModel\Order\Payment as ResourcePayment;

class TwoCardOrder extends \Magento\Payment\Helper\Data
{
    /**
     * @var HelperData
     */
    protected $helperData;

    /**
     * @var OrderFactory
     */
    protected $orderFactory;

    /**
     * @var OrderFactory
     */
    protected $orderRepository;

    /**
     * @var InvoiceRepository
     */
    protected $invoiceRepository;

    /**
     * @var CreditmemoFactory
     */
    private $creditmemoFactory;

    /**
     * @var CreditmemoService
     */
    private $creditmemoService;

    /**
     * @var ResourcePayment
     */
    private $resourcePayment;

    /**
     * @var PriceCurrencyInterface
     */
    private $priceCurrency;

    /**
     * @var TransactionFactory
     */
    private $transactionFactory;

    /**
     * @var Api
     */
    private $api;

    /**
     * Order constructor.
     * @param Context $context
     * @param LayoutFactory $layoutFactory
     * @param Factory $paymentMethodFactory
     * @param Emulation $appEmulation
     * @param Config $paymentConfig
     * @param Initial $initialConfig
     * @param OrderFactory $orderFactory
     * @param CreditmemoFactory $creditmemoFactory
     * @param OrderRepository $orderRepository
     * @param InvoiceRepository $invoiceRepository
     * @param CreditmemoService $creditmemoService
     * @param ResourcePayment $resourcePayment
     * @param PriceCurrencyInterface $priceCurrency ,
     * @param Data $helperData
     * @param TransactionFactory $transactionFactory
     */
    public function __construct(
        Context $context,
        LayoutFactory $layoutFactory,
        Factory $paymentMethodFactory,
        Emulation $appEmulation,
        Config $paymentConfig,
        Initial $initialConfig,
        OrderFactory $orderFactory,
        CreditmemoFactory $creditmemoFactory,
        OrderRepository $orderRepository,
        InvoiceRepository $invoiceRepository,
        CreditmemoService $creditmemoService,
        ResourcePayment $resourcePayment,
        PriceCurrencyInterface $priceCurrency,
        HelperData $helperData,
        TransactionFactory $transactionFactory,
        Api $api
    )
    {
        parent::__construct($context, $layoutFactory, $paymentMethodFactory, $appEmulation, $paymentConfig, $initialConfig);
        $this->priceCurrency = $priceCurrency;
        $this->helperData = $helperData;
        $this->orderFactory = $orderFactory;
        $this->orderRepository = $orderRepository;
        $this->invoiceRepository = $invoiceRepository;
        $this->creditmemoFactory = $creditmemoFactory;
        $this->creditmemoService = $creditmemoService;
        $this->resourcePayment = $resourcePayment;
        $this->transactionFactory = $transactionFactory;
        $this->api = $api;
    }

    /**
     * Update Order Status
     * @param $order
     * @param $status
     * @param $transaction
     * @param false $callbackUpdate
     * @return bool
     */
    public function updateOrder($order, $status, $transaction, $callbackUpdate = false)
    {
        try {
            /** @var \Magento\Sales\Model\Order\Payment $payment */
            $payment = $order->getPayment();

            $secondTransaction = $this->getSecondChargeTransaction($payment, $transaction['id']);

            $currentFirstCcStatus = $payment->getAdditionalInformation('first_cc_status');
            $currentSecondCcStatus = $payment->getAdditionalInformation('second_cc_status');

            if (!$secondTransaction)
                throw new \Exception(__('There was an error trying to get second charge information'));

            $firstCcSummary = $transaction['amount']['summary'];
            $secondCcSummary = $secondTransaction['amount']['summary'];

            $firstCcAmountPaid = $firstCcSummary['paid'] ?? 0;
            $firstCcRefundedAmount = $firstCcSummary['refunded'] ?? 0;

            $secondCcAmountPaid = $secondCcSummary['paid'] ?? 0;
            $secondCcRefundedAmount = $secondCcSummary['refunded'] ?? 0;

            $firstCcStatus = $transaction['status'];
            $secondCcStatus = $secondTransaction['status'];

            if (in_array($payment->getMethod(), $this->helperData->getAllowedMethods())) {
                if ($firstCcStatus === Api::STATUS_PAID && $secondCcStatus === Api::STATUS_PAID) {

                    if ($this->invoiceOrder($order, $payment, Invoice::CAPTURE_OFFLINE)) {

                        $payment->setAdditionalInformation('first_cc_amount_captured', $firstCcAmountPaid);
                        $payment->setAdditionalInformation('second_cc_amount_captured', $secondCcAmountPaid);
                        $payment->setAdditionalInformation('amount_captured', $firstCcAmountPaid + $secondCcAmountPaid);
                        $order->addCommentToStatusHistory(sprintf(__('Charge %s - STATUS: %s'), $transaction['id'], $firstCcStatus));
                        $order->addCommentToStatusHistory(sprintf(__('Charge %s - STATUS: %s'), $secondTransaction['id'], $secondCcStatus));
                    }

                } else if (in_array($firstCcStatus, $this->api->deniedStatuses()) || in_array($secondCcStatus, $this->api->deniedStatuses())) {
                    $this->cancelOrder($order, $payment);
                    $payment->setAdditionalInformation('first_cc_amount_refunded', $firstCcRefundedAmount);
                    $payment->setAdditionalInformation('second_cc_amount_refunded', $secondCcRefundedAmount);
                    $payment->setAdditionalInformation('amount_refunded', $firstCcRefundedAmount + $secondCcRefundedAmount);
                } else {
                    if ($currentFirstCcStatus != $firstCcStatus) {
                        $formattedAmount = $this->priceCurrency->format($firstCcAmountPaid / Data::ROUND_FACTOR , false);

                        $order->addCommentToStatusHistory(
                            __('Charge %1 status automatically updated to %2, amount paid %3', $transaction['id'], __($firstCcStatus), $formattedAmount)
                        );
                        $this->orderRepository->save($order);
                    }

                    if ($currentSecondCcStatus != $secondCcStatus) {
                        $formattedAmount = $this->priceCurrency->format($secondCcAmountPaid / Data::ROUND_FACTOR , false);

                        $order->addCommentToStatusHistory(
                            __('Charge %1 status automatically updated to %2, amount paid %3', $secondTransaction['id'], __($secondCcStatus), $formattedAmount)
                        );
                        $this->orderRepository->save($order);
                    }
                }
            }
        } catch (\Exception $e) {
            $this->helperData->log('ERROR updateOrder.');
            $this->helperData->log($e->getMessage());
        }

        return false;
    }

    /**
     * @param \Magento\Sales\Model\Order\Payment $payment
     * @param $firstChargeId
     * @return false|mixed
     */
    protected function getSecondChargeTransaction($payment, $firstChargeId)
    {
        $firstCardTransactionId = $payment->getAdditionalInformation('tid');
        $secondCardTransactionId = $payment->getAdditionalInformation('second_cc_tid');

        $secondChargeId = $firstChargeId == $firstCardTransactionId ? $secondCardTransactionId : $firstCardTransactionId;
        $secondCharge = $this->api->transaction()->consultCharge($secondChargeId);
        if (!isset($secondCharge['error'])) {
            return $secondCharge['response'];
        }

        return false;
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @param \Magento\Sales\Model\Order\Payment $payment
     * @return \Magento\Sales\Model\Order\Payment
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function cancelOrder($order, $payment)
    {
        if ($order->canCreditmemo()) {
            $creditMemo = $this->creditmemoFactory->createByOrder($order);
            $this->creditmemoService->refund($creditMemo, true);

            $payment->setAdditionalInformation('refunded', true);
            $payment->setAdditionalInformation('refunded_date', date('Y-m-d h:i:s'));
        } else if ($order->canCancel()) {
            $order->cancel();
        }

        return $payment;
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @param \Magento\Sales\Model\Order\Payment $payment
     * @param string $capture
     * @return bool
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function invoiceOrder($order, $payment, $capture = Invoice::CAPTURE_ONLINE)
    {
        if ($order->canInvoice()) {
            /** @var \Magento\Sales\Model\Order\Invoice $invoice */
            $invoice = $order->prepareInvoice();
            $invoice->setRequestedCaptureCase($capture);
            $invoice->register();
            $invoice->pay();
            !$invoice->getTransactionId() ? $invoice->setTransactionId($payment->getLastTransId()) : null;
            $this->invoiceRepository->save($invoice);

            $payment->setAdditionalInformation('captured', true);
            $payment->setAdditionalInformation('captured_date', date('Y-m-d h:i:s'));

            $paidStatus = $this->helperData->getConfig('paid_order_status', $payment->getMethod()) ?: null;
            $order->addCommentToStatusHistory(__('The payment was automatically confirmed'), $paidStatus);
            $this->orderRepository->save($order);
            return true;
        }

        return false;
    }

    /**
     * @param \Magento\Sales\Model\Order\Payment $payment
     * @param $transaction
     * @return \Magento\Sales\Model\Order\Payment
     */
    public function updateAdditionalInfo($payment, $transaction)
    {
        try {
            if (isset($transaction['status']))
                $payment->setAdditionalInformation('status', $transaction['status']);

            $this->_eventManager->dispatch(
                'pagseguro_payment_update_additional_info',
                [
                    'payment' => $payment,
                    'transaction' => $transaction
                ]
            );

        } catch (\Exception $e) {
            $this->_logger->warning($e->getMessage());
        }

        return $payment;
    }

    /**
     * @param string $incrementId
     * @return \Magento\Sales\Model\Order
     */
    public function loadOrder($incrementId)
    {
        $order = $this->orderFactory->create();
        if ($incrementId) {
            $order->loadByIncrementId($incrementId);
        }

        return $order;
    }

}
