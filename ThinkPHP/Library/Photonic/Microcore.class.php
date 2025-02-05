<?php
/***
 * Microcore
 * 資料庫操作類
 ***/
// Sky core v2 model lite
/*
array(	
	"title" => array(
		"type" => "varchar(255)",
		"name" => "標題",
		"null" => true,
		'input' => 'text',
		'showeTable' => false,
		'associate' => null,
		'note' => '',
	),
);
*/

namespace Photonic;
class Microcore
{
	public $version = 2;
	public $fields_array;
	public $fields_cksum;
	public $table_name;
	public $show_action = true;
	
	// auto script (feidle)
	function __construct($array, $tbn)
	{
		// Table Name ( like im_news )
		$this->table_name = $tbn;
		
		// ID 
		$array0["n"] = array("type"=>"int", "name"=>"編號", "null"=>false, "other"=>"AUTO_INCREMENT PRIMARY KEY", "input"=>"not", "showeTable"=>true);
		// Date
		$array0["date"] = array("type"=>"int", "name"=>"時間", "null"=>false, "input"=>"text", "showeTable"=>false);
		// sort by nb
		$array0["nb"] = array("type"=>"int", "name"=>"排序", "null"=>false, "input" =>"text", "showeTable"=>true, "note"=>"( 0 = 自動編號 )");
		
		$array = array_merge($array0, $array);
		//dump($array);
		//array_merge($this->fields_array, $array);
		$this->fields_array = $array;
		//dump($this->fields_array);
		$this->fields_cksum = md5( json_encode($array) );
	}

	// 初始化，建立表格
	function init()
	{
		$this->doCreate();
		$this->checkTable();
		if( $_GET['skymodel'] == 'checkTable' ){
			$this->checkTable();
		}elseif( $_GET['skymodel'] == 'reCreate' ){
			$this->reCreate();
		}elseif( $_GET['skymodel'] == 'version' ){
			echo $this->version;exit;
		}
		return true; 
	}

	// 檢查表格欄位
	function checkTable(){
		$tmp = M()->query("SHOW COLUMNS FROM `{$this->table_name}`");
		// print_r($tmp);
		$tmp_fields = $this->fields_array;
		if( count($tmp) != count($this->fields_array) or $_GET['forced'] == 1 ){
			foreach( $tmp as $k => $v ){
				if( empty($tmp_fields[$v['Field']]) ){
					try{
						// 嘗試建立
						M()->query("ALTER TABLE `{$this->table_name}` DROP COLUMN {$v['Field']};");
					}catch(\Think\Exception $e){
						// 已經有該資料欄了
					};
				}//end if
				unset($tmp_fields[$v['Field']]);
			}
			foreach( $tmp_fields as $k => $v ){
				try{
					// 嘗試建立
					M()->query("ALTER TABLE `{$this->table_name}` ADD {$k} {$v['type']}; ");
				}catch(\Think\Exception $e){
					// 已經有該資料欄了
				};
			}// foreach
			// exit;
		}
		return true; 
	}
	
	// 重建資料
	function reCreate(){
		$tmp = M()->select("DROP TABLE `".$this->table_name."`");
		return true; 
	}
	
