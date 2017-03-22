<?php
/**
 * PlatiOnline payment module

 * @category    Xtreme
 * @package     Xtreme_PlatiOnline
 * @author      Marian-Daniel Ursache <marian.ursache@gmail.com>, Michael Mussulis <michael@xtreme-vision.net>
 * @copyright   Xtreme Vision SRL
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *
 * Base Template Block
*/

namespace Xtreme\PlatiOnline\Block;

use Magento\Framework\UrlFactory;

class BaseBlock extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Xtreme\Test\Helper\Data
     */
    protected $_devToolHelper;

    /**
     * @var \Magento\Framework\Url
     */
    protected $_urlApp;

    /**
     * @var \Xtreme\Test\Model\Config
     */
    protected $_config;

    /**
     * @var \Xtreme\PlatiOnline\Model\Payment
     */
    protected $_paymentApi;

    /**
     * @var \Xtreme\PlatiOnline\Model\Payment
     */
    protected $_checkoutSession;

    /**
     * @param \Xtreme\Test\Block\Context $context
     */
    public function __construct(\Xtreme\PlatiOnline\Block\Context $context)
    {
        $this->_devToolHelper = $context->getTestHelper();
        $this->_config = $context->getConfig();
        $this->_urlApp=$context->getUrlFactory()->create();
        $this->_paymentApi = $context->getPaymentApi();
        $this->_checkoutSession = $context->getCheckoutSession();

        parent::__construct($context);
    }

    /**
     * Function for getting event details
     * @return array
     */
    public function getEventDetails()
    {
        return  $this->_devToolHelper->getEventDetails();
    }

    /**
     * Function for getting current url
     * @return string
     */
    public function getCurrentUrl()
    {
        return $this->_urlApp->getCurrentUrl();
    }

    /**
     * Function for getting controller url for given router path
     * @param string $routePath
     * @return string
     */
    public function getControllerUrl($routePath)
    {
        return $this->_urlApp->getUrl($routePath);
    }

    /**
     * Function for getting config value
     * @param string $path
     * @return string
     */
    public function getConfigValue($path)
    {
        return $this->_config->getCurrentStoreConfigValue($path);
    }

    /**
     * Function canShowTest
     * @return bool
     */
    public function canShowTest()
    {
    }
}
