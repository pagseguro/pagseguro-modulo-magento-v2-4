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

namespace PagSeguro\Payment\Gateway\Request;

use PagSeguro\Payment\Helper\Data;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;

class RefundRequest implements BuilderInterface
{
    /**
     * Builds ENV request
     *
     * @param array $buildSubject
     * @return array
     */
    public function build(array $buildSubject)
    {
        if (!isset($buildSubject['payment'])
            || !$buildSubject['payment'] instanceof PaymentDataObjectInterface
        ) {
            throw new \InvalidArgumentException('Payment data object should be provided');
        }

        /** @var \Magento\Sales\Model\Order\Payment\Interceptor $payment */
        $payment = $buildSubject['payment']->getPayment();
        if (!isset($buildSubject['amount'])) {
            $buildSubject['amount'] = $payment->getOrder()->getGrandTotal();
        }

        $value = new \stdClass();
        $value->value = (int) round($buildSubject['amount'] * Data::ROUND_FACTOR);

        $request = new \stdClass();
        $request->amount = $value;

        $clientConfig = [
            'transaction_id' => $payment->getAdditionalInformation('id')
        ];

        return ['request' => $request, 'client_config' => $clientConfig];
    }
}
