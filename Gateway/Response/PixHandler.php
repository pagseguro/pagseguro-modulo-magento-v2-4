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

namespace PagSeguro\Payment\Gateway\Response;

use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Sales\Model\Order;

class PixHandler implements HandlerInterface {

    /**
     * Handles transaction id
     *
     * @param array $handlingSubject
     * @param array $response
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function handle(array $handlingSubject, array $response) {

        if (!isset($handlingSubject['payment']) || !$handlingSubject['payment'] instanceof PaymentDataObjectInterface) {

        throw new \InvalidArgumentException('Payment data object should be provided');

        } // end if;

        /**
         * @var PaymentDataObjectInterface $paymentData
         */
        $paymentData = $handlingSubject['payment'];

        $transaction = $response['transaction'];

        /**
         * @var $payment \Magento\Sales\Model\Order\Payment
         */
        $payment = $paymentData->getPayment();

        if (isset($transaction['id'])) {

            if (isset($transaction['qr_codes'])) {

                foreach ($transaction['qr_codes'] as $qr_code) {

                    $digitableText = '';

                    $qrcode_image = '';

                    if (isset($qr_code['text'])) {

                        $digitableText = $qr_code['text'];

                    } // end if;

                    if (isset($qr_code['links'])) {

                        $qrcode_image = $qr_code['links'][0]['href'];

                    } // end if;

                } // end foreach;

                $payment->setAdditionalInformation('print_options', [
                    'linhadigitavel'    => $digitableText,
                    'qrcode_image'      => $qrcode_image
                ]);

            } // end if;

            if (isset($digitableText)) {

                $payment->setAdditionalInformation('linhadigitavel', $digitableText);

            }

            if (isset($digitableText)) {

                $payment->setAdditionalInformation('qrcode', $qrcode_image);

            }
        }
    }
}
