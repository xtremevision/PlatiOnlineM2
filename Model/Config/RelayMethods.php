<?php
/**
 * PlatiOnline payment module

 * @category    Xtreme
 * @package     Xtreme_PlatiOnline
 * @author      Marian-Daniel Ursache <marian.ursache@gmail.com>, Michael Mussulis <michael@xtreme-vision.net>
 * @copyright   Xtreme Vision SRL
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *
 * Relay Methods Model
 */

namespace Xtreme\PlatiOnline\Model\Config;

use Magento\Framework\Option\ArrayInterface;

class RelayMethods implements ArrayInterface
{
    /**
     * Options getter
     * @return array
     */
    public function toOptionArray()
    {
        $optionsArray = [];

        foreach ($this->toArray() as $key => $value) {
            $optionsArray[] = [
                'value' => $key,
                'label' => $value
            ];
        }

        return $optionsArray;
    }

    /**
     * Get options in "key-value" format
     * @return array
     */
    public function toArray()
    {
        return [
            'PTOR' => __('POST using JavaScript'),
            'POST_S2S_PO_PAGE' => __('POST server PO, customer get the PO template'),
            'POST_S2S_MT_PAGE' => __('POST server PO, customer get the Merchant template'),
            'SOAP_PO_PAGE' => __('SOAP server, customer get the PO template'),
            'SOAP_MT_PAGE' => __('SOAP server, customer get the Merchant template')
        ];
    }
}
