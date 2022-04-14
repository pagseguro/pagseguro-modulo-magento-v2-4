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

use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\TransferInterface;
use PagSeguro\Payment\Helper\Data;

class Transaction implements ClientInterface
{
    const LOG_NAME = 'pagseguro-payment-transaction';

    /**
     * @var Data
     */
    private $helper;

    /**
     * @var Api
     */
    private $api;

    /**
     * @param Data $helper
     * @param Api $api
     */
    public function __construct(
        Data $helper,
        Api $api
    ) {
        $this->helper = $helper;
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
        $transaction = $this->api->transaction()->create(
            $transferObject->getBody()
        );

        $this->api->logRequest($transferObject->getBody(), self::LOG_NAME);
        $this->api->logResponse($transaction, self::LOG_NAME);

        $status = $transaction['status'] ?? null;
        $this->api->saveTransaction($status, $transferObject->getBody(), $transaction);

        return ['status' => $status, 'transaction' => $transaction['response']];
    }
}
