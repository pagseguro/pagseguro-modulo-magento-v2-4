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

declare(strict_types=1);

namespace PagSeguro\Payment\Api\Data;

interface TransactionSearchResultsInterface extends \Magento\Framework\Api\SearchResultsInterface
{
    /**
     * Get transaction list.
     * @return TransactionInterface[]
     */
    public function getItems();

    /**
     * Set entity_id list.
     * @param TransactionInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}

