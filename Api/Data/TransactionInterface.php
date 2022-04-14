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

interface TransactionInterface
{
    /**
     * Constants for keys of data array. Identical to the name of the getter in snake case.
     */
    const ENTITY_ID = 'entity_id';
    const ORDER_ID = 'order_id';
    const PAGSEGURO_ID = 'pagseguro_id';
    const CODE = 'code';
    const REQUEST = 'request';
    const RESPONSE = 'response';
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

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
     * Get OrderId.
     *
     * @return string
     */
    public function getOrderId();

    /**
     * Set OrderId.
     * @param $orderId
     */
    public function setOrderId($orderId);

    /**
     * Get PagseguroId.
     *
     * @return string
     */
    public function getPagseguroId();

    /**
     * Set PagseguroId.
     * @param $pagseguroId
     */
    public function setPagseguroId($pagseguroId);

    /**
     * Get Code.
     *
     * @return string
     */
    public function getCode();

    /**
     * Set Code.
     * @param $code
     */
    public function setCode($code);

    /**
     * Get Request.
     *
     * @return string
     */
    public function getRequest();

    /**
     * Set Request.
     * @param $request
     */
    public function setRequest($request);

    /**
     * Get Response.
     *
     * @return string
     */
    public function getResponse();

    /**
     * Set Response.
     * @param $response
     */
    public function setResponse($response);

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
}
