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


class OAuth extends Field
{

    protected $helperData;

    public function __construct(Context $context, HelperData $helperData)
    {
        parent::__construct($context);
        $this->helperData = $helperData;
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
        $maybeToken = $this->helperData->getGeneralConfig('token');

        if ($maybeToken) {

            // Replace field markup with validation button
            $title = __('Remover Autorização');

            $storeId = 0;
            if ($this->getRequest()->getParam('website')) {

                $website = $this->_storeManager->getWebsite($this->getRequest()->getParam('website'));

                if ($website->getId()) {

                    /** @var Store $store */
                    $store = $website->getDefaultStore();

                    $storeId = $store->getStoreId();

                }

            }

            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

            $oauthEndpoint = $this->getUrl('pagseguropayment/credential/remove', ['storeId' => $storeId]);

            $html = "
                <button
                    type=\"button\"
                    id=\"pagseguro-oauth-button\"
                    title=\"{$title}\"
                    data-token=\"{$maybeToken}\"
                    class=\"button\"
                    onclick=\"paseguroOauthRemove.call(this, '{$oauthEndpoint}')\">
                    <span>{$title}</span>
                </button>";

        } else {

            // Replace field markup with validation button
            $title = __('Autorize a sua conta');

            $storeId = 0;

            $oauthUrl = $this->helperData->getOAuthUrl();

            if ($this->getRequest()->getParam('website')) {

                $website = $this->_storeManager->getWebsite($this->getRequest()->getParam('website'));

                if ($website->getId()) {

                    /** @var Store $store */
                    $store = $website->getDefaultStore();

                    $storeId = $store->getStoreId();

                }

            }

            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

            $oauthEndpoint = $this->getUrl('pagseguropayment/credential/oauth', ['storeId' => $storeId]);

            $html = "
                <button
                    type=\"button\"
                    id=\"pagseguro-oauth-button\"
                    title=\"{$title}\"
                    data-exchange-url=\"{$oauthEndpoint}\"
                    class=\"button\"
                    onclick=\"paseguroOauthRedirect.call(this, '{$oauthUrl}')\">
                    <span>{$title}</span>
                </button>";

        }



        return $html;
    }
}
