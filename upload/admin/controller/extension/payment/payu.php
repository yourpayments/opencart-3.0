<?php 
class ControllerExtensionPaymentPayu extends Controller {
	private $error = array(); 

	public function index() {
		$this->load->language('extension/payment/payu');
		
		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/setting');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('payment_payu', $this->request->post);
			
			$this->session->data['success'] = $this->language->get('text_success');
			
			$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true));
		}

		$arr = array( 
				"heading_title", "text_payment", "text_success", "text_pay", "text_card", 
				"entry_merchant", "entry_secretkey", "entry_debug", "entry_LU", "entry_order_status", 
				"entry_currency", "entry_backref", "entry_vat", "entry_order_type", "entry_language", "entry_status", 
				"entry_sort_order", "error_permission", "error_merchant", "error_secretkey",
				"entry_debug_on", "entry_debug_off", "entry_order_net", "entry_order_gross", "entry_ipn", "text_edit");

		foreach ($arr as $v) $data[$v] = $this->language->get($v);
		$data['button_save'] = $this->language->get('button_save');
		$data['button_cancel'] = $this->language->get('button_cancel');
		$data['text_enabled'] = $this->language->get('text_enabled');
		$data['text_disabled'] = $this->language->get('text_disabled');

		#$data['LUURL'] = "index.php?route=payment/payu/callback";


//------------------------------------------------------------
        $arr = array("warning", "merchant", "secretkey", "type");
        foreach ( $arr as $v ) $data['error_'.$v] = ( isset($this->error[$v]) ) ? $this->error[$v] : "";
//------------------------------------------------------------

		$data['breadcrumbs'] = array();

   		$data['breadcrumbs'][] = array(
       		'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
   		);

   		$data['breadcrumbs'][] = array(
       		'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true)
   		);

   		$data['breadcrumbs'][] = array(
       		'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/payment/payu', 'user_token=' . $this->session->data['user_token'], true)
   		);
				
		$data['action'] = $this->url->link('extension/payment/payu', 'user_token=' . $this->session->data['user_token'], true);
		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] .'&type=payment', 'SSL');

//------------------------------------------------------------
		$this->load->model('localisation/order_status');
		
		$data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();
		
		$arr = array( "payment_payu_merchant", "payment_payu_secretkey", "payment_payu_debug", "payment_payu_LU", "payment_payu_currency", 
					  "payment_payu_backref", "payment_payu_vat", "payment_payu_entry_order_type", "payment_payu_language", 
					  "payment_payu_status", "payment_payu_sort_order", "payment_payu_order_status_id" );

		foreach ($arr as $v) {
			$data[$v] = ( isset($this->request->post[$v]) ) ? $this->request->post[$v] : $this->config->get($v);
		}

		$data['payment_payu_LU']      = is_null($data['payment_payu_LU']) ? 'https://secure.ypmn.ru/order/lu.php' : $data['payment_payu_LU'];
		$data['payment_payu_backref'] = is_null($data['payment_payu_backref']) ? HTTPS_CATALOG . 'index.php?route=extension/payment/payu/result_payment' : $data['payment_payu_backref'];
		$data['payment_payu_ipn']     = HTTPS_CATALOG . 'index.php?route=extension/payment/payu/callback';

//------------------------------------------------------------

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');
				
		$this->response->setOutput($this->load->view('extension/payment/payu', $data));
	}

//------------------------------------------------------------
	private function validate() {
		if (!$this->user->hasPermission('modify', 'extension/payment/payu')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}
		
		if (!$this->request->post['payment_payu_merchant']) {
			$this->error['merchant'] = $this->language->get('error_merchant');
		}

		if (!$this->request->post['payment_payu_secretkey']) {
			$this->error['secretkey'] = $this->language->get('error_secretkey');
		}

		return (!$this->error) ? true : false ;
	}
}
?>