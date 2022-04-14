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

class Street implements \Magento\Framework\Data\OptionSourceInterface
{

   public function toOptionArray()
    {
        return [
            '0' => __('Street Line %1', 1),
            '1' => __('Street Line %1', 2),
            '2' => __('Street Line %1', 3),
            '3' => __('Street Line %1', 4),
        ];
    }
}
