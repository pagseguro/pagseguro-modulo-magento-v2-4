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

class OAuth extends Client
{

    public function getAccessToken($data = false, $url = false)
    {

      $path = $this->getEndpointPath('oauth_token');

      $this->setOAuthUrl($url);

      $api = $this->getApi($path, true);

      $api->setMethod(Request::METHOD_POST);

      $api->setRawBody($this->json->serialize($data));

      return $this->doRequest($api);

    }

    public function removeAccessToken($data = false, $url = false)
    {

      $path = $this->getEndpointPath('oauth_revoke');

      $this->setOAuthUrl($url);

      $api = $this->getApi($path, true);

      $api->setMethod(Request::METHOD_POST);

      $api->setRawBody($this->json->serialize($data));

      return $this->doRequest($api);

    }
}
