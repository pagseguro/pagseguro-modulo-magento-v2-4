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

namespace PagSeguro\Payment\Gateway\Request\TwoCreditCard;

use PagSeguro\Payment\Helper\Data;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;

class CaptureRequest implements BuilderInterface
{
    /**
     * @param array $buildSubject
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
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
        $firstCcAmount = $payment->getAdditionalInformation('first_cc_amount_with_interest');
        $secondCcAmount = $payment->getAdditionalInformation('second_cc_amount_with_interest');

        $request = $this->getAmountData($firstCcAmount);

        $clientConfig = [
            'transaction_id' => $payment->getAdditionalInformation('id'),
            'first_cc_transaction_id' => $payment->getAdditionalInformation('id'),
            'second_cc_transaction_id' => $payment->getAdditionalInformation('second_cc_tid'),
            'first_cc_amount' => $firstCcAmount,
            'second_cc_amount' => $secondCcAmount,
            'first_cc_request' => $this->getAmountData($firstCcAmount),
            'second_cc_request' => $this->getAmountData($secondCcAmount),
        ];

        return ['request' => $request, 'client_config' => $clientConfig];
    }

    /**
     * @param $amount
     * @return \stdClass
     */
    protected function getAmountData($amount)
    {
        $value = new \stdClass();
        $value->value = (int) round($amount * Data::ROUND_FACTOR);

        $request = new \stdClass();
        $request->amount = $value;
        return $request;
    }
}
