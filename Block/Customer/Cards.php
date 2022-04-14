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

namespace PagSeguro\Payment\Block\Customer;

use PagSeguro\Payment\Model\ResourceModel\Card\CollectionFactory;
use PagSeguro\Payment\Helper\Data;
use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\View\Element\Template;
use Magento\Customer\Model\Session;

/**
 * Class CreditCards
 *
 * @api
 * @since 100.2.0
 */
class Cards extends Template
{
    /**
     * @var CollectionFactory
     */
    protected $cardCollectionFactory;

    /**
     * @var Session
     */
    protected $customerSession;

    /**
     * @var FormKey
     */
    protected $formKey;

    /**
     * @var Data
     */
    protected $helper;

    /**
     * Payment Cards constructor.
     * @param Template\Context $context
     * @param CollectionFactory $cardCollectionFactory
     * @param Session $customerSession
     * @param FormKey $formKey
     * @param Data $helper
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        CollectionFactory $cardCollectionFactory,
        Session $customerSession,
        FormKey $formKey,
        Data $helper,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->helper = $helper;
        $this->formKey = $formKey;
        $this->customerSession = $customerSession;
        $this->cardCollectionFactory = $cardCollectionFactory;
    }

    /**
     * @return \PagSeguro\Payment\Model\ResourceModel\Card\Collection
     */
    public function getCards()
    {

        $customerId = $this->customerSession->getCustomerId();
        $cards = $this->cardCollectionFactory->create();
        $cards->addFieldToFilter('customer_id', $customerId);

        return $cards;
    }

    /**
     * @param \PagSeguro\Payment\Model\Card $card
     * @return string
     */
    public function getExpirationDate($card)
    {
        $month = str_pad($card->getCcExpMonth(), 2, 0, STR_PAD_LEFT);
        $year = $card->getCcExpYear();
        return $month . '/' . $year;
    }

    /**
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getFormKey()
    {
        return $this->formKey->getFormKey();
    }

    /**
     * @param $cardId
     * @param $formKey
     * @return mixed
     */
    public function getDeleteUrl($cardId, $formKey)
    {
        $params = ["id" => $cardId, 'form_key' => $formKey];
        return $this->helper->getUrl('pagseguropayment/cards/delete', $params);
    }
}
