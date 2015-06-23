# Omnipay Alipay Example, Build with Laravel

* commands
```
composer update -vvv
php artisan optimize
```

* Start
```
//There is a cart.html under laravel/public folder
http://yourdomain.com/cart.html
```

### Payto API, get payto url
```php
Route::post('pay/alipay/payto', function () {
    $returnUrl = Input::getUriForPath('/pay/alipay/return');
    $notifyUrl = Input::getUriForPath('/pay/alipay/notify');
    $gateway    = Omnipay::create('Alipay_Express');
    $gateway->setPartner(Config::get('pay.alipay.id'));
    $gateway->setKey(Config::get('pay.alipay.key'));
    $gateway->setSellerEmail(Config::get('pay.alipay.email'));
    $gateway->setNotifyUrl($returnUrl);
    $gateway->setReturnUrl($notifyUrl);
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
});
```

### Pay Success Client Return(1 min limit to verify)
```php
/**
 * pay success client return.
 */
Route::get('pay/alipay/return', function () {
    $gateway = Omnipay::create('Alipay_Express');
    $gateway->setPartner(Config::get('pay.alipay.id'));
    $gateway->setKey(Config::get('pay.alipay.key'));
    $gateway->setSellerEmail(Config::get('pay.alipay.email'));
    $options['request_params'] = Input::all();
    $options['ca_cert_path']   = storage_path() . '/cert/cacert.pem';
    $options['sign_type']      = 'MD5';
    $request                   = $gateway->completePurchase($options)->send();
    $debugData                = $request->getData();
    if ($request->isSuccessful()) { //
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
```


### Pay Success Server Notify
```php
/**
 * pay success server notify.(!!!not support local-test server)
 */
Route::post('pay/alipay/notify', function () {
    $gateway = Omnipay::create('Alipay_Express');
    $gateway->setPartner(Config::get('pay.alipay.id'));
    $gateway->setKey(Config::get('pay.alipay.key'));
    $gateway->setSellerEmail(Config::get('pay.alipay.email'));
    $options['request_params'] = Input::all();
    $options['ca_cert_path']   = storage_path() . '/cert/cacert.pem';
    $options['sign_type']      = 'MD5';
    $request                   = $gateway->completePurchase($options)->send();
    $debugData                = $request->getData();
    if ($request->isSuccessful()) {
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
```

# TL;DR

## Alipay Mobile Express, Get Order Info String
```php
Route::post('pay/alipay/mobile/payto', function () {
    $notifyUrl = Input::getUriForPath('/pay/alipay/mobile/notify');
    $gateway    = Omnipay::create('Alipay_MobileExpress');
    $gateway->setPartner(Config::get('pay.alipay.id'));
    $gateway->setKey(Config::get('pay.alipay.key'));
    $gateway->setSellerEmail(Config::get('pay.alipay.email'));
    $gateway->setNotifyUrl($notifyUrl);
    //private key
    $gateway->setPrivateKey($yourPrivateKey);
    # new order
    # db
    $order    = array(
        'out_trade_no' => sprintf('%d%d', time(), mt_rand(1000, 9999)), //your site trade no, unique
        'subject'      => 'test', //order title
        'total_fee'    => Input::get('total_fee'), //order total fee
        'it_b_pay'     => '1d', //m-minute,h-hour,d-day,1c-today, integer only
        'sign_type'    => 'RSA'
    );
    $response = $gateway->purchase($order)->send();
    # get order info string, the data contain a key `order_info_str`
    $data = $response->getRedirectData();

    return Response::json($data);
});
```
## Alipay Mobile Express, Server Notify
```php
Route::post('/pay/alipay/mobile/notify', function () {
    //Gateway:Alipay_MobileExpress
    $gateway = Omnipay::create('Alipay_MobileExpress');
    $gateway->setPartner(Config::get('pay.alipay.id'));
    $gateway->setKey(Config::get('pay.alipay.key'));
    $gateway->setSellerEmail(Config::get('pay.alipay.email'));
    $options['request_params'] = Input::all();
    //Alipay Public Key File Path
    $options['ali_public_key']   = storage_path('cert/ali_public_key.pem');
    //The sign type should be RSA
    $options['sign_type']      = 'RSA';
    $request                   = $gateway->completePurchase($options)->send();
    $debugData                 = $request->getData();
    if ($request->isSuccessful()) {
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
```


## Alipay Mobile Express, Payto For iOS
```objective-c
//AppDelegate.m
- (BOOL)application:(UIApplication *)application openURL:(NSURL *)url sourceApplication:(NSString *)sourceApplication annotation:(id)annotation {
    if ([url.host isEqualToString:@"safepay"]) {
        [[AlipaySDK defaultService] processOrderWithPaymentResult:url
                                                  standbyCallback:^(NSDictionary *resultDic) {
                                                       NSLog(@"result1 = %@",resultDic);
                                                  }];
        return YES;
    }
}




//YourController.m
//Click Buy
NSString featureId = @"com.example.product.1001";

[APIClient get:@"http://api.example.com/pay/alipay/payto" data:{featureId:featureId, num:1} result:(NSDictionary *result){
    NSLog(@"result2 = %@", result)
    [[AlipaySDK defaultService] payOrder:result[@"order_info_str"] fromScheme:@"your-schema" callback:^(NSDictionary *resultDic) {
       //result

    }];
}];
```

## Alipay Mobile Express, Payto For Android
```java

//Call Alipay pay
PayTask alipay = new PayTask(PayDemoActivity.this);

//You should get the orderInfoStr from server first
String result = alipay.pay(orderInfoStr);
```
