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

namespace PagSeguro\Payment\Controller\Cards;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\ActionInterface;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use PagSeguro\Payment\Helper\Data as HelperData;
use PagSeguro\Payment\Model\ResourceModel\Card\CollectionFactory as CardCollectionFactory;

class Retrieve extends Action implements ActionInterface
{
    /**
     * @var HelperData
     */
    protected $helperData;

    /**
     * @var Json
     */
    protected $json;

    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var Session
     */
    protected $checkoutSession;

    /**
     * @var CardCollectionFactory
     */
    protected $cardCollectionFactory;

    /**
     * Retrieve constructor.
     * @param Context $context
     * @param Json $json
     * @param Session $checkoutSession
     * @param JsonFactory $resultJsonFactory
     * @param HelperData $helperData
     * @param CardCollectionFactory $cardCollectionFactory
     */
    public function __construct(
        Context $context,
        Json $json,
        Session $checkoutSession,
        JsonFactory $resultJsonFactory,
        HelperData $helperData,
        CardCollectionFactory $cardCollectionFactory
    ) {
        $this->json = $json;
        $this->checkoutSession = $checkoutSession;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->helperData = $helperData;
        $this->cardCollectionFactory = $cardCollectionFactory;

        return parent::__construct($context);
    }

    public function execute()
    {
        $result = $this->resultJsonFactory->create();
        $result->setHttpResponseCode(401);

        try{
            $cards = [];
            $customerId = $this->checkoutSession->getQuote()->getCustomerId();
            $collection = $this->cardCollectionFactory->create();
            $collection->addFieldToFilter('customer_id', $customerId);

            if ($collection->getSize()) {
                /** @var \PagSeguro\Payment\Model\Card $card */
                foreach ($collection as $card) {
                    $token = [
                        'id' => $card->getId(),
                        'cc_number' => sprintf('xxxx-xxxx-xxxx-%s', $card->getCcLast4()),
                        'cc_type' => $card->getCcType()
                    ];
                    $cards[] = $token;
                }
            }

            $result->setJsonData($this->json->serialize($cards));
            $result->setHttpResponseCode(200);
        } catch (\Exception $e) {
            $result->setHttpResponseCode(500);
        }

        return $result;
    }
}