	// 建立資料
	function doCreate()
	{
		//$tmp = M()->query("SHOW TABLES LIKE '".$this->table_name."' ");
		//dump($tmp);exit;
		if( count($tmp) == 0 ){
			$unique_data = array();
			$tmp_data .= "CREATE TABLE  `{$this->table_name}` (";
			$addtagg = false;

			foreach($this->fields_array as $k => $v ){
				if( $v['null'] == false ){
					$v_null = 'NOT NULL';
				}else{
					$v_null = 'NULL';
				}
				if( $addtagg == true ){
					$tmp_data .= ", ";
				}
				if( strlen($v['UNIQUE']) > 0 ){
					$unique_data[$v['UNIQUE']][] = $k;
				}
				$tmp_data .= " `{$k}` {$v['type']} {$v_null} {$v['other']} COMMENT '{$v['name']}'";
				$addtagg = true;
			}
			$tmp_data .= ") ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci ;";
			try{
				//嘗試建立
				M()->query($tmp_data);
			}catch(\Think\Exception $e){
				// 已經有該資料庫了
			};
			
			// add UK
			foreach($unique_data as $k => $v ){
				$memv = implode("`, `", $v);
				// print_r($unique_data);exit;
				$tmp_data = "ALTER TABLE `{$this->table_name}` ADD UNIQUE  `{$k}` (  `{$memv}` ) COMMENT  '';";
				try{
					//嘗試建立
					M()->query($tmp_data);
				}catch(\Think\Exception $e){
					// 已經有該資料庫了
				};
			}//end foreach
			
			// add FK
			foreach($this->fields_array as $k => $v ){
				if( $v['associate'] != null ){
					$tmp_data = "ALTER TABLE `{$this->table_name}` ADD FOREIGN KEY (`{$k}`) REFERENCES {$v['associate']} ON DELETE CASCADE ON UPDATE CASCADE;";
					try{
						//嘗試建立
						M()->query($tmp_data);
					}catch(\Think\Exception $e){
						// 已經有該資料欄了
					};
				}//end if
			}//end foreach
		}
		return true; 
	}
	
	//插入資料
	function intodata($data, $ignore = false)
	{
		if( count($data) == 0 ){return false;}
		if( strlen($data['date']) == 0 ){
			$data['date'] = strtotime("now");
		}else{
			$data['date'] = strtotime($data['date']);
		}
		$data['nb'] = (int)$data['nb'];
		//dump($this->fields_array);
		foreach( $this->fields_array["fields_array"] as $k => $v ){
			if( $k != 'n' and isset($data[$k]) ){
				if( strlen($v['regx']) > 0 ){
					if( !preg_match($v['regx'], $data[$k]) ){
						return false;
					}
				}
				if( $v['null'] == false ){
					if( strlen($data[$k]) == 0 ){
						return false;
					}
				}
				$only_fields1[] = "`{$k}`";
				// $only_fields2[] = ":{$k}";
				$only_fields2[] = "'{$data[$k]}'";
				$array[":{$k}"] = $data[$k];
			}
		}
		$ignore_sql = $ignore ? 'IGNORE' : '';
		$head = implode(", ", $only_fields1);
		$body = implode(", ", $only_fields2);
		$sql_body = "INSERT {$ignore_sql} INTO  `{$this->table_name}` ({$head})VALUES ({$body});";
		try{
			//嘗試建立
			M()->query($sql_body);
		}catch(\Think\Exception $e){
			// 已經有該資料庫了
		};
		try{
			//嘗試建立
			M()->query("update `{$this->table_name}` set `nb` = `n`*10 where `nb` = 0 ");
		}catch(\Think\Exception $e){
			// 更新失敗
		};
		
		return true;
	}

