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
use PagSeguro\Payment\Helper\Data as HelperData;
use Magento\Backend\Block\Template\Context;


class Validation extends Field
{

    protected $helperData;

    public function __construct(Context $context, HelperData $helperData)
    {
        $this->helperData = $helperData;
        parent::__construct($context);
    }

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

        $html = '';
        $token = $this->helperData->getGeneralConfig('token');

        if ($token) {

            // Replace field markup with validation button
            $title = __('Valide a sua conta');
            $storeId = 0;

            if ($this->getRequest()->getParam('website')) {
                $website = $this->_storeManager->getWebsite($this->getRequest()->getParam('website'));
                if ($website->getId()) {
                    /** @var Store $store */
                    $store = $website->getDefaultStore();
                    $storeId = $store->getStoreId();
                }
            }

            $endpoint = $this->getUrl('pagseguropayment/credential/validate', ['storeId' => $storeId]);
            $html = "
                <button
                    type=\"button\"
                    title=\"{$title}\"
                    class=\"button\"
                    onclick=\"pagseguroPaymentValidator.call(this, '{$endpoint}')\">
                    <span>{$title}</span>
                </button>
                <input id='payment_other_pagseguropayment_pagseguropayment_general_token' type='hidden' value='{$token}'>";

        }

        return $html;
    }
}
