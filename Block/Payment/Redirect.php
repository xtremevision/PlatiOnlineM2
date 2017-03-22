<?php
/**
 * PlatiOnline payment module

 * @category    Xtreme
 * @package     Xtreme_PlatiOnline
 * @author      Marian-Daniel Ursache <marian.ursache@gmail.com>, Michael Mussulis <michael@xtreme-vision.net>
 * @copyright   Xtreme Vision SRL
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *
 * Redirect Action Block
*/

namespace Xtreme\PlatiOnline\Block\Payment;

use Xtreme\PlatiOnline\Block\BaseBlock;

class Redirect extends BaseBlock
{
    /**
     * Get the redirect URL
     * @return string
     */
    public function getRedirectUrl()
    {
        return $this->_paymentApi->getLastRedirect();
    }
}
