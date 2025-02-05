<?php
namespace Photonic;
use Think\Controller;

use Photonic\CustoHelper;

class FigHelper extends Controller
{
    function _initialize(){
    }

    public static function instance(){
        return new FigHelper();
    }

    public static function get_eve_by_step_id($step_id=0){
      $eve = D('eve_steps as es')
            ->field('es.*, 
                     e.evesno, e.title as eve_title, e.cum_id as crm_id, e.caseid,
                     c.sn
            ')
            ->join('eve_events as e ON e.id=es.eve_id','left')
            ->join('crm_contract as c ON c.id=e.caseid','left')
            ->where('es.id="'.$step_id.'"')
            ->find();
      if($eve){
        $eve['show_name'] = CustoHelper::get_crm_show_name($eve['crm_id']);
      }
      return $eve;
    }
  }

?>