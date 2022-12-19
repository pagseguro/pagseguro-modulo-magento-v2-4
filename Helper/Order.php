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

class Order extends \Magento\Payment\Helper\Data
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
        TransactionFactory $transactionFactory
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
            $currentStatus = $payment->getAdditionalInformation('status');

            $amountSummary = $transaction['amount']['summary'];

            $amount = $amountSummary['total'] ?? 0;
            $amountPaid = $amountSummary['paid'] ?? 0;
            $refundedAmount = $amountSummary['refunded'] ?? 0;

            $formattedAmount = $this->priceCurrency->format($amountPaid / Data::ROUND_FACTOR , false);
            $formattedRefunded = $this->priceCurrency->format($refundedAmount / Data::ROUND_FACTOR , false);

            if ($amount && in_array($payment->getMethod(), $this->helperData->getAllowedMethods())) {
                if ($status === Api::STATUS_PAID) {
                    if ($refundedAmount) {
                        $order->addCommentToStatusHistory(__('Refund of %1', $formattedRefunded));
                        $this->orderRepository->save($order);

                        $payment->setAdditionalInformation('amount_captured', $amountPaid);
                        $payment->setAdditionalInformation('amount_refunded', $refundedAmount);
                    } else {
                        $payment = $this->invoiceOrder($order, $payment, Invoice::CAPTURE_OFFLINE);
                        $payment->setAdditionalInformation('amount_captured', $amountPaid);
                    }
                } else if ($status === Api::STATUS_CANCELED) {
                    $payment = $this->cancelOrder($order, $payment);
                    $payment->setAdditionalInformation('amount_refunded', $refundedAmount);
                } else {
                    if ($currentStatus != $status) {
                        $order->addCommentToStatusHistory(
                            __('Order status automatically updated to %1, amount of %2', __($status), $formattedAmount)
                        );
                        $this->orderRepository->save($order);
                    }
                }

                if ($transaction) {
                    $payment = $this->updateAdditionalInfo($payment, $transaction);
                    $this->resourcePayment->save($payment);
                }

                return true;
            }
        } catch (\Exception $e) {
            $this->helperData->log('ERROR updateOrder.');
            $this->helperData->log($e->getMessage());
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
     * @return \Magento\Sales\Model\Order\Payment
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
        }

        return $payment;
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
