<?php
namespace Mp\Controller;
use Think\Controller;

/**
 * 插件移动端控制器
 * @author 艾逗笔<765532665@qq.com>
 */
class MobileBaseController extends Controller {

    public $ress = null;
    /**
     * 初始化
     * @author 艾逗笔<765532665@qq.com>
     */
    public function _initialize() {

        if (!is_wechat_browser() && !get_user_id() && !I('out_trade_no') && $this->wechat_only) {
            $mp_info = get_mp_info();
            if (isset($mp_info['appid'])) {
                redirect('https://open.weixin.qq.com/connect/oauth2/authorize?appid='.$mp_info['appid'].'&redirect_uri=&wxref=mp.weixin.qq.com&from=singlemessage&isappinstalled=0&response_type=code&scope=snsapi_base&state=&connect_redirect=1#wechat_redirect');
            } else {
                exit('what?');
            }
        }
        
        // 新支付回调 防作弊
        if($GLOBALS["HTTP_RAW_POST_DATA"]){
            $xml = $GLOBALS["HTTP_RAW_POST_DATA"];
            $arr = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);

            $transaction_id = $arr['transaction_id'];
            $payment_res = wechat_query_order($transaction_id,$arr['attach']);

//            $this->ress = $payment_res;
            
//            $pay_result = D('pay_result');
//            $datass = array(
//            'id' => time(),
//            'cont' => serialize($payment_res)
//            );
//            $pay_result->add($datass);

            if($payment_res['out_trade_no']){

//                $this->out_trade_no = $payment_res['out_trade_no'];
                $mp_payment = M('mp_payment');

                if (!$mp_payment->where(array('orderid' => $payment_res['out_trade_no']))->find()) {

                    $datas = array(
                        'mpid' => $payment_res['attach'],
                        'openid' => $payment_res['openid'],
                        'orderid' => $payment_res['out_trade_no'],
                        'create_time' => strtotime($payment_res['time_end']),
                        'detail' => json_encode($payment_res)
                    );

                    M('mp_payment')->add($datas);

                    $return_code = $payment_res['return_code'];
                    $return_msg = $payment_res['return_msg'];
                    $xml = '<xml>
                          <return_code><![CDATA[' . $return_code . ']]></return_code>
                          <return_msg><![CDATA[' . $return_msg . ']]></return_msg>
                        </xml>';
                    return $xml;
//                    exit($xml);
                }
            }

        }

        if (get_mpid() && !get_openid()) {
            init_fans();
        }

        if (!get_ext_openid()) {
            init_ext_fans();       // 初始化鉴权用户
        }

        global $_G;
        $_G['site_path'] = SITE_PATH . '/';
        $_G['site_url'] = str_replace('index.php', '', 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF']);
        $_G['addons_path'] = str_replace('./', $_G['site_path'], ADDON_PATH);
        $_G['addons_url'] = $_G['site_url'] . str_replace('./', '', ADDON_PATH);
        $_G['addon'] = get_addon();
        $_G['addon_path'] = $_G['addons_path'] . $_G['addon'] . '/';
        $_G['addon_url'] = $_G['addons_url'] . $_G['addon'] . '/';
        $_G['addon_public_path'] = $_G['addon_path'] . 'View/Public/';
        $_G['addon_public_url'] = $_G['addon_url'] . 'View/Public/';
        $_G['current_url'] = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].'?'.$_SERVER['QUERY_STRING'];
        $_G['fans_info'] = get_fans_info();
        $_G['mp_info'] = get_mp_info();
        $_G['openid'] = get_openid();
        $_G['mpid'] = get_mpid();
        // 感谢 @苍竹先生<593485230@qq.com> 提供的处理浏览器openid问题的解决方案
        preg_match('/\/openid[\/|=]([_\-0-9A-Za-z]*+)/', $_G['current_url'], $m);		// 带上openid的参数字符串
        if (isset($m[0]) && !empty($m[0])) {
            get_openid($m[1]);                                              // 设置当前用户标识
        	$redirect_url = str_replace($m[0], '', $_G['current_url']);			// 去除openid的重定向访问链接
        	redirect($redirect_url);										// 重定向
        }
        add_hook('jssdk', 'Mp\Behavior\JssdkBehavior');                     // 注册导入jssdk的钩子
        add_hook('import_js', 'Mp\Behavior\ImportJsBehavior');              // 注册导入js的钩子
        add_hook('import_css', 'Mp\Behavior\ImportCssBehavior');              // 注册导入js的钩子
        $this->assign('_G', $_G);
    }

    /**
     * 获取微信支付参数
     * @author 艾逗笔<765532665@qq.com>
     */
    public function json_pay() {
        // @通过orderid 算价格
        $orderid = I('orderid');
        $ps_order_list = D('ps_order_list');
        $ps_order = $ps_order_list->where(array('orderid'=>$orderid))->find();
        if($ps_order){
            $params = array(
                'name' => $ps_order['order_name'],
                'total_fee' => intval($ps_order['amount']*100),
                'notify_url' => I('notify'),
                'order_id' => I('orderid'),
                'openid' => $ps_order['openid'],
                'mpid' => $ps_order['mpid']
            );
            $config = wechat_pay($params);
            $this->ajaxReturn($config);
        }else{
            exit('参数错误');
        }
    }

    /**
     * 图文详情
     * @author 艾逗笔<765532665@qq.com>
     */
    public function detail() {
        $detail = M('mp_material')->find(I('id'));
        $mp_info = M('mp')->find($detail['mpid']);
        $this->assign('mp', $mp_info);
        $this->assign('detail', $detail);
        parent::display('Material/detail');
    }

    /**
     * 重写模板显示方法
     * @author 艾逗笔<765532665@qq.com>
     */
    public function display($templateFile='',$charset='',$contentType='',$content='',$prefix='') {
        global $_G;
        if (empty($templateFile)) {
            $templateFile = $_G['addon_path'] . 'View/' . CONTROLLER_NAME . '/' . ACTION_NAME . C('TMPL_TEMPLATE_SUFFIX');
        } else {
            $tempArr = explode('/', $templateFile);
            switch (count($tempArr)) {
                case 1:
                    $templateFile = $_G['addon_path'] . 'View/' . CONTROLLER_NAME . '/' . $tempArr[0] . C('TMPL_TEMPLATE_SUFFIX');
                    break;
                case 2:
                    $templateFile = $_G['addon_path'] . 'View/' . CONTROLLER_NAME . '/' . $tempArr[0] . '/' . $tempArr[1] . C('TMPL_TEMPLATE_SUFFIX');
                    break;
                default:
                    break;
            }
        }
        if (!is_file($templateFile)) {
            E('模板不存在:'.$templateFile);
        }
        parent::display($templateFile,$charset,$contentType,$content,$prefix);
    }
}