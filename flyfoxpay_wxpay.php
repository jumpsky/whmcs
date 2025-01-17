<?php
use WHMCS\Database\Capsule;
class Pays
{
    private $pid;
    private $key;

    public function __construct($pid, $key)
    {
        $this->pid = $pid;
        $this->key = $key;
    }

    /**
     * @Note  支付发起
     * @param $type   支付方式
     * @param $out_trade_no     订单号
     * @param $notify_url     异步通知地址
     * @param $return_url     回调通知地址
     * @param $name     商品名称
     * @param $money     金额
     * @param $sitename     站点名称
     * @return string
     */
    public function submit($type, $out_trade_no, $notify_url, $return_url, $name, $money, $sitename)
    {
        $data = [
            'pid' => $this->pid,
            'type' => $type,
            'out_trade_no' => $out_trade_no,
            'notify_url' => $notify_url,
            'return_url' => $return_url,
            'name' => $name,
            'money' => $money,
            'sitename' => $sitename
        ];
        $string = http_build_query($data);
        $sign = $this->getsign($data);
        return 'https://api.jxspay.cn/submit/?' . $string . '&sign=' . $sign . '&sign_type=MD5';
    }

    /**
     * @Note   验证签名
     * @param $data  待验证参数
     * @return bool
     */
    public function verify($data)
    {
        if (!isset($data['sign']) || !$data['sign']) {
            return false;
        }
        $sign = $data['sign'];
        unset($data['sign']);
        unset($data['sign_type']);
        $sign2 = $this->getSign($data, $this->key);
        if ($sign != $sign2) {
            return false;
        }
        return true;
    }

    /**
     * @Note  生成签名
     * @param $data   参与签名的参数
     * @return string
     */
    private function getSign($data)
    {
        $data = array_filter($data);
        ksort($data);
        $str1 = '';
        foreach ($data as $k => $v) {
            $str1 .= '&' . $k . "=" . $v;
        }
        $str = $str1 . $this->key;
        $str = trim($str, '&');
        $sign = md5($str);
        return $sign;
    }
}
function flyfoxpay_wxpay_MetaData() {
    return array(
        'DisplayName' => '聚合收款',
        'APIVersion' => '1.1',
    );
}

function flyfoxpay_wxpay_config() {
    $configarray = array(
        "FriendlyName"  => array(
            "Type"  => "System",
            "Value" => "聚合收款"
        ),
       "mchid" => array(
            "FriendlyName" => "商户ID",
            "Type"         => "text",
            "Size"         => "128",
        ),
        "key" => array(
            "FriendlyName" => "商户KEY",
            "Type"         => "text",
            "Size"         => "128",
        )
        
    );

    return $configarray;
}

function flyfoxpay_wxpay_link($params) {
    if($_REQUEST['alipaysubmit'] == 'yes'){
	
	   $pay = new Pays($params['mchid'], $params['key']);

//支付方式
$type = 'all';

//订单号
$out_trade_no = $params['invoiceid'];

//异步通知地址
$notify_url = 'https://'.$_SERVER['HTTP_HOST'].'/modules/gateways/flyfoxpay/callback_wxpay.php';

//回调通知地址
$return_url = $params['returnurl'];

//商品名称
$name = 'SS-'.$_SERVER['HTTP_HOST'];

//支付金额（保留小数点后两位）
$money = $params['amount'];

//站点名称
$sitename = $_SERVER['HTTP_HOST'];

//发起支付
$url = $pay->submit($type, $out_trade_no, $notify_url, $return_url, $name, $money, $sitename);
		$sHtml = "<script language='javascript' type='text/javascript'>window.location.href='".$url."';</script>";
	   exit($sHtml);
	}
    if(stristr($_SERVER['PHP_SELF'],'viewinvoice')){
		return '<form method="post" id=\'alipaysubmit\'><input type="hidden" name="alipaysubmit" value="yes"></form><button type="button" class="btn btn-danger btn-block" onclick="document.forms[\'alipaysubmit\'].submit()">使用聚合收款支付</button>';
    }else{
         return '<img style="width: 150px" src="'.$params['systemurl'].'/modules/gateways/flyfoxpay_wxpay/alipay.png" alt="聚合收款" />';
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
    $currencys=localAPI("GetCurrencies", [], flyfoxpay_wxpay_getAdminname());
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
if(!function_exists("flyfoxpay_wxpay_getAdminname")){
function flyfoxpay_wxpay_getAdminname(){
    $admin = Capsule::table('tbladmins')->first();
    return $admin->username;
}
}
