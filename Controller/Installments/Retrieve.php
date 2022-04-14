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

namespace PagSeguro\Payment\Controller\Installments;

use PagSeguro\Payment\Helper\Data as HelperData;
use PagSeguro\Payment\Helper\Installments;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;

class Retrieve extends Action implements ActionInterface
{
    /**
     * @var HelperData
     */
    protected $helperData;

    /**
     * @var Json
     */
    protected $json;

    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var Session
     */
    protected $checkoutSession;

    /**
     * @var Installments
     */
    private $helperInstallments;

    public function __construct(
        Context $context,
        Json $json,
        Session $checkoutSession,
        JsonFactory $resultJsonFactory,
        Installments $helperInstallments,
        HelperData $helperData
    )
    {
        $this->json = $json;
        $this->checkoutSession = $checkoutSession;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->helperData = $helperData;
        $this->helperInstallments = $helperInstallments;

        return parent::__construct($context);
    }

    public function execute()
    {
        $result = $this->resultJsonFactory->create();
        $result->setHttpResponseCode(401);

        $amount = $this->getRequest()->getParam('amount');

        try {
            $grandTotal = $amount ?: $this->checkoutSession->getQuote()->getGrandTotal();
            $interestRate = (float)$this->checkoutSession->getQuote()->getPagseguropaymentInterestAmount();
            $result->setJsonData($this->json->serialize(
                $this->helperInstallments->getAllInstallments(
                    ($grandTotal - $interestRate),
                    \PagSeguro\Payment\Model\OneCreditCard\Ui\ConfigProvider::CODE
                )
            ));
            $result->setHttpResponseCode(200);
        } catch (\Exception $e) {
            $result->setHttpResponseCode(500);
        }

        return $result;
    }
}
