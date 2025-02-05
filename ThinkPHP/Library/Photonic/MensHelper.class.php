<?php
namespace Photonic;
use Think\Controller;

class MensHelper extends Controller
{
  public static function instance(){
    return new MensHelper();
  }

  public static function get_eip_apart($cond=[]){
    $where_query = "status = '1'";
    if(($cond['id'] ?? '')){
      $where_query .= " and id = '".$cond['id']."'";
    }
    if(($cond['name'] ?? '')){
      $where_query .= " and name = '".$cond['name']."'";
    }
    if(($cond['cid'] ?? '')){
      $where_query .= " and cid = '".$cond['cid']."'";
    }
    $apart = M("eip_apart")->where($where_query)->order('parent_id asc, id asc')->select();
    return $apart;
  }

  public static function get_mens_rest_top_examiner(){
    $rest_examine_aparts = [0];
    $rest_examine = D('eip_apart')->where('rest_examine=1')->select();
    foreach ($rest_examine as $key => $value) {
      array_push($rest_examine_aparts, $value['id']);
    }
    $eip_top_examiner_options = self::get_mens_working([
      'apartmentid in ('.implode(',', $rest_examine_aparts).')'
    ]);
    return $eip_top_examiner_options;
  }
  public static function aj_getmean(){
    $eip_user = self::get_mens_working([], $_POST);
    if(!isset($_POST['no_no'])){
      array_unshift($eip_user , ['id'=> 0, 'name'=> '無']);
    }

    foreach($eip_user as $vo){
      if( $vo['id'] == $_POST['ck']){
        echo "<option value='{$vo['id']}' selected>{$vo['name']}</option>";
      }
      else{
        echo "<option value='{$vo['id']}'>{$vo['name']}</option>";
      }
    }
  }
  public static function get_mens_working($where_query=[], $params=[]){
    $working_definition = $params['working_definition'] ?? 'is_job=1';
    $where_query = array_merge($where_query, [
      $working_definition." AND status=1 AND ( no LIKE '%正%' or no LIKE '%臨%')"
    ]);
    return self::get_mens($where_query, $params);
  }
  public static function get_mens($where_query=[], $params=[]){
    array_push($where_query, "eip_user.id !=".self::$top_adminid);
    foreach ($params as $key => $value) {
      if(in_array($key, [])){
        if($value != ''){ array_push($where_query, $key.' LIKE "%'.$value.'%"'); }
      }
      else if(in_array($key, ['eip_user.id'])){
        if($value != ''){ array_push($where_query, $key.'="'.$value.'"'); }
      }
      else if(in_array($key, ['right','apartmentid', 'jobid', 'is_job', 'use_attendance', 'pay_count_type'])){
        if($value != ''){ array_push($where_query, 'eip_user.'.$key.'="'.$value.'"'); }
      }
      else if($key=='searchtext'){
        if($value != ''){ 
          array_push($where_query, '(
            eip_user.name LIKE "%'.$value.'%" OR 
            eip_user.ename LIKE "%'.$value.'%" OR 
            eip_user.phone LIKE "%'.$value.'%" OR 
            eip_user.mphone LIKE "%'.$value.'%" OR 
            eip_user.email LIKE "%'.$value.'%" OR 
            eip_user.email2 LIKE "%'.$value.'%"
          )'); 
        }
      }
      else if($key=='status'){
        if($value!==''){
          array_push($where_query, 'eip_user.status='.$value);
        }else{
          array_push($where_query, 'eip_user.status=1');
        }
      }
    }
    // dump($where_query);exit;

    $eip_user = D('eip_user');
    $field_query = 'eip_user.*';
    if(isset($params['field'])){
      if($params['field']){ $field_query = $params['field']; }
    }
    
    $eip_user = $eip_user->field($field_query)
                  ->join("LEFT JOIN work_time ON work_time.id = eip_user.work_time_id")
                  ->where($where_query)->order("eip_user.id asc")->select();
    return $eip_user;
  }
  public static function get_mens_with_salary_record($params=[]){
    $params['salary_record_date'] = isset($params['salary_record_date']) ? $params['salary_record_date'] : date('Y-m-t');
    
    $where_query = [ "e.id !=".self::$top_adminid ];
    foreach ($params as $key => $value) {
      if(in_array($key, ['id', 'use_attendance', 'is_job', 'apartmentid', 'jobid', 'right'])){ /*eip_user的篩選欄位*/
        if($value != ''){ array_push($where_query, 'e.'.$key.'="'.$value.'"'); }
      }else if(in_array($key, ['pay_type'])){ /*salary_records的篩選欄位*/
        if($value != ''){ array_push($where_query, 'sr.'.$key.'="'.$value.'"'); }
      }else if($key=='working_and_stop'){ /*特殊篩選*/
        if($value != ''){ array_push($where_query, 'e.is_job in (1,2)'); }
      }else if($key=='searchtext'){
        if($value != ''){ 
          array_push($where_query, '(
            e.name LIKE "%'.$value.'%" OR 
            e.ename LIKE "%'.$value.'%" OR 
            e.phone LIKE "%'.$value.'%" OR 
            e.mphone LIKE "%'.$value.'%" OR 
            e.email LIKE "%'.$value.'%" OR 
            e.email2 LIKE "%'.$value.'%"
          )'); 
        }
      }
    }
    $check_salary_record_date = date('Y-m-t', strtotime($params['salary_record_date']));
    $check_salary_record_date = date('Y-m-d', strtotime($check_salary_record_date.'+ 1Day'));
    $qualified_salary_record = '
      LEFT JOIN (
        SELECT sr_t.*
        FROM salary_records sr_t
        INNER JOIN (
            SELECT user_id, MAX(day_s) AS day_s_max
            FROM salary_records
            WHERE day_s < "'.$check_salary_record_date.'"
            GROUP BY user_id
        ) sr_t2 ON sr_t.user_id = sr_t2.user_id AND sr_t.day_s = sr_t2.day_s_max
      ) sr ON e.id = sr.user_id
    ';
    $eip_user = D('eip_user e')->field('e.*, sr.day_s, sr.pay_type, sr.bonus, wt.time_come, wt.time_leave')
                             ->join($qualified_salary_record)
                             ->join("LEFT JOIN work_time wt ON wt.id = e.work_time_id")
                             ->where($where_query)
                             ->order("e.id asc")
                             ->index('id')
                             ->select();
    return $eip_user;
  }

