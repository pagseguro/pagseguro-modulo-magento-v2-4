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

namespace PagSeguro\Payment\Model\Adminhtml\Source;

class Installments implements \Magento\Framework\Data\OptionSourceInterface
{

   public function toOptionArray()
    {
        return [
            1 => __('in cash payments'),
            2 => __('%1 installments', 2),
            3 => __('%1 installments', 3),
            4 => __('%1 installments', 4),
            5 => __('%1 installments', 5),
            6 => __('%1 installments', 6),
            7 => __('%1 installments', 7),
            8 => __('%1 installments', 8),
            9 => __('%1 installments', 9),
            10 => __('%1 installments', 10),
            11 => __('%1 installments', 11),
            12 => __('%1 installments', 12)
        ];
    }
}
