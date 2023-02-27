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

use Laminas\Http\Request;

class CredentialAuthentication extends Client
{
    /**
     * @return array
     */
    public function get($token = false, $url = false)
    {
        $this->setToken($token);
        $this->setServiceUrl($url);
        $path = $this->getEndpointPath('get_public_key');
        $api = $this->getApi($path);
        $api->setMethod(Request::METHOD_GET);

        $response = $api->send();

        if (!$response || $response->getStatusCode() == 404) {
            return $this->post($token, $url);
        }

        return [
            'status'    => $response->getStatusCode(),
            'response'  => $this->json->unserialize($response->getContent())
        ];


    }

    /**
     * @return array
     */
    public function post($token = false, $url = false)
    {
        $this->setToken($token);
        $this->setServiceUrl($url);
        $path = $this->getEndpointPath('create_public_key');
        $api = $this->getApi($path);
        $api->setMethod(Request::METHOD_POST);
        $api->setRawBody($this->json->serialize([
            "type" => "card"
        ]));

        $response = $api->send();

        return [
            'status'    => $response->getStatusCode(),
            'response'  => $this->json->unserialize($response->getContent())
        ];
    }
}
