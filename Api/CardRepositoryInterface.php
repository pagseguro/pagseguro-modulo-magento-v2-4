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

namespace PagSeguro\Payment\Api;

use PagSeguro\Payment\Api\Data\CardInterface;
use PagSeguro\Payment\Api\Data\CardSearchResultsInterface;
use Magento\Framework\Api\SearchCriteriaInterface;

interface CardRepositoryInterface
{
    /**
     * Save Card
     * @param CardInterface $token
     * @return CardInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function save(
        CardInterface $token
    );

    /**
     * Retrieve Card
     * @param string $tokenId
     * @return CardInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getById($tokenId);

    /**
     * Retrieve Card matching the specified criteria.
     * @param SearchCriteriaInterface $searchCriteria
     * @return CardSearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(
        SearchCriteriaInterface $searchCriteria
    );

    /**
     * Card Queue
     * @param CardInterface $token
     * @return bool true on success
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function delete(
        CardInterface $token
    );

    /**
     * Delete Card by ID
     * @param string $tokenId
     * @return bool true on success
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteById($tokenId);
}

