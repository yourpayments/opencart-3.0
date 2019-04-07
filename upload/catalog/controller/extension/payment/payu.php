<?php
class ControllerExtensionPaymentPayu extends Controller
{
	public function index()
	{
		$data['button_confirm'] = $this->language->get('button_confirm');
		$data['text_loading'] = $this->language->get('text_loading');
		$data['continue'] = $this->url->link('checkout/success');
		
		$option = array(
			'merchant' => $this->config->get('payment_payu_merchant'), 
			'secretkey' => $this->config->get('payment_payu_secretkey'), 
			'debug' => $this->config->get('payment_payu_debug'),
			'button' => '
				<div class="buttons">
					<div class="pull-right">
						<input type="submit" value="'.$data['button_confirm'].'" id="button-confirm" class="btn btn-primary" />
					</div>
				</div>',
		);

		if ($this->config->get('payment_payu_LU') != "") {
			$option['luUrl'] = $this->config->get('payment_payu_LU');
		}

		$forSend = $this->buildPayUOrder();
		$pay = PayU::getInst()
						->setOptions($option)
						->setData($forSend)
						->LU();

		$data['pay'] = $pay;

		$template = 'extension/payment/payu';
		
		return $this->load->view($template, $data);
	}

	public function confirm()
	{
		if ($this->session->data['payment_method']['code'] == 'payment_payu') {
			// $this->load->model('checkout/order');
			// $this->model_checkout_order->addOrderHistory($this->session->data['order_id'], $this->config->get('payment_payu_order_status_id'));
		}
	}

	public function callback()
	{
		$option = array(
			'merchant' => $this->config->get('payment_payu_merchant'), 
			'secretkey' => $this->config->get('payment_payu_secretkey')
		);

		$payansewer = PayU::getInst()->setOptions( $option )->IPN();
		$order_id = $_POST['REFNOEXT'];

		$this->load->model('checkout/order');
		$order_info = $this->model_checkout_order->getOrder($order_id);
		$this->model_checkout_order->addOrderHistory($order_id, $this->config->get('payment_payu_order_status_id'));

		echo $payansewer;
	}

	public function result_payment()
	{
		$this->load->language('extension/payment/payu');

		$message = '';
		if (isset($_GET['err'])) {
			$message = $_GET['err'] .'<br>';
		}

		$result = isset($_GET['result']) ? $_GET['result'] : '';
		switch ($result) {
			case '-1': $message .= $this->language->get('payment_result_qiwi'); break;
			case '0' : $message .= $this->language->get('payment_result_success'); break;
			case '1' : $message .= $this->language->get('payment_result_failure'); break;
			default  : $message .= $this->language->get('payment_result_empty'); break;
		}

		$data['heading_title'] = $this->language->get('heading_title');
		$data['heading_subtitle'] = $this->language->get('heading_subtitle');
		$data['message'] = $message;

		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');

		$template = 'extension/payment/payu_result';
		$this->response->setOutput($this->load->view($template, $data));
	}

	protected function buildPayUOrder()
	{
		$this->load->model('checkout/order');

		$order_id = $this->session->data['order_id'];
		$order_info = $this->model_checkout_order->getOrder($order_id);

		$ret = array(
			'ORDER_REF'        => $order_id,
			'ORDER_PNAME'      => array(),
			'ORDER_PCODE'      => array(),
			'ORDER_PINFO'      => array(),
			'ORDER_PRICE'      => array(),
			'ORDER_QTY'        => array(),
			'ORDER_VAT'        => array(),
			'ORDER_PRICE_TYPE' => array(),
			'ORDER_SHIPPING'   => $order_info['total'],
			'PRICES_CURRENCY'  => $this->config->get('payment_payu_currency'),
			'LANGUAGE'         => $this->config->get('payment_payu_language'),
			'BACK_REF'         => $this->config->get('payment_payu_backref'),
		);

		foreach ($this->cart->getProducts() as $item) {
			$unitPrice = $this->tax->calculate($item['price'], $item['tax_class_id'], $this->config->get('config_tax'));

			$ret['ORDER_PNAME'][]      = $item['name'];
			$ret['ORDER_PCODE'][]      = $item['product_id'];
			$ret['ORDER_PINFO'][]      = $item['model'];
			$ret['ORDER_PRICE'][]      = $unitPrice;
			$ret['ORDER_QTY'][]        = $item['quantity'];
			$ret['ORDER_VAT'][]        = $this->config->get('payment_payu_vat');
			$ret['ORDER_PRICE_TYPE'][] = intval($this->config->get('payment_payu_entry_order_type')) == 0 ? 'NET' : 'GROSS';
			$ret['ORDER_SHIPPING']    -= $unitPrice * $item['quantity'];
		}

		$pref = array("FNAME" => "firstname", "LNAME" => "lastname", "ADDRESS" => "address_1", "ADDRESS2" => "address_2", "ZIPCODE" => "postcode", "CITY" => "city");
		foreach ($pref as $k => $v) {
			$bill = "payment_".$v;
			$deliv = "shipping_".$k;

			if (isset($order_info[$bill])) {
				$ret["BILL_".$k] = $order_info[$bill];
			}

			if (isset($order_info[$deliv])) {
				$ret["DELIVERY_".$k] = $order_info[$deliv];
			}
		}

		$nopref = array("EMAIL" => "email", "PHONE" => "telephone");
		foreach ($nopref as $k => $v) {
			$ret["BILL_".$k] = $order_info[$v];
			$ret["DELIVERY_".$k] = $order_info[$v];
		}

		return $ret;
	}
}

