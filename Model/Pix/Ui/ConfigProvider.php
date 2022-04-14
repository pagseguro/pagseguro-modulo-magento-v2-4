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

namespace PagSeguro\Payment\Model\Pix\Ui;

use PagSeguro\Payment\Helper\Data;
use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Asset\Repository;
use Magento\Framework\App\Action\Context;
use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Payment\Model\MethodInterface;

/**
 * Class ConfigProvider
 */
final class ConfigProvider implements ConfigProviderInterface
{
    const CODE = 'pagseguropayment_pix';

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
     * @param Repository $assetRepo
     * @param RequestInterface $request
     * @param PaymentHelper $paymentHelper
     * @param Session $checkoutSession
     * @param Data $helper
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function __construct(
        Context $context,
        Repository $assetRepo,
        RequestInterface $request,
        PaymentHelper $paymentHelper,
        Session $checkoutSession,
        Data $helper
    ) {
        $this->context = $context;
        $this->assetRepo = $assetRepo;
        $this->request = $request;
        $this->methodInstance = $paymentHelper->getMethodInstance(self::CODE);
        $this->checkoutSession = $checkoutSession;
        $this->helper = $helper;
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
        return [
            'payment' => [
                self::CODE => [
                    'grand_total' => $this->checkoutSession->getQuote()->getGrandTotal(),
                    'checkout_instructions' =>  $this->helper->getConfig('checkout_instructions', self::CODE),
                ]
            ]
        ];
    }

    /**
     * Retrieve url of a view file
     *
     * @param string $fileIdModel/Ui/ConfigProvider.php
     * @param array $params
     * @return string
     */
    public function getViewFileUrl($fileId, array $params = [])
    {
        try {
            $params = array_merge(['_secure' => $this->request->isSecure()], $params);
            return $this->assetRepo->getUrlWithParams($fileId, $params);
        } catch (\Exception $e) {
            return '';
        }
    }
}