	//更新資料
	function updatedata($data, $id){
		// if( strlen($data['date']) == 0 )
			// {$data['date'] = strtotime("now");}else{$data['date'] = strtotime($data['date']);}
		// $data['nb'] = (int)$data['nb'];
		
		foreach( $this->fields_array as $k => $v ){
			if( $k != 'n' ){
				$only_fields1[] = $k;
			}
		}
		if( count( $data ) > 0 ) {
			foreach( $data as $k => $v ){
				if( $k != 'n' ){
					$only_fields2[] = $k;
				}
			}
		} else {
			$only_fields2 = array();
		}
		$intersect = array_intersect($only_fields1, $only_fields2);
		// print_r($intersect);
		if( empty($intersect) ){return false;}
		foreach( $intersect as $k => $v ){
			// $set_data_array[] = "`{$v}` = :{$v}";

			if( strlen($this->fields_array[$v]['regx']) > 0 ){
				if( !preg_match($this->fields_array[$v]['regx'], $data[$v]) ){
					return false;
				}
			}
			if( $this->fields_array[$v]['null'] == false ){
				if( strlen($data[$v]) == 0 ){
					return false;
				}
			}
			$set_data_array[] = "`{$v}` = '{$data[$v]}'";
			$array[":{$v}"] = $data[$v];
		}
		$array[':n'] = $id;
		$set_data = implode(", ", $set_data_array);
		$sql_body = "update `{$this->table_name}` set {$set_data} where `n` = {$array[':n']}";
		
		try{
			//嘗試建立

			M()->query($sql_body);
		}catch(\Think\Exception $e){
			// 更新失敗
		};
		try{
			//嘗試建立
			M()->query("update `{$this->table_name}` set `nb` = `n`*10 where `nb` = 0 ");
		}catch(\Think\Exception $e){
			// 更新失敗
		};
		return true;
	}

	//查詢資料
	function select($where = '1=1', $limit = ''){
		//dump("select * FROM `{$this->table_name}` WHERE {$where} {$limit}");
		return M()->query("select * FROM `{$this->table_name}` WHERE {$where} {$limit}");
	}
	//Get count 
	function count($where = '1=1'){
		return M()->query("select COUNT(*) as `COUNT` FROM `{$this->table_name}` WHERE {$where} ");
	}

	//刪除資料
	function eventDelete($data)
	{
		if( isset($data['del']) and is_array($data['del']) ){
			foreach($data['del'] as $k => $v){
				try{
					//嘗試刪除
					M()->query("DELETE FROM `{$this->table_name}` WHERE `n` = {$v} ");
				}catch(\Think\Exception $e){
					// 刪除失敗, 該資料庫早已被刪除
				};
			}
			return true;
		}
		return false;
	}
		
