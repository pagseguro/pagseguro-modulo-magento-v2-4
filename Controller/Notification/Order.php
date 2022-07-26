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

namespace PagSeguro\Payment\Controller\Notification;

use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use PagSeguro\Payment\Gateway\Http\Client\Api;
use PagSeguro\Payment\Helper\Data as HelperData;
use PagSeguro\Payment\Helper\Order as HelperOrder;
use PagSeguro\Payment\Helper\TwoCardOrder as HelperTwoCardOrder;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Backend\App\Action\Context;

class Order extends Action implements \Magento\Framework\App\CsrfAwareActionInterface
{
    const LOG_NAME = 'pagseguropayment-notification';

    protected $eventName = 'order';

    /**
     * @var HelperData
     */
    protected $helperData;

    /**
     * @var HelperOrder
     */
    protected $helperOrder;

    /**
     * @var HelperTwoCardOrder
     */
    protected $helperTwoCardOrder;

    /**
     * @var Json
     */
    protected $json;

    /**
     * @var string
     */
    protected $requestContent;

    /**
     * @var ResultFactory
     */
    protected $resultFactory;

    /**
     * @var Api
     */
    protected $api;

    /**
     * PostBack constructor.
     * @param Context $context
     * @param Json $json
     * @param ResultFactory $resultFactory
     * @param HelperOrder $helperOrder
     * @param HelperData $helperData
     */
    public function __construct(
        Context $context,
        Json $json,
        ResultFactory $resultFactory,
        HelperOrder $helperOrder,
        HelperTwoCardOrder $helperTwoCardOrder,
        HelperData $helperData,
        Api $api
    ) {
        $this->json = $json;
        $this->resultFactory = $resultFactory;
        $this->helperOrder = $helperOrder;
        $this->helperTwoCardOrder = $helperTwoCardOrder;
        $this->helperData = $helperData;
        $this->api = $api;

        return parent::__construct($context);
    }

    public function execute()
    {
        $this->helperData->log('PagSeguro Transaction', self::LOG_NAME);

        $result = $this->resultFactory->create(ResultFactory::TYPE_RAW);
        $statusCode = 500;

        try {
            $content = $this->getContent($this->getRequest());
            $content = $content['charges'][0];
            $params = $this->getRequest()->getParams();

            $this->logParams($content, $params);

            if (isset($content['reference_id'])) {
                $orderIncrementId = $content['reference_id'] ?? null;
                $status = $content['status'];

                if ($orderIncrementId) {
                    $this->helperData->log(sprintf('STATUS: %s, Order #%s', $status, $orderIncrementId), self::LOG_NAME);

                    /** @var \Magento\Sales\Model\Order $order */
                    $order = $this->helperOrder->loadOrder($orderIncrementId);

                    /** @var \Magento\Sales\Model\Order\Payment $payment */
                    $payment = $order->getPayment();
                    if ($pagseguroId = $payment->getAdditionalInformation('id')) {
                        $response = $this->api->transaction()->consultCharge($pagseguroId);
                        $responseCode = $response['status'];
                        if ($responseCode == 200) {
                            $transaction = $response['response'];
                            $status = $transaction['status'];
                            if ($payment->getMethod() == \PagSeguro\Payment\Model\TwoCreditCard\Ui\ConfigProvider::CODE) {
                                $this->helperTwoCardOrder->updateOrder($order, $status, $transaction);
                            } else {
                                $this->helperOrder->updateOrder($order, $status, $transaction);
                            }

                            $statusCode = 204;
                        }
                    }
                }

                $result->setHttpResponseCode($statusCode);
            }
        } catch (\Exception $e) {
            $this->helperData->log($e->getMessage(), self::LOG_NAME);
        }

        $result->setHttpResponseCode($statusCode);
        return $result;
    }


    /**
     * @param RequestInterface $request
     * @return mixed|string
     */
    protected function getContent(RequestInterface $request)
    {
        if (!$this->requestContent) {
            try {
                $content = $this->getRequest()->getContent();
                $this->requestContent = $this->json->unserialize($content);
            } catch (\Exception $e) {
                $this->helperData->log($e->getMessage(), self::LOG_NAME);
            }
        }
        return $this->requestContent;
    }

    /**
     * @param $content
     * @param $params
     */
    protected function logParams($content, $params)
    {
        $this->helperData->log(__('Content'), self::LOG_NAME);
        $this->helperData->log($content, self::LOG_NAME);

        $this->helperData->log(__('Params'), self::LOG_NAME);
        $this->helperData->log($params, self::LOG_NAME);
    }

    /** * @inheritDoc */
    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        $result = $this->resultFactory->create(ResultFactory::TYPE_RAW);
        $result->setHttpResponseCode(403);
        return new InvalidRequestException(
            $result
        );
    }

    /** * @inheritDoc */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        // x-authenticity-token is not being sent, then we will retrieve the notified transaction to ensure
        return true;
    }

}
