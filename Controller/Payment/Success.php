<?php
/**
 * PlatiOnline payment module

 * @category    Xtreme
 * @package     Xtreme_PlatiOnline
 * @author      Marian-Daniel Ursache <marian.ursache@gmail.com>, Michael Mussulis <michael@xtreme-vision.net>
 * @copyright   Xtreme Vision SRL
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *
 * Success Action
 */

namespace Xtreme\PlatiOnline\Controller\Payment;

class Success extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Xtreme\PlatiOnline\Model\Payment
     */
    protected $_paymentApi;

    /**
     * @var \Xtreme\PlatiOnline\Helper\Data
     */
    protected $_devToolHelper;

    protected $_checkoutSession;
    protected $_orderFactory;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Xtreme\PlatiOnline\Helper\Data $devToolHelper,
        \Xtreme\PlatiOnline\Model\Payment $paymentApi
    ) {
        parent::__construct($context);
        $this->_checkoutSession = $checkoutSession;
        $this->_orderFactory = $orderFactory;
        $this->_paymentApi = $paymentApi;
        $this->_devToolHelper = $devToolHelper;
    }

    /**
     * Execute controller
     */
    public function execute()
    {
        $postParams = $this->getRequest()->getParams();

        if (!empty($postParams)) {
            $response = $this->_paymentApi->processReturn($postParams);
            $resultRedirect = $this->resultRedirectFactory->create();
            if (!$response['success']) {
                $resultRedirect->setPath('plationline/payment/failure');
                $this->messageManager->addError(__($response['transactionText']));
            } else {
                $resultRedirect->setPath('checkout/onepage/success');
                $this->messageManager->addSuccess(__($response['transactionText']));
            }
        }

        return $resultRedirect;
    }
}
