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

namespace PagSeguro\Payment\Gateway\Http\Client\TwoCreditCard;

use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\TransferInterface;
use PagSeguro\Payment\Gateway\Http\Client\Api;

class Refund implements ClientInterface
{
    const LOG_NAME = 'pagseguropayment-refund';

    /**
     * @var Api
     */
    private $api;

    /**
     * @param Api $api
     */
    public function __construct(
        Api $api
    ) {
        $this->api = $api;
    }
    /**
     * Places request to gateway. Returns result as ENV array
     *
     * @param TransferInterface $transferObject
     * @return array
     */
    public function placeRequest(TransferInterface $transferObject)
    {
        $config = $transferObject->getClientConfig();
        $transactionId = $config['transaction_id'];

        $firstCharge = $this->getCharge($transactionId);
        $secondCharge = $this->getCharge($config['second_cc_transaction_id']);

        $transaction = $this->cancelCharges($config, $firstCharge, $secondCharge);

        $status = $transaction['status'] ?? null;
        if (isset($transaction['error'])) {
            $status = $transaction['error']['status'];
        }

        $this->api->saveTransaction($status, $transferObject->getBody(), $transaction['first_cc']);
        $this->api->saveTransaction($status, $config['second_cc_request'], $transaction['second_cc']);

        return ['status' => $status, 'transaction' => $transaction];
    }


    protected function cancelCharges($config, $firstCharge, $secondCharge)
    {
        $response = ['first_cc' => $firstCharge, 'second_cc' => $secondCharge];
        if (!isset($firstCharge['status']) || !in_array($firstCharge['status'], $this->api->deniedStatuses())) {
            $firstResponse = $this->api->transaction()->cancelCharge($config['transaction_id'], $config['first_cc_request']);
            $this->api->logRequest($config['first_cc_request'], self::LOG_NAME);
            $this->api->logResponse($firstResponse, self::LOG_NAME);

            if (isset($firstResponse['response']['error_messages']) || $firstResponse['response']['status'] != $this->api::STATUS_CANCELED) {
                throw new \InvalidArgumentException(
                    sprintf(__("The charge %s could not be canceled. Current charge status on PagSeguro: %s"),
                        $config['transaction_id'], $firstCharge['status']
                    )
                );
            }

            $response['first_cc'] = $firstResponse['response'];
            $response['status'] = $firstResponse['status'];
        }

        if (!isset($secondCharge['status']) || !in_array($secondCharge['status'], $this->api->deniedStatuses())) {
            $secondResponse = $this->api->transaction()->cancelCharge($config['second_cc_transaction_id'], $config['second_cc_request']);

            $this->api->logRequest($config['second_cc_request'], self::LOG_NAME);
            $this->api->logResponse($secondResponse, self::LOG_NAME);

            if (isset($secondResponse['response']['error_messages']) || $secondResponse['response']['status'] != $this->api::STATUS_CANCELED) {
                throw new \InvalidArgumentException(
                    sprintf(__("The charge %s could not be canceled. Current charge status on PagSeguro: %s"),
                        $secondCharge['id'], $secondCharge['status']
                    )
                );
            }

            $response['second_cc'] = $secondResponse['response'];
            $response['status'] = $secondResponse['status'];
        }

        return $response;
    }

    protected function getCharge($chargeId)
    {
        $response = $this->api->transaction()->consultCharge($chargeId);
        if (!isset($response['error'])) {
            return $response['response'];
        }

        return false;
    }
}
