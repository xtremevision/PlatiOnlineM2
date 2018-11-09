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

use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\SalesSequence\Model\Builder;
use Magento\SalesSequence\Model\Config as SequenceConfig;

class InstallData implements InstallDataInterface
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
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function install(
        ModuleDataSetupInterface $setup,
        ModuleContextInterface $context
    ) {
        /**
         * Install order states
         */
        $data = [];
        $statuses = [
            'processing_plationline' => __('Processing PO'),
            'processed_plationline' => __('Processed PO'),
            'pending_settled_plationline' => __('Pending PO'),
            'pending_credited_plationline' => __('Pending Credit PO'),
            'credited_plationline' => __('Credited PO'),
            'credit_cancel_plationline' => __('Credit Cancel PO'),
            'credit_cashed_plationline' => __('Credit Cashed PO'),
            'canceling_plationline' => __('Canceling PO'),
            'cancel_plationline' => __('Canceled PO'),
            'payment_refused_plationline' => __('Refused PO'),
            'expired30_plationline' => __('Expired PO'),
            'error_plationline' => __('Error PO'),
            'onhold_plationline' => __('On Hold PO'),
            'timeoutccpage_plationline' => __('Timeout CC Page PO'),
            'abandonedccpage_plationline' => __('Abandoned CC Page PO')
        ];

        foreach ($statuses as $code => $info) {
            $data[] = ['status' => $code, 'label' => $info];
        }
        $setup->getConnection()->insertArray($setup->getTable('sales_order_status'), ['status', 'label'], $data);

        $data = [];
        $states = [
            'new' => [
                'statuses' => ['processing_plationline'],
                'visible_on_front' => true,
            ],
            'pending_payment' => [
                'statuses' => [
		    'pending_settled_plationline',
		    'pending_credited_plationline',
		    'credited_plationline',
		    'timeoutccpage_plationline',
		    'abandonedccpage_plationline'
		],
                'visible_on_front' => true,
            ],
            'completed' => [
                'statuses' => ['processed_plationline', 'credit_cashed_plationline'],
                'visible_on_front' => true,
            ],
            'canceled' => [
                'statuses' => [
                    'canceling_plationline',
                    'credit_cancel_plationline',
                    'cancel_plationline',
                    'payment_refused_plationline',
                    'expired30_plationline',
                    'error_plationline'
                ],
                'visible_on_front' => true,
            ],
            'holded' => [
                'statuses' => ['onhold_plationline'],
                'visible_on_front' => true,
            ],
        ];

        foreach ($states as $code => $info) {
            if (isset($info['statuses'])) {
                foreach ($info['statuses'] as $status) {
                    $data[] = [
                        'status' => $status,
                        'state' => $code,
                        'is_default' => 0,
                    ];
                }
            }
        }
        $setup->getConnection()->insertArray(
            $setup->getTable('sales_order_status_state'),
            ['status', 'state', 'is_default'],
            $data
        );

        /** Update visibility for states */
        //$states = ['new', 'processing', 'complete', 'closed', 'canceled', 'holded', 'payment_review'];
        $states = ['pending_payment'];
        foreach ($states as $state) {
            $setup->getConnection()->update(
                $setup->getTable('sales_order_status_state'),
                ['visible_on_front' => 1],
                ['state = ?' => $state]
            );
        }

    }
}
