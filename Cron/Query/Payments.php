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

namespace PagSeguro\Payment\Cron\Query;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\DataObject;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use PagSeguro\Payment\Helper\Data as HelperData;
use PagSeguro\Payment\Helper\Order as HelperOrder;
use PagSeguro\Payment\Gateway\Http\Client\Api;
use PagSeguro\Payment\Helper\TwoCardOrder as HelperTwoCardOrder;

class Payments extends DataObject
{
    /**
     * @var HelperData
     */
    protected $helper;

    /**
     * @var HelperOrder
     */
    protected $helperOrder;

    /**
     * @var Api
     */
    protected $api;

    /**
     * @var CollectionFactory
     */
    protected $orderCollectionFactory;

    /**
     * @var HelperTwoCardOrder
     */
    protected $helperTwoCardOrder;


    /**
     * @var ScopeConfigInterface
     */
    protected $storeConfig;

    /**
     * Payments constructor.
     * @param CollectionFactory $orderCollectionFactory
     * @param ScopeConfigInterface $storeConfig
     * @param HelperData $helper
     * @param HelperOrder $helperOrder
     * @param Api $api
     * @param array $data
     */
    public function __construct(
        CollectionFactory $orderCollectionFactory,
        ScopeConfigInterface $storeConfig,
        HelperData $helper,
        HelperOrder $helperOrder,
        HelperTwoCardOrder $helperTwoCardOrder,
        Api $api,
        array $data = []
    )
    {
        parent::__construct($data);
        $this->helper = $helper;
        $this->helperOrder = $helperOrder;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->helperTwoCardOrder = $helperTwoCardOrder;
        $this->storeConfig = $storeConfig;
        $this->api = $api;
    }

    public function execute()
    {
        $this->helper->log('STARTING CRON - Update Order.');

        try {
            $orderCollection = $this->orderCollectionFactory->create()
                ->addFieldToSelect('*')
                ->join(
                    ['payment' => 'sales_order_payment'],
                    'main_table.entity_id = payment.parent_id',
                    ['payment_method' => 'payment.method']
                )
                ->addFieldToFilter('state', ['nin' => $this->helper->getFinalStates()])
                ->addFieldToFilter('payment.method', ['in' => $this->helper->getAllowedMethods()]);

            /** @var \Magento\Sales\Model\Order $order */
            foreach ($orderCollection as $order) {
                /** @var \Magento\Sales\Model\Order\Payment $payment */
                $payment = $order->getPayment();
                $pagseguroId = $payment->getAdditionalInformation('id');
                $response = $this->api->transaction()->consultCharge($pagseguroId);
                $statusCode = $response['status'];
                if ($statusCode === 200) {
                    $transaction = $response['response'];
                    $status = $transaction['status'];
                    if ($payment->getMethod() === \PagSeguro\Payment\Model\TwoCreditCard\Ui\ConfigProvider::CODE) {
                        $this->helperTwoCardOrder->updateOrder($order, $status, $transaction);
                    } else {
                        $this->helperOrder->updateOrder($order, $status, $transaction);
                    }
                }
            }
        } catch (\Exception $e) {
            $this->helper->log('ERROR CRON Query Payments.');
            $this->helper->log($e->getMessage());
        }
    }
}
