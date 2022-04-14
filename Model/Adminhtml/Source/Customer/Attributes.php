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

namespace PagSeguro\Payment\Model\Adminhtml\Source\Customer;

use Magento\Eav\Model\ResourceModel\Entity\Attribute\CollectionFactory;
use Magento\Eav\Model\Entity\TypeFactory;

class Attributes implements \Magento\Framework\Data\OptionSourceInterface
{
    /** @var
     * CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var TypeFactory
     */
    protected $eavTypeFactory;

    public function __construct(
        CollectionFactory $collectionFactory,
        TypeFactory $eavTypeFactory
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->eavTypeFactory = $eavTypeFactory;
    }

   public function toOptionArray()
    {
        $options = [];
        foreach ($this->getOptions() as $optionValue => $optionLabel) {
            $options[] = ['value' => $optionValue, 'label' => $optionLabel];
        }
        return $options;
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        return $this->getOptions();
    }

    protected function getOptions()
    {
        /** @var \Magento\Eav\Model\Entity\Type $entityType */
        $entityType = $this->eavTypeFactory->create()->loadByCode('customer');

        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('entity_type_id', $entityType->getId());
        $collection->addFieldToFilter('frontend_input', 'text');
        $collection->addFieldToFilter('backend_type', ['nin' => ['static', 'decimal', 'int', 'text', 'boolean']]);
        $collection->addOrder('attribute_code', 'asc');

        $options = ['' => __('-- Empty --')];
        foreach ($collection->getItems() as $attribute) {
            /** @var Attribute $attribute */
            $options[$attribute->getAttributeCode()] = __($attribute->getFrontend()->getLabel());
        }

        return $options;
    }
}
