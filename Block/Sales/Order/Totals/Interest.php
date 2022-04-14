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

namespace PagSeguro\Payment\Block\Sales\Order\Totals;

use Magento\Sales\Model\Order;

/**
 * Class FinanceCost
 *
 * @package MercadoPago\Core\Block\Sales\Order\Totals
 */
class Interest extends \Magento\Framework\View\Element\Template
{

    /**
     * @var \Magento\Framework\DataObject
     */
    protected $_source;

    /**
     * Get data (totals) source model
     *
     * @return \Magento\Framework\DataObject
     */
    public function getSource()
    {
        return $this->getParentBlock()->getSource();
    }

    /**
     * Add this total to parent
     */
    public function initTotals()
    {
        if ($this->getSource()->getPagseguropaymentInterestAmount() > 0) {
            $total = new \Magento\Framework\DataObject([
                'code'  => 'pagseguropayment_interest',
                'field' => 'pagseguropayment_interest_amount',
                'value' => $this->getSource()->getPagseguropaymentInterestAmount(),
                'label' => __('Interest Rate'),
            ]);

            $this->getParentBlock()->addTotalBefore($total, $this->getBeforeCondition());
        }

        return $this;
    }
}
