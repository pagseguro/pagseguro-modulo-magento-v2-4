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

namespace PagSeguro\Payment\Model\Total\Quote;

use PagSeguro\Payment\Helper\Installments;
use PagSeguro\Payment\Model\OneCreditCard\Ui\ConfigProvider;
use Magento\Checkout\Model\Session;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\ShippingAssignmentInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address\Total;
use Magento\Quote\Model\Quote\Address\Total\AbstractTotal;

class Interest extends AbstractTotal
{
    /**
     * @var Session
     */
    protected $checkoutSession;

    /**
     * @var Installments
     */
    private $helperInstallments;

    /**
     * @var CartRepositoryInterface
     */
    private $quoteRepository;

    /**
     * PagSeguro Interest constructor.
     *
     * @param Session $checkoutSession
     * @param Installments $helperInstallments
     * @param CartRepositoryInterface $quoteRepository
     */
    public function __construct(
        Session $checkoutSession,
        Installments $helperInstallments,
        CartRepositoryInterface $quoteRepository
    ) {
        $this->setCode('pagseguropayment_interest');
        $this->checkoutSession = $checkoutSession;
        $this->helperInstallments = $helperInstallments;
        $this->quoteRepository = $quoteRepository;
    }

    /**
     * Return selected installments
     *
     * @return int
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function getInstallments()
    {
        $installments = 0;

        return $installments;
    }

    /**
     * Calculate interest rate amount
     *
     * @return int|mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Exception
     */
    protected function getInterestAmount()
    {
        $installments = $this->getInstallments();
        if (is_array($installments)) {
            $totalWithInterest = 0;

            /** @var \Magento\Quote\Model\Quote $quote */
            $quoteId = $this->checkoutSession->getQuoteId();
            if ($quoteId) {
                $quote = $this->quoteRepository->get($quoteId);

                foreach ($installments as $key => $installment) {
                    $amount = $quote->getPayment()->getAdditionalInformation($key . '_amount');

                    if ($installment <= 1) {
                        $totalWithInterest += $amount;
                        continue;
                    }

                    $installment = $installment ?? 1;

                    $installmentsPrice = $this->helperInstallments->getInstallmentPrice(
                        $amount, $installment, null, $quote->getPayment()->getMethod()
                    );
                    $totalWithInterest += ($installmentsPrice * $installment);
                }

                $grandTotal = $quote->getGrandTotal() - $quote->getPagseguropaymentInterestAmount();
                if ($totalWithInterest > $grandTotal) {
                    return $totalWithInterest - $grandTotal;
                }
            }
        } else if ($installments > 1) {
            /** @var \Magento\Quote\Model\Quote $quote */
            $quoteId = $this->checkoutSession->getQuoteId();
            if ($quoteId) {
                $quote = $this->quoteRepository->get($quoteId);
                $grandTotal = $quote->getGrandTotal() - $quote->getPagseguropaymentInterestAmount();
                $installmentsPrice = $this->helperInstallments->getInstallmentPrice($grandTotal, $installments, null, $quote->getPayment()->getMethod());
                $totalWithInterest = $installmentsPrice * $installments;
                if ($totalWithInterest > $grandTotal) {
                    return $totalWithInterest - $grandTotal;
                }
            }
        }


        return 0;
    }

    /**
     * Collect address discount amount
     *
     * @param Quote $quote
     * @param ShippingAssignmentInterface $shippingAssignment
     * @param Total $total
     *
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function collect(
        Quote $quote,
        ShippingAssignmentInterface $shippingAssignment,
        Total $total
    ) {
        parent::collect($quote, $shippingAssignment, $total);

        $items = $shippingAssignment->getItems();
        if (!count($items)) {
            return $this;
        }

        $interest = $this->getInterestAmount();

        $quote->setPagseguropaymentInterestAmount($interest);
        $quote->setBasePagseguropaymentInterestAmount($interest);

        $total->setPagSeguroInterestDescription($this->getCode());
        $total->setPagseguropaymentInterestAmount($interest);
        $total->setBasePagseguropaymentInterestAmount($interest);

        $total->addTotalAmount($this->getCode(), $interest);
        $total->addBaseTotalAmount($this->getCode(), $interest);

        return $this;
    }

    /**
     * @param Quote $quote
     * @param Total $total
     *
     * @return array
     */
    public function fetch(Quote $quote, Total $total)
    {
        $result = null;
        $amount = $total->getPagseguropaymentInterestAmount();

        if ($amount) {
            $result = [
                'code' => $this->getCode(),
                'title' => __('Interest Rate'),
                'value' => $amount
            ];
        }

        return $result;
    }
}
