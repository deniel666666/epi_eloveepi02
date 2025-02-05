<?php
  namespace Trade\Controller;
  use Trade\Controller\GlobalController;

  use PhpOffice\PhpSpreadsheet\Spreadsheet;
  use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
  use Photonic\CustoHelper;

  class ImcrmController extends GlobalController
  {
    private $coper_role = ""; // 以此腳色匯入客戶
    public static $crm_dict = [ /* 一般匯入時，crm欄位對應excel表 */
      'status_supplier' => 'excela',
      'name'            => 'excelb',
      'no'              => 'excelc',
      'addr'            => 'excele',
      'comphone'        => 'excelf',
      'url1'            => 'exceli',
      'industr'         => 'excelk',
      
      'bossname'        => 'excell',
      'hzrq'            => 'excelt',
      'zbe'             => 'excelu', 
    ];
    public static $crm_dict_me = [ /* 經濟部替代時，crm欄位對應excel表 */
      'no' => 'B',
      'name' => 'C',
      'zip' => 'D',
      'addr' => 'E',
      'bossname' => 'F',
      'zbe' => 'G',
      'hzrq' => 'H',
    ];
    public static $status_supplier_option = ['@@@', '客戶', '供應商'];

    function _initialize(){
      parent::_initialize();
      parent::check_has_access(CONTROLLER_NAME, 'red');

      $eid_now = M('im_importclient im')->join('`eip_user` e on e.id= im.eid')
                                        ->where("( im.status = '11') and eid != '".session('eid')."' ")
                                        ->field('im.*,e.name')->group('eid')->find();
      if($eid_now){ /*有非你的人在操作*/
        echo '<script>alert("'.$eid_now['name'].'正在操作請稍等！！！");location.href="'.U('Index/index').'"</script>';
      }else{
        if(M("im_importclient")->where("status = '11'")->count() == 0){ /*無人操作*/
          M("im_importclient")->data(['n'=>'0','eid'=>session('eid'),'nb'=>0,'status'=>'11','date' => 0])->add();
        }
      }

      $this->coper_role = session('coper_role') ? session('coper_role') : "";
      // dump($this->coper_role);exit;

      $acc=D('access')->where('id='.session('accessId'))->select()[0];
      $this->assign('acc', $acc);

      $this->assign('page_title', '匯入客戶');
      $this->assign('page_title_link_self', u('Imcrm/index'));
      $this->assign('page_title_active', 21);  /*右上子選單active*/
    }
  
    public function index(){
      session('other_temp', false); /*紀錄目前為一般匯入流程*/
      $this->display();
    }

    public function show_column($im_data){
      return  "
        <td align='center'>".$im_data[self::$crm_dict['status_supplier']]."</td>
        <td align='center'>".$im_data[self::$crm_dict['name']]."</td>
        <td align='center'>".$im_data[self::$crm_dict['no']]."</td>
        <td align='center'>".$im_data[self::$crm_dict['addr']]."</td>
        <td align='center'>".$im_data[self::$crm_dict['comphone']]."</td>
        <td align='center'>".$im_data[self::$crm_dict['url1']]."</td>
      ";
    }

    public function others(){
      $this->clearList(false);
      session('other_temp', true); /*紀錄目前為待處理名單處理流程*/
      parent::check_has_access(CONTROLLER_NAME, 'edi');

      $importclient = new \Photonic\Microcore($importclient_data, "im_importclient");
      $tmp = $importclient->eventDelete($_POST);
      if( $tmp ){
        $this->success('刪除完成...', U('Imcrm/others','',''),3);
        exit;
      }

      $nowpage = (int)$_GET['page'] ? (int)$_GET['page'] : 0;
      $max_mun = 25;
      $nowpage_sql = $nowpage*$max_mun;
      $searchstr = strlen($_POST['search']) > 0 ? "`excela` like '%{$_POST['search']}%' or 
                             `excelb` like '%{$_POST['search']}%' or 
                             `excelc` like '%{$_POST['search']}%' or 
                             `exceld` like '%{$_POST['search']}%' or 
                             `excele` like '%{$_POST['search']}%' or 
                             `excelf` like '%{$_POST['search']}%' or 
                             `excelg` like '%{$_POST['search']}%' or 
                             `excelh` like '%{$_POST['search']}%' or 
                             `exceli` like '%{$_POST['search']}%' or 
                             `excelj` like '%{$_POST['search']}%' or 
                             `excelk` like '%{$_POST['search']}%' or 
                             `excell` like '%{$_POST['search']}%' or 
                             `excelm` like '%{$_POST['search']}%' or 
                             `exceln` like '%{$_POST['search']}%' or 
                             `excelo` like '%{$_POST['search']}%' or 
                             `excelp` like '%{$_POST['search']}%' or 
                             `excelq` like '%{$_POST['search']}%' or 
                             `excelr` like '%{$_POST['search']}%' or 
                             `excels` like '%{$_POST['search']}%' or 
                             `excelt` like '%{$_POST['search']}%' or 
                             `excelu` like '%{$_POST['search']}%' or 
                             `excelv` like '%{$_POST['search']}%' or 
                             `excelw` like '%{$_POST['search']}%' or 
                             `excelx` like '%{$_POST['search']}%' or 
                             `excely` like '%{$_POST['search']}%' or 
                             `excelz` like '%{$_POST['search']}%'" : "1=1";
      //dump($searchstr . " and `status` = 0"."limit {$nowpage_sql},{$max_mun}");
      
      $list = $importclient->select("(".$searchstr . ") and status !='11'  ", "limit {$nowpage_sql},{$max_mun}");

      $list_totall = $importclient->count($searchstr);
      //dump($importclient);exit;
      //dump($list);exit;
      
      $tmpath = parse_url($_SERVER["REQUEST_URI"]);
      parse_str($tmpath['query'], $tmpath['variables']);
      
      if( $list_totall[0]['COUNT'] > $max_mun ){
        // exit;
        if( $nowpage <  $list_totall[0]['COUNT'] and ($nowpage+1)*$max_mun < $list_totall[0]['COUNT'] ){
          $tmp = $nowpage +1 ;
          $tmpath['variables']['page'] = $tmp;
          $nav['next'] = "<a href='{$tmpath['path']}?".http_build_query($tmpath['variables'])."' class='p_l'><span class='footfont_9'>下一頁</span></a> ";
        }
        if( $nowpage > 0){
          $tmp = $nowpage -1 ;
          $tmpath['variables']['page'] = $tmp;
          $nav['previous'] = " <a href='{$tmpath['path']}?".http_build_query($tmpath['variables'])."' class='p_l'><span class='footfont_9'>上一頁</span></a>";
        }
        for( $i=($nowpage-2); $i<=($nowpage+2); $i++ ){
          unset($class);
          if( $i < 0 ){continue;}
          if( $i >= ($list_totall[0]['COUNT']/$max_mun) ){continue;}
          // if( strlen($nav['mun']) > 0){$nav['mun'] .= " - ";}
          $tmp = $i+1;
          if( $i==$nowpage ){ 
            $class = ' class="current"';
          }
          $tmpath['variables']['page'] = $i;
          $nav['mun'] .= "<a href='{$tmpath['path']}?".http_build_query($tmpath['variables'])."' {$class}><span class='footfont_9'>{$tmp}</span></a> ";
        }
      }//end if
      
      foreach( $list as $k => $v ){
        $url = "{:u('Extra/group_edit', array('n'=>". $v['n'] ."),'')}";
        $url_del = "{:u('Extra/group_edit', array('del'=>1, 'n'=>". $v['n'] ."),'')}";
        $gop .= "
          <tr>
            <td align='center'><input type='checkbox' class='seleck' name='del[]' value='{$v['n']}'></td>
            <td align='center'>".D("eip_user")->where("id='".$v['eid']."'")->field('name')->find()['name']."</td>
            ".$this->show_column($v)."
          </tr>
        ";
      }//end foreach
      
      //dump($gop);exit;
      $this->assign('nav', $nav);
      $this->assign('gop', $gop);
      // 藉由緩存傳遞資料給下一個方法
      F('Simportclient', $importclient);
      //dump(F('Simportclient'));exit;

      // 協同處理腳色
      $crm_cum_pri = parent::index_set('crm_cum_pri', $where="true",$word="name",$rname=false);
      
      $this->display();
    }
    public function other_step2()
    {
      if(isset($_POST['coper_role'])){
        $this->coper_role = $_POST['coper_role'];
        session('coper_role', $this->coper_role);
        if(!$this->coper_role){
          $this->error('請選擇匯入腳色');
        }
      }

      //global $importclient;
      $list_user = M()->query("SELECT i.eid,e.name FROM  im_importclient i  join eip_user e on i.eid = e.id where 1=1 group by i.eid");
      // dump($list_user);
      session('list_user', $list_user);
      session('other_temp', true); /*紀錄目前為待處理名單處理流程*/
        
      try{
        D()->query("update `im_importclient` set status = 0 where status = 1 ");
      }catch(\Think\Exception $e){
        // 更新失敗
      };
      redirect('step2', 0, '页面跳转中...');
    }

    private function adjust_to_db_format($dch){
      $data = [];
      /*廠商類型*/
      if(in_array($dch['excela'], self::$status_supplier_option)){
        $data['status_supplier'] = array_search($dch['excela'], self::$status_supplier_option);
      }else{
        $data['status_supplier'] = 1;
      }
      $data['name'] = $dch['excelb'];             /*全稱*/
      $data['nick'] = mb_substr($data['name'],0,5,"utf-8"); /*簡稱*/
      $data['no'] = $dch['excelc'];               /*統編*/
      $data['zip'] = $dch['exceld'];              /*郵遞區號*/
      $data['addr'] = $dch['excele'];             /*地址*/
      $data['comphone'] = $dch['excelf'];         /*電話*/
      $data['commobile'] = $dch['excelg'];        /*手機*/
      $data['commail'] = $dch['excelh'];          /*MAIL*/

      $data['url1'] = $dch['exceli'];             /*官方網站*/
      $data['comfax'] = $dch['excelj'];           /*傳真*/
      $data['industr'] = $dch['excelk'];          /*產業別*/
      $data['bossname'] = $dch['excell'];         /*負責人名稱*/
      $data['bossphone'] = $dch['excelm'];        /*負責人電話*/
      $data['bossmobile'] = $dch['exceln'];       /*負責人手機*/
      $data['bossmail'] = $dch['excelo'];         /*負責人MAIL*/

      $data['zbe'] = $dch['excelt'];              /*資本額*/
      $data['hzrq'] = $dch['excelu'];             /*核准日期*/
      $data['mom'] = $dch['excelv'];              /*公司備註*/
      $data['mom'] .= $dch['mom'] ? "\n".$dch['excelw'] : "";  /*員工人數*/

      /*客戶來源*/
      $sourse = D("crm_cum_sourse")->where("name ='".$dch['excelx']."'")->find();
      $data['sourceid'] = $sourse ? $sourse['id'] : "0";

      return $data;
    }
    private function adjust_to_db_format_contact($v){
      $contData = [];
      if($v['excelp']){ $contData['cname'] = $v['excelp']; }  /*姓名*/
      if($v['excelq']){ $contData['phone'] = $v["excelq"]; }  /*電話*/
      if($v['excelr']){ $contData['mobile'] = $v["excelr"]; } /*手機*/
      if($v['excels']){ $contData['mail'] = $v["excels"]; }   /*信箱*/
      return $contData;
    }
    private function add_chats($cumid, $data){
      $chats_key = 'excely';
      if( empty( trim($data[$chats_key]) ) ){ // 如果為空，不產生對話紀錄
        return;
      }
      $chats_data =[
        'lxrid'       =>0,                  // 預設聯絡負責人
        'qulid'       => 5,                 // 預設例行事
        'chattype'    => 1,                 // 預設電訪
        'chattype2'   => 1,                 // 預設致電
        'content'     => $data[$chats_key], // 訪談內容
        'appmdate'    => 0,                 // 預設不預約
        'eid'         => $data['eid'],      // 員工ID
        'doid'        => 0,                 // 資料庫必填
        'cumid'       => $cumid,            // 公司ID
        'cid'         => 1,                 // 資料庫必填
        'dateline'    => time(),            // 訪談時間
        'smevt'       => 0,                 // 預設無小事
        'doevt'       => 0,                 // 預設無小事處理
        'color_class' => null,              // 預設無記錄人員顏色
      ];
      M("crm_chats")->data($chats_data)->add();
    }
    private function save_changed_crm_in_db($crm_id){
      if(session('step4_list')){
        $step4_list = session('step4_list');
      }else{
        $step4_list = [];
      }
      array_push($step4_list, $crm_id);
      session('step4_list', $step4_list);
    }

    public function step1(){
      $importclient = new \Photonic\Microcore($importclient_data, "im_importclient");
      //print_r($importclient);exit;
      
      //dump($_POST);exit;
      $tmp = $importclient->eventDelete($_POST);
      if( $tmp ){
        $this->success('刪除完成...', U('Imcrm/step1/del/y'),3);
        exit;
      }
      
      // 確認使用者是否有傳入檔案位址, 並讀取 excel 至資料庫
      if( $_FILES['file1']['error'] == 0 and strlen($_FILES['file1']['tmp_name']) > 0 ){
        $PHPExcel = new Spreadsheet();
        $PHPReader = new Xlsx();
        // 檢查匯入的檔案是否為 Excel 檔
        if(!$PHPReader->canRead($_FILES['file1']['tmp_name'])){
          $this->error('檔案錯誤, 請確認檔案為 Excel', U('Imcrm/index','',''), 3);
          exit;
        }
        // 匯入檔案內的資料
        
        $PHPExcel = $PHPReader->load($_FILES['file1']['tmp_name']);
        //dump($PHPExcel);exit;
        $this->Dbio_Create("im_importclient");
        $this->ExceltoDb_Where("im_importclient",$PHPExcel);
        
      }elseif($_GET['del']==y || isset($_POST['search'])){
      }else{
        $this->error("沒有匯入檔案或匯入錯誤",U('Imcrm/index'));
      }//end if
      
      $nowpage = (int)$_GET['page'] ? (int)$_GET['page'] : 0;
      $max_mun = 25;
      $nowpage_sql = $nowpage*$max_mun;
      $searchstr = strlen($_POST['search']) > 0 ? "`excela` like '%{$_POST['search']}%' or 
                             `excelb` like '%{$_POST['search']}%' or 
                             `excelc` like '%{$_POST['search']}%' or 
                             `exceld` like '%{$_POST['search']}%' or 
                             `excele` like '%{$_POST['search']}%' or 
                             `excelf` like '%{$_POST['search']}%' or 
                             `excelg` like '%{$_POST['search']}%' or 
                             `excelh` like '%{$_POST['search']}%' or 
                             `exceli` like '%{$_POST['search']}%' or 
                             `excelj` like '%{$_POST['search']}%' or 
                             `excelk` like '%{$_POST['search']}%' or 
                             `excell` like '%{$_POST['search']}%' or 
                             `excelm` like '%{$_POST['search']}%' or 
                             `exceln` like '%{$_POST['search']}%' or 
                             `excelo` like '%{$_POST['search']}%' or 
                             `excelp` like '%{$_POST['search']}%' or 
                             `excelq` like '%{$_POST['search']}%' or 
                             `excelr` like '%{$_POST['search']}%' or 
                             `excels` like '%{$_POST['search']}%' or 
                             `excelt` like '%{$_POST['search']}%' or 
                             `excelu` like '%{$_POST['search']}%' or 
                             `excelv` like '%{$_POST['search']}%' or 
                             `excelw` like '%{$_POST['search']}%' or 
                             `excelx` like '%{$_POST['search']}%' or 
                             `excely` like '%{$_POST['search']}%' or 
                             `excelz` like '%{$_POST['search']}%'" : "1=1";
      //dump($searchstr . " and `status` = 0"."limit {$nowpage_sql},{$max_mun}");
      $list = $importclient->select("(".$searchstr . ") and `status` = 0", "limit {$nowpage_sql},{$max_mun}");
      $list_totall = $importclient->count($searchstr);
      
      $count=M('im_importclient')->where("(".$searchstr . ") and `status` = 0")->count();
      $Page = new \Think\Page($count,$max_mun);
      $show = $Page->show();
      $imdb=M('im_importclient')->where("(".$searchstr . ") and `status` = 0")->limit($Page->firstRow.','.$Page->listRows)->select();
      
      //dump($importclient);exit;
      //dump($list);exit;
      
      $tmpath = parse_url($_SERVER["REQUEST_URI"]);
      parse_str($tmpath['query'], $tmpath['variables']);
      
      if( $list_totall[0]['COUNT'] > $max_mun ){
        // exit;
        if( $nowpage <  $list_totall[0]['COUNT'] and ($nowpage+1)*$max_mun < $list_totall[0]['COUNT'] ){
          $tmp = $nowpage +1 ;
          $tmpath['variables']['page'] = $tmp;
          $nav['next'] = "<a href='{$tmpath['path']}?".http_build_query($tmpath['variables'])."' class='p_l'><span class='footfont_9'>下一頁</span></a> ";
        }
        if( $nowpage > 0){
          $tmp = $nowpage -1 ;
          $tmpath['variables']['page'] = $tmp;
          $nav['previous'] = " <a href='{$tmpath['path']}?".http_build_query($tmpath['variables'])."' class='p_l'><span class='footfont_9'>上一頁</span></a>";
        }
        for( $i=($nowpage-2); $i<=($nowpage+2); $i++ ){
          unset($class);
          if( $i < 0 ){continue;}
          if( $i >= ($list_totall[0]['COUNT']/$max_mun) ){continue;}
          // if( strlen($nav['mun']) > 0){$nav['mun'] .= " - ";}
          $tmp = $i+1;
          if( $i==$nowpage ){ 
            $class = ' class="current"';
          }
          $tmpath['variables']['page'] = $i;
          $nav['mun'] .= "<a href='{$tmpath['path']}?".http_build_query($tmpath['variables'])."' {$class}><span class='footfont_9'>{$tmp}</span></a> ";
        }
      }//end if
      
      foreach( $list as $k => $v ){
        $url = "{:u('Extra/group_edit', array('n'=>". $v['n'] ."),'')}";
        $url_del = "{:u('Extra/group_edit', array('del'=>1, 'n'=>". $v['n'] ."),'')}";
        $gop .= "
          <tr>
            <td align='center'><input type='checkbox' class='seleck' name='del[]' value='{$v['n']}'></td>
            ".$this->show_column($v)."
          </tr>
        ";
      }//end foreach
      
      //dump($gop);exit;
      $this->assign('nav', $nav);
      $this->assign('gop', $gop);
      // 藉由緩存傳遞資料給下一個方法
      F('Simportclient', $importclient);
      //dump(F('Simportclient'));exit;
      
      // 協同處理腳色
      $crm_cum_pri = parent::index_set('crm_cum_pri', $where="true",$word="name",$rname=false);

      $this->display();
    }
    
    public function step2(){
      //dump($_POST);
      // 藉由緩存接收資料上一個方法傳遞的資料
      $importclient = F('Simportclient');

      if(isset($_POST['coper_role'])){
        $this->coper_role = $_POST['coper_role'];
        session('coper_role', $this->coper_role);
        if(!$this->coper_role){
          $this->error('請選擇匯入腳色');
        }
      }

      /*操作重複處理*/
      foreach ($_POST['operator'] as $key => $operator) {
        // dump($operator);
        switch ($operator) {
          case 'monthnull': /*匯入補空母體*/
            if( isset($_POST['monther'][$key]) && isset($_POST['child'][$key]) ){
              $mother_id = $_POST['monther'][$key];
              foreach ($_POST['child'][$key] as $child_id) {
                if($mother_id==$child_id) continue;

                // 取得母項資料
                $dmo = $importclient->select("`n` = {$mother_id} and status = '0' ");
                // 取得子項資料
                $dch = $importclient->select("`n` = {$child_id} and status = '0'");

                if($dch[0] != NULL){
                  // 以子項為基礎為母項補資料
                  foreach( $dch[0] as $vk => $vv ){
                    
                    // 如果子項有的資料母像沒有則補齊, n=>id, data=>資料建立日期, nb=>排序
                    if( strlen($vv) > 0 and strlen($dmo[0][$vk]) == 0 and $vk != 'n' and $vk != 'date' and $vk != 'nb'){
                      $tdata[$vk] = $vv;
                    }//end if
                  }//end foreach
                }//end if
                
                //var_dump($ck);
                // 更新母項資料
                if($tdata)
                  M("im_importclient")->where("n = '{$mother_id}'")->data($tdata)->save();

                // 刪除此筆子項資料
                $importclient->eventDelete(array("del" => array((int)$child_id)));
              }
            }
            break;

          case 'monthrep': /*匯入替代母體*/
            if( isset($_POST['monther'][$key]) && isset($_POST['child'][$key]) ){
              $mother_id = $_POST['monther'][$key];
              if($mother_id==$_POST['child'][$key][0]) break;
              
              M('im_importclient')->where('status = "0" and n='.$mother_id)->delete();
            }
            break;

          case 'delck': /*殺除勾選*/
            if( isset($_POST['ckb'][$key]) ){
              foreach( $_POST['ckb'][$key] as $item_id ){
                $importclient->eventDelete(array("del" => array((int)$item_id)));
              }
            }
            break;

          case 'status1': /*移至待處理*/
            if( isset($_POST['ckb'][$key]) ){
              foreach( $_POST['ckb'][$key] as $item_id ){
                $sql_body = "update `im_importclient` set `status`=1  where `n`={$item_id}";
                try{
                  //嘗試建立
                  M()->query($sql_body);
                }catch(\Think\Exception $e){
                  // 更新失敗
                };
              }
            }
            break;

          case 'aloneck': /*勾選成獨立客*/
            if( isset($_POST['ckb'][$key]) ){
              foreach( $_POST['ckb'][$key] as $item_id ){
                $sql_body = "update `im_importclient` set `status`=2  where `n`={$item_id}";
                try{
                  //嘗試建立
                  M()->query($sql_body);
                }catch(\Think\Exception $e){
                  // 更新失敗
                };
              }
            }
            break;
          
          default:
            # code...
            break;
        }
      }

      $importclientcount = M("im_importclient")->where(" status ='0' ")->count();
      $this->assign('importclientcount', $importclientcount[0]['count']);

      $this->display();
    }
    public function step2_get_repeat(){
      $importclient = F('Simportclient');

      /*比對重複*/
      $ckurl = $_POST['check'] ? $_POST['check'] : 'no';
      $ckfield[0] = $ckurl;
      $ckfield[1] = self::$crm_dict[$ckurl];
      if(!$ckfield[1]){ return; }
      
      $name_sql = $this->im_importclient_replace($ckfield);
      $list = M()->query("
        SELECT  md5({$name_sql}) AS `n`,{$name_sql} AS `name`, COUNT(*) AS count 
        FROM `im_importclient` 
        WHERE `status` = 0  AND ({$name_sql} != '' AND {$name_sql} is not null)
        GROUP BY {$name_sql} 
        HAVING COUNT(*) > 1 
        LIMIT 50
      ");
      /*產生重複資料的html*/
      foreach( $list as $k => $v ){
        if( $v['name'] == "" ) {
          continue;
        }
        else{
          if( in_array($ckfield[1], [
              self::$crm_dict['name'],
              self::$crm_dict['url1'],
            ]) 
          ){ /*比對的是名稱、網址，因為有省略過內容所以後面要加萬用字元%*/
            $sql1 = "{$name_sql} like '%".addslashes($v['name'])."%' ";
          }
          else{
            $sql1 = "{$name_sql} like '".addslashes($v['name'])."' ";
          }
          $sql = str_replace($ckfield[1], $ckfield[0], $sql1);
        }
        //dump($sql1);
        $tmp = $importclient->select("(".$sql1 . ") and `status`=0 ");
        
        if(count($tmp) <= 1)
          continue;
        
        $op .= "
          <table cellpadding='0' cellspacing='0' id='sameTable' class='same table edit_table' style='min-width:1200px'>
            <thead>
              <tr>
                  <th>母體</th>
                  <th>子體</th>
                  <th>匯入者</th>
                  <th>".self::$system_parameter["廠商類型"]."</th>
                  <th>".self::$system_parameter["公司名稱"]."</th>
                  <th>".self::$system_parameter["統編"]."</th>
                  <th>".self::$system_parameter["地址"]."</th>
                  <th>".self::$system_parameter["公司電話"]."</th>
                  <th>".self::$system_parameter["官方網站"]."</th>
                  <th><label> 
                    <input type='checkbox'  class='imcrmall{$k}' name='all' onclick='iimcrmall({$k})' /> 選擇</label>
                  </th>
                </tr> 
            </thead>
            <tbody>
        ";
        //dump($_SESSION);

        foreach( $tmp as $tk => $tv ){
          $name ="<td align='center'>".D("eip_user")->where("id='".$tv['eid']."'")->field('name')->find()['name']."</td>";
          $op .= "
            <tr class='{$v['n']}'>
              <td align='center' class='co1 mother_td'>
                <input type='radio' class='mother' name='monther[{$v['n']}]' value='{$tv['n']}' />
              </td>
              <td align='center' class='child_td'>
                <input type='checkbox' class='child' name='child[{$v['n']}][]' value='{$tv['n']}' />
              </td>
              ".$name."
              ".$this->show_column($tv)."
              <td align='center' class='ckb_td'>
                <input type='checkbox' class='select_checkbox seleck{$k}' name='ckb[{$v['n']}][]' value='{$tv['n']}' onclick='imcrmall({$k})'  />
              </td>
            </tr>
          ";
        }
        $op .= "
            <tr>
              <td colspan='10' class='operate_area' key='{$v['n']}'>
                <span class='operate_btn'><input type='radio' name='operator[{$v['n']}]' id='{$v['n']}_monthnull' value='monthnull'><label for='{$v['n']}_monthnull' class='ml-1'> 匯入補空母體</label></span>
                <span class='operate_btn'><input type='radio' name='operator[{$v['n']}]' id='{$v['n']}_monthrep' value='monthrep'><label for='{$v['n']}_monthrep' class='ml-1'> 匯入替代母體</label></span>
                <span style='float:right'>
                  <span class='operate_btn'><input type='radio' name='operator[{$v['n']}]' id='{$v['n']}_delck' value='delck'><label for='{$v['n']}_delck' class='ml-1'> 殺除勾選</label></span>
                  <span class='operate_btn'><input type='radio' name='operator[{$v['n']}]' id='{$v['n']}_status1' value='status1'><label for='{$v['n']}_status1' class='ml-1'> 移至待處理</label></span>";

        if(session('other_temp')){
          $op .= "  <span class='operate_btn'><input type='radio' name='operator[{$v['n']}]' id='{$v['n']}_aloneck' value='aloneck'><label for='{$v['n']}_aloneck' class='ml-1'> 勾選成獨立客戶</label></span>";
        }
        $op .= "    </span>
              </td>
            </tr>
          </tbody>
        </table>";
      }
      
      if($tmp == NULL) {
        $op = null;
      }
      if($_POST['check'] != NULL) {
        echo $op;
        exit;
      }
    }

    public function step3(){
      // dump($_POST);exit;
      // 藉由緩存接收資料上一個方法傳遞的資料
      $importclient = F('Simportclient');
      //dump($importclient);exit;

      $my_access = parent::get_my_access();
      $this->assign('my_access', $my_access);
      
      foreach ($_POST['operator'] as $key => $operator) {
        // dump($operator);
        switch ($operator) {
          case 'monthnull': /*匯入補空母體*/
            if( isset($_POST['monther'][$key]) && isset($_POST['child'][$key]) ){
              $mother_id = $_POST['monther'][$key];
              foreach ($_POST['child'][$key] as $child_id) {
                $dch = $importclient->select("`n` = {$child_id}");
                $data = $this->adjust_to_db_format($dch[0]);

                $ori_crm_crm = M("crm_crm")->where("id ='{$mother_id}'")->find();
                foreach($ori_crm_crm as $key => $vo){
                  if($vo != "" || $key == 'id'){
                    unset($data[$key]);
                  }
                }
                $this->save_changed_crm_in_db($ori_crm_crm['id']);

                if($ori_crm_crm['typeid']==5){ /*原本是開放客戶*/
                  $data[$this->coper_role] = $dch[0]['eid'];
                  if($this->coper_role=='wid'){
                    $data['typeid'] = 1;
                    $data['newclient_date'] = CustoHelper::get_newclient_date($ori_crm_crm['id'], $data);
                  }
                }
                // dump($data);exit;
                M("crm_crm")->where("id ='{$mother_id}'")->data($data)->save();
                
                $this->add_chats($mother_id, $dch[0]);

                if($my_access['imcrm_edi']==0 && !session('other_temp') && $ori_crm_crm['typeid']!=5 && $this->coper_role=='wid'){
                  $sql_body = "update `im_importclient` set `status`=1  where `n`={$dch[0]['n']}";
                  try{
                    //嘗試建立
                    M()->query($sql_body);
                  }catch(\Think\Exception $e){
                    // 更新失敗
                  };
                }else{
                  M("im_importclient")->where("n = '".$dch[0]['n']."'")->delete();
                }
              }
            }
            break;

          case 'monthrep': /*匯入替代母體*/
            if( isset($_POST['monther'][$key]) && isset($_POST['child'][$key]) ){
              $mother_id = $_POST['monther'][$key];
              foreach ($_POST['child'][$key] as $child_id) {
                $dch = $importclient->select("`n` = {$child_id}");
                $data = $this->adjust_to_db_format($dch[0]);

                $ori_crm_crm = M("crm_crm")->where("id ='{$mother_id}'")->find();
                $this->save_changed_crm_in_db($ori_crm_crm['id']);
                unset($data['id']);
                
                if($ori_crm_crm['typeid']==5){ /*原本是開放客戶*/
                  $data[$this->coper_role] = $dch[0]['eid'];
                  if($this->coper_role=='wid'){
                    $data['typeid'] = 1;
                    $data['newclient_date'] = CustoHelper::get_newclient_date($ori_crm_crm['id'], $data);
                  }
                }
                // dump($data);exit;
                M("crm_crm")->where("id ='{$mother_id}'")->data($data)->save();

                $this->add_chats($mother_id, $dch[0]);

                if($my_access['imcrm_edi']==0 && !session('other_temp') && $ori_crm_crm['typeid']!=5 && $this->coper_role=='wid'){
                  $sql_body = "update `im_importclient` set `status`=1  where `n`={$dch[0]['n']}";
                  try{
                    //嘗試建立
                    M()->query($sql_body);
                  }catch(\Think\Exception $e){
                    // 更新失敗
                  };
                }else{
                  M("im_importclient")->where("n = '".$dch[0]['n']."'")->delete();
                }
              }
            }
            break;

          case 'delck': /*殺除勾選*/
            if( isset($_POST['ckb'][$key]) ){
              foreach( $_POST['ckb'][$key] as $item_id ){
                $importclient->eventDelete(array("del" => array((int)$item_id)));
              }
            }
            break;

          case 'status1': /*移至待處理*/
            if( isset($_POST['ckb'][$key]) ){
              foreach( $_POST['ckb'][$key] as $item_id ){
                $sql_body = "update `im_importclient` set `status`=1  where `n`={$item_id}";
                try{
                  //嘗試建立
                  M()->query($sql_body);
                }catch(\Think\Exception $e){
                  // 更新失敗
                };
              }
            }
            break;

          case 'aloneck': /*勾選成獨立客*/
            if( isset($_POST['ckb'][$key]) ){
              foreach( $_POST['ckb'][$key] as $item_id ){
                $sql_body = "update `im_importclient` set `status`=2  where `n`={$item_id}";
                try{
                  //嘗試建立
                  M()->query($sql_body);
                }catch(\Think\Exception $e){
                  // 更新失敗
                };
              }
            }
            break;

          default:
            # code...
            break;
        }
      }

      $importclientcount = $importclient->count();
      $this->assign('importclientcount', $importclientcount[0]['count']);

      $this->display();
    }
    public function step3_get_repeat(){
      // 藉由緩存接收資料上一個方法傳遞的資料
      $importclient = F('Simportclient');
      /*比對重複*/
      $ckurl = strlen($_POST['check']) > 0 ? $_POST['check'] : 'name';
      /*比對重複*/
      $ckurl = $_POST['check'] ? $_POST['check'] : 'no';
      $ckfield[0] = $ckurl;
      $ckfield[1] = self::$crm_dict[$ckurl];
      if(!$ckfield[1]){ return; }
      
      $name_sql = $this->im_importclient_replace($ckfield);
      $list = M()->query("
        SELECT  md5({$name_sql}) AS `n`, {$name_sql} AS `name`, 
            COUNT(if(src = 'import', `src`, NULL)) AS count_import, 
            COUNT(if(src = 'local', `src`, NULL)) AS count_local 
        FROM (
          SELECT  `".self::$crm_dict['name']."`, 
              `".self::$crm_dict['no']."`, 
              `".self::$crm_dict['addr']."`, 
              `".self::$crm_dict['comphone']."`, 
              `".self::$crm_dict['url1']."`, 
              'import' AS `src` 
          FROM `im_importclient` 
          WHERE `status` = 0 AND {$name_sql}!=''
          UNION 
          SELECT `name`, `no`, `addr`, `comphone`, `url1`, 'local' AS `src` 
          FROM `crm_crm`
        ) AS t1  
        GROUP BY {$name_sql} 
        HAVING COUNT( if(src = 'import', `src`, NULL) ) >= 1 AND COUNT( if(src = 'local', `src`, NULL) ) >= 1 
        LIMIT 50
      ");

      /*產生重複html*/
      foreach( $list as $k => $v ){
        if( $v['name'] == "" ) {
          continue;
        }
        else{

          if( in_array($ckfield[1], [
              self::$crm_dict['name'],
              self::$crm_dict['url1'],
            ]) 
          ){ /*比對的是名稱、網址，因為有省略過內容所以後面要加萬用字元%*/
            $sql1 = "{$name_sql} like '%".addslashes($v['name'])."%' ";
          }
          else{
            $sql1 = "{$name_sql} like '".addslashes($v['name'])."' ";
          }
          $sql = str_replace($ckfield[1], $ckfield[0], $sql1);
        }
        $op .= "
        <table cellpadding='0' cellspacing='0' id='sameTable' class='same table edit_table' style='min-width:1200px'>
          <thead>
            <tr>
              <th>母體</th>
              <th>子體</th>
              <th>匯入者</th>
              <th>".self::$system_parameter["廠商類型"]."</th>
              <th>".self::$system_parameter["公司名稱"]."</th>
              <th>".self::$system_parameter["統編"]."</th>
              <th>".self::$system_parameter["地址"]."</th>
              <th>".self::$system_parameter["公司電話"]."</th>
              <th>".self::$system_parameter["官方網站"]."</th>
              <th>
                <label> 
                 <input type='checkbox'  class='imcrmall{$k}' name='all' onclick='iimcrmall({$k})' /> 選擇</label>
              </th>
            </tr> 
          </thead>
          <tbody>
          ";
          
        /*找出所有重複的資料庫名單*/
        $tmp = M()->query("SELECT * FROM `crm_crm` where " . $sql);
        foreach( $tmp as $tk => $tv ){
          $op .= "<tr class='color1 monther {$v['n']}'>
                <td align='center' class='co1 mother_td'>
                  <input type='radio' id='input' name='monther[{$v['n']}]' value='{$tv['id']}' />
                </td>
                <td align='center'>
                </td>
                <td align='center'>".D("eip_user")->where("id='".$tv[$this->coper_role]."'")->field('name')->find()['name']."</td>
                <td align='center'>".(self::$status_supplier_option[$tv['status_supplier']]??'')."</td>
                <td align='center'>{$tv['name']}</td>
                <td align='center'>{$tv['no']}</td>
                <td align='center'>{$tv['addr']}</td>
                <td align='center'><span>{$tv['comphone']}</span></td>
                <td align='center'>{$tv['url1']}</td>
                <td align='center'>
                </td>
              </tr> ";
        }
        
        /*找出所有重複的匯入名單*/
        $tmp = $importclient->select( $sql1 . " and `status` = 0");
        foreach( $tmp as $tk => $tv ){
          $name ="<td align='center'>".D("eip_user")->where("id='".$tv['eid']."'")->field('name')->find()['name']."</td>";

          $op .= "
          <tr class='{$v['n']}'>
            <td align='center' class='co1'></td>
            <td align='center' class='child_td'>
              <input type='radio' class='child' name='child[{$v['n']}][]' value='{$tv['n']}' />
            </td>"
            .$name.
            "".$this->show_column($tv)."
            <td align='center' class='ckb_td'>
              <input type='checkbox' class='select_checkbox seleck{$k}' name='ckb[{$v['n']}][]' value='{$tv['n']}' />
            </td>
          </tr> ";
        }

        $op .= "<tr>
              <td colspan='10' class='operate_area' key='{$v['n']}'>
                <span class='operate_btn'><input type='radio' name='operator[{$v['n']}]' id='{$v['n']}_monthnull' value='monthnull'><label for='{$v['n']}_monthnull' class='ml-1'>匯入補空母體</label></span>
                <span class='operate_btn'><input type='radio' name='operator[{$v['n']}]' id='{$v['n']}_monthrep' value='monthrep'><label for='{$v['n']}_monthrep' class='ml-1'>匯入替代母體</label></span>
                <span style='float:right'>
                  <span class='operate_btn'><input type='radio' name='operator[{$v['n']}]' id='{$v['n']}_delck' value='delck'><label for='{$v['n']}_delck' class='ml-1'>殺除勾選</label></span>
                  <span class='operate_btn'><input type='radio' name='operator[{$v['n']}]' id='{$v['n']}_status1' value='status1'><label for='{$v['n']}_status1' class='ml-1'>移至待處理</label></span>";

        if(session('other_temp')){
          $op .= "<span class='operate_btn'><input type='radio' name='operator[{$v['n']}]' id='{$v['n']}_aloneck' value='aloneck'><label for='{$v['n']}_aloneck' class='ml-1'>勾選成獨立客戶</label></span>";
        }
        $op .= "  </span>
              </td>
            </tr>
          </tbody>
        </table>";
      }
      
      if($tmp == null)
        $op = null;
      if($_POST['check'] != NULL) {
        echo $op;
        exit;
      }
    }

    /*處理sql語句REPLACE*/
    public function im_importclient_replace($ckfield){
      $name_sql = '`'.$ckfield[1].'`';
      $im_importclient_replace = D('im_importclient_replace')->where('name="'.$ckfield[0].'"')->find();
      if($im_importclient_replace){
        $im_importclient_replace = $im_importclient_replace['content'] ? explode(',', $im_importclient_replace['content']) : [];
        foreach ($im_importclient_replace as $value) {
          $name_sql = "REPLACE(".$name_sql.",'".$value."','')";
        }
      }
      $name_sql = "REPLACE(".$name_sql.",' ','')";
      return $name_sql;
    }

    /*切換資料庫客戶的協同人員*/
    public function step4(){
      /*取得匯入腳色*/
      $coper_role = $this->get_import_role();

      $coper_role = M("crm_cum_pri")->where(["ename"=> ['eq', $this->coper_role]])->select();
      if(!$coper_role){
        $this->error('請選擇匯入腳色');
      }else{
        $this->assign('coper_role', $coper_role[0]);
      }
      // exit;
      // 處理切換資料庫客戶業務
      if($_POST){
        foreach ($_POST as $crm_id => $new_coper_member) {
          $crm_crm = D('crm_crm')->find($crm_id);
          if(!$crm_crm){ continue; }
          if($crm_crm[$this->coper_role]!=$new_coper_member){ /*有更換協同人員*/
            $update_data[$this->coper_role] = $new_coper_member;
            $update_data['newclient_date'] = CustoHelper::get_newclient_date($crm_id, $update_data);
            if($this->coper_role=='wid'){ /*匯入的腳色是業務*/
              // 檢查潛在上限
              if($crm_crm['typeid']==2){
                $potErrMsg = CustoHelper::potNum_anysis($new_coper_member, [$crm_id]);//檢查潛在上限
                if($potErrMsg){ $this->error($potErrMsg); }
              }
              // 新增客戶紀錄
              CustoHelper::add_salesrecord(
                $new_coper_member,// 業務id
                $opeid  = session('eid'),    // 操作人員
                $cid    = $crm_id,// 客戶id
                $typeid = $crm_crm['typeid'] // 修改的客戶類型
              );
            }
            D('crm_crm')->where('id='.$crm_id)->save($update_data);
          }
          $step4_list = session('step4_list');
          foreach ($step4_list as $key => $value) {
            if($value == $crm_id)
              unset($step4_list[$key]);
          }
          session('step4_list', $step4_list);
        }
      }

      if(!session('step4_list')){
        redirect('step5', 0, '页面跳转中...');
      }
      $id_where = implode(',', session('step4_list'));
      ///////////////////////////////////////////
      
      $list = M('crm_crm')->where("id in (" .$id_where. ")")->select();
      foreach($list as $k => $v ){
        $option = "";
        $add_ori_wid_option = true;
        $wid_name = D("eip_user")->where("id='".$v[$this->coper_role]."'")->field('name')->find()['name'];
        foreach($_SESSION['list_user'] as $lk => $lv){
          
          $selected ='';
          if( $lv['eid'] == $v[$this->coper_role]){
            $selected ='selected';
            $add_ori_wid_option = false;
          }
          $option .= "<option value='{$lv['eid']}' $selected>{$lv['name']}</option>";
        }
        if($add_ori_wid_option)
          $option .= "<option value='{$v[$this->coper_role]}' selected>{$wid_name}</option>";

        $op .= "
        <tr>
          <td align='center'><input type='checkbox' class='seleck' name='del[]' value='{$v[$this->coper_role]}'></td>
          <td align='center'>".$wid_name."</td>
          <td align='center'><select name='{$v['id']}'>{$option}</select></td>
          <td align='center'>{$v['name']}</td>
          <td align='center'>{$v['no']}</td>
          <td align='center'>{$v['addr']}</td>
          <td align='center'><span>{$v['comphone']}</span></td>
          <td align='center'>{$v['url1']}</td>
        </tr> ";
      }
      $this->assign('op',$op);
      
      $this->display();
    }

    /*切換匯入名單的操作人員*/
    public function step5(){
      /*取得匯入腳色*/
      $coper_role = $this->get_import_role();

      //dump($_POST);
      // 藉由緩存接收資料上一個方法傳遞的資料
      $importclient = F('Simportclient');
      //dump($importclient);exit;
      $count = 0;

      if($count != 0){
        M('im_importclient')->where("n != ''")->delete();
        $this->error('請重新檢查資料 '.$data['name'].' 確定衝突排除後再執行匯入', U('Imcrm/index','',''), 3);
      }

      ///////////////////////////////////////////
      
      $nowpage = (int)$_GET['page'] ? (int)$_GET['page'] : 0;
      $max_mun = 25;
      $nowpage_sql = $nowpage*$max_mun;
      $list = $importclient->select("status = 0 or status = 2 ", "limit {$nowpage_sql},{$max_mun}");
      $list_totall = $importclient->count("status != 11");
      // print_r($list_totall);
      $tmpath = parse_url($_SERVER["REQUEST_URI"]);
      parse_str($tmpath['query'], $tmpath['variables']);
      if( $list_totall[0]['COUNT'] > $max_mun ){
        //exit;
        if( $nowpage <  $list_totall[0]['COUNT'] and ($nowpage+1)*$max_mun < $list_totall[0]['COUNT'] ){
          $tmp = $nowpage +1 ;
          $tmpath['variables']['page'] = $tmp;
          $nav['next'] = "<a href='{$tmpath['path']}?".http_build_query($tmpath['variables'])."' class='p_l'><span class='footfont_9'>下一頁</span></a> ";
        }
        if( $nowpage > 0){
          $tmp = $nowpage -1 ;
          $tmpath['variables']['page'] = $tmp;
          $nav['previous'] = " <a href='{$tmpath['path']}?".http_build_query($tmpath['variables'])."' class='p_l'><span class='footfont_9'>上一頁</span></a>";
        }
        for( $i=($nowpage-2); $i<=($nowpage+2); $i++ ){
          unset($class);
          if( $i < 0 ){continue;}
          if( $i >= ($list_totall[0]['COUNT']/$max_mun) ){continue;}
          // if( strlen($nav['mun']) > 0){$nav['mun'] .= " - ";}
          $tmp = $i+1;
          if( $i == $nowpage ){ $class = ' class="current"';}
          $tmpath['variables']['page'] = $i;
          $nav['mun'] .= "<a href='{$tmpath['path']}?".http_build_query($tmpath['variables'])."' {$class}><span class='footfont_9'>{$tmp}</span></a> ";
        }
      }
      foreach($list as $k => $v ){
        $option = "";
        foreach($_SESSION['list_user'] as $lk => $lv){
          
          $selected ='';
          if( $lv['eid'] == $v['eid'])
            $selected ='selected';
          $option .= "<option value='{$lv['eid']}' $selected>{$lv['name']}</option>";
        }
        $op .= "
        <tr>
          <td align='center'><input type='checkbox' class='seleck' name='del[]' value='{$v['n']}'></td>
          <td align='center'>".D("eip_user")->where("id='".$v['eid']."'")->field('name')->find()['name']."</td>
          <td align='center'><select name='{$v['n']}'>{$option}</select></td>
          ".$this->show_column($v)."
        </tr> ";
      }
      //M('im_importclient')->where("n != '' and status != '1' ")->delete();
      $this->assign('nav',$nav);
      $this->assign('op',$op);
      
      $this->display();
    }

    /*添加客戶&展示結果*/
    public function step6(){
      /*取得匯入腳色*/
      $coper_role = $this->get_import_role();

      //dump($_POST);exit;
      foreach($_POST as $pk => $pv){
        M('im_importclient')->where("n = '".$pk."'")->data(['eid'=>$pv,'status'=>'2'])->save();
      }

      // 藉由緩存接收資料上一個方法傳遞的資料
      $importclient = F('Simportclient');
      //dump($importclient);exit;
      $count = 0;
      $tmp = $importclient->eventDelete($_POST);
      if( $tmp ){
        $this->success('請繼續處理人員切換', U('Imcrm/step5','',''), 3);
        exit;
      }
      $lista = $importclient->select("status = 0 or status = 2", "");
      // dump($lista);exit;

      // 客戶數檢查
      if( CustoHelper::check_crm_num(count($lista)) && count($lista)>0){
        $this->error(self::$system_parameter['客戶'].'數量超出上限', U('Imcrm/index'));
      }

      foreach( $lista as $k => $v ){
        $data = $this->adjust_to_db_format($v);
        // dump($data);exit;
        $data['status'] = $v['status'];
        $data['createtime'] = time();
        $data['typeid'] = session("eid") == self::$top_adminid ? "4" : "1"; // admin帳號匯入紀錄資料、其他帳號則紀錄新進
        $data[$this->coper_role] = $v['eid']; // 紀錄協同人員
        $data['newclient_date'] = CustoHelper::get_newclient_date(0, $data);

        foreach( $data as $kk => $vv ){
          $data[$kk] = addslashes($vv);
          /*if($vv == null)
            $count++;*/
          // $field[] = $kk;
        }
        if ($data['status']==0 || $data['status']==2){ /*針對匯入客戶、獨立客 進行處理*/
          $insertId = M('crm_crm')->data($data)->add();

          // 新增客戶紀錄
          CustoHelper::add_salesrecord(
            $salesid  = $v['eid'],        // 業務id
            $opeid    = $v['eid'],        // 操作人員(管理人員)
            $cid      = $insertId,        // 客戶id
            $typeid   = $data['typeid'],  // 修改的客戶類型
            $new      = true              // 新增
          );
          $contData = $this->adjust_to_db_format_contact($v);
          if($contData){
            $contData['cumid'] = $insertId;
            M('crm_contact')->data($contData)->add();
          }
        }

        $this->add_chats($insertId, $v);

      }//end foreach
      if($count != 0){
        M('im_importclient')->where("n != ''")->delete();
        $this->error('請重新檢查資料 '.$data['name'].' 確定衝突排除後再執行匯入', U('Imcrm/index','',''), 3);
      }

      try{
        //嘗試建立
        M()->query("update `im_importclient` set status = 10 where status = 0 or status = 2 ");
      }catch(\Think\Exception $e){
        // 更新失敗
      };
      ///////////////////////////////////////////
      
      $nowpage = (int)$_GET['page'] ? (int)$_GET['page'] : 0;
      $max_mun = 25;
      $nowpage_sql = $nowpage*$max_mun;
      $list = $importclient->select("status = 10", "limit {$nowpage_sql},{$max_mun}");
      $list_totall = $importclient->count("status = 10");
      // print_r($list_totall);
      $tmpath = parse_url($_SERVER["REQUEST_URI"]);
      parse_str($tmpath['query'], $tmpath['variables']);
      if( $list_totall[0]['COUNT'] > $max_mun ){
        //exit;
        if( $nowpage <  $list_totall[0]['COUNT'] and ($nowpage+1)*$max_mun < $list_totall[0]['COUNT'] ){
          $tmp = $nowpage +1 ;
          $tmpath['variables']['page'] = $tmp;
          $nav['next'] = "<a href='{$tmpath['path']}?".http_build_query($tmpath['variables'])."' class='p_l'><span class='footfont_9'>下一頁</span></a> ";
        }
        if( $nowpage > 0){
          $tmp = $nowpage -1 ;
          $tmpath['variables']['page'] = $tmp;
          $nav['previous'] = " <a href='{$tmpath['path']}?".http_build_query($tmpath['variables'])."' class='p_l'><span class='footfont_9'>上一頁</span></a>";
        }
        for( $i=($nowpage-2); $i<=($nowpage+2); $i++ ){
          unset($class);
          if( $i < 0 ){continue;}
          if( $i >= ($list_totall[0]['COUNT']/$max_mun) ){continue;}
          // if( strlen($nav['mun']) > 0){$nav['mun'] .= " - ";}
          $tmp = $i+1;
          if( $i == $nowpage ){ $class = ' class="current"';}
          $tmpath['variables']['page'] = $i;
          $nav['mun'] .= "<a href='{$tmpath['path']}?".http_build_query($tmpath['variables'])."' {$class}><span class='footfont_9'>{$tmp}</span></a> ";
        }
      }
      foreach($list as $k => $v ){
        $op .= "
        <tr>
          <td align='center'><input type='checkbox' class='seleck' name='del[]' value='{$v['n']}'></td>
          <td align='center'>".D("eip_user")->where("id='".$v['eid']."'")->field('name')->find()['name']."</td>
          ".$this->show_column($v)."
        </tr> ";
      }
      M('im_importclient')->where("n != '' and status != '1' ")->delete();
      M("im_importclient")->where("eid = '".session('eid')."' and nb = '0' and status = '11' ")->delete();
      session('list_user', null);
      session('other_temp', false);
      session('step4_list', null);
      $this->assign('nav',$nav);
      $this->assign('op',$op);
      
      $this->display();
    }

    /*取得所選擇的匯入腳色*/
    public function get_import_role(){
      $coper_role = M("crm_cum_pri")->where(["ename"=> ['eq', $this->coper_role]])->select();
      if(!$coper_role){
        $this->error('請選擇匯入腳色');
      }else{
        $this->assign('coper_role', $coper_role[0]);
      }

      return $coper_role;
    }


    public function me_import(){
      parent::check_has_access(CONTROLLER_NAME, 'edi');
      $this->display();
    }
    /*展示經濟部替代匯入*/
    public function me_import_show(){
      parent::check_has_access(CONTROLLER_NAME, 'edi');

      // 確認使用者是否有傳入檔案位址, 並讀取 excel 至資料庫
      if( $_FILES['file1']['error'] == 0 and strlen($_FILES['file1']['tmp_name']) > 0 ){
        $PHPExcel = new Spreadsheet();
        $PHPReader = new Xlsx();
        // 檢查匯入的檔案是否為 Excel 檔
        if(!$PHPReader->canRead($_FILES['file1']['tmp_name'])){
          $this->error('檔案錯誤, 請確認檔案為 Excel');
          exit;
        }
        // 匯入檔案內的資料
        
        $PHPExcel = $PHPReader->load($_FILES['file1']['tmp_name']);
        $sheetData = $PHPExcel->getSheet()->toArray(null,true,true,true);
        if(count($sheetData) <=1){ $this->error('請提供名單'); }

        $gop = [];
        foreach (array_slice($sheetData, 1) as $key => $value) {
          // 建立時間
          $hzrq = "";
          if($value[self::$crm_dict_me['hzrq']]){
            $dates = explode('/', trim($value[self::$crm_dict_me['hzrq']]));
            if(count($dates)>1){
              $hzrq = ((Int)$dates[0] + 1911) . "-" . $dates[1]  . "-". $dates[2];
            }else{
              $hzrq = $dates[0];
            }
          }

          if($value[self::$crm_dict_me['no']] || $value[self::$crm_dict_me['name']]){
            array_push($gop, [
              'no'        => trim($value[self::$crm_dict_me['no']]),
              'name'      => trim($value[self::$crm_dict_me['name']]),
              'zip'       => trim($value[self::$crm_dict_me['zip']]),
              'addr'      => trim($value[self::$crm_dict_me['addr']]),
              'bossname'  => trim($value[self::$crm_dict_me['bossname']]),
              'zbe'       => $zbe,
              'hzrq'      => $hzrq,
            ]);
          }
        }
        // dump($gop);exit;
        $this->assign('gop', $gop);
        if(!$gop){ $this->error('請確認名單是否完整'); }

        $this->display();

      }else{
        $this->error("沒有匯入檔案或匯入錯誤");
      }//end if
    }
    /*執行經濟部替代匯入*/
    public function me_import_do(){
      parent::check_has_access(CONTROLLER_NAME, 'edi');

      $me_list = [];
      if($_POST){
        if($_POST['me_list']){
          $me_list = $_POST['me_list'];
        }
      }
      if(!$me_list){ $this->error('請提供名單'); }
      // dump($me_list); exit;

      $update_count = 0;
      foreach ($me_list as $k => $v) {
        $v = json_decode($v);
        $crm_crm = D('crm_crm')->where('no ="'.$v->no.'"')->select(); // 找同統一編號的客戶
        if(!$crm_crm){ // 如果沒找到
          $crm_crm = D('crm_crm')->where('name ="'.$v->name.'"')->select(); // 找同名稱的客戶
        }

        // dump($crm_crm[0]['id']);
        if($crm_crm){ // 如果有相同的客戶，處理替代
          $update_data = [];
          foreach ($v as $vk => $vv) {
            if($vv!=""){ // 如果資料不為空
              $update_data[$vk] = $vv;  // 加入更新的資料
            }
          }

          if(isset($update_data['hzrq'])){
            if($update_data['hzrq'] > $crm_crm[0]['hzrq'] && $crm_crm[0]['hzrq']){ /*如果新的核准日比原始的大*/
              unset($update_data['hzrq']); /*不更新核准日*/
            }
          }
          // dump($update_data);

          $result = 0;
          if($update_data){
            $result = D('crm_crm')->where('id ="'.$crm_crm[0]['id'].'"')->save($update_data);
          }
          $update_count += $result;
        
        }else{ // 如果無相同的客戶
          // 移入待處理
            $inser_data = [
              'date'    => time(),
              'status'  => 1,
              'eid'     => session('eid'),
            ];
            if($v->name != ""){ $inser_data[self::$crm_dict['name']] = $v->name; }
            if($v->no != ""){ $inser_data[self::$crm_dict['no']] = $v->no; }
            if($v->addr != ""){ $inser_data[self::$crm_dict['addr']] = $v->addr; }
            if($v->bossname != ""){ $inser_data[self::$crm_dict['bossname']] = $v->bossname; }
            if($v->hzrq != ""){ $inser_data[self::$crm_dict['hzrq']] = $v->hzrq; }
            if($v->zbe != ""){ $inser_data[self::$crm_dict['zbe']] = $v->zbe; }
            // dump($inser_data);exit;
            // M('im_importclient')->data($inser_data)->add();

          // 新增至開放客戶
            $crm_data = $v;
            $crm_data->typeid = 5;
            // dump($crm_data);exit;
            M('crm_crm')->data($crm_data)->add();
        }
      }

      $this->success('處理成功', U('Imcrm/me_import'));
    }

    /*批次丟棄客戶*/
    public function trash(){
      parent::check_has_access(CONTROLLER_NAME, 'hid');
      $this->display();
    }
    public function trash_table(){
      parent::check_has_access(CONTROLLER_NAME, 'hid');

      // 確認使用者是否有傳入檔案位址, 並讀取 excel 至資料庫
      if( $_FILES['file1']['error'] == 0 and strlen($_FILES['file1']['tmp_name']) > 0 ){
        $PHPExcel = new Spreadsheet();
        $PHPReader = new Xlsx();
        // 檢查匯入的檔案是否為 Excel 檔
        if(!$PHPReader->canRead($_FILES['file1']['tmp_name'])){
          $this->error('檔案錯誤, 請確認檔案為 Excel', U('Imcrm/index','',''), 3);
          exit;
        }
        // 匯入檔案內的資料
        
        $PHPExcel = $PHPReader->load($_FILES['file1']['tmp_name']);
        $sheetData = $PHPExcel->getSheet()->toArray(null,true,true,true);

        if(count($sheetData) <=1){ $this->error('請提供名單'); }

        $sheetData = array_slice($sheetData, 1);
        $tax_nos = array_map(function($i){ if($i['A']) return$i['A']; }, $sheetData);
        $tax_nos = implode(',', $tax_nos);
        // dump($tax_nos);exit;

        $gop = $tax_nos ? D('crm_crm')->where(array('no'=>array('in',$tax_nos)))->where('typeid !=6')->select() : [];
        if(!$gop){ $this->error('無需更新的名單'); }
        // dump($gop);
        $this->assign('gop', $gop);

        $this->display();

      }else{
        $this->error("沒有匯入檔案或匯入錯誤");
      }//end if
    }
    public function do_trash(){
      parent::check_has_access(CONTROLLER_NAME, 'hid');

      $ids = [];
      if($_POST){
        if($_POST['trash']){
          $ids = $_POST['trash'];
        }
      }
      if(!$ids){$this->error('無客戶需移至垃圾桶', U('Imcrm/trash'));}

      $result = D('crm_crm')->where(array('id'=>array('in',$ids)))->save(['typeid'=>6]);
      if($result){
        $this->success('移至垃圾桶成功', U('Imcrm/trash'));
      }else{
        $this->error('無需更新的名單', U('Imcrm/trash'));
      }
    }

    /***
      *excel到資料庫輸入輸出應用
    ***/
    public $comrow=array();//excel對應欄位值
    public $sqlrow=array();//資料庫對應欄位值
    public $sqlremark=array();//資料庫中文欄位
    /***
      *建資料表 以及取出相關資料
      輸入資料表名
    ***/
    function Dbio_Create($DB_TABLE){//新增資料表
      //將比對資料串起來
      $posts=M('crm_word')->join(' `crm_title` on `crm_title`.id= `crm_word`.title_id')->where("`match_status`='1'")->select();
      
      $i=1;
      foreach($posts as $post){
        $this->comrow[$i]=$post['match_key'];
        $this->sqlrow[$i]=$post['title_name'];
        $this->sqlremark[$i]=$post['title_remark'];
        $i++;
      }
    }
    /***
      *將資料比對並匯入資料庫
      輸入資料表名
    ***/
    public function ExceltoDb_Where($DB_TABLE,$Excel)//存入資料庫
    {
      $sqlline="";
      
      $sheetData = $Excel->getSheet(0)->toArray(null,true,true,true);
      //欄與列的index
      $rowindex=0;
      //某行完全沒有值的判斷變數
      $rownull=true;
      //資料對應的欄位標題，有時標題也有利用的空間，完整版我是有用到。
      $title = array();
      //一樣的資料
      
      //確定抓到標題
      $checktitle=true;
      $totalname="";
      //讀列
      $addr_akey = str_replace('excel', '', self::$crm_dict['addr']); /*地址欄位所對應的小寫英文代碼*/
      $industr_akey = str_replace('excel', '', self::$crm_dict['industr']); /*產業別欄位所對應的小寫英文代碼*/
      foreach($sheetData as $key => $col){
        if($key != 1 && $key != 2){
          foreach($col as $akey => $acol){
            if($acol != ""){
              if(strtolower($akey) == $addr_akey){
                $acol = str_replace("台","臺",$acol);
                $acol = str_replace("F","樓",$acol);
                $acol = str_replace("壹","1",$acol);
                $acol = str_replace("貳","2",$acol);
                $acol = str_replace("參","3",$acol);
                $acol = str_replace("肆","4",$acol);
                $acol = str_replace("伍","5",$acol);
                $acol = str_replace("陸","6",$acol);
                $acol = str_replace("柒","7",$acol);
                $acol = str_replace("捌","8",$acol);
                $acol = str_replace("玖","9",$acol);
                $acol = str_replace("零","0",$acol);
              }
              if(strtolower($akey) == $industr_akey && mb_strlen( $acol, "utf-8") >= 5){
                $this->error(self::$system_parameter["產業別"].'輸入錯誤,不超過4個字', U('Imcrm/index','',''), 3);
              }

              $sqlline.="`excel".strtolower($akey)."`,";
              $write .= "'".$acol."',";
            }
          }
          if($write != ""){
            $sqlline=$sqlline."`date`,`status`,`eid`";
            $sqlout=$write."'".strtotime("now")."','0','".session('eid')."'";
            $sql_body="insert into {$DB_TABLE} ({$sqlline}) values ({$sqlout})";
            try{
              //嘗試建立
              M()->query($sql_body);
            }catch(\Think\Exception $e){
              // 已經有該資料庫了
            }
          }
          $sqlline = "";
          $write = "";
        }
      }
    }
    
    //放棄本次匯入
    public function clearList($del_user=true){
      if(session('other_temp')){
        M('im_importclient')->where("status != '11'")->save(['status'=>1]);
      }
      M('im_importclient')->where("status != '1' AND status != '11' ")->delete();
      if($del_user){
        M('im_importclient')->where("status = '11' AND eid='".session('adminId')."'")->delete();
      }
      session('other_temp', false); /*紀錄目前為一般匯入流程*/
    }

    // 編輯排除字
    public function edit_repeat(){
      parent::check_has_access(CONTROLLER_NAME, 'edi');

      $replaces = M("im_importclient_replace")->select();
      $this->assign('replaces', $replaces);

      $this->display();
    }
    public function do_edit_repeat(){
      parent::check_has_access(CONTROLLER_NAME, 'edi');

      $id = $_POST['id'];
      unset($_POST['id']);
      unset($_POST['name']);

      M("im_importclient_replace")->where('id="'.$id.'"')->data($_POST)->save();
      $this->success('修改成功');
    }
  }
?>