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
use PagSeguro\Payment\Gateway\Http\Client\Api;
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
     * @var Api
     */
    private $api;

    /**
     * @param Context $context
     * @param PriceCurrencyInterface $priceCurrency
     * @param Data $helper
     */
    public function __construct(
        Context $context,
        PriceCurrencyInterface $priceCurrency,
        Data $helper,
        Api $api
    ) {
        $this->priceCurrency = $priceCurrency;
        $this->helper = $helper;
        $this->api = $api;
        parent::__construct($context);
    }

    public function getAllInstallments($price = null, $configGroup = 'pagseguropayment_one_cc', $configSection = 'payment', $creditCardType = 'VI')
    {

        $amount = (int)round($price * Data::ROUND_FACTOR);

        if ($creditCardType === 'VI') {
            $creditCardType = 'visa';
        }

        if ($creditCardType === 'ELO') {
            $creditCardType = 'elo';
        }

        if ($creditCardType === 'MC') {
            $creditCardType = 'mastercard';
        }

        if ($creditCardType === 'HC') {
            $creditCardType = 'hipercard';
        }

        if ($creditCardType === 'AE') {
            $creditCardType = 'amex';
        }

        $getInstallmentsFromApi = $this->getInstallmentsFromApi($amount, $configGroup, $configSection);

        $installmentPlans = $getInstallmentsFromApi['response']['payment_methods']['credit_card'][$creditCardType]['installment_plans'];

        $allInstallments = [
            ['value' => 1, 'text' => __('1x of %1 (without interest)', $this->priceCurrency->format($price, false))]
        ];

        try {
            if ($price > 0) {
                $maxInstallments = (int) $this->helper->getConfig('max_installments', $configGroup, $configSection) ?: 1;
                $defaultInterestRate = (float)$this->helper->getConfig('interest_rate', $configGroup, $configSection);
                $minInstallmentAmount = (float)$this->helper->getConfig('minimum_installment_amount', $configGroup, $configSection);
                $installmentsWithoutInterest = (int)$this->helper->getConfig('max_installments_without_interest', $configGroup, $configSection) ?: 1;


                if ($minInstallmentAmount > 0) {
                    while ($maxInstallments > ($price / $minInstallmentAmount))
                        $maxInstallments--;

                    while ($installmentsWithoutInterest > ($price / $minInstallmentAmount))
                        $installmentsWithoutInterest--;
                }

                $maxInstallments = ($maxInstallments == 0) ? 1 : $maxInstallments;
                foreach ($installmentPlans as $planKey => $planValue) {

                    $installmentNumber = $planValue['installments'];

                    $value = $planValue['installment_value'] / 100;

                    $total = $planValue['amount']['value'] / 100;
                    $interestFree = $planValue['interest_free'];
                    $interestText = ($interestFree) ? __('without interest') : __('with interest');

                    $allInstallments[] = [
                        'value' => $installmentNumber,
                        'text' => __(
                            '%1x of %2 (%3). Total: %4',
                            $installmentNumber,
                            $this->priceCurrency->format($value, false),
                            $interestText,
                            $this->priceCurrency->format($total, false)
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
     * Undocumented function
     *
     * @since 1.0.0
     *
     * @param [type] $price
     * @param string $configGroup
     * @param string $configSection
     * @return object
     */
    public function getInstallmentsFromApi($price, $configGroup = 'pagseguropayment_one_cc', $configSection = 'payment')
    {

        $maxInstallments = (int) $this->helper->getConfig('max_installments', $configGroup, $configSection) ?: 1;
        $installmentsWithoutInterest = (int) $this->helper->getConfig('max_installments_without_interest', $configGroup, $configSection) ?: 0;

        if ($installmentsWithoutInterest == 1) {
            $installmentsWithoutInterest = 0;
        }

        return $this->api->interest()->getInterestForInstallments($price, $maxInstallments, $installmentsWithoutInterest);
    }

}
