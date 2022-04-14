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
use function PHPUnit\Framework\throwException;

class Capture implements ClientInterface
{
    const LOG_NAME = 'pagseguropayment-capture';

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

        $logTransaction['first_cc'] = [
            'id' => $transactionId,
            'request' => $transferObject->getBody()
        ];

        $firstCharge = $this->getCharge($transactionId);
        $secondCharge = $this->getCharge($config['second_cc_transaction_id']);

        if (!$this->canPlaceRequest($firstCharge, $secondCharge)) {
            $this->cancelCharges($config, $firstCharge, $secondCharge);
        }

        $canPlaceSecondRequest = true;

        $status = 200;
        $transaction = ['first_cc' => $firstCharge, 'second_cc' => $secondCharge];
        if (!isset($firstCharge['error']) && ($firstCharge['status'] != Api::STATUS_PAID)) {
            $response = $this->api->transaction()->captureCharge(
                $transactionId,
                $transferObject->getBody()
            );

            $status = $response['status'] ?? null;
            if (isset($response['error'])) {
                $canPlaceSecondRequest = false;
                $status = $response['error']['status'];
            }

            $logTransaction['first_cc']['response'] = $response['response'];
            $transaction['first_cc'] = $response['response'];
            $this->api->saveTransaction($status, $transferObject->getBody(), $response);
        }

        if ((!isset($secondCharge['error']) && ($secondCharge['status'] != Api::STATUS_PAID)) || !$canPlaceSecondRequest) {
            $response = $this->api->transaction()->captureCharge(
                $config['second_cc_transaction_id'],
                $config['second_cc_request']
            );

            $status = $response['status'] ?? null;
            if (isset($response['error'])) {
                $status = $response['error']['status'];
            }

            $logTransaction['second_cc'] = [
                'id' => $config['second_cc_transaction_id'],
                'request' => $config['second_cc_request'],
                'response' => $response['response']
            ];

            $transaction['second_cc'] = $response['response'];
            $this->api->saveTransaction($status, $config['second_cc_request'], $response);
        }

        $this->api->logResponse($logTransaction, self::LOG_NAME);

        return ['status' => $status, 'transaction' => $transaction];
    }

    protected function canPlaceRequest($firstCharge, $secondCharge)
    {
        if (
            !isset($firstCharge['status'])
            || !isset($secondCharge['status'])
            || in_array($firstCharge['status'], $this->api->deniedStatuses())
            || in_array($secondCharge['status'], $this->api->deniedStatuses())
        ) {
            return false;
        }

        return true;
    }

    protected function cancelCharges($config, $firstCharge, $secondCharge)
    {
        if (!isset($firstCharge['status']) || in_array($firstCharge['status'], $this->api->deniedStatuses())) {
            if (!in_array($secondCharge['status'], $this->api->deniedStatuses())) {
                $this->api->transaction()->cancelCharge($config['second_cc_transaction_id'], $config['second_cc_request']);
            }

            throw new \InvalidArgumentException(
                sprintf(__("The charge %s can not be captured. Current charge status on PagSeguro: %s. The charge %s will be canceled too."),
                    $config['transaction_id'], $firstCharge['status'], $secondCharge['id']
                )
            );
        }

        if (!isset($secondCharge['status']) || in_array($secondCharge['status'], $this->api->deniedStatuses())) {
            if (!in_array($firstCharge['status'], $this->api->deniedStatuses())) {
                $this->api->transaction()->cancelCharge($config['transaction_id'], $config['first_cc_request']);
            }

            throw new \InvalidArgumentException(
                sprintf(__("The charge %s can not be captured. Current charge status on PagSeguro: %s. The charge %s will be canceled too."),
                    $secondCharge['id'], $secondCharge['status'], $firstCharge['id']
                )
            );
        }
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
