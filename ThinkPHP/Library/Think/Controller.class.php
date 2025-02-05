<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2014 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
namespace Think;

use Photonic\Common;
/**
 * ThinkPHP 控制器基类 抽象类
 */
abstract class Controller {

    /**
     * 视图实例对象
     * @var view
     * @access protected
     */    
    protected $view     =  null;

    /**
     * 控制器参数
     * @var config
     * @access protected
     */      
    protected $config   =   array();

    static public $eip_company;
    static public $eip_company_custom;
    static public $admin_access_id = "1";       // 管理員權限id 傳訊光：1
    static public $top_teamid;                  // 最高權限組別 傳訊光：23
    static public $top_adminid;                 // 最高權限帳號 傳訊光：32
    static public $our_company_id;              // 自己公司 傳訊光：18426
    static public $system_parameter;
    static public $use_function_top = [];       // 全系統開放使用的功能(powercat)
    static public $use_function = [];           // 自己有使用的功能(一定比use_function_top少)
    static public $control_auto_sale_day;       // 定期出帳日期(id=3)
    static public $control_ecpay_invoice;       // 是否有電子發票(id=5)
    static public $control_send_pay_remaind;    // 是否使用請款提醒功能(id=6)
    static public $control_export_crm;          // 是否有匯出客戶(id=7)
    static public $control_crm_num;             // 客戶數上限(id=8)(-1為無上限)
    static public $control_sign_in;             // 是否啟用線上簽名(id=9)
    static public $control_salary_by_companys;  // 薪資是否依公司分配(id=10)
    static public $control_lng_lat;             // 公司位置經、緯度、距離公尺(id=11)
    static public $control_working_time;        // 正常上下班時間(id=12)
    static public $control_money_input;         // 控制系統輸入金額(id=13)

   /**
     * 架构函数 取得模板对象实例
     * @access public
     */
    public function __construct() {
        Hook::listen('action_begin',$this->config);
        //实例化视图类
            $this->view     = Think::instance('Think\View');


        /*系統相關參數*/
            $eip_company = D('eip_company')->where('id=1')->find();
            self::$eip_company = $eip_company;
            $this->assign('eip_company', self::$eip_company);
            self::$top_teamid = $eip_company['top_teamid'];
            self::$top_adminid = $eip_company['top_adminid'];
            self::$our_company_id = $eip_company['top_id'];
            $this->assign('top_teamid', self::$top_teamid);
            $this->assign('top_adminid', self::$top_adminid);
            $this->assign('our_company_id', self::$our_company_id);

            $eip_company_custom = D('eip_company')->where('id=2')->find();
            self::$eip_company_custom = $eip_company_custom;
            $this->assign('eip_company_custom', self::$eip_company_custom);

            self::$system_parameter = Common::get_lang_menu();
            $this->assign('system_parameter', self::$system_parameter);

            /*系統開放使用中功能*/
            $use_function_top = [];
            $use_function_tops = Common::get_can_see_menu(self::$top_adminid)['list'];
            foreach ($use_function_tops as $key => $value) {
                array_push($use_function_top, $value['id']);
            }
            // dump($use_function_top);exit;
            self::$use_function_top = $use_function_top;
            $this->assign('use_function_top', self::$use_function_top);

            /*自己可使用的功能*/
            $use_function = [];
            $can_see_menu = Common::get_can_see_menu(session('adminId'), $read_count=true);
			$this->assign('menu_arranged',$can_see_menu['arranged']);
			$this->assign('menu_list',$can_see_menu['list']);
			$this->assign("file_share_num",$can_see_menu['file_share_num']);//共用文件未讀
			$this->assign("publish_count",$can_see_menu['publish_count']);//你需要發布的事件
			$this->assign("getlist_ck",$can_see_menu['getlist_ck']);//輪到你可執行的事件
			$this->assign("runing_ck",$can_see_menu['runing_ck']);//輪到你可執行的事件
            
            foreach ($can_see_menu['list'] as $key => $value) {
                array_push($use_function, $value['id']);
            }
            // dump($use_function);exit;
            self::$use_function = $use_function;
            $this->assign('use_function', self::$use_function);			

            $system_parameter_data = D('system_parameter')->order('id asc')->select();
            self::$control_auto_sale_day = $system_parameter_data[2]['data'] ?? 0; //id=3
            $this->assign('control_auto_sale_day', self::$control_auto_sale_day);
            self::$control_ecpay_invoice = $system_parameter_data[4]['data'] ?? 0; //id=5
            $this->assign('control_ecpay_invoice', self::$control_ecpay_invoice);
            self::$control_send_pay_remaind = $system_parameter_data[5]['data'] ?? 0; //id=6
            $this->assign('control_send_pay_remaind', self::$control_send_pay_remaind);
            self::$control_export_crm = $system_parameter_data[6]['data'] ?? 0; //id=7
            $this->assign('control_export_crm', self::$control_export_crm);
            self::$control_crm_num = $system_parameter_data[7]['data'] ?? 0; //id=8
            $this->assign('control_crm_num', self::$control_crm_num);
            self::$control_sign_in = $system_parameter_data[8]['data'] ?? 0; //id=9
            $this->assign('control_sign_in', self::$control_sign_in);
            self::$control_salary_by_companys = $system_parameter_data[9]['data'] ?? 0; //id=10
            $this->assign('control_salary_by_companys', self::$control_salary_by_companys);
            self::$control_lng_lat = $system_parameter_data[10]['data'] ?? 0; //id=11
            $this->assign('control_lng_lat', self::$control_lng_lat);
            self::$control_working_time = $system_parameter_data[11]['data'] ?? 0; //id=12
            $this->assign('control_working_time', self::$control_working_time);
            self::$control_money_input = $system_parameter_data[12]['data'] ?? 0; //id=13
            $this->assign('control_money_input', self::$control_money_input);
            
            if (defined('CONTROL_MONEY_INPUT')) {
                define('CONTROL_MONEY_INPUT', self::$control_money_input);
            }
            $this->assign('TAX_RATE', TAX_RATE);

            $adminId = session('adminId') ?? '';
            $this->assign('adminId', $adminId);


        //控制器初始化
        if(method_exists($this,'_initialize'))
            $this->_initialize();
    }

