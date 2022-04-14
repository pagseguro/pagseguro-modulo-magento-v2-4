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

namespace PagSeguro\Payment\Observer;

use Magento\Framework\App\Cache\Type\Config;
use Magento\Framework\App\Config\ReinitableConfigInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\App\RequestInterface;
use PagSeguro\Payment\Gateway\Http\Client\Api;
use PagSeguro\Payment\Helper\Data as HelperData;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\Message\ManagerInterface;

class PaymentConfigSaveAfter implements ObserverInterface
{
    /**
     * @var Api
     */
    private $api;

    /**
     * @var HelperData
     */
    private $helper;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var TypeListInterface
     */
    private $cacheTypeList;

    /**
     * @var ReinitableConfigInterface
     */
    private $appConfig;

    /**
     * @var ManagerInterface
     */
    private $messageManager;

    /**
     * PaymentConfigSaveAfter constructor.
     *
     * @param Api $api
     * @param HelperData $helperData
     * @param TypeListInterface $cacheTypeList
     * @param ReinitableConfigInterface $config
     * @param ManagerInterface $messageManager
     * @param RequestInterface $request
     */
    public function __construct(
        Api $api,
        HelperData $helperData,
        TypeListInterface $cacheTypeList,
        ReinitableConfigInterface $config,
        ManagerInterface $messageManager,
        RequestInterface $request
    ) {
        $this->api = $api;
        $this->helper = $helperData;
        $this->request = $request;
        $this->appConfig = $config;
        $this->cacheTypeList = $cacheTypeList;
        $this->messageManager = $messageManager;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(Observer $observer)
    {
        $groups = $this->request->getParam('groups');
        if (!$groups || !isset($groups['pagseguropayment'])) {
            return;
        }

        //Clean cache to get new config data
        $this->cacheTypeList->cleanType(Config::TYPE_IDENTIFIER);
        $this->appConfig->reinit();

        //Only if it has API KEY saved
        if ($this->helper->getGeneralConfig('token')) {
            $this->createPublicKey();
        }

    }

    /**
     * Generate Public Key Used on Encrypted Card
     */
    protected function createPublicKey()
    {
        $token = $this->helper->getGeneralConfig('token');

        $response = $this->api->credentialAuthentication()->get($token);

        if ($response['status'] < 200 || $response['status'] >= 300) {
            $message = __('There was an error trying to validate your credential. Please check if your token is correct');
            $this->messageManager->addErrorMessage($message);
        }

        if (isset($response['response']['public_key'])) {
            $this->helper->saveConfig($response['response']['public_key'], 'public_key');
        }
    }
}
