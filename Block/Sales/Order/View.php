<?php

namespace Xtreme\PlatiOnline\Block\Sales\Order;
use Magento\Customer\Model\Context;
use Magento\Framework\View\Element\Template;
use Magento\Sales\Model\Order;

//class View extends \Magento\Framework\View\Element\Template
class View extends \Magento\Sales\Block\Order\View
{
    protected $_orderFactory;
    protected $_orderConfig;
    protected $httpContext;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\App\Http\Context $httpContext
     * @param \Magento\Payment\Helper\Data $paymentHelper
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\Http\Context $httpContext,
        \Magento\Payment\Helper\Data $paymentHelper,
        array $data,
        \Magento\Sales\Model\Order $orderFactory,
        \Magento\Sales\Model\Order\Config $orderConfig
    ) {
        $this->httpContext = $httpContext;
        $this->_orderFactory = $orderFactory;
        $this->_orderConfig = $orderConfig;
        parent::__construct($context, $registry, $httpContext, $paymentHelper, $data);
    }

    /**
     * Initialize data and prepare it for output
     *
     * @return string
     */
    protected function _beforeToHtml()
    {
        $this->prepareBlockData();
        return parent::_beforeToHtml();
    }

    /**
     * Prepares block data
     *
     * @return void
     */
    protected function prepareBlockData()
    {
        $order = $this->getOrder();

        $this->addData(
            [
                'is_order_visible' => $this->isVisible($order),
                'view_order_url' => $this->getUrl(
                    'sales/order/view/',
                    ['order_id' => $order->getEntityId()]
                ),
                'print_url' => $this->getUrl(
                    'sales/order/print',
                    ['order_id' => $order->getEntityId()]
                ),
                'can_print_order' => $this->isVisible($order),
                'can_view_order'  => $this->canViewOrder($order),
                'order_id'  => $order->getIncrementId()
            ]
        );
    }

    /**
     * Can view order
     *
     * @param Order $order
     * @return bool
     */
    protected function canViewOrder(Order $order)
    {
        return $this->httpContext->getValue(Context::CONTEXT_AUTH)
        && $this->isVisible($order);
    }

    /**
     * Is order visible
     *
     * @param Order $order
     * @return bool
     */
    protected function isVisible(Order $order)
    {
        return !in_array(
            $order->getStatus(),
            $this->_orderConfig->getInvisibleOnFrontStatuses()
        );
    }
    
    public function getOrder(){
        $orderId = $this->getRequest()->getParam('orderId');
        return $this->_orderFactory->load($orderId);
    }


}