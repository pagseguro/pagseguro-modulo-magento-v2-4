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

class Pix extends AbstractInfo
{
    /**
     * @var  \Magento\Framework\Stdlib\DateTime\DateTime
     */
    private $date;

    /**
     * @var PriceCurrencyInterface
     */
    private $priceCurrency;

    /**
     * Pix constructor.
     * @param Context $context
     * @param ConfigInterface $config
     * @param Config $paymentConfig
     * @param PriceCurrencyInterface $priceCurrency
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $date
     * @param array $data
     */
    public function __construct(
        Context $context,
        ConfigInterface $config,
        Config $paymentConfig,
        PriceCurrencyInterface $priceCurrency,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        array $data = []
    ) {
        parent::__construct($context, $config, $paymentConfig, $data);
        $this->date = $date;
        $this->priceCurrency = $priceCurrency;
    }

    /**
     * @param \Magento\Framework\DataObject|array|null $transport
     * @return \Magento\Framework\DataObject
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _prepareSpecificInformation($transport = null)
    {
        $payment = $this->getInfo();

        $pixPrintOptions = [];
        if ($payment->getAdditionalInformation('print_options')) {
            foreach ($payment->getAdditionalInformation('print_options') as $key => $printOption) {
                $pixPrintOptions[] = sprintf(__('Print <a href="%s" target="_blank">' . $key . '</a>'), $printOption);
            }
        }

        $body = [
            (string)__('TID') => $payment->getAdditionalInformation('id'),
            (string)__('Pix') => implode(' | ', $pixPrintOptions),
            (string)__('Linha Digitável') => $payment->getAdditionalInformation('linhadigitavel'),
            (string)__('Expiration Date') => date_format(date_create($payment->getAdditionalInformation('expiration_date')), 'd-m-Y H:i:s'),
        ];

        $transport = new DataObject($body);
        $transport = parent::_prepareSpecificInformation($transport);

        return $transport;
    }
}