class PayU
{
	var 
		$luUrl = "https://secure.payu.ru/order/lu.php", 
		$button = "<input type='submit'>",
		$debug = 0,
		$showinputs = "hidden";

	private static $Inst = false, 
	               $merchant, 
	               $key;

	private $data = array(), 
	        $dataArr = array(), 
	        $answer = "";

	private	$LUcell = array(
			'MERCHANT' => 1, 
			'ORDER_REF' => 0, 
			'ORDER_DATE' => 1, 
			'ORDER_PNAME' => 1, 
			'ORDER_PGROUP' => 0,
			'ORDER_PCODE' => 1, 
			'ORDER_PINFO' => 0, 
			'ORDER_PRICE' => 1, 
			'ORDER_QTY' => 1, 
			'ORDER_VAT' => 1, 
			'ORDER_SHIPPING' => 1, 
			'PRICES_CURRENCY' => 1, 
			'PAY_METHOD' => 0, 
			'ORDER_PRICE_TYPE' => 1
		);

	private $IPNcell = array(
		"IPN_PID", 
		"IPN_PNAME", 
		"IPN_DATE", 
		"ORDERSTATUS"
	);

	private function __construct()
	{}

	private function __clone()
	{}

	public function __toString()
	{ 
		return ($this->answer === "") ? "<!-- Answer are not exists -->" : $this->answer;  
	}

	public static function getInst()
	{	
		if (self::$Inst === false) {
			self::$Inst = new PayU();
		}

		return self::$Inst;
	}

	#---------------------------------------------
	# Add all options for PayU object. 
	# Can change all public variables;
	# $opt = array( merchant, secretkey, [ luUrl, debug, button ] );
	#---------------------------------------------
	function setOptions($opt = array())
	{
		if (!isset($opt['merchant']) || !isset($opt['secretkey'])) {
			die("No params");
		}

		self::$merchant = $opt['merchant'];
		self::$key = htmlspecialchars_decode( $opt['secretkey'] );
		unset($opt['merchant'], $opt['secretkey']);

		if (count($opt) === 0) {
			return $this;
		}

		foreach ($opt as $k => $v) {
			$this->$k = $v;
		}

		return $this;
	}

	function setData($array = null)
	{	
		if ($array === null) {
			die("No data");
		}

		$this->dataArr = $array;
		
		return $this;
	}

	#--------------------------------------------------------
	#	Generate HASH
	#--------------------------------------------------------
	function Signature( $data = null ) 
	{		
		$str = "";
		foreach ($data as $v) {
			$str .= $this->convData($v);
		}

		if ($this->debug > 0) {
			$str .= '4TRUE';
		}

		if (function_exists('hash_hmac')) {
			return hash_hmac("md5", $str, self::$key);
		}

		return $this->hash_hmac("md5", self::$key,$str);
	}

	function hash_hmac($algo, $data, $key, $raw_output = false)
	{
		$algo = strtolower($algo);
		$pack = 'H'.strlen($algo($key));
		$size = 64;
		$opad = str_repeat(chr(0x5C), $size);
		$ipad = str_repeat(chr(0x36), $size);

		if (strlen($key) > $size) {
			$key = str_pad(pack($pack, $algo($key)), $size, chr(0x00));
		} else {
			$key = str_pad($key, $size, chr(0x00));
		}

		for ($i = 0; $i < strlen($key) - 1; $i++) {
			$opad[$i] = $opad[$i] ^ $key[$i];
			$ipad[$i] = $ipad[$i] ^ $key[$i];
		}

		$output = $algo($opad.pack($pack, $algo($ipad.$data)));

		return ($raw_output) ? pack($pack, $output) : $output;
	}

