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

namespace PagSeguro\Payment\Model\ResourceModel;

use PagSeguro\Payment\Model\TransactionFactory;
use PagSeguro\Payment\Api\Data\TransactionInterfaceFactory;
use PagSeguro\Payment\Api\Data\TransactionSearchResultsInterfaceFactory;
use PagSeguro\Payment\Api\TransactionRepositoryInterface;
use PagSeguro\Payment\Model\ResourceModel\Transaction as ResourceTransaction;
use PagSeguro\Payment\Model\ResourceModel\Transaction\CollectionFactory as TransactionCollectionFactory;
use Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;

class TransactionRepository implements TransactionRepositoryInterface
{

    /** @var ResourceTransaction  */
    protected $resource;

    /** @var TransactionFactory  */
    protected $tokenFactory;

    /** @var TransactionCollectionFactory  */
    protected $tokenCollectionFactory;

    /** @var TransactionSearchResultsInterfaceFactory  */
    protected $searchResultsFactory;

    /** @var TransactionInterfaceFactory  */
    protected $dataTransactionFactory;

    /** @var JoinProcessorInterface  */
    protected $extensionAttributesJoinProcessor;

    /** @var StoreManagerInterface  */
    private $storeManager;

    /** @var CollectionProcessorInterface  */
    private $collectionProcessor;

    /**
     * @param ResourceTransaction $resource
     * @param TransactionFactory $tokenFactory
     * @param TransactionInterfaceFactory $dataTransactionFactory
     * @param TransactionCollectionFactory $tokenCollectionFactory
     * @param TransactionSearchResultsInterfaceFactory $searchResultsFactory
     * @param StoreManagerInterface $storeManager
     * @param CollectionProcessorInterface $collectionProcessor
     * @param JoinProcessorInterface $extensionAttributesJoinProcessor
     */
    public function __construct(
        ResourceTransaction $resource,
        TransactionFactory $tokenFactory,
        TransactionInterfaceFactory $dataTransactionFactory,
        TransactionCollectionFactory $tokenCollectionFactory,
        TransactionSearchResultsInterfaceFactory $searchResultsFactory,
        StoreManagerInterface $storeManager,
        CollectionProcessorInterface $collectionProcessor,
        JoinProcessorInterface $extensionAttributesJoinProcessor
    )
    {
        $this->resource = $resource;
        $this->tokenFactory = $tokenFactory;
        $this->tokenCollectionFactory = $tokenCollectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->dataTransactionFactory = $dataTransactionFactory;
        $this->storeManager = $storeManager;
        $this->collectionProcessor = $collectionProcessor;
        $this->extensionAttributesJoinProcessor = $extensionAttributesJoinProcessor;
    }

    /**
     * {@inheritdoc}
     */
    public function save(
        \PagSeguro\Payment\Api\Data\TransactionInterface $transaction
    ) {
        try {
            $transaction = $this->resource->save($transaction);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__(
                'Could not save the transaction info: %1',
                $exception->getMessage()
            ));
        }
        return $transaction;
    }

    /**
     * {@inheritdoc}
     * @throws NoSuchEntityException
     */
    public function getById($tokenId)
    {
        $token = $this->tokenFactory->create();
        $this->resource->load($token, $tokenId);
        if (!$token->getId()) {
            throw new NoSuchEntityException(__('Transaction with id "%1" does not exist.', $tokenId));
        }
        return $token;
    }

    /**
     * {@inheritdoc}
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $criteria
    )
    {
        $collection = $this->tokenCollectionFactory->create();

        $this->extensionAttributesJoinProcessor->process(
            $collection,
            \PagSeguro\Payment\Api\Data\TransactionInterface::class
        );

        $this->collectionProcessor->process($criteria, $collection);

        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($criteria);

        $items = [];
        foreach ($collection as $model) {
            $items[] = $model;
        }

        $searchResults->setItems($items);
        $searchResults->setTotalCount($collection->getSize());
        return $searchResults;
    }

    /**
     * {@inheritdoc}
     */
    public function delete(
        \PagSeguro\Payment\Api\Data\TransactionInterface $token
    )
    {
        try {
            $tokenModel = $this->tokenFactory->create();
            $this->resource->load($tokenModel, $token->getEntityId());
            $this->resource->delete($tokenModel);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__(
                'Could not delete the Transaction: %1',
                $exception->getMessage()
            ));
        }
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteById($tokenId)
    {
        return $this->delete($this->getById($tokenId));
    }
}
