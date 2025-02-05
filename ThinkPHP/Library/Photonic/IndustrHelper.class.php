<?php
namespace Photonic;

use Think\Controller;

class IndustrHelper extends Controller
{
    public static function instance(){
        return new IndustrHelper();
    }

	public static function get_all_industr(){
		$crm_industr = M("crm_industr")->field("id,industr")->where("industr != ''")->group("industr")->select();
		$crm_crm_industr = M("crm_crm")->field("id,industr")->where("industr != ''")->group("industr")->select();
		foreach ($crm_crm_industr as $key => $value) {
			$need_add = true;
			foreach ($crm_industr as $key2 => $value2) {
				if($value['industr']==$value2['industr']){
					$need_add = false;
					break;
				}
			}
			
			if($need_add){
				array_push($crm_industr, $value);
			}
		}

		return $crm_industr;
	}
}
