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

namespace PagSeguro\Payment\Block\Info;

use Magento\Framework\DataObject;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\View\Element\Template\Context;
use Magento\Payment\Gateway\ConfigInterface;
use Magento\Payment\Model\Config;
use PagSeguro\Payment\Helper\Card as HelperCard;

class OneCreditCard extends AbstractInfo
{
    /**
     * @var PriceCurrencyInterface
     */
    protected $priceCurrency;

    /**
     * @var HelperCard
     */
    protected $helperCard;

    /**
     * CreditCard constructor.
     * @param Context $context
     * @param ConfigInterface $config
     * @param Config $paymentConfig
     * @param HelperCard $helperCard
     * @param array $data
     */
    public function __construct(
        Context $context,
        ConfigInterface $config,
        Config $paymentConfig,
        PriceCurrencyInterface $priceCurrency,
        HelperCard $helperCard,
        array $data = []
    ) {
        parent::__construct($context, $config, $paymentConfig, $data);
        $this->priceCurrency = $priceCurrency;
        $this->helperCard = $helperCard;
    }

    /**
     * @param \Magento\Framework\DataObject|array|null $transport
     * @return \Magento\Framework\DataObject
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _prepareSpecificInformation($transport = null)
    {
        $installments = $this->getInfo()->getAdditionalInformation('installments');

        /** @var \Magento\Sales\Model\Order $order */
        $order = $this->getInfo()->getOrder();
        $installmentValue = $order->getGrandTotal() / $installments;

        if ($savedCard = $this->getInfo()->getAdditionalInformation('card_id')) {
            $cardInfo = $this->getSavedCardInfo($savedCard, $order->getCustomerId());
            if ($cardInfo->getId()) {
                $this->getInfo()->setCcLast4($cardInfo->getCcLast4());
                $this->getInfo()->setCcType($cardInfo->getCcType());
            }
        }

        $body = [
            (string)__('TID') => $this->getInfo()->getAdditionalInformation('id'),
            (string)__('Credit Card Type') => $this->getCcTypeName(),
            (string)__('Credit Card Owner') => $this->getInfo()->getCcOwner() ?: 'N/A',
            (string)__('Credit Card Number') => sprintf('xxxx-xxxx-xxxx-%s', $this->getInfo()->getCcLast4()),
            (string)__('Installments') => sprintf('%s x of %s', $installments, $this->priceCurrency->format($installmentValue, false))
        ];

        $transport = new DataObject($body);

        $transport = parent::_prepareSpecificInformation($transport);
        return $transport;
    }

    /**
     * Retrieve credit card type name
     *
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getCcTypeName()
    {
        $types = $this->paymentConfig->getCcTypes();
        $ccType = $this->getInfo()->getCcType();
        if (isset($types[$ccType])) {
            return $types[$ccType];
        }
        return empty($ccType) ? __('N/A') : __(ucwords($ccType));
    }

    /**
     * @param $cardId
     * @param $customerId
     * @return null
     */
    private function getSavedCardInfo($cardId, $customerId)
    {
        $cardInfo = null;
        if ($cardId) {
            $cardInfo = $this->helperCard->getCardByToken($cardId, $customerId);
        }

        return $cardInfo;
    }
}