	function addBuf()
	{
		
        // EDM 群組
		$edmgroup_data = array(
		     "name" => array(
			      "type" => "varchar(255)",
				  "name" => "群組名稱",
				  "null" => false,
				  "input" => "text",
				  // 'regx' => "/^([a-z0-9_\.-]+)@([\da-z\.-]+)\.([a-z\.]{2,6})$/",
				  "associate" => NULL,
				  "showeTable" => true,
			 ),
		);
		$edmgroup = new Microcore($edmgroup_data, "im_edmgroup");
		$edmgroup->init();
		$GLOBALS['fields_array'] = array_merge((array)$GLOBALS['fields_array'], (array)$edmgroup);
		//dump($GLOBALS['fields_array']);exit;
		
		// EDM 用戶
		$edmuser_data = array(
		     "mail" => array(
			      "type" => "varchar(255)",
				  "name" => "收件人信箱",
				  "null" => false,
				  "input" => "text",
				  "regx" => "/^([a-z0-9_\.-]+)@([\da-z\.-]+)\.([a-z\.]{2,6})$/",
				  "other" => "unique",
				  "associate" => null,
				  "showeTable" => true,
			 ),
			 "name" => array(
			      "type" => "varchar(255)",
				  "name" => "收件人名稱",
				  "null" => true,
				  "input" => "text",
				  "associate" => null,
				  "showeTable" => true,
			 ),
		);
		$edmuser = new Microcore($edmuser_data, "im_edmuser");
		$edmuser->init();
		$GLOBALS['fields_array'] = array_merge((array)$GLOBALS['fields_array'], (array)$edmuser);
		//dump($GLOBALS['fields_array']);exit;
		
		// EDM Link
		$edmlink_data = array(
		     "userid" => array(
			      "type" => "int",
			      "name" => "用戶編號",
			      "null" => false,
			      "input" => "text",
			      "associate" => " `im_edmuser`(`n`) ",
			      "UNIQUE" => "group1",
			      "showeTable" => true,
			 ),
			 "groupid" => array(
			      "type" => "int",
				  "name" => "群組編號",
				  "null" => false,
				  "input" => "text",
				  "associate" => " `im_edmgroup`(`n`) ",
				  "UNIQUE" => "group1",
				  "showeTable" => true,
			 ),
		);
		$edmlink = new Microcore($edmlink_data, "im_edmlink");
		$edmlink->init();
		$GLOBALS['fields_array'] = array_merge((array)$GLOBALS['fields_array'], (array)$edmlink);
		//dump($GLOBALS['fields_array']);exit;
		
		// EDM 
		$edm_data = array(
		     "title" => array(
			      "type" => "varchar(255)",
				  "name" => "標題",
				  "null" => false,
				  "input" => "text",
				  "showeTable" => true,
			 ),
			 "totalsent" => array(
			      "type" => "int",
				  "name" => "總發送量",
				  "null" => false,
				  "input" => "text",
				  "showeTable" => true,
			 ),
			 "opensent" => array(
			      "type" => "int",
				  "name" => "開信量",
				  "null" => false,
				  "input" => "text",
				  "showeTable" => true,
			 ),
			 "to" => array(
			      "type" => "longblob",
				  "name" => "信件內容",
				  "null" => true,
				  "input" => "textarea",
				  "showeTable" => false,
			 ),
			 "groupid" => array(
			      "type" => "int",
				  "name" => "群組編號",
				  "null" => false,
				  "input" => "text",
				  "associate" => " `im_edmgroup`(`n`) ",
				  // 'UNIQUE' => 'group1',
				  "showeTable" => true,
			 ),
		);
		$edm = new Microcore($edm_data, "im_edm");
		$edm->init();
		$GLOBALS['fields_array'] = array_merge((array)$GLOBALS['fields_array'], (array)$edm);
		//dump($GLOBALS['fields_array']);exit;
		
		// EDM sent
		$edmsent_data = array(	
		     "title" => array(
			      "type" => "varchar(255)",
				  "name" => "標題",
				  "null" => true,
				  "input" => "text",
				  "showeTable" => true,
			 ),
			 "mail" => array(
			      "type" => "varchar(255)",
				  "name" => "信箱",
				  "null" => true,
				  "input" => "text",
				  "showeTable" => true,
			 ),
			 "to" => array(
			      "type" => "longblob",
			      "name" => "內容",
		          "null" => true,
				  "input" => "textarea",
				  "showeTable" => false,
				  "note" => "",
			 ),
		);
		$edmsent = new Microcore($edmsent_data, "im_edmsent");
		$edmsent->init();
		$GLOBALS['fields_array'] = array_merge((array)$GLOBALS['fields_array'], (array)$edmsent);
		//dump($GLOBALS['fields_array']);exit;
		
		$importclient_data = array(
		     "status"=>array( "type"=>"int", "name"=>"狀態", "null"=>true, "input"=>"text", "showeTable"=>true),
		     "excela"=>array( "type"=>"varchar(255)", 'regx'=>"/^([\d]*)$/", "name"=>"excela", "null"=>true, "input"=>"text", "showeTable"=>true),
		     "excelb" => array( 	"type" => "varchar(255)", 	"name" => "excelb", 	"null" => true, 	'input' => 'text', 	'showeTable' => true, 	),
		     "excelc" => array( 	"type" => "varchar(255)", 	"name" => "excelc", 	"null" => true, 	'input' => 'text', 	'showeTable' => true, 	),
		     "exceld" => array( 	"type" => "varchar(255)", 	"name" => "exceld", 	"null" => true, 	'input' => 'text', 	'showeTable' => true, 	),
		     "excele" => array( 	"type" => "varchar(255)", 	"name" => "excele", 	"null" => true, 	'input' => 'text', 	'showeTable' => true, 	),
		     "excelf" => array( 	"type" => "varchar(255)", 	"name" => "excelf", 	"null" => true, 	'input' => 'text', 	'showeTable' => true, 	),
		     "excelg" => array( 	"type" => "varchar(255)", 	"name" => "excelg", 	"null" => true, 	'input' => 'text', 	'showeTable' => true, 	),
		     "excelh" => array( 	"type" => "varchar(255)", 	"name" => "excelh", 	"null" => true, 	'input' => 'text', 	'showeTable' => true, 	),
		     "exceli" => array( 	"type" => "varchar(255)", 	"name" => "exceli", 	"null" => true, 	'input' => 'text', 	'showeTable' => true, 	),
		     "excelj" => array( 	"type" => "varchar(255)", 	"name" => "excelj", 	"null" => true, 	'input' => 'text', 	'showeTable' => true, 	),
		     "excelk" => array( 	"type" => "varchar(255)", 	"name" => "excelk", 	"null" => true, 	'input' => 'text', 	'showeTable' => true, 	),
		     "excell" => array( 	"type" => "varchar(255)", 	"name" => "excell", 	"null" => true, 	'input' => 'text', 	'showeTable' => true, 	),
		     "excelm" => array( 	"type" => "varchar(255)", 	"name" => "excelm", 	"null" => true, 	'input' => 'text', 	'showeTable' => true, 	),
		     "exceln" => array( 	"type" => "varchar(255)", 	"name" => "exceln", 	"null" => true, 	'input' => 'text', 	'showeTable' => true, 	),
		     "excelo" => array( 	"type" => "varchar(255)", 	"name" => "excelo", 	"null" => true, 	'input' => 'text', 	'showeTable' => true, 	),
		     "excelp" => array( 	"type" => "varchar(255)", 	"name" => "excelp", 	"null" => true, 	'input' => 'text', 	'showeTable' => true, 	),
		     "excelq" => array( 	"type" => "varchar(255)", 	"name" => "excelq", 	"null" => true, 	'input' => 'text', 	'showeTable' => true, 	),
		     "excelr" => array( 	"type" => "varchar(255)", 	"name" => "excelr", 	"null" => true, 	'input' => 'text', 	'showeTable' => true, 	),
		     "excels" => array( 	"type" => "varchar(255)", 	"name" => "excels", 	"null" => true, 	'input' => 'text', 	'showeTable' => true, 	),
		     "excelt" => array( 	"type" => "varchar(255)", 	"name" => "excelt", 	"null" => true, 	'input' => 'text', 	'showeTable' => true, 	),
		     "excelu" => array( 	"type" => "varchar(255)", 	"name" => "excelu", 	"null" => true, 	'input' => 'text', 	'showeTable' => true, 	),
		     "excelv" => array( 	"type" => "varchar(255)", 	"name" => "excelv", 	"null" => true, 	'input' => 'text', 	'showeTable' => true, 	),
		     "excelw" => array( 	"type" => "varchar(255)", 	"name" => "excelw", 	"null" => true, 	'input' => 'text', 	'showeTable' => true, 	),
		     "excelx" => array( 	"type" => "varchar(255)", 	"name" => "excelx", 	"null" => true, 	'input' => 'text', 	'showeTable' => true, 	),
		     "excely" => array( 	"type" => "varchar(255)", 	"name" => "excely", 	"null" => true, 	'input' => 'text', 	'showeTable' => true, 	),
		     "excelz" => array( 	"type" => "varchar(255)", 	"name" => "excelz", 	"null" => true, 	'input' => 'text', 	'showeTable' => true, 	),
		);
		$importclient = new Microcore($importclient_data, "im_importclient");
		$importclient->init();
		$GLOBALS['fields_array'] = array_merge((array)$GLOBALS['fields_array'], (array)$importclient);
		//dump($GLOBALS['fields_array']);exit;
		$this->fields_array = $GLOBALS['fields_array'];
	}
		
}
		
		


?>