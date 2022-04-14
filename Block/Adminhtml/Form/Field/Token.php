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

namespace PagSeguro\Payment\Block\Adminhtml\Form\Field;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\Store;


class Token extends Field
{
    /**
     * @inheritDoc
     */
    protected function _renderScopeLabel(AbstractElement $element): string
    {
        return '';
    }

    /**
     * @inheritDoc
     * @throws LocalizedException
     */
    protected function _getElementHtml(AbstractElement $element): string
    {
        $html = "
            <input
                type=\"hidden\"
                id=\"pagseguro-oauth-token\"
                name=\"pagseguro-oauth-token\"
            />";

        return $html;
    }

}
