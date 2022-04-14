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

namespace PagSeguro\Payment\Model\Total\Creditmemo;

use Magento\Sales\Model\Order\Creditmemo;
use Magento\Sales\Model\Order\Total\AbstractTotal;

class Interest extends AbstractTotal
{
    /**
     * @param Creditmemo $creditmemo
     * @return $this
     */
    public function collect(Creditmemo $creditmemo)
    {
        $order = $creditmemo->getOrder();
        $amount = $order->getPagseguropaymentInterestAmount();
        $baseAmount = $order->getBasePagseguropaymentInterestAmount();
        if ($amount) {
            $creditmemo->setPagseguropaymentInterestAmount($amount);
            $creditmemo->setBasePagseguropaymentInterestAmount($baseAmount);
            $creditmemo->setGrandTotal($creditmemo->getGrandTotal() + $amount);
            $creditmemo->setBaseGrandTotal($creditmemo->getBaseGrandTotal() + $baseAmount);
        }

        return $this;
    }
}
