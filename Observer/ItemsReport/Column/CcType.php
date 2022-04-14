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

namespace PagSeguro\Payment\Observer\ItemsReport\Column;

use Magento\Framework\App\ResourceConnection;

class CcType
{
    protected $resourceConnection;
    protected $logger;

    public function __construct (
        \Psr\Log\LoggerInterface $logger,
        ResourceConnection $resorceConnection
    ) {
        $this->logger = $logger;
        $this->resourceConnection = $resorceConnection;
    }

    public function afterCreateFlatTable($subject, $result)
    {
        try {
            $connection = $this->resourceConnection->getConnection();
            $tableName = $connection->getTableName('pagseguropayment_items_report_flat_table');

            $connection->addColumn($tableName, "cc_type", array(
                'type'      => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'nullable'  => true,
                'length'    => 255,
                'after'     => null,
                'comment' => "Bandeira do Cartão"
            ));
        } catch (\Exception $e) {
            $this->logger->info("ItemsReport PagSeguro_Payment_Column (Exception) Reset: " . $e->getMessage());
        }
    }
}
