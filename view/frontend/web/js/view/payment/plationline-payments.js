/**
 * PlatiOnline payment payments javascritp
 *
 * @category    Xtreme
 * @package     Xtreme_PlatiOnline
 * @author      Marian-Daniel Ursache <marian.ursache@gmail.com>, Michael Mussulis <michael@xtreme-vision.net>
 * @copyright   Xtreme Vision SRL
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *
 *   Payment Redirect Component
 */
/*browser:true*/
/*global define*/
define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        Component,
        rendererList
    ) {
        'use strict';

        rendererList.push(
            {
                type: 'xtreme_plationline',
                component: 'Xtreme_PlatiOnline/js/view/payment/method-renderer/plationline-redirect-method'
            }
        );
        /** Add view logic here if needed */
        return Component.extend({});
    }
);
