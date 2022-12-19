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

namespace PagSeguro\Payment\Block\Adminhtml\Form\Field;

use PagSeguro\Payment\Gateway\Http\Client\Api;
use PagSeguro\Payment\Helper\Data as HelperData;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;

class OAuthCodeBackend extends \Magento\Framework\App\Config\Value
{

    /**
     * @var HelperData
     */
    private $helperData;

    /**
     * @var Api
     */
    private $api;

    /**
     * @var bool
     */
    private $hasError;

    private $urlInterface;

    /**
     * @var ManagerInterface
     */
    private $messageManager;

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\Config\ScopeConfigInterface $config,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        HelperData $helperData,
        Api $api,
        \Magento\Framework\UrlInterface $urlInterface,
        ManagerInterface $messageManager,
    ) {
        $this->urlInterface = $urlInterface;
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, []);
        $this->helperData = $helperData;
        $this->api = $api;
        $this->hasError = false;
        $this->messageManager = $messageManager;
    }

    public function beforeSave()
    {

        if ($this->getValue() === 'revoke') {

            $this->helperData->saveConfig('', 'code_verifier');
            $this->helperData->saveConfig('', 'token');

        } else {

            $token = $this->helperData->getConfig('token', 'general');

            if ($this->getValue() && !$token) {

                $value = explode('|', $this->getValue());

                $code = $value[0];

                $codeVerifier = $value[1];

                $redirectUrl = $this->urlInterface->getCurrentUrl();

                $redirectUrl = substr($redirectUrl, 0, strpos($redirectUrl, "?"));

                $redirectUrl = str_replace('/save', '/edit', $redirectUrl);

                $data = [
                    'grant_type'    => 'authorization_code',
                    'code'          => $code,
                    'redirect_uri'  => $redirectUrl . '?code_verifier=' . $codeVerifier,
                    'code_verifier' => $codeVerifier
                ];

                $this->helperData->log(print_r($data, true));

                $response = $this->api->oAuth()->getAccessToken($data);

                $this->helperData->log($response);


                if ($response['status'] < 200 || $response['status'] >= 300) {
                    $message = __('There was an error trying to validate your credential. Please check if your token is correct');
                    $this->messageManager->addErrorMessage($message);
                }

                if (isset($response['response']['access_token'])) {
                    $this->helperData->saveConfig($response['response']['access_token'], 'token');
                }

            }

        }



        parent::beforeSave();
    }
}