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

namespace PagSeguro\Payment\Gateway\Http\Client;

use PagSeguro\Payment\Gateway\Http\Client\Api\CredentialAuthentication;
use PagSeguro\Payment\Gateway\Http\Client\Api\OAuth;
use PagSeguro\Payment\Gateway\Http\Client\Api\Transaction;
use PagSeguro\Payment\Gateway\Http\Client\Api\Interest;
use PagSeguro\Payment\Helper\Data;
use PagSeguro\Payment\Helper\Transaction as HelperTransaction;

class Api
{
    const STATUS_IN_ANALYSIS = 'IN_ANALYSIS';
    const STATUS_AUTHORIZED = 'AUTHORIZED';
    const STATUS_WAITING = 'WAITING';
    const STATUS_PAID = 'PAID';
    const STATUS_CANCELED = 'CANCELED';
    const STATUS_DECLINED = 'DECLINED';

    /**
     * @var Data
     */
    private $helper;

    /**
     * @var HelperTransaction
     */
    private $helperTransaction;

    /**
     * @var CredentialAuthentication
     */
    private $credentialAuthentication;

    /**
     * @var OAuth
     */
    private $oAuth;

    /**
     * @var Transaction
     */
    private $transaction;

    /**
     * @var Interest
     */
    private $interest;

    /**
     * @param Data $helper
     * @param HelperTransaction $helperTransaction
     * @param CredentialAuthentication $credentialAuthentication
     * @param OAuth $OAuth
     * @param Transaction $transaction
     */
    public function __construct(
        Data $helper,
        HelperTransaction $helperTransaction,
        CredentialAuthentication $credentialAuthentication,
        OAuth $oAuth,
        Transaction $transaction,
        Interest $interest
    ) {
        $this->helperTransaction = $helperTransaction;
        $this->helper = $helper;
        $this->credentialAuthentication = $credentialAuthentication;
        $this->oAuth = $oAuth;
        $this->transaction = $transaction;
        $this->interest = $interest;

    }

    /**
     * @return CredentialAuthentication
     */
    public function credentialAuthentication()
    {
        return $this->credentialAuthentication;
    }

    /**
     * @return OAuth
     */
    public function oAuth()
    {
        return $this->oAuth;
    }

    public function transaction()
    {
        return $this->transaction;
    }

    public function interest()
    {
        return $this->interest;
    }

    /**
     * @param string $statusCode
     * @param \stdClass $request
     * @param $response
     */
    public function saveTransaction($statusCode, $request, $response)
    {
        try {
            $pagseguroId = null;
            $orderId = null;

            if (is_object($request) || is_array($request)) {
                if (!$orderId && property_exists($request, 'reference_id')) {
                    $orderId = $request->reference_id;
                }

                if (isset($request->payment_method->card->security_code)) {
                    $request->payment_method->card->security_code = null;
                }

                if (isset($request->payment_method->card->number)) {
                    $ccNumber = $request->payment_method->card->number;
                    $request->payment_method->card->number = substr($ccNumber, 0, 6) . '****' . substr($ccNumber, -4);
                }

                $request = json_encode($request);
            }

            if (is_array($response)) {
                $response = isset($response['response']) ? $response['response'] : $response;

                $pagseguroId = isset($response['id']) ? $response['id'] : null;
                if (!$orderId && isset($request['reference_id'])) {
                    $orderId = $request['reference_id'];
                }

                $response = json_encode($response);
            }

            $this->helperTransaction->saveTransaction(
                $orderId,
                $pagseguroId,
                $request,
                $response,
                $statusCode
            );
        } catch (\Exception $e) {
            $this->helper->log($e->getMessage());
        }
    }



    /**
     * @param $request
     * @param string $name
     */
    public function logRequest($request, $name = 'pagseguropayment')
    {
        $this->helper->log('REQUEST', $name);
        if (isset($request->payment_method->card->security_code)) {
            $request->payment_method->card->security_code = null;
        }

        if (isset($request->payment_method->card->number)) {
            $ccNumber = $request->payment_method->card->number;
            $request->payment_method->card->number = substr($ccNumber, 0, 6) . '****' . substr($ccNumber, -4);
        }

        $this->helper->log($request, $name);
    }

    /**
     * @param $response
     * @param string $name
     */
    public function logResponse($response, $name = 'pagseguropayment')
    {
        $this->helper->log('RESPONSE', $name);
        $this->helper->log($response, $name);
    }

    /**
     * @return string[]
     */
    public function deniedStatuses()
    {
        return [
            self::STATUS_DECLINED,
            self::STATUS_CANCELED
        ];
    }
}
