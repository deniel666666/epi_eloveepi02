<?php
namespace Trade\Controller;
use Trade\Controller\GlobalController;

use Photonic\AttendanceHelper;
use Photonic\ScheduleHelper;
use Photonic\MensHelper;

class AttendanceRecordsController extends GlobalController
{
    public function _initialize(){
        parent::_initialize();

        $excludeAction = ['adddata']; // 排除權限的功能
        if (!in_array(strtolower(ACTION_NAME), $excludeAction)) {
            parent::check_has_access(CONTROLLER_NAME, 'red');
        }
        $this->assign('CONTROLLER_NAME', CONTROLLER_NAME);

        $powercat_id = 156;
        $powercat    = D('powercat')->find($powercat_id);

        $this->powercat_current = $powercat;
        $this->assign('page_title_active', $powercat_id);    /*右上子選單active*/
    }

    public function index(){
        [$default_work_time] = AttendanceHelper::get_default_work_time();
        $this->assign('default_work_time_come', $default_work_time['time_come']);
        $this->assign('default_work_time_leave', $default_work_time['time_leave']);
        //部門清單
        parent::index_set('eip_apart');
        //職稱清單
        parent::index_set('eip_jobs');
        //員工類型
        parent::index_set('eip_user_right_type', 'id!=0');

        $this->display('Attendance/records');
    }

    /**
     * 依查詢月份取得指定年月的日期
     */
    public function getMonthDates(){
        $ym = I('get_month');
        $result = AttendanceHelper::getMonthDates($ym);
        return $this->ajaxReturn($result);
    }
    /**
     * 依查詢月份取得資料人員資料(包含需打卡日)
     */
    public function getUserList(){
        $ym = I('get_month');
        $date_s = substr($ym,0,4).'-'.substr($ym,4,2).'-01';
        $date_e = date('Y-m-t', strtotime($date_s));

        $men_filter = I('search_params');
        $men_params = I('search_params');
        $params = [ 
            'schedule_date_user_user_id_in'=>[],
            'schedule_id' => I('schedule_id', '') ?? '',
        ];
        $mens_all = MensHelper::get_mens([], $men_params);
        foreach ($mens_all as $value) {
            array_push($params['schedule_date_user_user_id_in'], $value['id']);
        }

        $view_all = $this->access[strtolower(CONTROLLER_NAME) . '_all'];
        if(!$view_all){ /*沒看全部*/
            $men_filter['id'] = session('adminId'); /*限制看自己*/
            $men_params['eip_user.id'] = session('adminId'); /*限制看自己*/
            $params['schedule_date_user_user_id'] = session('adminId'); /*限制看自己*/
        }
        $result = AttendanceHelper::getUserList($ym, $men_filter, $params);
        $mens = $result['mens'];
        foreach ($mens as $key => $value) {
            $mens[$key]['need_schedules'] = [];
            $mens[$key]['schedules_date'] = [];
        }

        $mens2 = MensHelper::get_mens_working([], $men_params);
        // dump($mens2);exit;
        foreach ($mens2 as $key => $value) {
            if(!isset($mens[$value['id']])){
                $mens[$value['id']] = $value;
                $mens[$value['id']]['need_works'] = [];
                $mens[$value['id']]['works_date'] = [];
                $mens[$value['id']]['need_schedules'] = [];
                $mens[$value['id']]['schedules_date'] = [];
            }
        }

        $params['date_s'] = $date_s;
        $params['date_e'] = $date_e;
        // dump($params);exit;
        $schedules = ScheduleHelper::get_schedules($params, true, true);
        foreach ($schedules as $value) {
            $user_id = $value['schedule_date_user_user_id'];
            $mens[$user_id]['need_schedules'][] = $value['date'];
            if(!isset($mens[$user_id]['schedules_date'][$value['date']])){
                $mens[$user_id]['schedules_date'][$value['date']] = [];
            }
            $mens[$user_id]['schedules_date'][$value['date']][] = $value;
        }
        $result['mens'] = $mens;
        return $this->ajaxReturn($result);
    }