    /**
     * 模板显示 调用内置的模板引擎显示方法，
     * @access protected
     * @param string $templateFile 指定要调用的模板文件
     * 默认为空 由系统自动定位模板文件
     * @param string $charset 输出编码
     * @param string $contentType 输出类型
     * @param string $content 输出内容
     * @param string $prefix 模板缓存前缀
     * @return void
     */
    protected function display($templateFile='',$charset='',$contentType='',$content='',$prefix='') {
        $this->view->display($templateFile,$charset,$contentType,$content,$prefix);
    }

    /**
     * 输出内容文本可以包括Html 并支持内容解析
     * @access protected
     * @param string $content 输出内容
     * @param string $charset 模板输出字符集
     * @param string $contentType 输出类型
     * @param string $prefix 模板缓存前缀
     * @return mixed
     */
    protected function show($content,$charset='',$contentType='',$prefix='') {
        $this->view->display('',$charset,$contentType,$content,$prefix);
    }

    /**
     *  获取输出页面内容
     * 调用内置的模板引擎fetch方法，
     * @access protected
     * @param string $templateFile 指定要调用的模板文件
     * 默认为空 由系统自动定位模板文件
     * @param string $content 模板输出内容
     * @param string $prefix 模板缓存前缀* 
     * @return string
     */
    protected function fetch($templateFile='',$content='',$prefix='') {
        return $this->view->fetch($templateFile,$content,$prefix);
    }

    /**
     *  创建静态页面
     * @access protected
     * @htmlfile 生成的静态文件名称
     * @htmlpath 生成的静态文件路径
     * @param string $templateFile 指定要调用的模板文件
     * 默认为空 由系统自动定位模板文件
     * @return string
     */
    protected function buildHtml($htmlfile='',$htmlpath='',$templateFile='') {
        $content    =   $this->fetch($templateFile);
        $htmlpath   =   !empty($htmlpath)?$htmlpath:HTML_PATH;
        $htmlfile   =   $htmlpath.$htmlfile.C('HTML_FILE_SUFFIX');
        Storage::put($htmlfile,$content,'html');
        return $content;
    }

    /**
     * 模板主题设置
     * @access protected
     * @param string $theme 模版主题
     * @return Action
     */
    protected function theme($theme){
        $this->view->theme($theme);
        return $this;
    }

    /**
     * 模板变量赋值
     * @access protected
     * @param mixed $name 要显示的模板变量
     * @param mixed $value 变量的值
     * @return Action
     */
    protected function assign($name,$value='') {
        $this->view->assign($name,$value);
        return $this;
    }

    public function __set($name,$value) {
        $this->assign($name,$value);
    }

    /**
     * 取得模板显示变量的值
     * @access protected
     * @param string $name 模板显示变量
     * @return mixed
     */
    public function get($name='') {
        return $this->view->get($name);      
    }

    public function __get($name) {
        return $this->get($name);
    }

    /**
     * 检测模板变量的值
     * @access public
     * @param string $name 名称
     * @return boolean
     */
    public function __isset($name) {
        return $this->get($name);
    }

    /**
     * 魔术方法 有不存在的操作的时候执行
     * @access public
     * @param string $method 方法名
     * @param array $args 参数
     * @return mixed
     */
    public function __call($method,$args) {
        if( 0 === strcasecmp($method,ACTION_NAME.C('ACTION_SUFFIX'))) {
            if(method_exists($this,'_empty')) {
                // 如果定义了_empty操作 则调用
                $this->_empty($method,$args);
            }elseif(file_exists_case($this->view->parseTemplate())){
                // 检查是否存在默认模版 如果有直接输出模版
                $this->display();
            }else{
                E(L('_ERROR_ACTION_').':'.ACTION_NAME);
            }
        }else{
            E(__CLASS__.':'.$method.L('_METHOD_NOT_EXIST_'));
            return;
        }
    }

