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
use PagSeguro\Payment\Model\ResourceModel\Card\CollectionFactory as CardCollectionFactory;
use PagSeguro\Payment\Model\CardFactory;
use PagSeguro\Payment\Model\ResourceModel\Card as ResourceCard;
use PagSeguro\Payment\Api\CardRepositoryInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Exception\LocalizedException;

class Card extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var HelperData
     */
    protected $helperData;

    /**
     * @var CardCollectionFactory
     */
    protected $cardCollectionFactory;

    /**
     * @var CardFactory
     */
    protected $cardFactory;

    /**
     * @var ResourceCard
     */
    protected $resourceCard;

    /**
     * @var CardRepositoryInterface
     */
    protected $cardRepository;


    /**
     * Order constructor.
     * @param Context $context
     * @param CardCollectionFactory $cardCollectionFactory
     * @param CardFactory $cardFactory
     * @param ResourceCard $resourceCard
     * @param CardRepositoryInterface $cardRepository
     * @param Data $helperData
     */
    public function __construct(
        Context $context,
        CardCollectionFactory $cardCollectionFactory,
        CardFactory $cardFactory,
        ResourceCard $resourceCard,
        CardRepositoryInterface $cardRepository,
        HelperData $helperData
    )
    {
        parent::__construct($context);
        $this->helperData = $helperData;
        $this->cardCollectionFactory = $cardCollectionFactory;
        $this->cardFactory = $cardFactory;
        $this->resourceCard = $resourceCard;
        $this->cardRepository = $cardRepository;
    }

    /**
     * @param $id
     * @return \PagSeguro\Payment\Api\Data\CardInterface
     * @throws LocalizedException
     */
    public function getCardById($id)
    {
        $cardObject = $this->cardRepository->getById($id);
        return $cardObject;
    }

    /**
     * @param $token
     * @param null $customerId
     * @return false
     */
    public function getCardByToken($token, $customerId = null)
    {
        $collection = $this->cardCollectionFactory->create();
        $collection->addFieldToFilter('token', $token);
        $customerId ?? $collection->addFieldToFilter('customer_id', $customerId);

        if ($collection->getSize()) {
            return $collection->getFirstItem();
        }

        return false;
    }

    /**
     * @param $token
     * @param \Magento\Sales\Model\Order\Payment $payment
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    public function createCard($token, $payment)
    {
        $order = $payment->getOrder();
        if ($order->getCustomerId() && $token) {
            $this->saveCard(
                $order->getCustomerId(),
                $token,
                $payment->getCcType(),
                $payment->getCcOwner(),
                $payment->getCcLast4(),
                $payment->getCcExpMonth(),
                $payment->getCcExpYear()
            );
        }
    }

    /**
     * @param $token
     * @param \Magento\Sales\Model\Order\Payment $payment
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    public function createSecondCard($token, $payment)
    {
        $order = $payment->getOrder();
        if ($order->getCustomerId() && $token) {
            $this->saveCard(
                $order->getCustomerId(),
                $token,
                $payment->getAdditionalInformation('second_cc_type'),
                $payment->getAdditionalInformation('second_cc_owner'),
                $payment->getAdditionalInformation('second_cc_last4'),
                $payment->getAdditionalInformation('second_cc_exp_month'),
                $payment->getAdditionalInformation('second_cc_exp_year')
            );
        }
    }

    /**
     * @param $customerId
     * @param $token
     * @param $ccType
     * @param $ccOwner
     * @param $ccLast4
     * @param $ccExpMonth
     * @param $ccExpYear
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    public function saveCard(
        $customerId,
        $token,
        $ccType,
        $ccOwner,
        $ccLast4,
        $ccExpMonth,
        $ccExpYear
    ) {
        $card = $this->cardFactory->create();
        $card->setCustomerId($customerId);
        $card->setToken($token);
        $card->setCcType($ccType);
        $card->setCcOwner($ccOwner);
        $card->setCcLast4($ccLast4);
        $card->setCcExpMonth($ccExpMonth);
        $card->setCcExpYear($ccExpYear);

        $this->resourceCard->save($card);
    }
}
