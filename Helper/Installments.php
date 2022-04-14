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

namespace PagSeguro\Payment\Helper;

use Exception;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Pricing\PriceCurrencyInterface;

/**
 * Installments data helper
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Installments extends AbstractHelper
{
    /**
     * @var Data
     */
    protected $helper;

    /**
     * @var PriceCurrencyInterface
     */
    protected $priceCurrency;

    /**
     * @param Context $context
     * @param PriceCurrencyInterface $priceCurrency
     * @param Data $helper
     */
    public function __construct(
        Context $context,
        PriceCurrencyInterface $priceCurrency,
        Data $helper
    ) {
        $this->priceCurrency = $priceCurrency;
        $this->helper = $helper;
        parent::__construct($context);
    }

    public function getAllInstallments($price = null, $configSection = 'pagseguropayment')
    {
        $allInstallments = [
            ['value' => 1, 'text' => __('1x of %1 (without interest)', $this->priceCurrency->format($price, false))]
        ];
        try {
            if ($price > 0) {
                $maxInstallments = (int)$this->helper->getConfig('max_installments', $configSection) ?: 1;
                $defaultInterestRate = (float)$this->helper->getConfig('interest_rate', $configSection);
                $minInstallmentAmount = (float)$this->helper->getConfig('minimum_installment_amount', $configSection);
                $installmentsWithoutInterest = (int)$this->helper->getConfig('max_installments_without_interest', $configSection) ?: 1;

                if ($minInstallmentAmount > 0) {
                    while ($maxInstallments > ($price / $minInstallmentAmount))
                        $maxInstallments--;

                    while ($installmentsWithoutInterest > ($price / $minInstallmentAmount))
                        $installmentsWithoutInterest--;
                }

                $maxInstallments = ($maxInstallments == 0) ? 1 : $maxInstallments;
                for ($i = 2; $i <= $maxInstallments; $i++) {
                    $interestRate = ($i <= $installmentsWithoutInterest) ? 0 : $defaultInterestRate;
                    if (!$interestRate) {
                        $interestType = $this->helper->getConfig('interest_type', $configSection);
                        if ($interestType == 'per_installments') {
                            // Interest per number of installments
                            $interestRate = (float)$this->helper->getConfig('interest_' . $i . '_installments', $configSection) / 100;
                        }
                    }

                    $value = ($i <= $installmentsWithoutInterest)
                        ? ($price / $i)
                        : $this->getInstallmentPrice($price, $i, $interestRate, $configSection);

                    $total = $value * $i;

                    $interestText = ($interestRate) ? __('with interest') : __('without interest');

                    $allInstallments[] = [
                        'value' => $i,
                        'text' => __(
                            '%1x of %2 (%3). Total: %4',
                            $i,
                            $this->priceCurrency->format($value, false),
                            $interestText,
                            $this->priceCurrency->format($total, false),
                            $this->priceCurrency->convert($interestRate)
                        )
                    ];
                }
            }
        } catch (\Exception $e) {
            $this->_logger->error($e->getMessage());
        }
        return $allInstallments;
    }

    /**
     * @param float $price
     * @param int $installment
     * @param float $interestRate
     * @param string $configSection
     * @return float
     * @throws Exception
     */
    public function getInstallmentPrice($price, $installment, $interestRate = null, $configSection = 'pagseguropayment')
    {
        $installmentAmount = $price / $installment;

        try {
            $installmentsWithoutInterest = (int)$this->helper->getConfig('max_installments_without_interest', $configSection) ?: 1;
            $hasInterest = $this->helper->getConfig('has_interest', $configSection);
            if ($hasInterest && $installment > $installmentsWithoutInterest) {

                if ($interestRate === null)
                    $interestRate = (float)$this->helper->getConfig('interest_rate', $configSection);

                $interestRate = $interestRate / 100;
                $interestType = $this->helper->getConfig('interest_type', $configSection);

                if ($interestRate || $interestType == 'per_installments') {
                    switch ($interestType) {
                        case 'price':
                            //Amortization
                            $installmentAmount = round($price * (($interestRate * pow((1 + $interestRate), $installment)) / (pow((1 + $interestRate), $installment) - 1)), 2);
                            break;
                        case 'compound':
                            //M = C * (1 + i)^n
                            $installmentAmount = ($price * pow(1 + $interestRate, $installment)) / $installment;
                            break;
                        case 'simple':
                            //M = C * ( 1 + ( i * n ) )
                            $installmentAmount = ($price * (1 + ($installment * $interestRate))) / $installment;
                            break;
                        case 'per_installments':
                            // Interest per number of installments
                            $interestRate = (float)$this->helper->getConfig('interest_' . $installment . '_installments', $configSection) / 100;
                            $installmentAmount = ($price * (1 + $interestRate)) / $installment;
                            break;
                    }
                } else {
                    if ($installment) {
                        $installmentAmount = $price / $installment;
                    }
                }
            }
        } catch (\Exception $e) {
            $this->_logger->error($e->getMessage());
        }

        return $installmentAmount;
    }

}