    /**
     * 操作错误跳转的快捷方法
     * @access protected
     * @param string $message 错误信息
     * @param string $jumpUrl 页面跳转地址
     * @param mixed $ajax 是否为Ajax方式 当数字时指定跳转时间
     * @return void
     */
    protected function error($message='',$jumpUrl='',$ajax=false) {
        $this->dispatchJump($message,0,$jumpUrl,$ajax);
    }

    /**
     * 操作成功跳转的快捷方法
     * @access protected
     * @param string $message 提示信息
     * @param string $jumpUrl 页面跳转地址
     * @param mixed $ajax 是否为Ajax方式 当数字时指定跳转时间
     * @return void
     */
    protected function success($message='',$jumpUrl='',$ajax=false) {
        $this->dispatchJump($message,1,$jumpUrl,$ajax);
    }

    /**
     * Ajax方式返回数据到客户端
     * @access protected
     * @param mixed $data 要返回的数据
     * @param String $type AJAX返回数据格式
     * @param int $json_option 传递给json_encode的option参数
     * @return void
     */
    protected function ajaxReturn($data,$type='',$json_option=0) {
        if(empty($type)) $type  =   C('DEFAULT_AJAX_RETURN');
        switch (strtoupper($type)){
            case 'JSON' :
                // 返回JSON数据格式到客户端 包含状态信息
                header('Content-Type:application/json; charset=utf-8');
                exit(json_encode($data,$json_option));
            case 'XML'  :
                // 返回xml格式数据
                header('Content-Type:text/xml; charset=utf-8');
                exit(xml_encode($data));
            case 'JSONP':
                // 返回JSON数据格式到客户端 包含状态信息
                header('Content-Type:application/json; charset=utf-8');
                $handler  =   isset($_GET[C('VAR_JSONP_HANDLER')]) ? $_GET[C('VAR_JSONP_HANDLER')] : C('DEFAULT_JSONP_HANDLER');
                exit($handler.'('.json_encode($data,$json_option).');');  
            case 'EVAL' :
                // 返回可执行的js脚本
                header('Content-Type:text/html; charset=utf-8');
                exit($data);            
            default     :
                // 用于扩展其他返回格式数据
                Hook::listen('ajax_return',$data);
        }
    }

    /**
     * Action跳转(URL重定向） 支持指定模块和延时跳转
     * @access protected
     * @param string $url 跳转的URL表达式
     * @param array $params 其它URL参数
     * @param integer $delay 延时跳转的时间 单位为秒
     * @param string $msg 跳转提示信息
     * @return void
     */
    protected function redirect($url,$params=array(),$delay=0,$msg='') {
        $url    =   U($url,$params);
        redirect($url,$delay,$msg);
    }

    /**
     * 默认跳转操作 支持错误导向和正确跳转
     * 调用模板显示 默认为public目录下面的success页面
     * 提示页面为可配置 支持模板标签
     * @param string $message 提示信息
     * @param Boolean $status 状态
     * @param string $jumpUrl 页面跳转地址
     * @param mixed $ajax 是否为Ajax方式 当数字时指定跳转时间
     * @access private
     * @return void
     */
    private function dispatchJump($message,$status=1,$jumpUrl='',$ajax=false) {
        if(true === $ajax || IS_AJAX) {// AJAX提交
            $data           =   is_array($ajax)?$ajax:array();
            $data['info']   =   $message;
            $data['status'] =   $status;
            $data['url']    =   $jumpUrl;
            $this->ajaxReturn($data);
        }
        if(is_int($ajax)) $this->assign('waitSecond',$ajax);
        if(!empty($jumpUrl)) $this->assign('jumpUrl',$jumpUrl);
        // 提示标题
        $this->assign('msgTitle',$status? L('_OPERATION_SUCCESS_') : L('_OPERATION_FAIL_'));
        //如果设置了关闭窗口，则提示完毕后自动关闭窗口
        if($this->get('closeWin'))    $this->assign('jumpUrl','javascript:window.close();');
        $this->assign('status',$status);   // 状态
        //保证输出不受静态缓存影响
        C('HTML_CACHE_ON',false);
        if($status) { //发送成功信息
            $this->assign('message',$message);// 提示信息
            // 成功操作后默认停留1秒
            if(!isset($this->waitSecond))    $this->assign('waitSecond','1');
            // 默认操作成功自动返回操作前页面
            if(!isset($this->jumpUrl)) $this->assign("jumpUrl",$_SERVER["HTTP_REFERER"]);
            $this->display(C('TMPL_ACTION_SUCCESS'));
        }else{
            $this->assign('error',$message);// 提示信息
            //发生错误时候默认停留3秒
            if(!isset($this->waitSecond))    $this->assign('waitSecond','3');
            // 默认发生错误的话自动返回上页
            if(!isset($this->jumpUrl)) $this->assign('jumpUrl',"javascript:history.back(-1); setTimeout(function(){location.href=`/`}, 1000);");
            $this->display(C('TMPL_ACTION_ERROR'));
            // 中止执行  避免出错后继续执行
            exit ;
        }
    }

   /**
     * 析构方法
     * @access public
     */
    public function __destruct() {
        // 执行后续操作
        Hook::listen('action_end');
    }
}
// 设置控制器别名 便于升级
class_alias('Think\Controller','Think\Action');
