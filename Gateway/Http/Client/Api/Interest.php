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

class Interest extends Client
{

    public function getInterestForInstallments($price, $maxInstallments, $installmentsWithoutInterest, $url = false)
    {

      $path = $this->getEndpointPath('fees_calculate');
      $this->setServiceUrl($url);
      $api = $this->getApiInterest($path, $price, $maxInstallments, $installmentsWithoutInterest);
      $api->setMethod(Request::METHOD_GET);

      return $this->doRequest($api);

    }

}
