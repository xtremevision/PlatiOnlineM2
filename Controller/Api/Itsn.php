<?php
/**
 * PlatiOnline payment module

 * @category    Xtreme
 * @package     Xtreme_PlatiOnline
 * @author      Marian-Daniel Ursache <marian.ursache@gmail.com>, Michael Mussulis <michael@xtreme-vision.net>
 * @copyright   Xtreme Vision SRL
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *
 * ITSN Action
 */
namespace Xtreme\PlatiOnline\Controller\Api;

class Itsn extends \Magento\Framework\App\Action\Action
{

    /**
     * @var \Magento\Framework\App\Cache\TypeListInterface
     */
    private $cacheTypeList;

    /**
     * @var \Magento\Framework\App\Cache\StateInterface
     */
    private $cacheState;

    /**
     * @var \Magento\Framework\App\Cache\Frontend\Pool
     */
    private $cacheFrontendPool;

    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    private $resultPageFactory;

    /**
     * @var \Magento\Framework\App\Request\Http
     */
    private $request;

    /**
     * @var \Xtreme\PlatiOnline\Model\Payment
     */
    private $paymentApi;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList
     * @param \Magento\Framework\App\Cache\StateInterface $cacheState
     * @param \Magento\Framework\App\Cache\Frontend\Pool $cacheFrontendPool
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param \Xtreme\PlatiOnline\Model\Payment $payment
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
        \Magento\Framework\App\Cache\StateInterface $cacheState,
        \Magento\Framework\App\Cache\Frontend\Pool $cacheFrontendPool,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Xtreme\PlatiOnline\Model\Payment $payment
    ) {
        parent::__construct($context);
        $this->cacheTypeList = $cacheTypeList;
        $this->cacheState = $cacheState;
        $this->cacheFrontendPool = $cacheFrontendPool;
        $this->resultPageFactory = $resultPageFactory;
        $this->paymentApi = $payment;
    }

    /**
     * Flush cache storage
     *
     */
    public function execute()
    {
        $response = $this->paymentApi->processItsn($this->getRequest()->getParams());
        $raspuns_xml = '<?xml version="1.0" encoding="UTF-8" ?>';
        $raspuns_xml .= '<itsn>';
        $raspuns_xml .= '<x_trans_id>' . $response['transactionId'] . '</x_trans_id>';
        $raspuns_xml .= '<merchServerStamp>' . date("Y-m-d H:m:s") . '</merchServerStamp>';
        $raspuns_xml .= '<f_response_code>' . ($response['success'] ? 1 : 0) . '</f_response_code>';
        $raspuns_xml .= '</itsn>';

        $this->getResponse()->setBody($raspuns_xml);
    }
}