	#--------------------------------------------------------
	# Outputs a string for hmac format.
	# For a string like 'aa' it will return '2aa'.
	#--------------------------------------------------------
	private function convString($string) 
	{	
		return mb_strlen($string, '8bit') . $string;
	}

	#--------------------------------------------------------
	# The same as convString except that it receives
	# an array of strings and returns the string from all values within the array.
	#--------------------------------------------------------	
	private function convArray($array) 
	{
  		$return = '';
  		foreach ($array as $v) {
  			$return .= $this->convString( $v );
  		}

  		return $return;
	}

	private function convData( $val )
	{
		return ( is_array( $val ) ) ? $this->convArray( $val ) : $this->convString( $val );
	}
	#----------------------------

	#====================== LU GENERETE FORM =================================================

	public function LU()
	{	
		$arr = &$this->dataArr;
		$arr['MERCHANT'] = self::$merchant;

		if (!isset($arr['ORDER_DATE'])) {
			$arr['ORDER_DATE'] = date("Y-m-d H:i:s");
		}

		$arr['TESTORDER']  = ($this->debug == 1) ? "TRUE" : "FALSE";
		$arr['DEBUG'] = $this->debug;

		$arr['ORDER_HASH'] = $this->Signature($this->checkArray($arr));
		$this->answer = $this->genereteForm($arr);
		return $this;
	}

	#-----------------------------
	# Check array for correct data
	#-----------------------------
	private function checkArray( $data )
	{
		$this->cells = array();
		$ret = array();
		foreach ($this->LUcell as $k => $v) { 	
			if (isset($data[$k])) {
				$ret[$k] = $data[$k];
			} elseif ($v == 1) {
				die("$k is not set");
			}
		}

		return $ret;
	}

	#-----------------------------
	# Method which create a form
	#-----------------------------
	private function genereteForm($data)
	{	
		$form = '<form method="post" action="'.$this->luUrl.'" accept-charset="utf-8">';
		foreach ($data as $k => $v) {
			$form .= $this->makeString( $k, $v );
		}

		return $form . $this->button .'</form>';
	}	

	#-----------------------------
	# Make inputs for form
	#-----------------------------	
	private function makeString($name, $val)
	{
		$str = "";
		if (!is_array($val)) {
			return '<input type="'.$this->showinputs.'" name="'.$name.'" value="'.htmlspecialchars($val).'">'."\n";
		}

		foreach ($val as $v) {
			$str .= $this->makeString( $name.'[]', $v );
		}

		return $str;
	}

#======================= END LU =====================================	


#======================= IPN READ ANSWER ============================

	public function IPN()
	{	
		$arr = &$this->dataArr;
		$arr = $_POST;

		foreach ($this->IPNcell as $name) {
			if (!isset($arr[$name])) {
				die("Incorrect data");
			}
		}

		$hash = $arr["HASH"];  
		unset($arr["HASH"]);
		$sign = $this->Signature($arr);

		if ($hash != $sign) {
			return $this;
		}

		$datetime = date("YmdHis");
		$sign = $this->Signature(array(
			"IPN_PID" => $arr[ "IPN_PID" ][0], 
			"IPN_PNAME" => $arr[ "IPN_PNAME" ][0], 
			"IPN_DATE" => $arr[ "IPN_DATE" ], 
			"DATE" => $datetime
		));

		$this->answer = "<!-- <EPAYMENT>$datetime|$sign</EPAYMENT> -->";

		return $this;
	}

	#======================= END IPN ============================

	#======================= Check BACK_REF =====================
	function checkBackRef( $type = "http")
	{
		$path = $type.'://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];
		$tmp = explode("?", $path);
		$url = $tmp[0].'?';
		$params = array();

		foreach ($_GET as $k => $v) {
			if ($k != "ctrl") {
				$params[] = $k.'='.rawurlencode($v);
			}
		}

		$url = $url.implode("&", $params);
		$arr = array($url);
		$sign = $this->Signature( $arr );

		#echo "$sign === ".$_GET['ctrl'];
		$this->answer = ( $sign === $_GET['ctrl'] ) ? true : false;

		return $this->answer;
	}

	#======================= END Check BACK_REF =================
}
?>