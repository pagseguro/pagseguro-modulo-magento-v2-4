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

namespace PagSeguro\Payment\Api\Data;

interface CardInterface
{
    /**
     * Constants for keys of data array. Identical to the name of the getter in snake case.
     */
    const ENTITY_ID     = 'entity_id';
    const CUSTOMER_ID   = 'customer_id';
    const TOKEN         = 'token';
    const CC_TYPE       = 'cc_type';
    const CC_OWNER      = 'cc_owner';
    const CC_LAST4      = 'cc_last4';
    const CC_EXP_MONTH  = 'cc_exp_month';
    const CC_EXP_YEAR   = 'cc_exp_year';
    const CREATED_AT    = 'created_at';
    const UPDATED_AT    = 'updated_at';

    /**
     * Get EntityId.
     *
     * @return int
     */
    public function getEntityId();

    /**
     * Set EntityId.
     * @param $entityId
     */
    public function setEntityId($entityId);

    /**
     * Get CustomerId.
     *
     * @return int
     */
    public function getCustomerId();

    /**
     * Set CustomerId.
     * @param $customerId
     */
    public function setCustomerId($customerId);

    /**
     * Get Token.
     *
     * @return string
     */
    public function getToken();

    /**
     * Set Token.
     * @param $token
     */
    public function setToken($token);

    /**
     * Get CcType.
     *
     * @return string
     */
    public function getCcType();

    /**
     * Set CcType.
     * @param $ccType
     */
    public function setCcType($ccType);

    /**
     * Get CcOwner.
     *
     * @return string
     */
    public function getCcOwner();

    /**
     * Set CcOwner.
     * @param $ccOwner
     */
    public function setCcOwner($ccOwner);

    /**
     * Get CcLast4.
     *
     * @return int
     */
    public function getCcLast4();

    /**
     * Set CcLast4.
     * @param $ccLast4
     */
    public function setCcLast4($ccLast4);

    /**
     * Get CcExpMonth.
     *
     * @return int
     */
    public function getCcExpMonth();

    /**
     * Set CcExpMonth.
     * @param $ccExpMonth
     */
    public function setCcExpMonth($ccExpMonth);

    /**
     * Get CcExpYear.
     *
     * @return int
     */
    public function getCcExpYear();

    /**
     * Set CcExpYear.
     * @param $ccExpYear
     */
    public function setCcExpYear($ccExpYear);

    /**
     * Get CreatedAt.
     *
     * @return string
     */
    public function getCreatedAt();

    /**
     * Set CreatedAt.
     * @param $createdAt
     */
    public function setCreatedAt($createdAt);

    /**
     * Get UpdatedAt.
     *
     * @return string
     */
    public function getUpdatedAt();

    /**
     * Set UpdatedAt.
     * @param $updatedAt
     */
    public function setUpdatedAt($updatedAt);
}
