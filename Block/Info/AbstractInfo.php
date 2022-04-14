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

namespace PagSeguro\Payment\Block\Info;

use Magento\Framework\DataObject;
use Magento\Framework\View\Element\Template\Context;
use Magento\Payment\Block\ConfigurableInfo;
use Magento\Payment\Gateway\ConfigInterface;
use Magento\Payment\Model\Config;

class AbstractInfo extends ConfigurableInfo
{
    protected $_template = 'PagSeguro_Payment::payment/info/default.phtml';

    /**
     * @var ConfigInterface
     */
    protected $config;

    /**
     * @var Config
     */
    protected $paymentConfig;

    /**
     * Info constructor.
     * @param Context $context
     * @param ConfigInterface $config
     * @param Config $paymentConfig
     * @param array $data
     */
    public function __construct(
        Context $context,
        ConfigInterface $config,
        Config $paymentConfig,
        array $data = []
    ) {
        parent::__construct($context, $config, $data);
        $this->paymentConfig = $paymentConfig;
    }

    /**
     * @inheritDoc
     */
    public function _construct()
    {
        $this->setTemplate($this->_template);
    }

    /**
     * Returns label
     *
     * @param string $field
     * @return \Magento\Framework\Phrase
     */
    protected function getLabel($field)
    {
        return __($field);
    }

    /**
     * Returns value view
     *
     * @param string $field
     * @param string $value
     * @return string | Phrase
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function getValueView($field, $value)
    {
        return __($value);
    }

}
