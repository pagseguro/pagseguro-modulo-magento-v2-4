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

namespace PagSeguro\Payment\Controller\Adminhtml\Credential;

use PagSeguro\Payment\Gateway\Http\Client\Api;
use PagSeguro\Payment\Helper\Data as HelperData;

class OAuthCode extends \Magento\Framework\App\Config\Value
{

    /**
     * @var HelperData
     */
    private $helper;

    /**
     * @var Api
     */
    private $api;

    /**
     * @var bool
     */
    private $hasError;

    public function __construct(
        HelperData $helperData,
        Api $api
    ) {
        parent::construct();
        $this->helper = $helperData;
        $this->api = $api;
        $this->hasError = false;

        $this->helper->log($this->getValue());
    }

    public function beforeSave()
    {
        $this->helper->log($this->getValue());

        parent::beforeSave();
    }
}