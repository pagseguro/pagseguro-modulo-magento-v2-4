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

namespace PagSeguro\Payment\Gateway\Http\Client\Api;

use Magento\Framework\Encryption\EncryptorInterface;
use Laminas\Http\Client as HttpClient;
use Magento\Framework\Serialize\Serializer\Json;
use PagSeguro\Payment\Helper\Data;


class Client
{
    /**
     * @var Data
     */
    protected $helper;

    /**
     * @var HttpClient
     */
    protected $api;

    /**
     * @var EncryptorInterface
     */
    protected $encryptor;

    /**
     * @var Json
     */
    protected $json;

    /**
     * @var string
     */
    protected $token;

    /**
     * @var string
     */
    protected $serviceUrl;

    /**
     * @var string
     */
    protected $oAuthURL;

    /**
     * @param Data $helper
     * @param EncryptorInterface $encryptor
     * @param Json $json
     */
    public function __construct(
        Data $helper,
        EncryptorInterface $encryptor,
        Json $json
    ) {
        $this->helper = $helper;
        $this->encryptor = $encryptor;
        $this->json = $json;
        $this->setServiceUrl();
        $this->setOAuthUrl();
    }

    /**
     * @param null $token
     */
    public function setToken($token = null)
    {
        $this->token = $token ?: $this->helper->getGeneralConfig('token');
    }

    /**
     * @return string
     */
    public function getToken()
    {
        if (!$this->token) {
            $this->setToken();
        }

        return $this->token;
    }

    /**
     * @return array
     */
    protected function getDefaultHeaders()
    {
        return [
            'Content-Type'      => 'application/json',
            'Authorization'     => 'Bearer ' . $this->getToken(),
            'x-api-version'     => '4.0',
            'cmd-description'   => 'magento4-v.' . $this->getMagentoVersion()
        ];
    }

    /**
     * @return array
     */
    protected function getOauthHeaders()
    {
        return [
            'Content-Type'      => 'application/json',
            'Authorization'     => 'Pub ' . $this->getChiperText(),
            'x-api-version'     => '4.0',
        ];
    }

    protected function getClientId()
    {
        $client_id = $this->helper->getApplicationConfig('client_id');
        if ($this->helper->getGeneralConfig('sandbox')) {
            $client_id = $this->helper->getApplicationConfig('client_id_sandbox');
        }
        return $client_id;
    }

    protected function getChiperText()
    {
        $cipher_text = $this->helper->getApplicationConfig('cipher_text');
        if ($this->helper->getGeneralConfig('sandbox')) {
            $cipher_text = $this->helper->getApplicationConfig('cipher_text_sandbox');
        }
        return $cipher_text;
    }


    protected function getMagentoVersion()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $productMetadata = $objectManager->get('Magento\Framework\App\ProductMetadataInterface');
        return $productMetadata->getVersion();
    }

    /**
     * @return array
     */
    protected function getDefaultOptions()
    {
        return [
            'timeout' => 30
        ];
    }

    /**
     * @param string $endpoint
     * @param string $id
     * @return string|string[] $path
     */
    protected function getEndpointPath($endpoint, $id = null)
    {
        $fullEndpoint = $this->helper->getEndpointConfig($endpoint);
        $path = str_replace(
            ['{{id}}'],
            [$id],
            $fullEndpoint
        );
        return $path;
    }

    /**
     * @param null $useSandbox
     */
    public function setServiceUrl($useSandbox = null)
    {
        $serviceUrl = $this->helper->getGeneralConfig('api_url');
        if ($this->helper->getGeneralConfig('sandbox') || $useSandbox) {
            $serviceUrl = $this->helper->getGeneralConfig('sandbox_url');
        }
        $this->serviceUrl = $serviceUrl;
    }

    /**
     * @param null $useSandbox
     */
    public function setOAuthUrl($useSandbox = null)
    {
        $oAuthURL = $this->helper->getGeneralConfig('oauth_url');
        if ($this->helper->getGeneralConfig('sandbox') || $useSandbox) {
            $oAuthURL = $this->helper->getGeneralConfig('oauth_sandbox_url');
        }

        $this->oAuthURL = $oAuthURL;
    }

    /**
     * @return mixed
     */
    protected function getServiceUrl()
    {
        return $this->serviceUrl;
    }

    /**
     * @return mixed
     */
    protected function getOAuthUrl()
    {
        return $this->oAuthUrl;
    }

    /**
     * @return HttpClient
     */
    public function getApi($path, $oauth = null)
    {

        $uri = $this->getServiceUrl();

        $this->api = new HttpClient(
            $uri . $path,
            $this->getDefaultOptions()
        );

        $this->api->setHeaders($this->getDefaultHeaders());

        if ($oauth) {

            $this->api->setHeaders($this->getOAuthHeaders());

        }

        $this->api->setEncType('application/json');

        return $this->api;
    }

    /**
     * @param $api
     * @return array
     */
    protected function doRequest($api)
    {
        $response = $api->send();

        return [
            'status' => $response->getStatusCode(),
            'response' => $this->json->unserialize($response->getContent())
        ];
    }

    /**
     * @param object $response
     * @return array
     */
    protected function returnContent($response)
    {
        $content = $response->getContent() ? $this->json->unserialize($response->getContent()) : '';
        return [
            'status' => $response->getStatusCode(),
            'response' => $content
        ];
    }
}