    /**
     * 修改打卡時間(需編輯權限)
     */
    public function saveData(){
        // 檢查權限
        parent::check_has_access(CONTROLLER_NAME, 'edi');
        $ym         = I('post.get_month');
        $id         = I('post.id');
        $type       = I('post.type');
        $time_come  = I('post.time_come');
        $time_leave = I('post.time_leave');
        $date       = I('post.date');
        $user_id    = I('post.user_id');

        $update_data = array();
        switch ($type) {
            case 'in':
                $update_data['time_come'] = $time_come ? $time_come : -1;
                break;
            case 'out':
                $update_data['time_leave'] = $time_leave ? $time_leave : -1;
                break;
            case 'new' || !$id:
                if (!$date || !$user_id) $this->error('請求資料有誤');
                $update_data['time_come'] = $time_come ? $time_come : -1;
                $update_data['time_leave'] = $time_leave ? $time_leave : -1;
                break;
        }
        // 修改紀錄
        if ($id) {
            $result = AttendanceHelper::saveData($ym, $id, $update_data);
            if ($result) {
                $this->success('修改成功');
            } else {
                $this->error('無資料須修改');
            }
        }
        $this->success('資料不完整');
    }
    /**
     * 員工自行打卡打卡
     */
    public function saveData_staff(){
        $ym = date('Ym');
        // 檢查當日是否需要打卡
        $records = AttendanceHelper::getDataList($ym, true, [
            'user_id' => session('adminId'),
            'date' => date('Y-m-d'),
        ]);
        if (count($records)==0){ 
            // $this->error('今日免打卡');
            $record= null;
        }else{
            $record = $records[0];
        }

        // 取得系統參數 (公司位置緯、經度、距離公尺)
        $system_parameter = explode(", ", $this->control_lng_lat);

        // 計算距離
        $distance = AttendanceHelper::getDistance(
            I('post.longitude'), // 第1組經度
            I('post.latitude'),  // 第1組經度
            $system_parameter[1], // 第2組緯度
            $system_parameter[0]  // 第2組緯度
        );

        // 取絕對值做比較，是否在距離範圍內
        if (abs($distance) > intval($system_parameter[2])) {
            $this->error('距離太遠');
        }

        switch (I('post.type')) {
            case 'in': // 上班打卡
                if(is_null($record)){ /*原本不需打卡*/
                    $result = AttendanceHelper::addRows(date('Ym'), [
                        [
                            'date' => date('Y-m-d'),
                            'user_id' => session('adminId'),
                            'time_come'=>date('H:i:s'),
                            'need_show' => 0,
                        ],
                    ]);
                    if($result){
                        $this->success('上班打卡成功');
                    }
                }
                if(!is_null($record['time_come'])){ 
                    $this->error('重複打卡');
                }
                $update_data = ['time_come'=>date('H:i:s'),];
                $result = AttendanceHelper::saveData($ym, $record['id'], $update_data);
                if ($result) {
                    $update_data['date'] = $record['date'];
                    $update_data['user_id'] = $record['user_id'];
                    parent::error_log('上班打卡 資料：' . json_encode($update_data, JSON_UNESCAPED_UNICODE));
                    $this->success('上班打卡成功');
                }
                $this->error('上班打卡失敗');
                break;
            case 'out': // 下班打卡
                if(is_null($record)){ /*原本不需打卡*/
                    $this->error('請先上班打卡');
                }
                if(is_null($record['time_come'])){
                    $this->error('請先上班打卡');
                }
                else if(!is_null($record['time_leave'])){
                    $this->error('重複打卡');
                }
                $update_data = ['time_leave'=>date('H:i:s'),];
                $result = AttendanceHelper::saveData($ym, $record['id'], $update_data);
                if ($result) {
                    $update_data['date'] = $record['date'];
                    $update_data['user_id'] = $record['user_id'];
                    parent::error_log('下班打卡 資料：' . json_encode($update_data, JSON_UNESCAPED_UNICODE));
                    $this->success('下班打卡成功');
                }
                $this->error('下班打卡失敗');
                break;
        }
        
    }
}