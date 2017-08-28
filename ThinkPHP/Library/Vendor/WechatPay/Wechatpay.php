<?php
/*
 *---------------------------------------------------------------
 *  DESC
 *---------------------------------------------------------------
 *  author:  baoshu
 *  website: kuhou.net
 *  email:   83507315@qq.com
 *  date:    2017/8/28 下午12:16
 */

require_once "lib/WxPay.Api.php";  //
require_once "WxPay.JsApiPay.php"; //
// 回调
require_once 'lib/WxPay.Notify.php';
// 日志
require_once 'log.php';

require_once 'config.php';

class Wechatpay
{
    /*
     * 下单 返回支付参数
     * 返回jsapi配置参数
     *  商品名，字符金额，字符成功跳转网址
     */
    public function wechat_pay($params = array())
    {
        $tools = new JsApiPay();
//        $openId = $tools->GetOpenid();
        $input = new WxPayUnifiedOrder();
        $input->SetBody($params['name']);
        $input->SetAttach($params['mpid']);
        $input->SetOut_trade_no($params['order_id']);
        $input->SetTotal_fee($params['total_fee']);
        $input->SetTime_start(date("YmdHis"));
        $input->SetTime_expire(date("YmdHis", time() + 600));
        $input->SetGoods_tag("test");
        $input->SetNotify_url($params['notify_url']); //支付成功跳转页面
        $input->SetTrade_type("JSAPI");
        $input->SetOpenid($params['openid']);
        $order = WxPayApi::unifiedOrder($input);
        return $jsApiParameters = $tools->GetJsApiParameters($order);
    }
}

// 支付回调
class PayNotifyCallBack extends WxPayNotify
{
    //查询订单
    public function Queryorder($transaction_id)
    {
        $input = new WxPayOrderQuery();
        $input->SetTransaction_id($transaction_id);
        $result = WxPayApi::orderQuery($input);
        return $result;
    }

    //重写回调处理函数
    public function NotifyProcess($data, &$msg)
    {
        // Log::DEBUG("call back:" . json_encode($data));
        $notfiyOutput = array();

        if (!array_key_exists("transaction_id", $data)) {
            $msg = "输入参数不正确";
            return false;
        }
        //查询订单，判断订单真实性
        if (!$this->Queryorder($data["transaction_id"])) {
            $msg = "订单查询失败";
            return false;
        }
        return true;
    }

}

