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

namespace PagSeguro\Payment\Model\OneCreditCard\Ui;

use PagSeguro\Payment\Helper\Data;
use PagSeguro\Payment\Helper\Installments;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Asset\Repository;
use Magento\Framework\App\Action\Context;
use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Payment\Model\MethodInterface;
use Magento\Payment\Model\Config;
use Magento\Payment\Model\CcGenericConfigProvider;
use Magento\Framework\UrlInterface;
use Magento\Payment\Model\CcConfig;

/**
 * Class ConfigProvider
 */
class ConfigProvider extends CcGenericConfigProvider
{
    const CODE = 'pagseguropayment_one_cc';

    /**
     * @var MethodInterface
     */
    protected $methodInstance;

    /**
     * @var Session
     */
    protected $checkoutSession;

    /**
     * @var Context
     */
    protected $context;

    /**
     * @var Config
     */
    protected $paymentConfig;

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @var Installments
     */
    protected $helperInstallments;

    /**
     * @var Data
     */
    protected $helper;

    /**
     * @var Repository
     */
    protected $assetRepo;

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @param Context $context
     * @param Session $checkoutSession
     * @param Data $helper
     * @param Config $paymentConfig
     * @param Installments $helperInstallments
     * @param CcConfig $ccConfig
     * @param UrlInterface $urlBuilder
     * @param PaymentHelper $paymentHelper
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function __construct(
        Context $context,
        Session $checkoutSession,
        Data $helper,
        Config $paymentConfig,
        CcConfig $ccConfig,
        UrlInterface $urlBuilder,
        PaymentHelper $paymentHelper
    ) {
        parent::__construct($ccConfig, $paymentHelper, [self::CODE]);
        $this->context = $context;
        $this->methodInstance = $paymentHelper->getMethodInstance(self::CODE);
        $this->checkoutSession = $checkoutSession;
        $this->helper = $helper;
        $this->paymentConfig = $paymentConfig;
        $this->urlBuilder = $urlBuilder;
    }

    /**
     * Retrieve assoc array of checkout configuration
     *
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getConfig()
    {
        $grandTotal = $this->checkoutSession->getQuote()->getGrandTotal();
        $methodCode = self::CODE;

        return [
            'payment' => [
                'ccform' => [
                    'grandTotal' => [$methodCode => $grandTotal],
                    'availableTypes' => [$methodCode => $this->getCcAvailableTypes($methodCode)],
                    'months' => [$methodCode => $this->getCcMonths()],
                    'years' => [$methodCode => $this->getCcYears()],
                    'hasVerification' => [$methodCode => $this->hasVerification($methodCode)],
                    'cvvImageUrl' => [$methodCode => $this->getCvvImageUrl()],
                    'canSave' => [$methodCode => false],
                    'canEncrypt' => true,
                    'publicKey' => [$methodCode => $this->helper->getGeneralConfig('public_key')],
                    'urls' => [
                        $methodCode => [
                            'manage_cards' => $this->urlBuilder->getUrl('pagseguropayment/cards/list'),
                            'retrieve_installments' => $this->urlBuilder->getUrl('pagseguropayment/installments/retrieve'),
                            'cards' => $this->urlBuilder->getUrl('pagseguropayment/cards/retrieve'),
                        ]
                    ]
                ]
            ]
        ];
    }
}
