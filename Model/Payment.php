<?php
/**
 * PlatiOnline payment module

 * @category    Xtreme
 * @package     Xtreme_PlatiOnline
 * @author      Marian-Daniel Ursache <marian.ursache@gmail.com>, Michael Mussulis <michael@xtreme-vision.net>
 * @copyright   Xtreme Vision SRL
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *
 * Payment Model
 */

namespace Xtreme\PlatiOnline\Model;

use \Magento\Payment\Model\Method\AbstractMethod as PaymentAbstractMethod;
use \Magento\Framework\Exception\LocalizedException;
use \Magento\Framework\App\Action\Context;
use \Magento\Sales\Model\Order;
use \Magento\Framework\DataObject;

class Payment extends PaymentAbstractMethod
{
    const CODE = 'xtreme_plationline';

    protected $_code = self::CODE;

    //protected $_isGateway		= true;
    protected $_isOffline		= true;
    //protected $_canCapture              = true;
    //protected $_canAuthorize            = true;
    //protected $_canRefund               = true;
    //protected $_canVoid                 = true;
    //protected $_canUseInternal          = false;

    /* Plationline response statuses */
    const PO_AUTHORIZING = 1;
    const PO_AUTHORIZED = 2;
    const PO_SETTLING = 3;
    const PO_CREDIT = 5;
    const PO_CANCELING = 6;
    const PO_CANCELED = 7;
    const PO_AUTH_REFUSED = 8;
    const PO_EXPIRED = 9;
    const PO_AUTH_ERROR = 10;
    const PO_PAYMENT_ONHOLD = 13;
    const PO_TIMEOUT_CC_PAGE = 16;
    const PO_ABANDONED_CC_PAGE = 17;

    /* Plationline credits response statuses */
    const PO_CREDIT_CREDITING = 1;
    const PO_CREDIT_CREDITED = 2;
    const PO_CREDIT_REFUSED = 3;
    const PO_CREDIT_CASHED = 4;

    /* Layout of the payment method */
    const PMLIST_HORISONTAL_LEFT = 0;
    const PMLIST_HORISONTAL = 1;
    const PMLIST_VERTICAL = 2;

    /* plationline payment action constant*/
    const PO_QUERY_ACTION = 0;
    const PO_REFUND_ACTION = 1;
    const PO_AUTHORIZE_ACTION = 2;
    const PO_INSTALLMENTS_ACTION = 20;

    const RELAY_PTOR = 'PTOR';
    const RELAY_POST_S2S_PO_PAGE = 'POST_S2S_PO_PAGE';
    const RELAY_POST_S2S_MT_PAGE = 'POST_S2S_MT_PAGE';
    const RELAY_SOAP_PO_PAGE = 'SOAP_PO_PAGE';
    const RELAY_SOAP_MT_PAGE = 'SOAP_MT_PAGE';

    private $platiOnlineApi = false;

    private $countryFactory;

    private $minAmount = null;
    private $maxAmount = null;
    private $supportedCurrencyCodes = ['USD', 'RON'];
    private $storeManager;
    private $localeResolver;
    private $checkoutSession;
    private $orderFactory;
    private $orderService;
    private $orderSender;
    private $_order;
    private $_transaction_id;
    private $_itsnMessage;
    protected $_builderInterface;
    protected $_invoiceService;
    protected $_transaction;
    private $_objectManager;
    private $_queryResponse;

