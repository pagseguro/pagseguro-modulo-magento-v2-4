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

use PagSeguro\Payment\Helper\Data as HelperData;
use PagSeguro\Payment\Model\ResourceModel\Transaction\CollectionFactory as TransactionCollectionFactory;
use PagSeguro\Payment\Model\TransactionFactory;
use PagSeguro\Payment\Model\ResourceModel\Transaction as ResourceTransaction;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Exception\LocalizedException;

class Transaction extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var HelperData
     */
    protected $helperData;

    /**
     * @var TransactionCollectionFactory
     */
    protected $transactionCollectionFactory;

    /**
     * @var TransactionFactory
     */
    protected $transactionFactory;

    /**
     * @var ResourceTransaction
     */
    protected $resourceTransaction;

    /**
     * Order constructor.
     * @param Context $context
     * @param TransactionCollectionFactory $transactionCollectionFactory
     * @param TransactionFactory $transactionFactory
     * @param ResourceTransaction $resourceTransaction
     * @param Data $helperData
     */
    public function __construct(
        Context $context,
        TransactionCollectionFactory $transactionCollectionFactory,
        TransactionFactory $transactionFactory,
        ResourceTransaction $resourceTransaction,
        HelperData $helperData
    ) {
        parent::__construct($context);
        $this->helperData = $helperData;
        $this->transactionCollectionFactory = $transactionCollectionFactory;
        $this->transactionFactory = $transactionFactory;
        $this->resourceTransaction = $resourceTransaction;
    }

    /**
     * @param $orderId
     * @param $pagseguroId
     * @param $request
     * @param $response
     * @param $code
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    public function saveTransaction(
        $orderId,
        $pagseguroId,
        $request,
        $response,
        $code
    ) {
        $transaction = $this->transactionFactory->create();
        $transaction->setOrderId($orderId);
        $transaction->setPagSeguroId($pagseguroId);
        $transaction->setRequest($request);
        $transaction->setResponse($response);
        $transaction->setCode($code);

        $this->resourceTransaction->save($transaction);
    }
}
