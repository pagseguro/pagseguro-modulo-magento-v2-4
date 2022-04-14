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

class Transaction extends Client
{
    /**
     * Create charge on PagSeguro API
     * @param $data
     * @return array
     */
    public function create($data)
    {

        if (isset($data->qr_codes)) {

            $path = $this->getEndpointPath('orders');

        } else {

            $path = $this->getEndpointPath('charge');

        }

        $api = $this->getApi($path);

        $api->setMethod(Request::METHOD_POST);
        $api->setRawBody($this->json->serialize($data));
        return $this->doRequest($api);
    }

    /**
     * Consult charge on PagSeguro API using charge id
     * @param $chargeId
     * @return array
     */
    public function consultCharge($chargeId)
    {
        $path = $this->getEndpointPath('consult', $chargeId);
        $api = $this->getApi($path);

        $api->setMethod(Request::METHOD_GET);
        return $this->doRequest($api);
    }

    /**
     * Cancel an charge on PagSeguro API
     *
     * The amount needs to be sent otherwise the API returns ERROR 40002 - invalid_parameter
     *
     * @param $chargeId
     * @param $data
     * @return array
     */
    public function cancelCharge($chargeId, $data)
    {
        $path = $this->getEndpointPath('cancel', $chargeId);
        $api = $this->getApi($path);

        $api->setMethod(Request::METHOD_POST);
        $api->setRawBody($this->json->serialize($data));
        return $this->doRequest($api);
    }

    /**
     * Capture an charge on PagSeguro API
     *
     * @param $chargeId
     * @param $data
     * @return array
     */
    public function captureCharge($chargeId, $data)
    {
        $path = $this->getEndpointPath('capture', $chargeId);
        $api = $this->getApi($path);

        $api->setMethod(Request::METHOD_POST);
        $api->setRawBody($this->json->serialize($data));
        return $this->doRequest($api);
    }
}
