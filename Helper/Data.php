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

namespace PagSeguro\Payment\Helper;

use Magento\Framework\App\Config\Initial;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\View\LayoutFactory;
use Magento\Payment\Model\Config;
use Magento\Payment\Model\Method\Factory;
use Magento\Store\Model\App\Emulation;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Serialize\Serializer\Json;
use PagSeguro\Payment\Logger\Logger;
use Magento\Framework\UrlInterface;
use Magento\Sales\Model\Order;
use \Magento\Framework\Math\Random;

/**
 * Class Data
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Data extends \Magento\Payment\Helper\Data
{
    const ROUND_FACTOR = 100;

    /**
     * @var Json
     */
    private $json;

    /**
     * PagSeguro Logging instance
     *
     * @var \PagSeguro\Payment\Logger\Logger
     */
    protected $logger;

    /**
     * @var WriterInterface
     */
    private $configWriter;

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @var Random
     */
    private $mathRandom;


    public function __construct(
        Context $context,
        LayoutFactory $layoutFactory,
        Factory $paymentMethodFactory,
        Emulation $appEmulation,
        Config $paymentConfig,
        Initial $initialConfig,
        Logger $logger,
        WriterInterface $configWriter,
        Json $json,
        Random $mathRandom
    ) {
        parent::__construct($context, $layoutFactory, $paymentMethodFactory, $appEmulation, $paymentConfig, $initialConfig);
        $this->urlBuilder = $context->getUrlBuilder();
        $this->logger = $logger;
        $this->configWriter = $configWriter;
        $this->json = $json;
        $this->mathRandom = $mathRandom;
    }

    /**
     * @param $config
     * @param string $group
     * @param string $section
     * @return mixed
     */
    public function getConfig($config, $group = 'payment', $section = 'pagseguropayment')
    {
        return $this->scopeConfig->getValue(
            $section . '/' . $group . '/' . $config,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @param $config
     * @param string $group
     * @return mixed
     */
    public function getGeneralConfig($config, $group = 'general')
    {
        return $this->getConfig($config, $group, 'pagseguropayment');
    }

    /**
     * @param string $config
     * @return mixed
     */
    public function getEndpointConfig($config)
    {
        return $this->getConfig($config, 'endpoints', 'pagseguropayment');
    }

    /**
     * @param $config
     * @param string $group
     * @return mixed
     */
    public function getApplicationConfig($config, $group = 'application')
    {
        return $this->getConfig($config, $group, 'pagseguropayment');
    }

    /**
     * @param string $value
     * @param string $config
     * @param string $group
     * @param string $section
     */
    public function saveConfig($value, $config, $group = 'general', $section = 'pagseguropayment')
    {
        $this->configWriter->save(
            $section . '/' . $group . '/' . $config,
            $value
        );
    }

    /**
     * @return array
     */
    public function getAllowedMethods()
    {
        return [
            \PagSeguro\Payment\Model\Ticket\Ui\ConfigProvider::CODE,
            \PagSeguro\Payment\Model\Pix\Ui\ConfigProvider::CODE,
            \PagSeguro\Payment\Model\OneCreditCard\Ui\ConfigProvider::CODE,
            \PagSeguro\Payment\Model\TwoCreditCard\Ui\ConfigProvider::CODE,
        ];
    }

    /**
     * @return array
     */
    public function getFinalStates()
    {
        return [
            Order::STATE_CANCELED,
            Order::STATE_CLOSED,
            Order::STATE_COMPLETE
        ];
    }

    /**
     * @param $string
     * @return mixed
     */
    public function digits($string)
    {
        return preg_replace('/[^0-9]/', '', $string);
    }

    /**
     * @param $string
     * @return string|string[]|null
     */
    public function formattedString($string)
    {
        return preg_replace('/[^A-Za-z0-9\-]/', ' ', $string);
    }

    /**
     * Log custom message using PagSeguro logger instance
     *
     * @param $message
     * @param string $name
     * @param void
     */
    public function log($message, $name = 'pagseguropayment')
    {
        if ($this->getGeneralConfig('debug')) {
            if (!is_string($message)) {
                $message = $this->json->serialize($message);
            }

            $this->logger->setName($name);
            $this->logger->debug($message);
        }
    }

    /**
     * @return UrlInterface
     */
    public function getUrlBuilder()
    {
        return $this->urlBuilder;
    }

    /**
     * Retrieve url
     *
     * @param string $route
     * @param array $params
     * @return  string
     */
    public function getUrl($route, $params = [])
    {
        return $this->_getUrl($route, $params);
    }

    public function getLogger()
    {
        return $this->_logger;
    }

    public function getPublicKey()
    {
        $publicKey = $this->getConfig('public_key');

        if (!$publicKey) {
            /** @var PagSeguro_Payment_Model_Service_Authentication $authenticationService */
            $authenticationService = Mage::getModel('pagseguropayment/service_authentication');
            $response = $authenticationService->validateToken();

            if ($response || $response->getStatus() === 200) {
                $information = json_decode($response->getBody());
                $publicKey = $information->public_key;

                /** @var Mage_Core_Model_Config $coreConfig */
                $coreConfig = Mage::getModel('core/config');
                $coreConfig->saveConfig('payment/pagseguropayment_settings/public_key', $publicKey);
            }
        }

        return $publicKey;
    }

    protected function base64url_encode($code)
    {
        $base64 = base64_encode($code);

        $base64 = trim($base64, "=");

        $base64url = strtr($base64, '+/', '-_');

        return ($base64url);
    }

    protected function getCodeVerifier()
    {
        return $this->mathRandom->getRandomString(32);
    }

    protected function getCodeChallenge()
    {

        $codeVerifier = $this->getConfig('code_verifier', 'general');

        if (!$codeVerifier) {
            $codeVerifier = $this->getCodeVerifier();
            $this->saveConfig($codeVerifier, 'code_verifier');
        }

        $codeChallenge = $this->base64url_encode(pack('H*', hash('sha256', $codeVerifier)));
        $this->saveConfig($codeChallenge, 'code_challenge');

        return [
            'challenge'     => $codeChallenge,
            'verifier'      => $codeVerifier,
        ];

    }

    public function getOAuthUrl()
    {

        $oAuthURL = $this->getGeneralConfig('oauth_url');

        if ($this->getGeneralConfig('sandbox')) {
            $oAuthURL = $this->getGeneralConfig('oauth_sandbox_url');
        }

        $code = $this->getCodeChallenge();

        $client_id = $this->getApplicationConfig('client_id');
        if ($this->getGeneralConfig('sandbox')) {
            $client_id = $this->getApplicationConfig('client_id_sandbox');
        }

        $params = array(
            'response_type'         => 'code',
            'client_id'             =>  $client_id,
            'scope'                 => 'payments.read+payments.create+payments.refund',
            'state'                 => 'active',
            'code_challenge'        => $code['challenge'],
            'code_challenge_method' => 'S256'
        );

        $queryParams = http_build_query($params);

        $queryParams = str_replace('amp;', '', $queryParams);

        $queryParams = str_replace('%2B', '+', $queryParams);

        $cipher_text = $this->getApplicationConfig('cipher_text');
        if ($this->getGeneralConfig('sandbox')) {
            $cipher_text = $this->getApplicationConfig('cipher_text_sandbox');
        }

        return [
            'url'           => $oAuthURL . '?' . $queryParams,
            'code_verifier' => $code['verifier'],
            'cipher_text'   => $cipher_text
        ];
    }

    public function getOAuthCodeUrl()
    {
        $oAuthURL = $this->getGeneralConfig('api_url');

        if ($this->getGeneralConfig('sandbox')) {
            $oAuthURL = $this->getGeneralConfig('sandbox_url');
        }

        return $oAuthURL . 'oauth2/token';

    }
}
