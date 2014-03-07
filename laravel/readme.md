# Omnipay Alipay Example, Build with Example

**commands
```
composer update -vvv
composer dump-autoload
php artisan dump-autoload
php artisan optimize

**start
```
http://youdomain.com/cart.html
```
composer update -vvv
composer dump-autoload
php artisan dump-autoload
php artisan optimize
```

### payto api, get payto url
```
Route::post(
    '/pay/alipay/payto.do',
    function () {
        $return_url = Input::getUriForPath('/pay/alipay/return.do');
        $notify_url = Input::getUriForPath('/pay/alipay/notify.do');
        $gateway    = Omnipay::create('Alipay_Express');
        $gateway->setPartner(Config::get('pay.alipay.id'));
        $gateway->setKey(Config::get('pay.alipay.key'));
        $gateway->setSellerEmail(Config::get('pay.alipay.email'));
        $gateway->setNotifyUrl($notify_url);
        $gateway->setReturnUrl($return_url);
        # new order
        # db
        $order    = array(
            'out_trade_no' => sprintf('%d%d', time(), mt_rand(1000, 9999)), //your site trade no, unique
            'subject'      => 'test', //order title
            'total_fee'    => Input::get('total_fee'), //order total fee
        );
        $response = $gateway->purchase($order)->send();
        # return a payto_url, and client redirect to alipay.
        return Response::json(['payto_url' => $response->getRedirectUrl()]);
    }
);
```

### pay return
```
/**
 * pay success client return.
 */
Route::get(
    '/pay/alipay/return.do',
    function () {
        //dd(Input:all());
        $gateway = Omnipay::create('Alipay_Express');
        $gateway->setPartner(Config::get('pay.alipay.id'));
        $gateway->setKey(Config::get('pay.alipay.key'));
        $gateway->setSellerEmail(Config::get('pay.alipay.email'));
        $options['request_params'] = Input::all();
        $options['ca_cert_path']   = storage_path() . '/cert/cacert.pem';
        $options['sign_type']      = 'MD5';
        $request                   = $gateway->completePurchase($options)->send();
        $debug_data                = $request->getData();
        if ($request->isSuccessful()) { //
            $out_trade_no = Input::get('out_trade_no');
            #####
            # eg: $order = Order::find($out_trade_no);
            # !!!!you should check your order status here for duplicate request.
            #####
            Event::fire('alipay.pay_success', ['out_trade_no' => $out_trade_no, 'meta' => Input::all()]);
            echo 'hey! pay verify success! make a redirect with client or server here';
        } else {
            echo 'hey! pay verify fail! make a redirect with client or server here';
        }
    }
);
```


### pay notify
```
/**
 * pay success server notify.(!!!not support local-test server)
 */
Route::get(
    '/pay/alipay/notify.do',
    function () {
        $gateway = Omnipay::create('Alipay_Express');
        $gateway->setPartner(Config::get('pay.alipay.id'));
        $gateway->setKey(Config::get('pay.alipay.key'));
        $gateway->setSellerEmail(Config::get('pay.alipay.email'));
        $options['request_params'] = Input::all();
        $options['ca_cert_path']   = storage_path() . '/cert/cacert.pem';
        $options['sign_type']      = 'MD5';
        $request                   = $gateway->completePurchase($options)->send();
        $debug_data                = $request->getData();
        if ($request->isSuccessful()) {
            $out_trade_no = Input::get('out_trade_no');
            #####
            # eg: $order = Order::find($out_trade_no);
            # !!!!you should check your order status here for duplicate request.
            #####
            Event::fire('alipay.pay_success', ['out_trade_no' => $out_trade_no, 'meta' => Input::all()]);
            die('success'); //it should be string 'success'
        } else {
            die('fail'); //it should be string 'fail'
        }
    }
);
```

