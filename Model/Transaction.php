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

use PagSeguro\Payment\Api\Data\TransactionInterface;

class Transaction extends \Magento\Framework\Model\AbstractModel implements TransactionInterface
{
    /**
     * CMS page cache tag.
     */
    const CACHE_TAG = 'pagseguro_api_transaction';

    /**
     * @var string
     */
    protected $_cacheTag = 'pagseguro_api_transaction';

    /**
     * Prefix of model events names.
     *
     * @var string
     */
    protected $_eventPrefix = 'pagseguro_api_transaction';

    /**
     * Initialize resource model.
     */
    protected function _construct()
    {
        $this->_init('PagSeguro\Payment\Model\ResourceModel\Transaction');
    }

    /**
     * Get OrderId.
     *
     * @return string
     */
    public function getOrderId()
    {
        return $this->getData(self::ORDER_ID);
    }

    /**
     * Set OrderId.
     * @param $orderId
     */
    public function setOrderId($orderId)
    {
        $this->setData(self::ORDER_ID, $orderId);
    }

    /**
     * Get PagseguroId.
     *
     * @return string
     */
    public function getPagseguroId()
    {
        return $this->getData(self::PAGSEGURO_ID);
    }

    /**
     * Set PagseguroId.
     * @param $pagseguroId
     */
    public function setPagseguroId($pagseguroId)
    {
        $this->setData(self::PAGSEGURO_ID, $pagseguroId);
    }

    /**
     * Get Code.
     *
     * @return string
     */
    public function getCode()
    {
        return $this->getData(self::CODE);
    }

    /**
     * Set Code.
     * @param $code
     */
    public function setCode($code)
    {
        $this->setData(self::CODE, $code);
    }

    /**
     * Get Request.
     *
     * @return string
     */
    public function getRequest()
    {
        return $this->getData(self::REQUEST);
    }

    /**
     * Set Request.
     * @param $request
     */
    public function setRequest($request)
    {
        $this->setData(self::REQUEST, $request);
    }

    /**
     * Get Response.
     *
     * @return string
     */
    public function getResponse()
    {
        return $this->getData(self::RESPONSE);
    }

    /**
     * Set Response.
     * @param $response
     */
    public function setResponse($response)
    {
        $this->setData(self::RESPONSE, $response);
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
