<?php
	namespace Trade\Controller;
	use Think\Controller;
	class FatApiController extends Controller
	{	
		
		public function zpi32()
		{

			$addr = $_GET['addr'];
			
			if(empty($addr)){
				echo '';
				exit;
			}
			

			$replace = [
				" " => "",
				"　" => "",
				"台" => "臺",
				"臺北縣" => "新北市",
				"桃園縣" => "桃園市",
				"一段"=>"１段",
				"1段" =>"１段",
				"二段"=>"２段",
				"2段" =>"２段",
				"三段"=>"３段",
				"3段" =>"３段",
				"四段"=>"４段",
				"4段" =>"４段",
				"五段"=>"５段",
				"5段" =>"５段",
				"六段"=>"６段",
				"6段" =>"６段",
				"七段"=>"７段",
				"7段" =>"７段",
				"八段"=>"８段",
				"8段" =>"８段",
				"九段"=>"９段",	
				"9段"=>"９段"
			];
			
			
			foreach($replace as $k => $v){ //替代可能影響的字段

				$addr = str_replace($k,$v,$addr);
				
			}
			

			$addr = array('last'=>$addr);
			
			$addr = $this->name_explode($addr,'County_name',["市","縣"]);//切割城市

			$addr = $this->name_explode($addr,'Area_names',["區","鎮","鄉"]);//切割鄉鎮

			$addr = $this->name_explode($addr,'Road_names',["段","路","村","街","巷","市","道"]);//切割不規則的街路門牌
			//dump($addr);
			$sql_where='';
			
			$d_range = $addr['last'];
			
			unset($addr['last']);
			
			$and_num = count($addr);
			$i=1;
			foreach($addr as $k => $v){
				
				$sql_where.=" {$k} like '%{$v}%'";
				
				if($i < $and_num)
					$sql_where.=" and ";
				
				$i++;
			}
			
			
			$street = $this->expload_array($d_range);
			
			//dump($sql_where);
			$Postal_code = M('32zip')->where($sql_where)->field('d_range,Postal_code,id')->select();
			//dump($Postal_code);
			$figure = ($street['號']%2);
			$postal_code_num ='';
			foreach($Postal_code as $k => $v){
								
				$v['d_range'] = trim($v['d_range'],"　") ;
				$v['d_range'] = preg_replace('/\s(?=)/', '',$v['d_range']);		
				

				$s = $this->expload_array($v['d_range']);
				
				
				if($s['單']=="1" && $figure == 0){
			
					continue;
				}
				if($s['雙']=="1" && $figure == 1){
				
					continue;
				}

				
				if($s['全']=="1" && !$s['單'] && !$s['雙'] && !$s['巷']){
			
					$postal_code_num = $v['postal_code'];
					break;
				}
				
				if($s['全']=="1" && $s['單']=="1" && !$s['雙'] && !$s['巷']){
			
					$postal_code_num = $v['postal_code'];
					break;
				}
				
				if($s['全']=="1" && !$s['單'] && $s['雙']=="1" && !$s['巷']){
			
					$postal_code_num = $v['postal_code'];
					break;
				}
				
				
				if($s['巷']){

					if(empty($street['巷'])){
						continue;
					}else{
						if($street['巷']!=$s['巷']){
							continue;
						}
						
						if($street['巷']==$s['巷']){
							$postal_code_num = $v['postal_code'];
							break;
						}
					}
				}
				if($s['號']){
					
					if($s['號'] == $street['號'] && !$s['雙'] && !$s['單']){
						$postal_code_num = $v['postal_code'];
						break;
						
					}
					
					if($s['下']=="1"){
						if($street['號'] < $s['號']){
							$postal_code_num = $v['postal_code'];
							
						}
					}
					if($s['上']=="1"){
						if($street['號'] > $s['號']){
			
							$postal_code_num = $v['postal_code'];
							
						}
					}
					
					if($s['至']=="1"){
						if(($s['號'] < $street['號']) && ($street['號'] < $s['號2'])){
			
							$postal_code_num = $v['postal_code'];
							
						}
					}

				}

				
			}
			
			//echo json_encode(array('code'=>$postal_code_num));
			echo $postal_code_num;
			
		}
		
		function name_explode($re,$array_name,$where){//切割城市
			
			$re_array = $re;//暫存

			$re = $re['last'];//抓最後
	
			$con = 'false';
			
			foreach($where as $k => $v){
				
				$name = explode($v,$re);

				$c_name = count($name);
				$tmp_name = '';
				if($c_name  > 2){
										
					for($i=1; $i<($c_name-1);$i++){
						
						//$tmp_name .= $name[$i].$v;
						$tmp_name .= $name[$i];
						
					}
					
					$name[1]=$tmp_name.$name[$c_name-1];
					
				}	
				
				if($c_name  >= 2){
					
					//$re_array[$array_name] = $name[0].$v;
					$re_array[$array_name] = $name[0];
					
					$re_array['last'] = $name[1];
					
					$con = 'true';
					
					break;
					
				}
				
			}
			//dump( $re_array);exit;
			return $re_array;
				
		}
		
		
		function expload_array($d_range){
			
			$num = '';
			$street =[];
			for($i=0;$i < mb_strlen($d_range);$i++){
				//dump($i);
				$n = mb_substr($d_range,$i,1,"utf-8");
				//dump($n);
				
				if(is_numeric($n)) {
					$num .=$n;
				}else{
					
					if($n == "-" || $n == "之" ){	
						$n="號";
					}
					
					if(!empty($street[$n])){
						$n=$n."2";		
					}
					if(empty($num)){
						$num = '1';
					}
					$street[$n] = $num;
					$num = '';
					
				}
			}
			return $street;
			
		}

		

	}
	
	
?>			






























