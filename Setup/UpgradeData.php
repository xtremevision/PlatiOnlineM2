<?php
/**
 * PlatiOnline payment module

 * @category    Xtreme
 * @package     Xtreme_PlatiOnline
 * @author      Marian-Daniel Ursache <marian.ursache@gmail.com>, Michael Mussulis <michael@xtreme-vision.net>
 * @copyright   Xtreme Vision SRL
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *
 * Module Database Setup
 */

namespace Xtreme\PlatiOnline\Setup;

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\SalesSequence\Model\Builder;
use Magento\SalesSequence\Model\Config as SequenceConfig;

class UpgradeData implements UpgradeDataInterface
{
    /**
     * @var Builder
     */
    private $sequenceBuilder;

    /**
     * @var SequenceConfig
     */
    private $sequenceConfig;

    /**
     * @param SalesSetupFactory $salesSetupFactory
     * @param Builder $sequenceBuilder
     * @param SequenceConfig $sequenceConfig
     */
    public function __construct(
        Builder $sequenceBuilder,
        SequenceConfig $sequenceConfig
    ) {
        $this->sequenceBuilder = $sequenceBuilder;
        $this->sequenceConfig = $sequenceConfig;
    }

    /**
     * {@inheritdoc}
     */
    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
	 $setup->startSetup();

        /** Update visibility for states */
        $states = ['new', 'completed', 'canceled', 'holded', 'pending_payment'];
        foreach ($states as $state) {
            $setup->getConnection()->update(
                $setup->getTable('sales_order_status_state'),
                ['visible_on_front' => 1],
                ['state = ?' => $state]
            );
        }
        $setup->endSetup();
    }
}
