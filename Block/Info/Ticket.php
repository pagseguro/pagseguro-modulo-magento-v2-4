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

class Ticket extends AbstractInfo
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
     * Ticket constructor.
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

        $expirationDate = $this->date->date('d/m/Y', $payment->getAdditionalInformation('due_date'));

        $ticketPrintOptions = [];
        if ($payment->getAdditionalInformation('print_options')) {
            foreach ($payment->getAdditionalInformation('print_options') as $key => $printOption) {
                $ticketPrintOptions[] = sprintf(__('Print <a href="%s" target="_blank">' . $key . '</a>'), $printOption);
            }
        }


        $body = [
            (string)__('TID') => $payment->getAdditionalInformation('id'),
            (string)__('Ticket') => implode(' | ', $ticketPrintOptions),
            (string)__('Barcode') => $payment->getAdditionalInformation('barcode'),
            (string)__('Expiration Date') => $expirationDate
        ];

        $transport = new DataObject($body);
        $transport = parent::_prepareSpecificInformation($transport);

        return $transport;
    }
}
