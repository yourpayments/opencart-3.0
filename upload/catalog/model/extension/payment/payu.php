<?php 
class ModelExtensionPaymentPayu extends Model {
	public function getMethod($address, $total) {
		$this->load->language('extension/payment/payu');

		$method_data = array(
			'code'       => 'payu',
			'title'      => $this->language->get('text_title'),
			'terms'      => '',
			'sort_order' => $this->config->get('payment_payu_sort_order')
		);
		
		return $method_data;
	}
}
?>