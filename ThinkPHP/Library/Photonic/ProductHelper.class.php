<?php
namespace Photonic;

use Think\Controller;

class ProductHelper extends Controller
{
  static public $countOfPage = 50;

  public static function instance(){
      return new ProductHelper();
  }

  static public function get_cat_unit($status=1, $get_or_pay=0){
    $field_query = 'unit.*, unit_c.name AS category_name';
    $cat_units = M("crm_cum_cat_unit unit")->join('LEFT JOIN crm_cum_cat_unit_category unit_c on unit_c.id=unit.category_id');
    
    $where = 'unit.get_or_pay="'.$get_or_pay.'"';
    if($status!=''){
      $where .= ' AND unit.status="'.$status.'"';
    }
    $cond = isset($_POST['cond']) ? $_POST['cond'] : [];

    if(isset($cond['crm_id'])){ /*依公司提供查詢*/
      $crm_provide = $cond['crm_provide'] ?? 0; /*是否提供*/
      if($crm_provide){ /*此廠商有提供的*/
        $cat_units->join('RIGHT JOIN crm_cum_cat_unit_crm unit_crm on unit_crm.crm_cum_cat_unit_id=unit.id');
        $where .= ' AND unit_crm.crm_id="'.$cond['crm_id'].'"';
      }else{  /*此廠商無提供的*/
        $where .= ' AND unit.id not in (SELECT crm_cum_cat_unit_id FROM crm_cum_cat_unit_crm WHERE crm_id="'.$cond['crm_id'].'")';
      }
    }
    if(isset($cond['search_category_id'])){
      if($cond['search_category_id']!='-1'){
        $where .=' AND unit.category_id = "'.$cond['search_category_id'].'"';
      }
    }
    if(isset($cond['searchKeyword'])){
      $where .=' AND (
        unit.name LIKE "%'.$cond['searchKeyword'].'%" OR
        unit.number LIKE "%'.$cond['searchKeyword'].'%" OR
        unit.type LIKE "%'.$cond['searchKeyword'].'%"
      )';
    }
    if(isset($cond['id'])){
      $where .=' AND unit.id = "'.$cond['id'].'"';
    }
    $cat_units = $cat_units->where($where)
                           ->field($field_query)
                           ->order("unit_c.orders asc, unit_c.id desc, unit.orders asc, unit.id desc")
                           ->group("unit.id")->select();
    // dump($cat_units);exit;

    $totalPage = count($cat_units) / self::$countOfPage;
    $totalPage = count($cat_units) % self::$countOfPage != 0 ? (Int)$totalPage+1 : $totalPage;
    $data = deal_pages($cond['currentPage'], $totalPage, $pages_limit=7);

    $data['countOfPage'] = self::$countOfPage;

    if(isset($cond['currentPage'])){
      if($cond['currentPage']){
        $index = ($cond['currentPage'] - 1) * self::$countOfPage;
        $cat_units = array_slice($cat_units, $index, self::$countOfPage);
      }
    }
    $data['cat_units'] = $cat_units;

    $layer_sub = D('accountant_item a')->field('a.*, b.name as top_name')
                    ->join("join accountant_item b on b.id=a.parent_id")
                    ->where('a.get_or_pay="'.$get_or_pay.'" AND a.parent_id!=0 AND a.status=1')
                    ->order('b.order_id asc, b.id asc, a.order_id asc, a.id asc')->select();
    $data['layer_sub'] = $layer_sub;
    // dump($data);exit;

    return $data;
  }

  static public function get_cat_unit_category($status=1, $get_or_pay=0){
    $cat_units = M("crm_cum_cat_unit_category");

    $where = 'get_or_pay="'.$get_or_pay.'"';
    if($status!=''){
      $where .= ' AND status="'.$status.'"';
    }
    $cond = isset($_POST['cond']) ? $_POST['cond'] : [];
    if(isset($cond['id'])){
      $where .=' AND id = "'.$cond['id'].'"';
    }
    $cat_units = $cat_units->where($where)->order("orders asc, id desc")->select();
    $data['cat_units'] = $cat_units;

    return $data;
  }

  static public function add_crm_cum_cat_unit_crm($crm_id, $crm_cum_cat_unit_ids){
    $insert_data = [];
    foreach ($crm_cum_cat_unit_ids as $value) {
      $insert_data[] = [
        'crm_id' => $crm_id,
        'crm_cum_cat_unit_id' => $value,
      ];
    }
    try {
      $result = M("crm_cum_cat_unit_crm")->addAll($insert_data);
    } catch (\Throwable $th) {
      $result = 0; /*資料重複或錯誤*/
    }
    return $result;
  }
  static public function delete_crm_cum_cat_unit_crm($crm_id, $crm_cum_cat_unit_ids){
    $delete_ids = [];
    foreach ($crm_cum_cat_unit_ids as $value) {
      $delete_ids[] = $value;
    }
    try {
      $result = M("crm_cum_cat_unit_crm")->where('crm_id="'.$crm_id.'" AND crm_cum_cat_unit_id in ('.implode(',', $delete_ids).')')->delete();
    } catch (\Throwable $th) {
      $result = 0; /*資料重複或錯誤*/
    }
    return $result;
  }
}
