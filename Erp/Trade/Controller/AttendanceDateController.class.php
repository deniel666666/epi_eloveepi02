<?php
namespace Trade\Controller;
use Trade\Controller\GlobalController;

use Photonic\AttendanceHelper;

class AttendanceDateController extends GlobalController
{
    public function _initialize(){
        parent::_initialize();
        parent::check_has_access(CONTROLLER_NAME, 'red');
        $this->assign('CONTROLLER_NAME', CONTROLLER_NAME);

        $powercat_id = 155;
        $powercat    = D('powercat')->find($powercat_id);
        $this->powercat_current = $powercat;
        $this->assign('page_title_active', $powercat_id);    /*右上子選單active*/
    }

    public function index(){
        $this->display('Attendance/date');
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
        $result = AttendanceHelper::getUserList($ym, [], [
            'need_show' => 1, /*只撈取需要計算出缺席的*/
        ]);
        return $this->ajaxReturn($result);
    }

    /**
     * 新增需打卡日期
     */
    public function addRows_with_options(){
        // 檢查權限
        parent::check_has_access(CONTROLLER_NAME, 'edi');

        // 檢查欄位
        $start_date   = I('post.start_date');
        $end_date     = I('post.end_date');
        $checked_days = I('post.checked_days');
        if (!$start_date || !$end_date) $this->error('未選擇日期區間');
        if (strtotime($start_date) > strtotime($end_date)) $this->error('日期區間設定錯誤');

        // 取得選擇區間中有效的日期
        $period_array = AttendanceHelper::get_days($start_date, $end_date, $checked_days);
        // dump($period_array);exit;
        if (count($period_array) == 0) {
            return $this->error('選擇區間無新日期可建立');
        }

        // 將有效的日期依年月組織起來
        $period_array_ym = AttendanceHelper::arrange_days($period_array);
        // dump($period_array_ym);exit;
        foreach ($period_array_ym as $ym => $dates) {
            // 依年月及所選定之日期取得所需新增的資料
            $todo_data = AttendanceHelper::get_todo_data($ym, $dates);
            // dump($todo_data);exit;
            // 新增資料
            $result = AttendanceHelper::addRows($ym, $todo_data['insertable_data']);
            // 更新資料
            $result = AttendanceHelper::updateRows($ym, $todo_data['need_update_to_need_show']);
        }
        // 取得已建立之日期
        $this->success('操作成功');
    }
    /**
     * 單一調整是否需打卡
     */
    public function change_attendance_date(){
        // 檢查權限
        parent::check_has_access(CONTROLLER_NAME, 'edi');

        $need_work  = I('post.need_work');
        $date       = I('post.date');
        $user_id    = I('post.user_id');

        $ym = substr($date,0,4).substr($date,5,2);
        if($need_work){ /*需打*/
            $result = AttendanceHelper::addRows($ym, [
                [
                    'date' => $date,
                    'user_id' => $user_id,
                ],
            ]);
            if(!$resul){ /*有重複資料*/
                $result = AttendanceHelper::updateRows($ym, [$date]);
            }
        }else{ /*免打卡*/
            $result = AttendanceHelper::deleteRows($ym, [
                'date' => $date,
                'user_id' => $user_id,
            ]);
        }
        if ($result) {
            $this->success('操作成功');
        }else{
            $this->error('操作失敗');
        }
    }
    /**
     * 刪除需打卡日期
     */
    public function deleteRows(){
        // 檢查權限
        parent::check_has_access(CONTROLLER_NAME, 'edi');

        // 檢查欄位
        $date = I('post.date');
        $get_month = I('post.get_month');
        if (!$date) $this->error('請求資料有誤');
        if (!$get_month) $this->error('請求資料有誤');
        $result = AttendanceHelper::deleteRows($get_month, ['date'=>$date]);
        if ($result) {
            $this->success('操作成功');
        }else{
            $this->error('操作失敗');
        }
    }
}