  public static function get_user_data($user_id){
    return D('eip_user')->where('id="'.$user_id.'"')->find();
  }

  /*計算年資*/
  public static function count_seniority($dutday=''){
    $dut_month = 0;
    if($dutday){
      if($dutday!='0000-00-00'){
        $current_date = date('Y-m-d');
        while ($current_date>$dutday) {
          $Y = (int)substr($dutday, 0, 4);
          $m = (int)substr($dutday, 5, 2) + 1;
          if($m==13){ $Y +=1; $m = 1; }
          $d = substr($dutday, 8, 2);
          $dutday = $Y.'-'.str_pad($m, 2, '0', STR_PAD_LEFT).'-'.str_pad($d, 2, '0', STR_PAD_LEFT);
          if($current_date>$dutday){
            $dut_month += 1; /*每次加1月*/
          }
        }
      }
    }
    $return_data['dut_month'] = $dut_month;
    $return_data['dut_years'] = round($dut_month / 12, 2);
    // dump($return_data);exit;
    return $return_data;
  }
  /*計算剩餘特休日*/
  public static function count_special_rest_remained($user_id){
    $special_rests_remained_hours = 0;
    $special_rest_accumulation_num = D('special_rest_accumulation')->where('user_id='.$user_id)->sum('rest_day') ?? 0;
    $special_rests_remained_hours += $special_rest_accumulation_num*8;
    $special_rest_used = D('rest_records')->where('rest_type_id=1 AND user_id='.$user_id)->sum('hours') ?? 0;
    $special_rests_remained_hours -= $special_rest_used;
    return $special_rests_remained_hours;
  }
  
