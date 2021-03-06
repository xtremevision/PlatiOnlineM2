<?php
/**
 * PlatiOnline payment module

 * @category    Xtreme
 * @package     Xtreme_PlatiOnline
 * @author      Marian-Daniel Ursache <marian.ursache@gmail.com>, Michael Mussulis <michael@xtreme-vision.net>
 * @copyright   Xtreme Vision SRL
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *
 * Failure Action Block
 */

namespace Xtreme\PlatiOnline\Block\Payment;

use Xtreme\PlatiOnline\Block\BaseBlock;

class Failure extends BaseBlock
{
    /**
     * Get the redirect URL
     * @return string
     */
    public function getOrderIdRedirectUrl()
    {
        return '#none';
    }

    public function getPOError()
    {
        return $this->checkoutSession->getData('PO_ERROR');
    }
	
	public function getPaymentLink()
	{
        return $this->checkoutSession->getData('PO_REDIRECT_URL');
	}
}
