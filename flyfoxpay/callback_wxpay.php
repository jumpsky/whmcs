<?php
use WHMCS\Database\Capsule;
# Required File Includes
include("../../../init.php");
include("../../../includes/functions.php");
include("../../../includes/gatewayfunctions.php");
include("../../../includes/invoicefunctions.php");

$gatewaymodule = "flyfoxpay_wxpay";
$GATEWAY       = getGatewayVariables($gatewaymodule);

if (!$GATEWAY["type"]) die("fail");
	  //实例化支付类
$pay = new Pays($GATEWAY['mchid'], $GATEWAY['key']);

//接收异步通知数据
$data = $_GET;

//商户订单号
$out_trade_no = $data['out_trade_no'];

//验证签名
if ($pay->verify($data)) {
    //验证支付状态
    if ($data['trade_status'] == 'TRADE_SUCCESS') {
    
		$invoiceid = checkCbInvoiceID($out_trade_no, $GATEWAY["name"]);
    checkCbTransID($out_trade_no);
	function convert_helper($invoiceid,$amount){
    $setting = Capsule::table("tblpaymentgateways")->where("gateway",$gatewaymodule)->where("setting","convertto")->first();
    ///系统没多货币 , 直接返回
    if (empty($setting)){ return $amount; }
    
    
    ///获取用户ID 和 用户使用的货币ID
    $data = Capsule::table("tblinvoices")->where("id",$invoiceid)->get()[0];
    $userid = $data->userid;
    $currency = getCurrency( $userid );

    /// 返回转换后的
    return  convertCurrency( $amount , $setting->value  ,$currency["id"] );
}
	  $amount = convert_helper( $invoiceid, $fee);
    addInvoicePayment($invoiceid,$data['trade_no'],trim($amount),$fee,$typess);
    logTransaction($GATEWAY["name"], $_REQUEST, "Successful");
    echo 'success';
        //这里就可以放心的处理您的业务流程了
        //您可以通过上面的商户订单号进行业务流程处理
    }
} else {
    echo '錯誤';
}