  public static function get_user_skills(){
    $user_skills = D('user_skill')->where('status=1')->order('order_id asc, id asc')->select();
    return $user_skills;
  }
  public static function get_bonus_type(){
    $bonus_type = D('bonus_type')->where('status=1')->order('order_id asc, id asc')->select();
    return $bonus_type;
  }
  public static function get_user_salary($user_id='0', $cond=[]){
    $where_query ='true';
    if(isset($cond['salary_date_s'])){
      if($cond['salary_date_s']){
        $where_query .= ' AND (`year`*12+`month`) >='. (substr($cond['salary_date_s'], 0, 4)*12+substr($cond['salary_date_s'], 5, 2));
      }
    }
    if(isset($cond['salary_date_e'])){
      if($cond['salary_date_e']){
        $where_query .= ' AND (`year`*12+`month`) <='. (substr($cond['salary_date_e'], 0, 4)*12+substr($cond['salary_date_e'], 5, 2));
      }
    }
    if($user_id!='0'){
      $where_query .= ' AND s.user_id="'.$user_id.'"';
    }
    $salary = D('salary s')->field('s.*, 
                                    e.name, e.no, e.bank, e.bank_code, e.bank_account, e.dutday
                            ')
                            ->join("LEFT JOIN eip_user e ON e.id= s.user_id")
                            ->where($where_query)
                            ->order('(`year`*12+`month`) desc, e.id asc')
                            ->select();
    return $salary;
  }
  public static function get_user_salary_record($user_id='0', $salary_ym='', $skillid=''){
    if($salary_ym){
      $year = substr($salary_ym, 0, 4);
      $month = substr($salary_ym, 4, 2);
      $day_s_like = $year.'-'.$month.'-31';
    }else{
      $day_s_like = date('Y').'-'.date('m').'-31';
    }
    $where_query = 'day_s <= "'.$day_s_like.'"';

    if($user_id!='0'){
      $where_query .= ' AND user_id="'.$user_id.'"';
    }
    // dump($where_query);

    /*取 小於等於設定月最後一日的 最後一筆 薪資紀錄*/
    $salary_records = D('salary_records')->where($where_query)->order('day_s desc')->find();

    $where_skill_query = 'srs.salary_records_id="'.$salary_records['id'].'"';
    if($skillid){ $where_skill_query .= ' AND srs.user_skill_id="'.$skillid.'"'; }
    $salary_records_skill = D('salary_records_skill srs')
                              ->field('srs.*, us.id as user_skill_id, us.name as user_skill_name')
                              ->where($where_skill_query)
                              ->join('user_skill as us ON us.id=srs.user_skill_id','left')
                              ->order('us.order_id asc, us.id asc')
                              ->select();
    $salary_records['pay_hour'] = count($salary_records_skill)>0 ? $salary_records_skill[0]['hour_pay'] : 0;
    $salary_records['salary_records_skill'] = $salary_records_skill;
    // dump($salary_records);
    return $salary_records;
  }
  public static function get_user_rest_record($params){
    $where_query = 'true ';
    $user_id = $params['user_id'] ?? '';
    if($user_id!==''){ $where_query .= ' AND rr.user_id='.$user_id; }

    $job_agent = $params['job_agent'] ?? '';
    if($job_agent!==''){ $where_query .= ' AND rr.job_agent='.$job_agent; }

    $examiner = $params['examiner'] ?? '';
    if($examiner!==''){ $where_query .= ' AND rr.examiner='.$examiner; }

    $examiner_top = $params['examiner_top'] ?? '';
    if($examiner_top!==''){ $where_query .= ' AND rr.examiner_top='.$examiner_top; }

    $apply_status = $params['apply_status'] ?? '';
    if($apply_status!==''){ 
      if($apply_status==-1){ /*申請中的請假*/
        $where_query .= ' AND rr.apply_status!=0';
      }else{
        $where_query .= ' AND rr.apply_status='.$apply_status;
      }
    }

    $rest_day_s_s = $params['rest_day_s_s'] ?? '';
    if($rest_day_s_s!==''){ $where_query .= ' AND rr.rest_day_s>="'.$rest_day_s_s.'"'; }
    $rest_day_s_e = $params['rest_day_s_e'] ?? '';
    if($rest_day_s_e!==''){ $where_query .= ' AND rr.rest_day_s<="'.$rest_day_s_e.'"'; }

    $salary_ym = $params['salary_ym'] ?? '';
    if($salary_ym){
      $year = substr($salary_ym, 0, 4);
      $month = substr($salary_ym, 4, 2);
      $day_s_like = $year.'-'.$month.'-%';
      $where_query .= ' AND rr.rest_day_s LIKE "'.$day_s_like.'"';
    }

    // dump($where_query);exit();
    $rest_records = D('rest_records rr')->field('rr.*, 
                                                 rt.name as rt_name, rt.deduct_percent,
                                                 eu0.name AS user_name, 
                                                 eu1.name AS job_agent_name, eu2.name AS examiner_name, eu3.name AS examiner_top_name,
                                                 rrr.note AS reply_note')
                                        ->join('rest_type AS rt ON rt.id=rr.rest_type_id', 'LEFT')
                                        ->join('eip_user AS eu0 ON eu0.id=rr.user_id', 'LEFT')
                                        ->join('eip_user AS eu1 ON eu1.id=rr.job_agent', 'LEFT')
                                        ->join('eip_user AS eu2 ON eu2.id=rr.examiner', 'LEFT')
                                        ->join('eip_user AS eu3 ON eu3.id=rr.examiner_top', 'LEFT')
                                        ->join('rest_records_reply AS rrr ON rrr.id=rr.rest_records_reply_id', 'LEFT')
                                        ->where($where_query)
                                        ->order('rr.rest_day_s desc, rr.id desc')->select();
    return $rest_records;
  }
}