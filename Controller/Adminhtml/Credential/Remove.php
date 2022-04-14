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

use Exception;
use Magento\Backend\App\Action;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use PagSeguro\Payment\Gateway\Http\Client\Api;
use PagSeguro\Payment\Helper\Data as HelperData;

class Remove extends Action
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

    /**
     * Retrieve constructor.
     * @param Action\Context $context
     * @param HelperData $helperData
     * @param Api $api
     */
    public function __construct(
        Action\Context $context,
        HelperData $helperData,
        Api $api
    ) {
        $this->helper = $helperData;
        $this->api = $api;
        $this->hasError = false;
        return parent::__construct($context);
    }

    /**
     * @return ResultInterface
     */
    public function execute(): ResultInterface
    {
        $response = $this->resultFactory->create(ResultFactory::TYPE_JSON);

        try {
            $this->removeCode();

            if ($this->hasError) {
                $response->setHttpResponseCode(400);
                return $response;
            }

            $response->setHttpResponseCode(200);
        } catch (Exception $e) {
            $response->setHttpResponseCode(400);
        }

        return $response;
    }

    protected function removeCode()
    {

        $token = $this->helper->getConfig('token');

        $url = $this->getRequest()->getParam('sandbox');

        $data = [
            'token_type_hint'   => 'access_token',
            'token'             => $token
        ];

        $response = $this->api->oAuth()->removeAccessToken($data, $url);

        $removeToken = $this->helper->saveConfig('', 'token');

        $this->helper->log($removeToken);

        $this->helper->log($response);


        /*
        if ($response['status'] < 200 || $response['status'] >= 300) {
            $message = __('There was an error trying to validate your credential. Please check if your token is correct');
            $this->messageManager->addErrorMessage($message);
            $this->hasError = true;
        }

        if (isset($response['response']['public_key'])) {
            $this->helper->saveConfig($response['response']['public_key'], 'public_key');
        }
        */
        return $removeToken;
    }
}
