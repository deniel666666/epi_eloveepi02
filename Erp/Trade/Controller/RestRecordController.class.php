<?php
  namespace Trade\Controller;

use Org\Util\Date;
use Trade\Controller\GlobalController;

  use Photonic\MensHelper;
  use Photonic\Common;
  use Photonic\GoogleStorage;

  class RestRecordController extends GlobalController {
    
    function _initialize(){
      parent::_initialize();

      $this->assign('page_title_active', 150);  /*右上子選單active*/
    }

    public function index(){
      parent::index_set('rest_type'); /*假種*/

      /*全部部門*/
      $eip_apart_options = MensHelper::get_eip_apart();
      $this->assign('eip_apart_options', $eip_apart_options);
      
      /*全部在職人員(有用於職務代理人選擇)*/
      $eip_user_options_working = MensHelper::get_mens_working();
      $this->assign('eip_user_options_working', $eip_user_options_working);
      /*公司審核人員*/
      $eip_top_examiner_options = MensHelper::get_mens_rest_top_examiner();
      $this->assign('eip_top_examiner_options', $eip_top_examiner_options);

      $this->assign('page_title', '核假管理');
      $this->display();
    }
    public function get_eip_user_options(){
      $return_data = [];
      /*全部在職人員(有用於職務代理人選擇)*/
      $apartmentid = $_POST['apartmentid'];
      $searchtext = trim($_POST['searchtext']);
      $eip_user_options = MensHelper::get_mens_working([], ['apartmentid'=>$apartmentid,'searchtext'=>$searchtext,]);
      $return_data['eip_user_options'] = $eip_user_options;
      $this->ajaxReturn($return_data);
    }
    public function get_eip_user_apart_options(){
      $return_data = [];
  
      /*找出所選員工*/
      $user_id = $_POST['user_id'];
      $eip_user_options = MensHelper::get_mens_working([], ['eip_user.id'=>$user_id,]);
      $apartmentid = $eip_user_options[0]['apartmentid'] ?? -1;
      /*找出同部門人員*/
      $eip_user_apart_options = MensHelper::get_mens_working([], ['apartmentid'=>$apartmentid]);
      $return_data['eip_user_apart_options'] = $eip_user_apart_options;
      
      $this->ajaxReturn($return_data);
    }

    /*AJAX:取得假勤紀錄*/
    public function get_rest_records(){
      $return_data['rest_records'] = MensHelper::get_user_rest_record($_GET);
      $this->ajaxReturn($return_data);
    }

    /*AJAX:添加、編輯假勤紀錄*/
    public function set_rest_records(){
      $data = $_POST;
      $data['apply_status'] = 2;	/*預設送職代審核*/
      $rest_id = $data['id'];
      $user_id = $data['user_id'];
      $rest_type_id = $data['rest_type_id'];
      unset($data['id']);

      if(!$user_id){ $this->error('未指定申請人'); }
      if($user_id!=session('adminId')){ /*非自己的請假申請*/
        parent::check_has_access('mens', 'edi'); /*檢查是否有編輯權限*/
      }
      if(!$rest_type_id){ $this->error('請選擇假別'); }
      if(!$data['rest_day_s']){ $this->error('請設定開始日期'); }
      if(!$data['rest_day_e']){ $this->error('請設定結束日期'); }
      if(substr($data['rest_day_s'], 5, 2)!=substr($data['rest_day_e'], 5, 2)){
        $this->error('跨月假期請分開建立');
      }
      if(!$data['hours']){ $this->error('請設定請假時數'); }

      $rest_type = D('rest_type')->where('id='.$rest_type_id)->find();
      /*判斷是否還能請假*/
      if($rest_type_id=='1'){/*特休*/
        $special_rests_remained_hours = MensHelper::count_special_rest_remained($user_id);
        if($data['hours']>$special_rests_remained_hours){
          $this->error('超出此假別請假時數上限');
        }
      }
      else{/*其他*/
        if(!$rest_type){ $this->error('無此假別'); }

        // 取得員工到職日期
        $user_data = MensHelper::get_user_data($user_id);
        if ($user_data['dutday'] === '0000-00-00') $this->error('HR未設定到職日');
        
        // 假別上限改為年度結算
        $dutday_m_d = date('m-d', strtotime($user_data['dutday'])); // 到職日
        $date_start = date('Y') . '-' . $dutday_m_d; // 今年
        $date_end   = date('Y-m-d', strtotime($date_start. ' + 1 years')); // 今年 +1年

        $map                 = array();
        $map['user_id']      = $user_id;
        $map['rest_type_id'] = $rest_type_id;
        $rest_day_s = $rest_day_e = " BETWEEN '". $date_start . "' AND " . "'". $date_end . "'";
        $hours_used = D('rest_records')->where($map)->where('rest_day_s' . $rest_day_s . ' OR rest_day_e' . $rest_day_e)->sum('hours');
        if($hours_used + $data['hours']>$rest_type['month_limit'] && $rest_type['month_limit']>=0){
          $this->error('超出此假別請假時數上限');
        }
        if($data['hours']<=0){ $this->error('小時設定過小'); }
        if($data['hours']*60 != (int)($data['hours']*60/$rest_type['min_range']) * $rest_type['min_range']){
          $this->error('最小請假單位為'.$rest_type['min_range'].'分鐘');
        }
      }
      if($rest_type['preapply_days']>=0){
        if(strtotime(date('Y-m-d'))>strtotime($data['rest_day_s'].'-'.$rest_type['preapply_days'].'Days') ||
           strtotime(date('Y-m-d'))>strtotime($data['rest_day_e'].'-'.$rest_type['preapply_days'].'Days') ){
          $this->error("請提前".$rest_type['preapply_days']."日申請");
        }
      }
      if(!$data['reason']){ $this->error('請設定事由'); }
      
      if(!isset($data['job_agent'])){ $this->error('請設定職務代理人'); }
      if($data['job_agent']==''){ $this->error('請設定職務代理人'); }
      if($data['job_agent']==0){/*申請設定無職務代理人*/
        $data['apply_status'] = 3; /*跳成部審核*/
      }
      
      if(!isset($data['examiner'])){ $this->error('請設定部審核人員'); }
      if($data['examiner']=='' || $data['examiner']=='0'){ $this->error('請設定部審核人員'); }

      if($data['hours']<$rest_type['top_examine_hours']){/*申請時數不需公司審核*/
        $data['examiner_top'] = 0;
      }else{
        if(!isset($data['examiner_top'])){ $this->error('請設定公司審核人員'); }
        if($data['examiner_top']=='' || $data['examiner_top']=='0'){ $this->error('請設定公司審核人員'); }
      }

      /*base64檔案*/
      $prove_file = $data['prove_file'] ?? '';
      $prove_file_name = $data['prove_file_name'] ?? '';
      // dump($data);exit;
      unset($data['prove_file']);
      if($rest_id==0 || $rest_id==''){
        $rest_id = D('rest_records')->data($data)->add();
        parent::error_log('新增假勤資訊:'.json_encode($data, JSON_UNESCAPED_UNICODE));
      }else{
        $rest_records = D('rest_records')->data($data)->where('id="'.$rest_id.'"')->find();
        if(!$rest_records){ $this->error('資料有誤'); }
        if($rest_records['user_id']!=$user_id){ $this->error('申請人須相同'); }

        // dump($data);exit;
        D('rest_records')->data($data)->where('id="'.$rest_id.'"')->save();
        parent::error_log('編輯假勤資訊:'.$rest_id.'，資料:'.json_encode($data, JSON_UNESCAPED_UNICODE));
      }

      /*上傳相關證明至google空間*/
      if($prove_file){
        $GoogleStorage = new GoogleStorage();
        $file_path = $this->google_file_path($rest_id);
        $upload_path = $GoogleStorage->upload_base64($prove_file, $prove_file_name, $file_path);

        D('rest_records')->data([
          'prove_file' => $upload_path,
        ])->where('id="'.$rest_id.'"')->save();
      }

      /*請假申請提醒*/
      $this->remind_rest_apply($rest_id);

      $this->success('操作成功');
    }
    public function download_file(){
      $file_path = $_GET['file_path'] ?? '';
      /*權限判斷*/
      $id = $_GET['id'] ?? ''; 
      $rest_records = D('rest_records')->where('id="'.$id.'"')->find();
      if(!$rest_records){ $this->error('資料有誤'); }

      $access = parent::get_my_access(); /*取得我的權限*/
      if($access['mens_edi']==0 && 							/*無可編輯權限*/
        $rest_records['job_agent']!=session('user_id') && 	/*非申請人*/
        $rest_records['job_agent']!=session('adminId') &&	/*非職代*/ 
        $rest_records['examiner']!=session('adminId') && 	/*非部審核人*/ 
        $rest_records['examiner_top']!=session('adminId')	/*非公司審核人*/ 
      ){
        $this->error('您無法查看此檔案');
      }

      $GoogleStorage = new GoogleStorage();
      $GoogleStorage->download($file_path);
    }
    public function google_file_path($rest_id){ /*回傳google storage檔案路徑*/
      $table = 'rest_records';
      return $table .'/'.$rest_id;
      }

    /*AJAX:回覆請假申請*/
    public function reply_rest_record(){
      $id = $_POST['id'] ?? '';
      $rest_records = D('rest_records')->where('id="'.$id.'"')->find();
      if(!$rest_records){ $this->error('請提供修改對象'); }

      $value = $_POST['value'] ?? '';
      if($value==''){ $this->error('資料不完整'); }
      $apply_status = $_POST['apply_status'] ?? '';
      if($apply_status!=$rest_records['apply_status']){ $this->error('尚未進入此審核階段'); }

      if($value!=1){ /*不同意*/
        $data['apply_status'] = 1; /*調整請假申請至修改中*/
      }
      if($apply_status==2){ /*職位代理人審核*/
        if($rest_records['job_agent']!=session('adminId')){ $this->error('您並非此申請之職務代理人'); }
        if($value==1){ /*同意*/
          $data['apply_status'] = 3;
        }
      }else if($apply_status==3){ /*審核人員(本部)審核*/
        if($rest_records['examiner']!=session('adminId')){ $this->error('您並非此申請之本部審核人員'); }
        if($value==1){ /*同意*/
          if($rest_records['examiner_top']){ /*有需要公司審核*/
            $data['apply_status'] = 4;
          }else{
            $data['apply_status'] = 0;
          }
        }
      }else if($apply_status==4){ /*審核人員(公司)審核*/
        if($rest_records['examiner_top']!=session('adminId')){ $this->error('您並非此申請之公司審核人員'); }
        if($value==1){ /*同意*/
          $data['apply_status'] = 0;
        }
      }
      $data_reply = [
        'rest_records_id' => $id,
        'apply_status' => $apply_status,
        'value' => $value,
        'note' => $_POST['reply_note'] ?? '',
        'time' => time(),
      ];
      // dump($data_reply);exit;
      $rest_records_reply_id = D('rest_records_reply')->data($data_reply)->add();
      parent::error_log('回覆假勤申請:'.$id.', 資料:'.json_encode($data_reply, JSON_UNESCAPED_UNICODE));
  
      $data['rest_records_reply_id'] = $rest_records_reply_id;
      // dump($data);exit;
      D('rest_records')->where('id='.$id)->data($data)->save();

      /*請假申請提醒*/
      $this->remind_rest_apply($id);
  
      $this->success('操作成功');
    }
    private function remind_rest_apply($rest_id){ /*請假申請提醒(請於請假記錄修改後呼叫)*/
      $rest_records = D('rest_records')->where('id='.$rest_id)->find();
      if(!$rest_records){ $this->error('資料有誤'); }
      $user_applicant = D('eip_user')->where('id='.$rest_records['user_id'])->find();
      
      if($rest_records['apply_status']==0){ /*申請成功*/
        $subject = '請假申請成功';
        $open_url = 'http://'.$_SERVER['HTTP_HOST'].'/'.U('RestRecord/index').'?tab=apply_completed&search_user_id='.$user_applicant['id'];
        $receiver_email = $user_applicant['email'];
        $receiver_id = $user_applicant['id'];
        $body ="
          <p>親愛的_".$user_applicant['name']."_您好：</p>
          <p>您於".$rest_records['rest_day_s']."~".$rest_records['rest_day_e']."之請假申請已通過審核
          <p>網址：<a href='".$open_url."'>".$open_url."</a></p>
        ";
      }
      else if($rest_records['apply_status']==1){ /*可修改申請(表示審核未通過)*/
        /*抓取最後一筆審核紀錄*/
        $data_reply = D('rest_records_reply')->where('rest_records_id="'.$rest_id.'"')->order('id desc')->find();
        if($data_reply['apply_status']==2){ /*職代未通過*/
          $user_examiner = D('eip_user')->where('id='.$rest_records['job_agent'])->find();
          $subject = '請假職務代理人不同意';
          $open_url = 'http://'.$_SERVER['HTTP_HOST'].'/'.U('RestRecord/index').'?tab=apply_uncompleted&search_user_id='.$user_applicant['id'];
          $receiver_email = $user_applicant['email'];
          $receiver_id = $user_applicant['id'];
          $body ="
            <p>親愛的_".$user_applicant['name']."_您好：</p>
            <p>您於".$rest_records['rest_day_s']."~".$rest_records['rest_day_e']."之請假申請的職務代理人 ".$user_examiner['name']." 並不同意</p>
            <p>請修改請假內容，並重新送出申請</p>
            <p>網址：<a href='".$open_url."'>".$open_url."</a></p>
          ";
        }
        else if($data_reply['apply_status']==3){ /*本部審核人員未通過*/
          $user_examiner = D('eip_user')->where('id='.$rest_records['examiner'])->find();
          $subject = '請假部審核人員不同意';
          $open_url = 'http://'.$_SERVER['HTTP_HOST'].'/'.U('RestRecord/index').'?tab=apply_uncompleted&search_user_id='.$user_applicant['id'];
          $receiver_email = $user_applicant['email'];
          $receiver_id = $user_applicant['id'];
          $body ="
            <p>親愛的_".$user_applicant['name']."_您好：</p>
            <p>您於".$rest_records['rest_day_s']."~".$rest_records['rest_day_e']."之請假申請的部審核人員 ".$user_examiner['name']." 並不同意</p>
            <p>請修改請假內容，並重新送出申請</p>
            <p>網址：<a href='".$open_url."'>".$open_url."</a></p>
          ";
        }
        else if($data_reply['apply_status']==4){ /*公司審核人員未通過*/
          $user_examiner = D('eip_user')->where('id='.$rest_records['examiner_top'])->find();
          $subject = '請假公司審核人員不同意';
          $open_url = 'http://'.$_SERVER['HTTP_HOST'].'/'.U('RestRecord/index').'?tab=apply_uncompleted&search_user_id='.$user_applicant['id'];
          $receiver_email = $user_applicant['email'];
          $receiver_id = $user_applicant['id'];
          $body ="
            <p>親愛的_".$user_applicant['name']."_您好：</p>
            <p>您於".$rest_records['rest_day_s']."~".$rest_records['rest_day_e']."之請假申請的公司審核人員 ".$user_examiner['name']." 並不同意</p>
            <p>請修改請假內容，並重新送出申請</p>
            <p>網址：<a href='".$open_url."'>".$open_url."</a></p>
          ";
        }
        else{ return; }
      }
      else if($rest_records['apply_status']==2){ /*換職代審核*/
        $user_examiner = D('eip_user')->where('id='.$rest_records['job_agent'])->find();
        /*提醒職務代理人確認*/
        $subject = '提醒請假職務代理審核';
        $open_url = 'http://'.$_SERVER['HTTP_HOST'].'/'.U('RestRecord/index').'?tab=job_agent';
        $receiver_email = $user_examiner['email'];
        $receiver_id = $user_examiner['id'];
        $body ="
          <p>親愛的_".$user_examiner['name']."_您好：</p>
          <p>您被".$user_applicant['name']."設定為".$rest_records['rest_day_s']."~".$rest_records['rest_day_e']."請假時之職務代理人</p>
          <p>請協助確認，以利請假流程繼續進行</p>
          <p>網址：<a href='".$open_url."'>".$open_url."</a></p>
        ";
      }
      else if($rest_records['apply_status']==3){ /*換本部審核人員審核*/
        $user_examiner = D('eip_user')->where('id='.$rest_records['examiner'])->find();
        /*提醒本部審核人員確認*/
        $subject = '提醒請假部審核';
        $open_url = 'http://'.$_SERVER['HTTP_HOST'].'/'.U('RestRecord/index').'?tab=examiner';
        $receiver_email = $user_examiner['email'];
        $receiver_id = $user_examiner['id'];
        $body ="
          <p>親愛的_".$user_examiner['name']."_您好：</p>
          <p>".$user_applicant['name']."於".$rest_records['rest_day_s']."~".$rest_records['rest_day_e']."申請請假</p>
          <p>請協助審核，以利請假流程繼續進行</p>
          <p>網址：<a href='".$open_url."'>".$open_url."</a></p>
        ";
      }
      else if($rest_records['apply_status']==4){ /*換公司審核人員審核*/
        $user_examiner = D('eip_user')->where('id='.$rest_records['examiner_top'])->find();
        /*提醒本部審核人員確認*/
        $subject = '提醒請假公司審核';
        $open_url = 'http://'.$_SERVER['HTTP_HOST'].'/'.U('RestRecord/index').'?tab=examiner_top';
        $receiver_email = $user_examiner['email'];
        $receiver_id = $user_examiner['id'];
        $body ="
          <p>親愛的_".$user_examiner['name']."_您好：</p>
          <p>".$user_applicant['name']."於".$rest_records['rest_day_s']."~".$rest_records['rest_day_e']."申請請假</p>
          <p>請協助審核，以利請假流程繼續進行</p>
          <p>網址：<a href='".$open_url."'>".$open_url."</a></p>
        ";
      }
      else{ return; }

      send_email($body, $receiver_email, $subject);
      $payload = [
              'title' => $subject,
              'msg' => "請登入系統查看",
              'open_url' => $open_url,
          ];
      $result = Common::send_notification_to_user($receiver_id, $payload);
      // dump($result);exit;
    }

    /*AJAX:刪除假勤紀錄*/
    public function delete_rest_records(){
      $id = $_POST['id'] ?? '';
      $rest_records = D('rest_records')->where('id='.$id)->find();
      if(!$rest_records){ $this->error('請提供刪除對象'); }

      if($rest_records['user_id']!=session('adminId')){ /*非自己的請假申請*/
        parent::check_has_access('mens', 'edi'); /*檢查是否有編輯權限*/
      }
      else{ /*自己的請假申請*/
        if($rest_records['apply_status']!=1){ /*不可修改*/
          parent::check_has_access('mens', 'edi'); /*檢查是否有編輯權限*/
        }
      }

      D('rest_records')->where('id='.$id)->delete();
      parent::error_log('刪除假勤資訊:'.$id.', 資料:'.json_encode($rest_records, JSON_UNESCAPED_UNICODE));
      $this->success('操作成功');
    }
  }
?>