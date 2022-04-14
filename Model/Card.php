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
namespace PagSeguro\Payment\Model;

use PagSeguro\Payment\Api\Data\CardInterface;

class Card extends \Magento\Framework\Model\AbstractModel implements CardInterface
{
    /**
     * CMS page cache tag.
     */
    const CACHE_TAG = 'pagseguro_api_card';

    /**
     * @var string
     */
    protected $_cacheTag = 'pagseguro_api_card';

    /**
     * Prefix of model events names.
     *
     * @var string
     */
    protected $_eventPrefix = 'pagseguro_api_card';

    /**
     * Initialize resource model.
     */
    protected function _construct()
    {
        $this->_init('PagSeguro\Payment\Model\ResourceModel\Card');
    }

    /**
     * Get Customer Id.
     *
     * @return string
     */
    public function getCustomerId()
    {
        return $this->getData(self::CUSTOMER_ID);
    }

    /**
     * Set CustomerId.
     * @param $customerId
     */
    public function setCustomerId($customerId)
    {
        $this->setData(self::CUSTOMER_ID, $customerId);
    }

    /**
     * Get Token.
     *
     * @return string
     */
    public function getToken()
    {
        return $this->getData(self::TOKEN);
    }

    /**
     * Set Token.
     * @param $token
     */
    public function setToken($token)
    {
        $this->setData(self::TOKEN, $token);
    }

    /**
     * Get CcType.
     *
     * @return string
     */
    public function getCcType()
    {
        return $this->getData(self::CC_TYPE);
    }

    /**
     * Set CcType.
     * @param $ccType
     */
    public function setCcType($ccType)
    {
        $this->setData(self::CC_TYPE, $ccType);
    }

    /**
     * Get CcOwner.
     *
     * @return string
     */
    public function getCcOwner()
    {
        return $this->getData(self::CC_OWNER);
    }

    /**
     * Set CcOwner.
     * @param $ccOwner
     */
    public function setCcOwner($ccOwner)
    {
        $this->setData(self::CC_OWNER, $ccOwner);
    }

    /**
     * Get CcLast4.
     *
     * @return int
     */
    public function getCcLast4()
    {
        return $this->getData(self::CC_LAST4);
    }

    /**
     * Set CcLast4.
     * @param $ccLast4
     */
    public function setCcLast4($ccLast4)
    {
        $this->setData(self::CC_LAST4, $ccLast4);
    }

    /**
     * Get CcExpMonth.
     *
     * @return int
     */
    public function getCcExpMonth()
    {
        return $this->getData(self::CC_EXP_MONTH);
    }

    /**
     * Set CcExpMonth.
     * @param $ccExpMonth
     */
    public function setCcExpMonth($ccExpMonth)
    {
        $this->setData(self::CC_EXP_MONTH, $ccExpMonth);
    }

    /**
     * Get CcExpYear.
     *
     * @return int
     */
    public function getCcExpYear()
    {
        return $this->getData(self::CC_EXP_YEAR);
    }

    /**
     * Set CcExpYear.
     * @param $ccExpYear
     */
    public function setCcExpYear($ccExpYear)
    {
        $this->setData(self::CC_EXP_YEAR, $ccExpYear);
    }

    /**
     * Get UpdatedAt.
     *
     * @return string
     */
    public function getUpdatedAt()
    {
        return $this->getData(self::UPDATED_AT);
    }

    /**
     * Set UpdatedAt.
     * @param $updatedAt
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->setData(self::UPDATED_AT, $updatedAt);
    }

    /**
     * Get CreatedAt.
     *
     * @return string
     */
    public function getCreatedAt()
    {
        return $this->getData(self::CREATED_AT);
    }

    /**
     * Set CreatedAt.
     * @param $createdAt
     */
    public function setCreatedAt($createdAt)
    {
        $this->setData(self::CREATED_AT, $createdAt);
    }

}
