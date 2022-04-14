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

use PagSeguro\Payment\Api\Data\CardInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Data\Form\FormKey\Validator;
use Magento\Framework\Exception\NotFoundException;
use PagSeguro\Payment\Api\CardRepositoryInterface;
use PagSeguro\Payment\Model\ResourceModel\Card\CollectionFactory;
use PagSeguro\Payment\Controller\CardsManagement;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */

class Delete extends CardsManagement
{
    /**
     * @var CardRepositoryInterface
     */
    private $cardRepository;

    /**
     * @var CollectionFactory
     */
    private $cardCollectionFactory;

    /**
     * @var Validator
     */
    private $fkValidator;

    /**
     * @param Context $context
     * @param Session $customerSession
     * @param Validator $fkValidator
     * @param CardRepositoryInterface $cardRepository
     * @param CollectionFactory $cardCollectionFactory
     */
    public function __construct(
        Context $context,
        Session $customerSession,
        Validator $fkValidator,
        CardRepositoryInterface $cardRepository,
        CollectionFactory $cardCollectionFactory
    ) {
        parent::__construct($context, $customerSession);
        $this->fkValidator = $fkValidator;
        $this->cardRepository = $cardRepository;
        $this->cardCollectionFactory = $cardCollectionFactory;

    }

    /**
     * Dispatch request
     *
     * @return ResultInterface|ResponseInterface
     * @throws NotFoundException
     */
    public function execute()
    {
        $request = $this->_request;
        if (!$request instanceof Http) {
            return $this->createErrorResponse(self::WRONG_REQUEST);
        }

        if (!$this->fkValidator->validate($request)) {
            return $this->createErrorResponse(self::WRONG_REQUEST);
        }

        $cardId = $request->getParam(self::ID_FIELD);
        if (!$this->verifyCard($cardId)) {
            return $this->createErrorResponse(self::WRONG_TOKEN);
        }

        try {
            $this->cardRepository->deleteById($cardId);
        } catch (\Exception $e) {
            return $this->createErrorResponse(self::ACTION_EXCEPTION);
        }

        return $this->createSuccessMessage();
    }

    /**collection = $this->to
     * @param int $errorCode
     * @return ResponseInterface
     */
    private function createErrorResponse($errorCode)
    {
        $this->messageManager->addErrorMessage(
            $this->errorsMap[$errorCode]
        );

        return $this->_redirect('pagseguropayment/cards/index');
    }

    /**
     * @return ResponseInterface
     */
    private function createSuccessMessage()
    {
        $this->messageManager->addSuccessMessage(
            __('Your card was successfully removed')
        );
        return $this->_redirect('pagseguropayment/cards/index');
    }

    /**
     * @param $cardId
     * @return bool
     */
    private function verifyCard($cardId)
    {
        $customerId = $this->customerSession->getCustomerId();
        $collection = $this->cardCollectionFactory->create();
        $collection->addFieldToFilter('customer_id', $customerId);
        $collection->addFieldToFilter('entity_id', $cardId);

        return ($collection->getSize() > 0);
    }

}
