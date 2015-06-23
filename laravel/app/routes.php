<?php
/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the Closure to execute when that URI is requested.
|
*/
use Omnipay\Omnipay;

Route::get('/', function () {
    return View::make('hello');
});

Route::post('pay/alipay/payto', function () {
    $return_url = Input::getUriForPath('/pay/alipay/return.do');
    $notify_url = Input::getUriForPath('/pay/alipay/notify.do');
    $gateway = Omnipay::create('Alipay_Express');
    $gateway->setPartner(Config::get('pay.alipay.id'));
    $gateway->setKey(Config::get('pay.alipay.key'));
    $gateway->setSellerEmail(Config::get('pay.alipay.email'));
    $gateway->setNotifyUrl($notify_url);
    $gateway->setReturnUrl($return_url);
    # new order
    # db
    $order = array(
        'out_trade_no' => sprintf('%d%d', time(), mt_rand(1000, 9999)), //your site trade no, unique
        'subject' => 'test', //order title
        'total_fee' => Input::get('total_fee'), //order total fee
    );
    $response = $gateway->purchase($order)->send();
    # return a payto_url, and client redirect to alipay.
    return Response::json(['payto_url' => $response->getRedirectUrl()]);
});

Route::post('pay/alipay/mobile/payto', function () {
    $notify_url = Input::getUriForPath('/pay/alipay/mobile/notify');
    $gateway = Omnipay::create('Alipay_MobileExpress');
    $gateway->setPartner(Config::get('pay.alipay.id'));
    $gateway->setKey(Config::get('pay.alipay.key'));
    $gateway->setSellerEmail(Config::get('pay.alipay.email'));
    $gateway->setNotifyUrl($notify_url);
    //private key
    $gateway->setPrivateKey($yourPrivateKey);
    # new order
    # db
    $order = array(
        'out_trade_no' => sprintf('%d%d', time(), mt_rand(1000, 9999)), //your site trade no, unique
        'subject' => 'test', //order title
        'total_fee' => Input::get('total_fee'), //order total fee
        'it_b_pay' => '1d', //m-minute,h-hour,d-day,1c-today, integer only
        'sign_type' => 'RSA'
    );
    $response = $gateway->purchase($order)->send();
    # get order info string, the data contain a key `order_info_str`
    $data = $response->getRedirectData();

    return Response::json($data);
});

/**
 * pay success client return.
 */
Route::post('pay/alipay/return', function () {
    //dd(Input:all());
    $gateway = Omnipay::create('Alipay_Express');
    $gateway->setPartner(Config::get('pay.alipay.id'));
    $gateway->setKey(Config::get('pay.alipay.key'));
    $gateway->setSellerEmail(Config::get('pay.alipay.email'));
    $options['request_params'] = Input::all();
    $options['ca_cert_path'] = storage_path('cert/cacert.pem');
    $options['sign_type'] = 'MD5';
    $response = $gateway->completePurchase($options)->send();
    $debugData = $response->getData();
    if ($response->isSuccessful() && $response->isTradeStatusOk()) { //
        $outTradeNo = Input::get('out_trade_no');
        #####
        # eg: $order = Order::find($out_trade_no);
        # !!!!you should check your order status here for duplicate request.
        #####
        Event::fire('alipay.pay_success', ['out_trade_no' => $outTradeNo, 'meta' => Input::all()]);
        echo 'hey! pay verify success! make a redirect with client or server here';
    } else {
        echo 'hey! pay verify fail! make a redirect with client or server here';
    }
});

/**
 * pay success server notify.(!!!not support local-test server)
 */
Route::get('pay/alipay/notify', function () {
    $gateway = Omnipay::create('Alipay_Express');
    $gateway->setPartner(Config::get('pay.alipay.id'));
    $gateway->setKey(Config::get('pay.alipay.key'));
    $gateway->setSellerEmail(Config::get('pay.alipay.email'));
    $options['request_params'] = Input::all();
    $options['ca_cert_path'] = storage_path('cert/cacert.pem');
    $options['sign_type'] = 'MD5';
    $response = $gateway->completePurchase($options)->send();
    $debugData = $response->getData();
    if ($response->isSuccessful() && $response->isTradeStatusOk()) {
        $outTradeNo = Input::get('out_trade_no');
        #####
        # eg: $order = Order::find($out_trade_no);
        # !!!!you should check your order status here for duplicate request.
        #####
        Event::fire('alipay.pay_success', ['out_trade_no' => $outTradeNo, 'meta' => Input::all()]);
        die('success'); //it should be string 'success'
    } else {
        die('fail'); //it should be string 'fail'
    }
});

/**
 * pay success server notify.(!!!not support local-test server)
 */
Route::get('pay/alipay/mobile/notify', function () {
    //Gateway:Alipay_MobileExpress
    $gateway = Omnipay::create('Alipay_MobileExpress');
    $gateway->setPartner(Config::get('pay.alipay.id'));
    $gateway->setKey(Config::get('pay.alipay.key'));
    $gateway->setSellerEmail(Config::get('pay.alipay.email'));
    $options['request_params'] = Input::all();
    //Alipay Public Key File Path
    $options['ali_public_key'] = storage_path('cert/ali_public_key.pem');
    //The sign type should be RSA
    $options['sign_type'] = 'RSA';
    $response = $gateway->completePurchase($options)->send();
    $debugData = $response->getData();
    if ($response->isSuccessful() && $response->isTradeStatusOk()) {
        $outTradeNo = Input::get('out_trade_no');
        #####
        # eg: $order = Order::find($out_trade_no);
        # !!!!you should check your order status here for duplicate request.
        #####
        Event::fire('alipay.pay_success', ['out_trade_no' => $outTradeNo, 'meta' => Input::all()]);
        die('success'); //it should be string 'success'
    } else {
        die('fail'); //it should be string 'fail'
    }
});

