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
use PagSeguro\Payment\Helper\Installments;

class TwoCcDataAssignObserver extends AbstractDataAssignObserver
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
     * @var Installments
     */
    private $helperInstallments;

    /**
     * DataAssignObserver constructor.
     * @param CartRepositoryInterface $quoteRepository
     * @param Session $checkoutSession
     * @param HelperCard $helperCard
     * @param Installments $helperInstallments,
     */
    public function __construct(
        CartRepositoryInterface $quoteRepository,
        Session $checkoutSession,
        HelperCard $helperCard,
        Installments $helperInstallments
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->checkoutSession = $checkoutSession;
        $this->helperCard = $helperCard;
        $this->helperInstallments = $helperInstallments;
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

        /** @var \Magento\Quote\Model\Quote\Payment $paymentInfo */
        $paymentInfo = $this->readPaymentModelArgument($observer);

        if (!empty($additionalData)) {
            if (
                (!isset($additionalData['cc_one_cc_type']) || !isset($additionalData['cc_one_cc_number']))
                && !isset($additionalData['cc_one_cc_id'])
            ) {
                throw new LocalizedException(__('First Credit Card Information not Provided'));
            }

            if (
                (!isset($additionalData['cc_two_cc_type']) || !isset($additionalData['cc_two_cc_number']))
                && !isset($additionalData['cc_two_cc_id'])
            ) {
                throw new LocalizedException(__('Second Credit Card Information not Provided'));
            }

            $firstCcAmount = $additionalData['cc_one_cc_amount'];
            $secondCcAmount = $additionalData['cc_two_cc_amount'];

            $amountWithoutInterest = $firstCcAmount + $secondCcAmount;
            $grandTotalWithoutInterest = $paymentInfo->getQuote()->getGrandTotal() - $paymentInfo->getQuote()->getPagseguropaymentInterestAmount();

            if (round($grandTotalWithoutInterest, 2) != round($amountWithoutInterest, 2)) {
                throw new LocalizedException(__('The cards amount total is wrong.'));
            }

            $firstCcInstallments = isset($additionalData['cc_one_installments']) ? $additionalData['cc_one_installments'] : 1;
            $secondCcInstallments = isset($additionalData['cc_two_installments']) ? $additionalData['cc_two_installments'] : 1;

            $firstCcCanSave = isset($additionalData['cc_one_cc_save']) ? (int) $additionalData['cc_one_cc_save'] : 0;
            $secondCcCanSave = isset($additionalData['cc_two_cc_save']) ? (int) $additionalData['cc_two_cc_save'] : 0;



            $firstCcEncrypted = $additionalData['cc_one_cc_encrypted'] ?? null;
            $secondCcEncrypted = $additionalData['cc_two_cc_encrypted'] ?? null;

            $firstCcType = $additionalData['cc_one_cc_type'] ?? null;
            $firstCcOwner = $additionalData['cc_one_cc_owner'] ?? null;
            $firstCcLast4 = isset($additionalData['cc_one_cc_number']) ? substr($additionalData['cc_one_cc_number'], -4) : null;
            $firstCcExpMonth = $additionalData['cc_one_cc_exp_month'] ?? null;
            $firstCcExpYear = $additionalData['cc_one_cc_exp_year'] ?? null;

            $firstCcAmount = $additionalData['cc_one_cc_amount'];
            $secondCcAmount = $additionalData['cc_two_cc_amount'];

            $firstCcTotalWithInterest = $this->helperInstallments->getInstallmentPrice($firstCcAmount, $firstCcInstallments, null, $paymentInfo->getMethod());
            $secondCcTotalWithInterest = $this->helperInstallments->getInstallmentPrice($secondCcAmount, $secondCcInstallments, null, $paymentInfo->getMethod());

            $secondCcType = $additionalData['cc_two_cc_type'] ?? null;
            $secondCcOwner = $additionalData['cc_two_cc_owner'] ?? null;
            $secondCcLast4 = isset($additionalData['cc_two_cc_number']) ? substr($additionalData['cc_two_cc_number'], -4) : null;
            $secondCcExpMonth = $additionalData['cc_two_cc_exp_month'] ?? null;
            $secondCcExpYear = $additionalData['cc_two_cc_exp_year'] ?? null;

            $paymentInfo->addData([
                'cc_type' => $firstCcType,
                'cc_owner' => $firstCcOwner,
                'cc_number' => $additionalData['cc_one_cc_number'] ?? null,
                'cc_last_4' => $firstCcLast4,
                'cc_cid' => $additionalData['cc_one_cc_cid'] ?? null,
                'cc_exp_month' => $firstCcExpMonth,
                'cc_exp_year' => $firstCcExpYear
            ]);

            $paymentInfo->setAdditionalInformation('first_cc_type', $firstCcType);
            $paymentInfo->setAdditionalInformation('first_cc_owner', $firstCcOwner);
            $paymentInfo->setAdditionalInformation('first_cc_number', $additionalData['cc_one_cc_number'] ?? null);
            $paymentInfo->setAdditionalInformation('first_cc_last4', $firstCcLast4);
            $paymentInfo->setAdditionalInformation('first_cc_cid', $additionalData['cc_one_cc_cid'] ?? null);
            $paymentInfo->setAdditionalInformation('first_exp_month', $firstCcExpMonth);
            $paymentInfo->setAdditionalInformation('first_exp_year', $firstCcExpYear);
            $paymentInfo->setAdditionalInformation('first_cc_encrypted', $firstCcEncrypted);
            $paymentInfo->setAdditionalInformation('first_cc_installments', $firstCcInstallments);
            $paymentInfo->setAdditionalInformation('first_cc_id', $firstSavedCardId);
            $paymentInfo->setAdditionalInformation('first_cc_save', $firstCcCanSave);
            $paymentInfo->setAdditionalInformation('first_cc_amount', $additionalData['cc_one_cc_amount']);
            $paymentInfo->setAdditionalInformation('first_cc_amount_with_interest', $firstCcTotalWithInterest);

            $paymentInfo->setAdditionalInformation('second_cc_type', $secondCcType);
            $paymentInfo->setAdditionalInformation('second_cc_owner', $secondCcOwner);
            $paymentInfo->setAdditionalInformation('second_cc_number', $additionalData['cc_two_cc_number'] ?? null);
            $paymentInfo->setAdditionalInformation('second_cc_last4', $secondCcLast4);
            $paymentInfo->setAdditionalInformation('second_cc_cid', $additionalData['cc_two_cc_cid'] ?? null);
            $paymentInfo->setAdditionalInformation('second_cc_exp_month', $secondCcExpMonth);
            $paymentInfo->setAdditionalInformation('second_cc_exp_year', $secondCcExpYear);
            $paymentInfo->setAdditionalInformation('second_cc_encrypted', $secondCcEncrypted);
            $paymentInfo->setAdditionalInformation('second_cc_installments', $secondCcInstallments);
            $paymentInfo->setAdditionalInformation('second_cc_id', $secondSavedCardId);
            $paymentInfo->setAdditionalInformation('second_cc_save', $secondCcCanSave);
            $paymentInfo->setAdditionalInformation('second_cc_amount', $additionalData['cc_two_cc_amount']);
            $paymentInfo->setAdditionalInformation('second_cc_amount_with_interest', $secondCcTotalWithInterest);

            $this->checkoutSession->setData('first_pagseguropayment_installments', $firstCcInstallments);
            $this->checkoutSession->setData('second_pagseguropayment_installments', $secondCcInstallments);
            $this->updateInterest();
        }
    }

    /**
     * @throws LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function updateInterest()
    {
        $quote = $this->checkoutSession->getQuote();
        $quote->setTotalsCollectedFlag(false)->collectTotals();
    }
}
