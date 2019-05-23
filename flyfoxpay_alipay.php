<?php
use WHMCS\Database\Capsule;

function flyfoxpay_alipay_MetaData() {
    return array(
        'DisplayName' => '翔狐科技(支付宝)',
        'APIVersion' => '1.1',
    );
}

function flyfoxpay_alipay_config() {
    $configarray = array(
        "FriendlyName"  => array(
            "Type"  => "System",
            "Value" => "翔狐科技(支付宝)"
        ),
        "account"  => array(
            "FriendlyName" => "商户邮箱",
            "Type"         => "text",
            "Size"         => "32",
        ),
        "key" => array(
            "FriendlyName" => "商户KEY",
            "Type"         => "password",
            "Size"         => "32",
        ),
        "mchid" => array(
            "FriendlyName" => "商户ID",
            "Type"         => "text",
            "Size"         => "32",
        )
    );

    return $configarray;
}

function flyfoxpay_alipay_link($params) {
    if($_REQUEST['alipaysubmit'] == 'yes'){
		$security  = array();
       $security['id']      = $params['mchid'];
       $security['mail']    = $params['account'];
       $security['keys']        = md5($params['key']);
       $security['trade_name']       = 'whmcs'.time();
		$security['trade_no']      = 'whmcs'.time();
       $security['customize1']      = $params['invoiceid'];
		 $security['amount']      = $params['amount'];
		$security['type']      = 'o_alipay';
		 $security['go']      = '1';
		$security['return']      = $params['returnurl'];
		$o='';
$sHtml = "<form id='alipaysubmit' name='alipaysubmit' action='https://sc-i.pw/api/' method='post'>";
	   while (list ($key, $val) = each ($security)) {
            $sHtml.= "<input type='hidden' name='".$key."' value='".$val."'/>";
       }
       $sHtml = $sHtml."<input type='submit' value='正在跳转'></form>";
	   $sHtml = $sHtml."<script>document.forms['alipaysubmit'].submit();</script>";
	   exit($sHtml);
	}
    if(stristr($_SERVER['PHP_SELF'],'viewinvoice')){
		return '<form method="post" id=\'alipaysubmit\'><input type="hidden" name="alipaysubmit" value="yes"></form><button type="button" class="btn btn-danger btn-block" onclick="document.forms[\'alipaysubmit\'].submit()">使用支付宝支付</button>';
    }else{
         return '<img style="width: 150px" src="'.$params['systemurl'].'/modules/gateways/flyfoxpay_alipay/alipay.png" alt="支付宝支付" />';
    }

}

//LeoTIME
/****************************************************************
 * 
 *       Author:Leo
 * 
 *       Made for daimiyun.cn
 * 
 * **************************************************************/
if(!function_exists("autogetamount")){
function autogetamount($params){
    $amount=$params['amount'];
    $currencyId=$params['currencyId'];
    $currencys=localAPI("GetCurrencies", [], flyfoxpay_alipay_getAdminname());
    if($currencys['result']=='success' and $currencys['totalresults']>=1){
        
    }else{
        var_dump($currencys);
        throw new \Exception('货币设置错误、API请求错误');
        //如果api请求错误或者货币数量小于1
    }
    //获取货币。
    $currencys=$currencys['currencies']['currency'];
    foreach($currencys as $currency){
        if($currencyId==$currency['id']){
            $from=$currency;
            break;
        }
    }
    if(!$from){
        throw new \Exception("货币错误，找不到起始货币。");
    }
    foreach($currencys as $currency){
        $hb=strtoupper($currency['code']);
        if($hb=='TWD'){
            $cny=$currency;
            break;
        }
    }
    if(!$cny){
        throw new \Exception("找不到新台币货币，请确认后台货币中存在货币代码为TWD的货币！");
    }
    $rate=$cny['rate']/$from['rate'];
    return [round((double)$rate*$amount,2),round((double)$rate,2)];
}
}
if(!function_exists("flyfoxpay_alipay_getAdminname")){
function flyfoxpay_alipay_getAdminname(){
    $admin = Capsule::table('tbladmins')->first();
    return $admin->username;
}
}