    private $debugReplacePrivateDataKeys = ['number', 'exp_month', 'exp_year', 'cvc'];

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Locale\Resolver $locale,
        \Magento\Directory\Model\CountryFactory $countryFactory,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Sales\Model\Service\OrderService $orderService,
        \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender,
        \Xtreme\PlatiOnline\Helper\PO5 $platiOnline,
        \Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface $builderInterface,
	\Magento\Sales\Model\Service\InvoiceService $invoiceService,
	\Magento\Framework\DB\Transaction $transaction,
        \Magento\Framework\ObjectManagerInterface $objectmanager,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            null,
            null,
            $data
        );

        $this->storeManager = $storeManager;
        $this->localeResolver = $locale;
        $this->countryFactory = $countryFactory;
        $this->checkoutSession = $checkoutSession;
        $this->platiOnlineApi = $platiOnline;
        $this->logger = $logger;
        $this->orderFactory = $orderFactory;
        $this->orderService = $orderService;
        $this->orderSender = $orderSender;
        $this->_builderInterface = $builderInterface;
	$this->_invoiceService = $invoiceService;
        $this->_transaction = $transaction;
        $this->_objectManager = $objectmanager;
    }

    /**
     * Authorize payment abstract method
     *
     * @param \Magento\Framework\DataObject|InfoInterface $payment
     * @param float $amount
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     * @api
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    /*public function authorize(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        if (!$this->canAuthorize()) {
            throw new \Magento\Framework\Exception\LocalizedException(__('The authorize action is not available.'));
        }
    }*/

    /**
     * Payment capture
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param float $amount
     * @return $this
     * @throws \Magento\Framework\Validator\Exception
     */
    /*public function capture(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {

    }*/

    protected function getImage()
    {
        return $this->storeManager->getBaseStaticDir() . "images/plationline_logo.jpg";
    }

    /**
     * Payment refund
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param float $amount
     * @return $this
     * @throws \Magento\Framework\Validator\Exception
     */
    public function refund(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        $transactionId = $payment->getParentTransactionId();
        $order = $payment->getOrder();
        $request = [
            'f_order_number' => $order->getIncrementId(),
            'f_amount' => (float) $amount,
            'x_trans_id' => (int) $transactionId,
        ];

        try {
            $response = $this->callApi($request, self::PO_REFUND_ACTION, 'refund');

            $this->logger->debug(array("refund: " => $response), null, true);
            if ($response_refund['PO_REFUND_RESPONSE']['PO_ERROR_CODE'] == 1) {
                $this->logger->debug(array("refund: " => $response), null, true);
                throw new LocalizedException(
                    new \Magento\Framework\Phrase(__(
                        "Error on refunding action: "
                        . $response_refund['PO_REFUND_RESPONSE']['PO_ERROR_REASON']
                    ))
                );
            }

            switch ($response['PO_REFUND_RESPONSE']['X_RESPONSE_CODE']) {
                case '1':
                    $order->addStatusToHistory(
                        'cancel_plationline',
                        __('Transaction has been successfuly refunded')
                    );
                    break;
                case '10':
                    throw new LocalizedException(
                        new \Magento\Framework\Phrase(__('Errors occured, transaction ') . $transid . ' ' . __('NOT REFUNDED'))
                    );
            }
        } catch (LocalizedException $e) {
            $this->debugData(['transaction_id' => $transactionId, 'exception' => $e->getMessage()]);
            $this->logger->error(__('Payment refunding error.'));
            throw new \Magento\Framework\Validator\Exception(__('Payment refunding error.'));
        }

        $payment
            ->setTransactionId($transactionId . '-' . \Magento\Sales\Model\Order\Payment\Transaction::TYPE_REFUND)
            ->setParentTransactionId($transactionId)
            ->setIsTransactionClosed(1)
            ->setShouldCloseParentTransaction(1);

        return $this;
    }

    /**
     * Get Redirect Url for last order from session
     * @return string
     */
    public function getLastRedirect()
    {
	try
	{
    	$last_order = $this->checkoutSession->getLastRealOrder();
		$this->_order = $last_order;
		
	    $this->logger->debug(array(
		"orderPlaceRedirectUrl(): last_order_id" => $last_order->getIncrementId(),	    
	    ), null, true);
        } catch (\Exception $e) {
            $this->logger->debug(['request' => $request, 'exception' => $e->getMessage()], null, true);
            throw new \Magento\Framework\Validator\Exception(__('Payment redirect error.' . $e->getMessage()));
        }

	$this->getPORedirectUrl($last_order);

        $redirectUrl = $this->checkoutSession->getData('PO_REDIRECT_URL');
        if (empty($redirectUrl)) {
            throw new LocalizedException(
                new \Magento\Framework\Phrase(__('Can not redirect to payment gateway.'))
            );
        }

	$this->_objectManager->create('Magento\Sales\Model\OrderNotifier')->notify($last_order);
        return $redirectUrl;
    }

    private function getPORedirectUrl($order)
    {
	$this->checkoutSession->setData('PO_ERROR', 0);
        $this->checkoutSession->setData('PO_REDIRECT_URL', '');
        $request = $this->getPostData($order);

        try {
            $this->logger->debug(array("before callApi" => $request), null, true);
            $response = $this->callApi($request);
            $this->logger->debug(array("after callApi: " => $response), null, true);
            if (!$response) {
                throw new LocalizedException(
                    new \Magento\Framework\Phrase(__('Invalid response from payment server'))
                );
            }
	    if($response['PO_ERROR_CODE'] == 0)
	    {
	        $this->saveTransactionBeforePayment($response);
        	$this->checkoutSession->setData('PO_REDIRECT_URL', $response['PO_REDIRECT_URL']);
	    }
    	    else
	    {
		$url = $this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_WEB)."plationline/payment/failure";
		$this->checkoutSession->setData('PO_REDIRECT_URL', $url);
		$this->checkoutSession->setData('PO_ERROR', 1);
	    }
        } catch (\Exception $e) {
            $this->logger->debug(['request' => $request, 'exception' => $e->getMessage()], null, true);
            throw new \Magento\Framework\Validator\Exception(__('Payment redirect error.' . $e->getMessage()));
        }

        return $this;
    }

    /**
     * Prepare array to send it to gateway page via POST
     *
     * @param
     * @return array
     */
    public function getPostData(\Magento\Sales\Model\Order $order)
    {
        if (empty($order)) {
            throw new LocalizedException(
                new \Magento\Framework\Phrase(__('Invalid quote data'))
            );
        }

        $f_request = [
            'f_order_number' => $order->getIncrementId(),
            'f_amount' => number_format($order->getBaseGrandTotal(), 2, '.', ''),
            'f_currency' => $this->storeManager->getStore()->getCurrentCurrency()->getCode(),
            'f_auth_minutes' => $this->getConfigData('payment_timeout'),
                // 0 - waiting forever, 20 - default (in minutes)
            'f_language' => substr($this->localeResolver->getDefaultLocale(), 0, 2),
            'customer_info' => $this->getAddressData(
                $order->getBillingAddress()
            ),
            'card_holder_info' => [
                'same_info_as' => 0 // 0 - different info, 1- same info as customer_info
            ],
            'transaction_relay_response' => [
                'f_relay_response_url' => $this->storeManager->getStore()->getBaseUrl() . "plationline/payment/success",
                // PTOR, POST_S2S_PO_PAGE, POST_S2S_MT_PAGE, SOAP_PO_PAGE, SOAP_MT_PAGE
                'f_relay_method' => $this->getConfigData('relay_method'),
                 // Valoarea = 1 (default value)
                 // Valoarea = 0 (systemul PO trimite rezultatul doar pentru tranzactiile "Autorizate" si "In curs de verificare")
                'f_post_declined' => 1,
                 // default 0
                'f_relay_handshake' => 1
            ],
            'f_order_cart' => [],
            'f_order_string' => 'Comanda nr. ' . $order->getIncrementId() . ' pe site-ul ' . $this->storeManager->getStore()->getBaseUrl(),
            'shipping_info' => ['same_info_as' => '1'],
        ];

        $shipping = $order->getShippingAddress();
        // shipping address
        if (isset($shipping) && !empty($shipping)) {
            // same info ass has to be first or else xml does not verify
            $f_request['shipping_info'] = array_merge(['same_info_as' => 0], $this->getAddressData(
                $shipping,
                'address'
            ));
        }

        foreach ($order->getAllItems() as $item) {
            if ($item->getParentItem()) {
                continue;
            }

            $f_request['f_order_cart'][] = [
                'prodid' => $item->getProductId(),
                'name' => $item->getName(),
                'description' => $item->getDescription(),
                'qty' => $item->getQtyOrdered(),
                'itemprice' => (float) $item->getPrice(),
                'vat' => (float) $item->getTaxAmount(),
                'stamp' => date('Y-m-d'),
                'prodtype_id' => 0
            ];
        }

        //coupon 1
        if (abs($order->getDiscountAmount()) > 0) {
            $f_request['f_order_cart']['coupon1'] = [
                'key' => '0',
                'value' => abs($order->getDiscountAmount()),
                'percent' => 0,
                'workingname' => __('Discount'),
                'type' => 0,
                'scop' => 1,
                'vat' => 0,
            ];
        }

        //coupon 2
        if (abs($order->getGiftCardsAmountUsed()) > 0) {
            $f_request['f_order_cart']['coupon2'] = [
                'key' => '0',
                'value' => abs($order->getGiftCardsAmountUsed()),
                'percent' => 0,
                'workingname' => __('Discount'),
                'type' => 0,
                'scop' => 1,
                'vat' => 0
            ];
        }

        // declare $f_request['f_order_cart']['coupon1'], $f_request['f_order_cart']['coupon2']; we index the field ['coupon'] to have different names in array and to avoid overwriting the values
        // the array to xml method takes care of this case by looking for "coupon" substring

        if ($order->getShippingAmount() > 0) {
            $f_request['f_order_cart']['shipping'] = [
                'name' => 'Shipping & Handling',
                'price' => number_format($order->getShippingAmount(), 2, '.', ''),
                'pimg' => 0,
                'vat' => number_format($order->getShippingTaxAmount(), 2, '.', ''),
            ];
        }

        return $f_request;
    }

    /**
     * Get address formated for sending
     * @param  [type] $address [description]
     * @param  [type] $email   [description]
     * @return [type]          [description]
     */
    protected function getAddressData(\Magento\Sales\Model\Order\Address $address, $addressName = 'invoice')
    {
        $addressData = [
            'contact' => [
                'f_email' => $address->getEmail(),
                'f_send_sms' => ($this->getConfigData('send_customer_sms') ? 1 : 0),
                'f_first_name' => $address->getFirstname(),
                'f_middle_name' => $address->getMiddlename(),
                'f_last_name' => $address->getLastname(),
            ],
            $addressName => [
                'f_zip' => $address->getPostcode(),
                'f_country' => $this->countryFactory->create()->loadByCode($address->getCountryId())->getName(),
                'f_state' => $address->getRegion(),
                'f_city' => $address->getCity(),
                'f_address' => str_replace("\n", ' ', join(', ', $address->getStreet())),
            ]
        ];

        if (strlen($address->getTelephone()) >= 4) {
            $addressData['contact']['f_phone'] = $address->getTelephone();
            $addressData['contact']['f_mobile_number'] = $address->getTelephone();
        }

        if ($address->getCompany()) {
            $addressData[$addressName]['f_company'] = $address->getCompany();
        }

        return $addressData;
    }

    /**
     * Process ITSN response from PO server
     * @param  array  $parameters
     */
    public function processItsn(array $parameters)
    {
        $this->logger->debug(array("processItsn: " => $parameters), null, true);

        $itsnMessage = $this->decryptItsnResponse($parameters);
        $this->logger->debug(array('itsn: ' => $itsnMessage), null, true);
        $this->_itsnMessage = $itsnMessage;
        $keys = array_keys($itsnMessage);
        $itsnMessage = $itsnMessage[$keys[0]];

        $success = $this->query(
            $itsnMessage['F_ORDER_NUMBER'],
            $itsnMessage['X_TRANS_ID']
        );

        $this->_order = $this->orderFactory->create()->loadByIncrementId($itsnMessage['F_ORDER_NUMBER']);
        $this->_transaction_id = $itsnMessage['X_TRANS_ID'];
		
        $statusCode = $this->_queryResponse['ORDER']['TRANZACTION']['STATUS_FIN1']['CODE'];

        if ($statusCode == self::PO_AUTHORIZED)
	{
            if ($this->_order->getState() != \Magento\Sales\Model\Order::STATE_PROCESSING)
	    {
                //$this->orderSender->send($order);
                $this->_order->setAuthorized(true);
                $this->_order->save();
            }
        }
	else if (in_array($statusCode, [self::PO_TIMEOUT_CC_PAGE, self::PO_ABANDONED_CC_PAGE]))
	{
	    $stateName = $this->getPOStateName($statusCode, null);
	    $poComment = $this->getPOComment($statusCode, null, $this->_transaction_id, ' (RELAY)') ;
    	    $this->logger->debug(array("Timeout or Abandoned: " => $poComment), null, true);
	    $this->_order->addStatusToHistory(
        	$stateName,
        	$poComment
    	    );
            $this->_order->save();
        }
        else if ($statusCode == self::PO_AUTH_REFUSED)
	{
	    $stateName = $this->getPOStateName($statusCode, null);
	    $poComment = $this->getPOComment($statusCode, null, $this->_transaction_id, ' (RELAY)') ;
    	    $this->logger->debug(array("PO Declined: " => $poComment), null, true);
	    $this->_order->addStatusToHistory(
        	$stateName,
        	$poComment
    	    );
            $this->_order->save();
	}
		
        //$this->_handleAuthorization();
		if($statusCode == self::PO_AUTHORIZED)
			$this->_createInvoice($statusCode == self::PO_AUTHORIZED);

        return [
            'success' => $success,
            'transactionId' => $itsnMessage['X_TRANS_ID']
        ];
    }

    /**
     * Decrypt a Itsn Response from PO
     * @param  array  $parameters   input data
     * @return array
     */
    public function decryptItsnResponse(array $parameters)
    {
        $this->platiOnlineApi->setRSAKeyDecrypt($this->getConfigData('rsa_private_itsn'));
        $this->platiOnlineApi->setIVITSN($this->getConfigData('iv_itsn'));

        return $this->platiOnlineApi->itsn(
            $parameters['f_itsn_message'],
            $parameters['f_crypt_message']
        );
    }

    public function processReturn(array $parameters = null)
    {
        if (in_array($this->getConfigData('relay_method'), ['SOAP_PO_PAGE', 'SOAP_MT_PAGE'])) {
            $soap = file_get_contents("php://input");
            $soapResponse = $this->platiOnlineApi->parse_soap_response($soap);
            $parameters = [
                'F_Relay_Message' => $soapResponse['PO_RELAY_REPONSE']['F_RELAY_MESSAGE'],
                'F_Crypt_Message' => $soapResponse['PO_RELAY_REPONSE']['F_CRYPT_MESSAGE']
            ];
        }

        $this->logger->debug(array("processReturn: " => $parameters), null, true);

        $authorizationResponse = $this->decryptResponse($parameters);
        $authData = isset($authorizationResponse['PO_AUTH_URL_RESPONSE'])
            ? $authorizationResponse['PO_AUTH_URL_RESPONSE']
            : $authorizationResponse['PO_AUTH_RESPONSE'];
        $this->logger->debug(array("processReturn: " => $authorizationResponse), null, true);
        // we lie, there is no query
        $success = $this->processQuery([
            'PO_QUERY_RESPONSE' => [
                'PO_ERROR_CODE' => 0,
                'ORDER' => [
                    'F_ORDER_NUMBER' => $authData['F_ORDER_NUMBER'],
                    'TRANZACTION' => [
                        'X_TRANS_ID' => $authData['X_TRANS_ID'],
                        'STATUS_FIN1' => [
                            'CODE' => $authData['X_RESPONSE_CODE'],
                        ],
                        'STATUS_FIN2' => [
                            'CODE' => '-',
                        ],
                    ],
                ],
            ]
        ]);

        $this->_order = $this->orderFactory->create()->loadByIncrementId($authData['F_ORDER_NUMBER']);
        $this->_transaction_id = $authData['X_TRANS_ID'];		
        $statusCode = $authData['X_RESPONSE_CODE'];
	$stateName = $this->getPOStateName($statusCode, null);
	$poComment = $this->getPOComment($statusCode, null, $this->_transaction_id, ' (RELAY)') ;
	$this->logger->debug(array("processReturn statename: " => $stateName, "poComment" => $poComment), null, true);
		
	$this->_order->addStatusToHistory(
            $stateName,
            $poComment
        );
		
		//$this->_order->setState($stateName)->setStatus($statusCode);
        if ($statusCode == self::PO_AUTHORIZED) {
            if ($this->_order->getState() != \Magento\Sales\Model\Order::STATE_PROCESSING) {
				$this->logger->debug(array("processReturn setAuthorized" => true), null, true);
                $this->_order->setAuthorized(true);
            }
        }
		
		$this->logger->debug(array("processReturn orderSave" => true), null, true);
		$this->_order->save();
		//$this->updateTransaction($authData['X_TRANS_ID']);
		
        return [
            'success' => $authData['X_RESPONSE_CODE'] != self::PO_AUTH_REFUSED,
            'transactionId' => $authData['X_TRANS_ID'],
            'transactionText' => $this->getPOUserText($authData['X_RESPONSE_CODE'])
        ];
    }

    private function decryptResponse(array $parameters)
    {
        $this->platiOnlineApi->setRSAKeyDecrypt($this->getConfigData('rsa_private_itsn'));
        $this->platiOnlineApi->setIVITSN($this->getConfigData('iv_itsn'));

        return $this->platiOnlineApi->auth_response(
            $parameters['F_Relay_Message'],
            $parameters['F_Crypt_Message']
        );
    }
    /**
     * Process ITSN response from PO server
     * @param  array  $parameters
     */
    public function query($orderNumber, $transactionId)
    {
        $request = [
            'f_website' => str_ireplace('www.', '', $_SERVER['SERVER_NAME']),
            'f_order_number' => $orderNumber,
            'x_trans_id' => $transactionId
        ];

        $response = $this->callApi($request, self::PO_QUERY_ACTION, 'query');

        return $this->processQuery($response);
    }

    /**
     * Call PO api
     * @param  array  $request request data
     * @param  [type] $action  request type
     * @return array           response
     */
    protected function callApi(array $request, $action = self::PO_AUTHORIZE_ACTION, $function = 'auth')
    {
        $this->platiOnlineApi->f_login = $this->getConfigData('login_id');
        $request['f_website'] = str_ireplace('www.', '', $_SERVER['SERVER_NAME']);

        $this->platiOnlineApi->setRSAKeyEncrypt($this->getConfigData('rsa_public_auth'));
        $this->platiOnlineApi->setIV($this->getConfigData('iv_auth'));

        // test mode: 0 - disabled, 1 - enabled
        $this->platiOnlineApi->test_mode = $this->getConfigData('test');

        //plationline autorizare
        $response = $this->platiOnlineApi->$function($request, $action);

        return $response;
    }

    /**
     * Process PO query response
     * @param  array $response    PO response data
     * @return [type]           [description]
     */
    public function processQuery($response)
    {
        $keys = array_keys($response);
        $responseData = $response[$keys[0]];

        $this->logger->debug(array("processQuery: " => $responseData), null, true);
		$this->_queryResponse = $responseData;

        if ($responseData['PO_ERROR_CODE'] != 0) {
            return false;
        }

        return true;
    }

    protected function _createInvoice($closed)
    {	
        $this->logger->debug(array("order_id" => $this->_order->getIncrementId(), "txid" => $this->_transaction_id), null, true);

        if (!$this->_order->canInvoice()) {
            //when order cannot create invoice, need to have some logic to take care
            $this->_order->addStatusToHistory($this->_order->getStatus(), // keep order status/state
                'Error in creating an invoice', $notified = true);
        }

        //$this->_order->getPayment()->setTransactionId($this->_transaction_id);
        //$this->_order->getPayment()->place();
        //$this->_order->save();
		$this->saveTransaction($closed);

        $invoice = $this->_invoiceService->prepareInvoice($this->_order);
        $invoice->register();
        $invoice->save();
        /*$transactionSave = $this->_transaction->addObject(
            $invoice
        )->addObject(
            $invoice->getOrder()
        );
        $transactionSave->save();*/
		
	$invoiceSender = $this->_objectManager->get('Magento\Sales\Model\Order\Email\Sender\InvoiceSender');
        $invoiceSender->send($invoice);
        //send notification code
	$this->_order->addStatusHistoryComment(
            __('Notified customer about invoice #%1.', $invoice->getId())
        )
        ->setIsCustomerNotified(true)
        ->save();
    }

    protected function _handleAuthorization($underVerification = true)
    {
        $payment = $this->_order->getPayment();
        $payment->setPreparedMessage("some message");
        $payment->setTransactionId($this->_transaction_id);
        $trans = $this->_builderInterface;
        $transaction = $trans->setPayment($payment)
            ->setOrder($this->_order)
            ->setTransactionId($this->_transaction_id)
            ->setAdditionalInformation(
                [\Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS => array('id'=>$this->_transaction_id)]
            )
            ->setFailSafe(true)
            //build method creates the transaction and returns the object
            ->build(\Magento\Sales\Model\Order\Payment\Transaction::TYPE_REFUND);
        $payment->addTransactionCommentsToOrder(
            $transaction,
            "some message"
        );
        $payment->setIsTransactionClosed(0);

        if (!$underVerification) {
            $payment->setIsTransactionPending(false);
            $this->_createInvoice();
            $payment->registerAuthorizationNotification($this->_itsnMessage['F_AMOUNT']);
            //$this->_order->setStatus(Order::STATE_PROCESSING);
        } else {
            $payment->setIsTransactionPending(true);
            $this->_order->setStatus(Order::STATE_PAYMENT_REVIEW);
			$this->_objectManager->create('Magento\Sales\Model\OrderNotifier')->notify($this->_order);
        }
        $this->_order->save();
    }


    /**
     * Save magento transaction details
     * @param  \Magento\Sales\Model\Order  $order
     * @param  array  $response
     * @param  boolean $closed
     * @return boolean
     */
    public function saveTransaction($closed = true)
    {
        //$keys = array_keys($this->_queryResponse);
        //$responseData = $this->_queryResponse[$keys[0]];
        $this->logger->debug(array("saveTransaction" => $this->_queryResponse), null, true);

        $transactionId = $this->_queryResponse['ORDER']['TRANZACTION']['X_TRANS_ID'];
        $state = $this->_queryResponse['ORDER']['TRANZACTION']['STATUS_FIN1']['CODE'];
        $creditState = $state == self::PO_CREDIT ? $this->_queryResponse['ORDER']['TRANZACTION']['STATUS_FIN2']['CODE'] : null;

        // prepare payment transaction
        $payment = $this->_order->getPayment();
        $payment->setTransactionId($transactionId);
        $payment->setLastTransId($transactionId);
        $payment->setCcTransId($transactionId);
        $payment->setIsTransactionClosed($closed);

        // save transaction raw details
        $transaction = $payment->addTransaction(\Magento\Sales\Model\Order\Payment\Transaction::TYPE_ORDER);
        $transaction->setAdditionalInformation(
            \Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS,
            $this->_queryResponse
        );
        $transaction->save();

        $this->_order->addStatusToHistory(
            $this->getPOStateName($state, $creditState),
            $this->getPOComment($state, $creditState, $transactionId, ' (ITSN)')
        );

        return $this->_order->save();
    }

    public function saveTransactionBeforePayment($response)
    {
        //$keys = array_keys($this->_queryResponse);
        //$responseData = $this->_queryResponse[$keys[0]];
        $this->logger->debug(array("saveTransactionBeforePayment" => $response), null, true);

        $transactionId = $response['X_TRANS_ID'];

        // prepare payment transaction
        $payment = $this->_order->getPayment();
        $payment->setTransactionId($transactionId);
        $payment->setLastTransId($transactionId);
        $payment->setCcTransId($transactionId);
        $payment->setIsTransactionClosed(false);

        // save transaction raw details
        $transaction = $payment->addTransaction(\Magento\Sales\Model\Order\Payment\Transaction::TYPE_ORDER);
        $transaction->setAdditionalInformation(
            \Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS,
            $response
        );
        $transaction->save();

		$creditState = null;
		$stateName = $this->getPOStateName(self::PO_AUTHORIZING, $creditState);
        $poComment = $this->getPOComment(self::PO_AUTHORIZING, $creditState, $transactionId);
        $this->logger->debug(array("statename" => $stateName, "poComment" => $poComment), null, true);

        $this->_order->addStatusToHistory(
            $stateName,
            $poComment
        );

        return $this->_order->save();
    }

    /**
     * Get PO state name
     * @param  int $status          transaction type
     * @param  int $creditState     credit type
     * @return string               PO status string
     */
    protected function getPOStateName($status, $creditState)
    {
        switch ($status) {
            case self::PO_AUTHORIZING:
                return 'processing_plationline';
            case self::PO_AUTHORIZED:
                return 'processed_plationline';
            case self::PO_SETTLING:
                return 'pending_settled_plationline';
            case self::PO_CREDIT:
                switch ($creditState) {
                    case self::PO_CREDIT_CREDITING:
                        return 'pending_credited_plationline';
                    case self::PO_CREDIT_CREDITED:
                        return 'credited_plationline';
                    case self::PO_CREDIT_REFUSED:
                        return 'credit_cancel_plationline';
                    case self::PO_CREDIT_CASHED:
                        return 'credit_cashed_plationline';
                };
                return 'unknown_credit_state_' . $creditState;
            case self::PO_CANCELING:
                return 'canceling_plationline';
            case self::PO_CANCELED:
                return 'cancel_plationline';
            case self::PO_AUTH_REFUSED:
                return 'payment_refused_plationline';
            case self::PO_EXPIRED:
                return 'expired30_plationline';
            case self::PO_AUTH_ERROR:
                return 'error_plationline';
            case self::PO_PAYMENT_ONHOLD:
                return 'onhold_plationline';
	    case self::PO_TIMEOUT_CC_PAGE:
                return 'timeoutccpage_plationline';
	    case self::PO_ABANDONED_CC_PAGE;
		return 'abandonedccpage_plationline';
        }
    }

    /**
     * Get PO comment string based on response status
     * @param  int $status
     * @param  int $creditState
     * @param  int $transactionId
     * @return string
     */
    protected function getPOComment($status, $creditState, $transactionId, $suffix = null)
    {
        switch ($status) {
            case self::PO_AUTHORIZING:
				$comment = __('PlatiOnline pending confirmation, Transaction code: ');
                break;
            case self::PO_AUTHORIZED:
                $comment = __('PlatiOnline confirmation via, Transaction code: ');
				break;
            case self::PO_SETTLING:
                $comment = __('PlatiOnline pending settlement, Transaction code: ');
				break;
            case self::PO_CREDIT:
                switch ($creditState) {
                    case self::PO_CREDIT_CREDITING:
                        $comment = __('PlatiOnline pending credit, Transaction code: ');
						break;
                    case self::PO_CREDIT_CREDITED:
						$comment = __('PlatiOnline credited, Transaction code: ');
                        break;
                    case self::PO_CREDIT_REFUSED:
                        $comment = __('PlatiOnline credit refused, Transaction code: ');
                        break;
                    case self::PO_CREDIT_CASHED:
                        $comment = __('PlatiOnline credit cashed, Transaction code: ');
                        break;
					default:
						$comment = __('PlatiOnline unknown credit state, Transaction code: ');
                        break;
                };
				break;
            case self::PO_CANCELING:
                $comment = __('PlatiOnline pending cancel, Transaction code: ');
				break;
            case self::PO_CANCELED:
                $comment = __('PlatiOnline canceled, Transaction code: ');
				break;
            case self::PO_AUTH_REFUSED:
                $comment = __('PlatiOnline refused, Transaction code: ');
				break;
            case self::PO_EXPIRED:
                $comment = __('PlatiOnline expired, Transaction code: ');
				break;
            case self::PO_AUTH_ERROR:
                $comment = __('PlatiOnline error, Transaction code: ');
				break;
            case self::PO_PAYMENT_ONHOLD:
                $comment = __('PlatiOnline on hold, Transaction code: ');
				break;
	    case self::PO_TIMEOUT_CC_PAGE:
                $comment = __('PlatiOnline timeout payment page, Transaction code: ');
				break;
	    case self::PO_ABANDONED_CC_PAGE;
                $comment = __('PlatiOnline abandoned cart, Transaction code: ');
				break;
        }
		
		$comment .= $transactionId;
		if(isset($suffix)) $comment .= $suffix;
		$comment .= PHP_EOL;
		return $comment;
    }

    /**
     * Get PO user string based on response status
     * @param  int $status
     * @return string
     */
    protected function getPOUserText($status)
    {
        switch ($status) {
            case self::PO_AUTHORIZED:
                return __('Transaction authorized.');
            case self::PO_AUTH_REFUSED:
                return __('Transaction failed.');
            case self::PO_PAYMENT_ONHOLD:
                return __('Transaction is pending verification.');
        }
    }

    /**
     * Get PO order status
     * @param  int $status
     * @return string
     */
    protected function getPOOrderStatus($status)
    {
        switch ($status) {
            case self::PO_AUTHORIZED:
                return \Magento\Sales\Model\Order::STATE_COMPLETE;
            case self::PO_AUTH_REFUSED:
                return \Magento\Sales\Model\Order::STATE_CANCELED;
            case self::PO_PAYMENT_ONHOLD:
                return \Magento\Sales\Model\Order::STATE_HOLDED;
        }

        return \Magento\Sales\Model\Order::STATE_PROCESSING;
    }

    /**
     * Determine method availability based on quote amount and config data
     *
     * @param \Magento\Quote\Api\Data\CartInterface|null $quote
     * @return bool
     */
    public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null)
    {
/*        if ($quote && (
            $quote->getBaseGrandTotal() < $this->_minAmount
            || ($this->_maxAmount && $quote->getBaseGrandTotal() > $this->_maxAmount))
        ) {
            return false;
        }

        if (!$this->getConfigData('api_key')) {
            return false;
        }
*/
        return true;
        //return parent::isAvailable($quote);
    }

    /**
     * Availability for currency
     *
     * @param string $currencyCode
     * @return bool
     */
    public function canUseForCurrency($currencyCode)
    {
/*        if (!in_array($currencyCode, $this->_supportedCurrencyCodes)) {
            return false;
        }
*/        return true;
    }

    /*public function getConfigPaymentAction()
    {
	return self::ACTION_AUTHORIZE;
    }*/

    public function getOrderPlaceRedirectUrl()
    {
        return "plationline/payment/redirect/";
    }
}
