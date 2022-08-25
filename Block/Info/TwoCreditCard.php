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

class TwoCreditCard extends AbstractInfo
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
        $installments = $this->getInfo()->getAdditionalInformation('first_cc_installments');
        $secondCcInstallments = $this->getInfo()->getAdditionalInformation('second_cc_installments');

        $firstCcAmount = $this->getInfo()->getAdditionalInformation('first_cc_amount_with_interest');
        $secondCcAmount = $this->getInfo()->getAdditionalInformation('second_cc_amount_with_interest');

        $firstCcInstallmentValue = $firstCcAmount / $installments;
        $secondCcInstallmentValue = $secondCcAmount / $secondCcInstallments;

        if ($firstCcSaved = $this->getInfo()->getAdditionalInformation('first_cc_id')) {
            /** @var \Magento\Sales\Model\Order $order */
            $order = $this->getInfo()->getOrder();

            $cardInfo = $this->getSavedCardInfo($firstCcSaved, $order->getCustomerId());
            if ($cardInfo->getId()) {
                $this->getInfo()->setCcLast4($cardInfo->getCcLast4());
                $this->getInfo()->setCcType($cardInfo->getCcType());
            }
        }

        if ($secondCcSaved = $this->getInfo()->getAdditionalInformation('second_cc_id')) {
            /** @var \Magento\Sales\Model\Order $order */
            $order = $this->getInfo()->getOrder();

            $cardInfo = $this->getSavedCardInfo($secondCcSaved, $order->getCustomerId());
            if ($cardInfo->getId()) {
                $this->getInfo()->setAdditionalInformation('second_cc_last4', $cardInfo->getCcLast4());
            }
        }

        $body = [
            (string)__('First TID') => $this->getInfo()->getAdditionalInformation('id'),
            (string)__('First Credit Card Type') => $this->getCcTypeName(),
            (string)__('First Credit Card Owner') => $this->getInfo()->getCcOwner() ?: 'N/A',
            (string)__('First Credit Card Number') => sprintf('xxxx-xxxx-xxxx-%s', $this->getInfo()->getCcLast4()),
            (string)__('First Credit Card Installments') => sprintf(
                '%s x of %s', $installments,
                $this->priceCurrency->format($firstCcInstallmentValue, false)
            ),
            (string)__('Second TID') => $this->getInfo()->getAdditionalInformation('second_cc_tid'),
            (string)__('Second Credit Card Type') => $this->getInfo()->getAdditionalInformation('second_cc_type'),
            (string)__('Second Credit Card Owner') => $this->getInfo()->getAdditionalInformation('second_cc_owner') ?: 'N/A',
            (string)__('Second Credit Card Number') => sprintf('xxxx-xxxx-xxxx-%s', $this->getInfo()->getAdditionalInformation('second_cc_last4') ),
            (string)__('Second Credit Card Installments') => sprintf(
                '%s x of %s', $secondCcInstallments,
                $this->priceCurrency->format($secondCcInstallmentValue, false)
            )
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
