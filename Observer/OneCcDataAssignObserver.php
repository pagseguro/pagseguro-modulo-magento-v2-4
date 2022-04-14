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

namespace PagSeguro\Payment\Observer;

use Magento\Checkout\Model\Session;
use Magento\Framework\Event\Observer;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Observer\AbstractDataAssignObserver;
use Magento\Quote\Api\CartRepositoryInterface;
use PagSeguro\Payment\Helper\Card as HelperCard;

class OneCcDataAssignObserver extends AbstractDataAssignObserver
{
    /**
     * @var Session
     */
    private $checkoutSession;

    /**
     * @var CartRepositoryInterface
     */
    private $quoteRepository;

    /**
     * @var HelperCard
     */
    private $helperCard;

    /**
     * DataAssignObserver constructor.
     * @param CartRepositoryInterface $quoteRepository
     * @param Session $checkoutSession
     * @param HelperCard $helperCard
     */
    public function __construct(
        CartRepositoryInterface $quoteRepository,
        Session $checkoutSession,
        HelperCard $helperCard
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->checkoutSession = $checkoutSession;
        $this->helperCard = $helperCard;
    }

    /**
     * @param Observer $observer
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute(Observer $observer)
    {
        $data = $this->readDataArgument($observer);

        /** @var array $additionalData */
        $additionalData = $data->getAdditionalData();

        if (!empty($additionalData)) {
            if (!isset($additionalData['cc_type']) || !isset($additionalData['cc_number'])) {
                throw new LocalizedException(__('Credit Card Information not provided'));
            }

            $installments = isset($additionalData['installments']) ? $additionalData['installments'] : 1;
            $canSave = isset($additionalData['cc_save']) ? (int) $additionalData['cc_save'] : 0;
            $ccId = null;
            if (isset($additionalData['cc_id']) && $additionalData['cc_id']) {
                $cardInfo = $this->helperCard->getCardById($additionalData['cc_id']);
                if ($cardInfo && $cardInfo->getId()) {
                    $ccId = $cardInfo->getToken();
                }
            }

            $ccEncrypted = isset($additionalData['cc_encrypted']) ? $additionalData['cc_encrypted'] : false;
            $ccType = $additionalData['cc_type'] ?? null;
            $ccOwner = $additionalData['cc_owner'] ?? null;
            $ccLast4 = isset($additionalData['cc_number']) ? substr($additionalData['cc_number'], -4) : null;
            $ccExpMonth = $additionalData['cc_exp_month'] ?? null;
            $ccExpYear = $additionalData['cc_exp_year'] ?? null;

            $this->updateInterest($installments);

            /** @var \Magento\Quote\Model\Quote\Payment $paymentInfo */
            $paymentInfo = $this->readPaymentModelArgument($observer);

            $paymentInfo->addData([
                'cc_type' => $ccType,
                'cc_owner' => $ccOwner,
                'cc_number' => $additionalData['cc_number'] ?? null,
                'cc_last_4' => $ccLast4,
                'cc_cid' => $additionalData['cc_cid'] ?? null,
                'cc_exp_month' => $ccExpMonth,
                'cc_exp_year' => $ccExpYear
            ]);

            $paymentInfo->setAdditionalInformation('cc_encrypted', $ccEncrypted);
            $paymentInfo->setAdditionalInformation('installments', $installments);
            $paymentInfo->setAdditionalInformation('cc_id', $ccId);
            $paymentInfo->setAdditionalInformation('cc_save', $canSave);
        }

    }

    /**
     * @param $installments
     * @throws LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function updateInterest($installments)
    {
        $this->checkoutSession->setData('pagseguropayment_installments', $installments);
        $quote = $this->checkoutSession->getQuote();
        $quote->setTotalsCollectedFlag(false)->collectTotals();
    }
}
