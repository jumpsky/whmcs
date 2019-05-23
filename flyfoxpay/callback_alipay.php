<?php
use WHMCS\Database\Capsule;
# Required File Includes
include("../../../init.php");
include("../../../includes/functions.php");
include("../../../includes/gatewayfunctions.php");
include("../../../includes/invoicefunctions.php");

$gatewaymodule = "flyfoxpay_alipay";
$GATEWAY       = getGatewayVariables($gatewaymodule);

if (!$GATEWAY["type"]) die("fail");

$security['orderid'] = $_REQUEST['orderid'];
//手续费
$fee = 0;
$url = "https://sc-i.pw/api/check/";//API位置
 
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:38.0) Gecko/20100101 Firefox/38.0');
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(
 array("key"=>$GATEWAY["key"], //商家KEY
       "id"=>$GATEWAY["mchid"], //商家ID
       "mail"=>$GATEWAY["account"], //商家EMAIL
       "trade_no"=>$_REQUEST['orderid'], //商家訂單ID
       ))); 
$output = curl_exec($ch); 
curl_close($ch);
/*
回傳格式:
//成功
{"status":"200","status_trade":"noapy","sign":"90e5f1f7ef87cd2e43729ba4378656b5"}
{"status":"200","trade_no":"1278217527512","type":"o_alipay","status_trade":"payok","sign":"*****"}
//以下為錯誤項目
{"status":"404","error":"未設置KEY或是ID或MAIL"}
{"status":"400","error":"請檢查ID或是KEY或MAIL是否有誤"}
{"status":"416","error":"請檢查訂單ID是否有誤"}
*/ 
$security1  = array();

$security1['mchid']      = $GATEWAY["mchid"];//商家ID

$security1['status']        = "7";//驗證，請勿更改

$security1['mail']      = $GATEWAY["account"];//商家EMAIL

$security1['trade_no']      = $security['orderid'];//商家訂單ID

foreach ($security1 as $k=>$v)

{

    $o.= "$k=".($v)."&";

}

$sign1 = md5(substr($o,0,-1).$GATEWAY["key"]);//**********請替換成商家KEY
$json=json_decode($output, true);
$security['out_trade_no'] = $json['customize1'];
$security['trade_no'] = $json['trade_no'];
$amount= $json['money'];
if($typesfa=="o_alipay"){$typess='flyfoxpay_alipay';}elseif($typesfa=="o_wxpay"){$typess='flyfoxpay_wxpay';}
if($json['sign']==$sign1){
    $invoiceid = checkCbInvoiceID($security['out_trade_no'], $GATEWAY["name"]);
    checkCbTransID($security['out_trade_no']);
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
    addInvoicePayment($invoiceid,$security['trade_no'],trim($amount),$fee,$typess);
    logTransaction($GATEWAY["name"], $_REQUEST, "Successful");
    if($_POST['orderid']!=='' OR $_POST['orderid']!==null){
               header('Content-Type: application/json');
               echo '{"ok":"ok"}';}else{
               echo 'success';}
} else {
    echo 'fail'.$output.'/'.$sign1;
}
