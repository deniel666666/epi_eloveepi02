<?php

namespace Trade\Model;

use Think\Model;

class AttendanceDateModel extends Model
{
    public function set_table_name($db_name=''){
        if($db_name){
            $this->name = $db_name;
            $this->name_q = '`'.$this->name.'`';
            $this->db->setModel($db_name);
            $this->setProperty('tableName', $db_name);
            $this->setProperty('trueTableName', $db_name);
        }
        return $this;
    }
    public function getDataList($get_month=null, $group_date=true, $params){
        $map = array();
        if ($get_month != null) {
            $start       = date('Y-m-01', strtotime($get_month));
            $end         = date('Y-m-t', strtotime($get_month));
            $map[$this->name_q.'.date'] = array('between', array($start, $end));
        }

        if(isset($params['user_id'])){
            if($params['user_id']){
                $map[$this->name_q.'.user_id'] = $params['user_id'];
            }
        }
        if(isset($params['date'])){
            if($params['date']){
                $map[$this->name_q.'.date'] = $params['date'];
            }
        }
        if(isset($params['need_show'])){
            if($params['need_show']!==''){
                $map[$this->name_q.'.need_show'] = $params['need_show'];
            }
        }
        

        $this->field($this->name_q.'.*, eip_user.dutday')
             ->join('eip_user on eip_user.id='.$this->name_q.'.user_id', 'left')
             ->where($map);
        if($group_date){ $this->group('date'); }
        return $this->order('date asc')->select();
    }
}
