<?php
// Heading
$_['heading_title']      = 'PayU';

// Text 
$_['text_payment']       = 'Оплата';
$_['text_payu']       	 = '<a onclick="window.open(\'http://www.payu.ru/\');"><img src="view/image/payment/payu.png" alt="PayU" title="PayU" style="border: 1px solid #EEEEEE;" /></a>';
$_['text_success']       = 'Настройки модуля обновлены!';   
$_['text_pay']           = 'PayU';
$_['text_card']          = 'Credit Card';
$_['text_edit']          = 'Редактирование PayU';

// Entry
$_['entry_merchant']     = 'Merchant ID:';
$_['entry_secretkey']    = 'Secret key:';
$_['entry_debug']        = 'Режим отладки:';
$_['entry_debug_on']     = 'Включен';
$_['entry_debug_off']    = 'Выключен';

$_['entry_LU']        	 = '<span data-toggle="tooltip" title="По-умолчанию стоит стандартная ссылка для русских и украинских мерчантов">Ссылка Live Update:</span>';
$_['entry_backref']      = '<span data-toggle="tooltip" title="Если оставить пустым - клиент останется в системе PayU">Ссылка возврата клиента:</span>';
$_['entry_ipn']          = 'IPN URL';
$_['entry_order_status'] = 'Статус заказа после оплаты:';
$_['entry_currency']     = 'Валюта мерчанта';
$_['entry_vat']       	 = '<span data-toggle="tooltip" title="0 - без НДС">Процент НДС:</span>';
$_['entry_order_type']   = '<span data-toggle="tooltip" title="Да / Нет">НДС включен в сумму заказа</span>';
$_['entry_order_net']    = 'Нет';
$_['entry_order_gross']  = 'Включен';
$_['entry_language']     = '<span data-toggle="tooltip" title="по-умолчанию: RU">Язык страницы:</span>';

$_['entry_status']       = 'Статус:';
$_['entry_sort_order']   = 'Порядок сортировки:';

// Error
$_['error_permission']   = 'У Вас нет прав для управления этим модулем!';
$_['error_merchant']     = 'Неверный ID магазина (Merchant ID)!';
$_['error_secretkey']    = 'Отсутствует секретный ключ!';
?>