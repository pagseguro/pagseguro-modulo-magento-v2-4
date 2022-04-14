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

use PagSeguro\Payment\Model\CardFactory;
use PagSeguro\Payment\Api\Data\CardInterfaceFactory;
use PagSeguro\Payment\Api\Data\CardSearchResultsInterfaceFactory;
use PagSeguro\Payment\Api\CardRepositoryInterface;
use PagSeguro\Payment\Model\ResourceModel\Card as ResourceCard;
use PagSeguro\Payment\Model\ResourceModel\Card\CollectionFactory as CardCollectionFactory;
use Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;

class CardRepository implements CardRepositoryInterface
{

    /** @var ResourceCard  */
    protected $resource;

    /** @var CardFactory  */
    protected $cardFactory;

    /** @var CardCollectionFactory  */
    protected $cardCollectionFactory;

    /** @var CardSearchResultsInterfaceFactory  */
    protected $searchResultsFactory;

    /** @var CardInterfaceFactory  */
    protected $dataCardFactory;

    /** @var JoinProcessorInterface  */
    protected $extensionAttributesJoinProcessor;

    /** @var StoreManagerInterface  */
    private $storeManager;

    /** @var CollectionProcessorInterface  */
    private $collectionProcessor;

    /**
     * @param ResourceCard $resource
     * @param CardFactory $cardFactory
     * @param CardInterfaceFactory $dataCardFactory
     * @param CardCollectionFactory $cardCollectionFactory
     * @param CardSearchResultsInterfaceFactory $searchResultsFactory
     * @param StoreManagerInterface $storeManager
     * @param CollectionProcessorInterface $collectionProcessor
     * @param JoinProcessorInterface $extensionAttributesJoinProcessor
     */
    public function __construct(
        ResourceCard $resource,
        CardFactory $cardFactory,
        CardInterfaceFactory $dataCardFactory,
        CardCollectionFactory $cardCollectionFactory,
        CardSearchResultsInterfaceFactory $searchResultsFactory,
        StoreManagerInterface $storeManager,
        CollectionProcessorInterface $collectionProcessor,
        JoinProcessorInterface $extensionAttributesJoinProcessor
    )
    {
        $this->resource = $resource;
        $this->cardFactory = $cardFactory;
        $this->cardCollectionFactory = $cardCollectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->dataCardFactory = $dataCardFactory;
        $this->storeManager = $storeManager;
        $this->collectionProcessor = $collectionProcessor;
        $this->extensionAttributesJoinProcessor = $extensionAttributesJoinProcessor;
    }

    /**
     * {@inheritdoc}
     */
    public function save(
        \PagSeguro\Payment\Api\Data\CardInterface $card
    ) {
        try {
            $card = $this->resource->save($card);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__(
                'Could not save the card info: %1',
                $exception->getMessage()
            ));
        }
        return $card;
    }

    /**
     * {@inheritdoc}
     * @throws NoSuchEntityException
     */
    public function getById($cardId)
    {
        $card = $this->cardFactory->create();
        $this->resource->load($card, $cardId);
        if (!$card->getId()) {
            throw new NoSuchEntityException(__('Card with id "%1" does not exist.', $cardId));
        }
        return $card;
    }

    /**
     * {@inheritdoc}
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $criteria
    )
    {
        $collection = $this->cardCollectionFactory->create();

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
        \PagSeguro\Payment\Api\Data\CardInterface $card
    )
    {
        try {
            $cardModel = $this->cardFactory->create();
            $this->resource->load($cardModel, $card->getEntityId());
            $this->resource->delete($cardModel);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__(
                'Could not delete the Card: %1',
                $exception->getMessage()
            ));
        }
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteById($cardId)
    {
        return $this->delete($this->getById($cardId));
    }
}
