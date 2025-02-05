<?php
/***
 * Edcode
 * 對密碼加密類
 *
 * 對密碼加密閉免被有心人盜取
 *
 ***/
namespace Photonic;
use Think\Controller;

use Photonic\Common;
use Photonic\CustoHelper;

class Invoice extends Controller
{
    function _initialize(){
        date_default_timezone_set("Asia/Taipei");

        // 1.載入SDK程式
            vendor('EcpayInvoice');
            $oService = new \NetworkService();   // // 初始化網路服務物件。
            $oService->ServiceURL = Invoice_Url;
            $this->oService = $oService;
    }

    public static function instance(){
        return new Invoice();
    }

    /*設定發票字軌*/
    public function setAphabeticLetter(){
        $c_year = (Integer)date('Y') - 1911;
        $c_month = (Integer)date('m');
        $c_day = (Integer)date('d');
        if( in_array($c_month, [1,3,5,7,9,11]) ){
        }

        if($c_month>=2){
            $c_month = 1;
            $c_year += 1;
        }
        $InvoiceTerm = ceil( $c_month / 2 );

        // 查詢財政部配號結果
            // 1.設定請求網址
                $this->oService->ServiceURL .= 'B2CInvoice/GetGovInvoiceWordSetting'; /*B2C查詢財政部配號結果*/

            // 2.寫入資訊
                $arData = [
                    "MerchantID" => $this->oService->MerchantID,
                    "InvoiceYear" => $c_year,
                ];

            // 3.送出
                $arParameters = $this->oService->CreatePostData($arData);   /*產生post資料*/
                $szResult = $this->oService->ServerPost($arParameters);     /*傳遞參數至遠端*/

            // 4.將Data解密
                $Return_Info = $this->oService->DecodeReturnInfo($szResult);
                // dump($Return_Info);

        $InvoiceInfo = $Return_Info['InvoiceInfo'][1];
        // dump($InvoiceInfo);

        // 字軌與配號設定
            // 1.設定請求網址
                $this->oService->ServiceURL = explode('B2CInvoice', $this->oService->ServiceURL)[0];
                $this->oService->ServiceURL .= 'B2CInvoice/AddInvoiceWordSetting'; /*B2C字軌與配號設定*/

            // 2.寫入資訊
                $arData = [
                    "MerchantID" => $this->oService->MerchantID,
                    "InvoiceTerm" => $InvoiceTerm,
                    "InvoiceYear" => $c_year,
                    "InvType" => "07",
                    "InvoiceCategory" => "1",
                    "InvoiceHeader" => $InvoiceInfo['InvoiceHeader'],
                    "InvoiceStart" => (String)$InvoiceInfo['InvoiceStart'],
                    "InvoiceEnd" => str_pad(($InvoiceInfo['InvoiceStart'] + $InvoiceInfo['InvoiceEnd']), 8,"0",STR_PAD_LEFT),
                ];

            // 3.送出
                $arParameters = $this->oService->CreatePostData($arData);   /*產生post資料*/
                $szResult = $this->oService->ServerPost($arParameters);     /*傳遞參數至遠端*/

            // 4.將Data解密
                $Return_Info = $this->oService->DecodeReturnInfo($szResult);
                dump($Return_Info);
    }

    /*開立電子發票*/
    public function create_invoice($money_table="", $payment_id=""){
        if(self::$control_ecpay_invoice!=1){
            $Return_Info['RtnMsg'] = '未使用電子發票功能';
            return $Return_Info ;
        }

        try{
            $this->setAphabeticLetter();
        }
        catch (Exception $e){        
        }

        $Return_Info = [
            'RelateNumber'=> "",
            'InvoiceDate' => "",
            'InvoiceNo' => "",
            'RandomNumber' => "",
            'RtnCode' => 0,
            'RtnMsg'=> "",
            'CheckMacValue' => "",
        ];

        if(!$money_table || !$payment_id){
            $Return_Info['RtnMsg'] = '資料提供不完整';
            return $Return_Info ;
        }    
        $payment = D($money_table)->where("id='".$payment_id."'")->find();
        if(!$payment){
            $Return_Info['RtnMsg'] = '無此付款紀錄';
            return $Return_Info ;
        }
        if($payment['invoice']=='無' || $payment['invoice']=='免付'){
            $Return_Info['RtnMsg'] = '免付發票，無須開立發票';
            return $Return_Info ;
        }
        if((Integer)$payment['xqj']==0 || !$payment['xqj']){
            $Return_Info['RtnMsg'] = '需付金額為0，無須開立發票';
            return $Return_Info ;
        }

        $contract = D('crm_contract')->find($payment['caseid']);
        if(!$contract){ 
            $Return_Info['RtnMsg'] = '無對應合約';
            return $Return_Info ;
         }
        if($contract['get_or_pay']!=0){ 
            $Return_Info['RtnMsg'] = '非收款合約，無須開立發票';
            return $Return_Info ;
         }

        /*客戶資料*/
        $crm = CustoHelper::get_crm_rightdata(['id'=>$contract['cid']])['newbier'];
        
        /*取得出貨資料(商品)*/
        if($money_table=='crm_seomoney'){
            $shipment = [
                [
                    'content'   => $payment['qh'].'-'.$payment['count'],
                    'num'       => 1,
                    'count'     => 1,
                    'money'     => $payment['dqmoney']<=$payment['upmoney'] ? $payment['dqmoney'] : $payment['upmoney'],
                ],
            ];
        }else{
            $shipment = D('crm_shipment')->where("moneyid='".$payment['id']."'")->order('id desc')->select();
        }

        try{
            // 1.設定請求網址
                $this->oService->ServiceURL = explode('B2CInvoice', $this->oService->ServiceURL)[0];
                $this->oService->ServiceURL .= 'B2CInvoice/Issue'; /*B2C開立發票*/

            // 2.寫入發票相關資訊
                $ItemTaxType = 1; /*設定含稅*/
                $add_fax_ratio = 1 + TAX_RATE;
                
                /* 商品資訊 */
                    $items = [];
                    $remain_xdj = (Integer)$payment['xdj'] ? $payment['xdj'] : 0;
                    foreach ($shipment as $key => $value) {
                        /*計算商品總價、單價*/
                        if($remain_xdj>0){ /*還有剩餘消預收款*/
                            if($remain_xdj > $value['money']){ /*剩餘消預收款 大於 出貨金額*/
                                $ItemAmount = 0;
                                $remain_xdj = $remain_xdj - $ItemAmount;
                                $ItemRemark = "從預收款中扣除";
                            }
                            else{
                               $ItemAmount = ($value['money'] - $remain_xdj) * $add_fax_ratio;
                               $remain_xdj = 0;
                               $ItemRemark = "從預收款中扣除";
                            }
                        }
                        else{
                            $ItemAmount = $value['money'] * $add_fax_ratio;
                            $ItemRemark = "需付款";
                        }
                        $ItemAmount = round($ItemAmount);
                        $ItemPrice = round($ItemAmount / $value['num'], 2); /*單價四捨五入到小數點後第二位*/

                        array_push($items, [
                            'ItemName' => $value['name'].'-'.$value['content'], 
                            'ItemCount' => $value['num'], 
                            'ItemWord' => '批', 
                            'ItemPrice' => $ItemPrice, 
                            'ItemAmount' => $ItemAmount,
                            'ItemTaxType' => $ItemTaxType, 
                            'ItemRemark' => $ItemRemark,
                        ]);
                    }
                
                /* 其他發票資料 */
                    $SalesAmount = round($payment['xqj'] * $add_fax_ratio);
                    $RelateNumber = 'PHOTO'.date('YmdHis').str_pad(rand(0, 999), 3, "0", STR_PAD_LEFT).str_pad($payment['id'], 7, "0", STR_PAD_LEFT);

                    $crm_no = $crm['no'];   /*統編(三聯發票需要)*/
                    $Print = "1";           /*可列印發票*/
                    if($payment['invoice']=='三聯'){
                        if(!$crm['no']){
                            $Return_Info['RtnMsg'] = '無統編，無法開立三聯發票';
                            return $Return_Info ;
                        }
                    }
                    $arData = [
                        'MerchantID'            => $this->oService->MerchantID,
                        'RelateNumber'          => $RelateNumber,       /*自訂編號*/
                        'CustomerID'            => $crm['id'],          /*客戶編號*/
                        'CustomerIdentifier'    => $crm_no,             /*統編*/
                        'CustomerName'          => $crm['name'],        /*客戶名稱，當列印註記=1(列印)時，為必填*/
                        'CustomerAddr'          => $crm['addr'],        /*客戶地址，當列印註記=1(列印)時，為必填*/
                        'CustomerPhone'         => $crm['comphone'],    /*客戶手機號碼，當客戶電子信箱為空字串時，為必填*/
                        'CustomerEmail'         => $crm['commail'],     /*客戶電子信箱，當客戶手機號碼為空字串時，為必填*/
                        'ClearanceMark'         => '',                  /*通關方式，當課稅類別[TaxType]=2(零稅率)時，為必填*/
                        'Print'                 => $Print,              /*列印註記，0：不列印(捐贈註記=1(捐贈)時、載具類別有值時)、1：要列印(統一編號有值時)*/
                        'Donation'              => '0',                 /*捐贈註記，0：不捐贈(統一編號有值時、載具類別有值時)、1：要捐贈*/
                        'LoveCode'              => '',                  /*捐贈碼，當捐贈註記=1時，為必填*/
                        'CarrierType'           => '',                  /*載具類別*/
                        'CarrierNum'            => '',                  /*載具編號*/
                        'TaxType'               => $ItemTaxType,        /*課稅類別*/
                        'SalesAmount'           => $SalesAmount,        /*發票總金額(含稅)*/
                        'InvoiceRemark'         => 'eip7.2',            /*發票備註*/
                        'Items'                 => $items,              /*商品*/
                        'InvType'               => '07',                /*字軌類別*/
                        'vat'                   => '',                  /*商品單價是否含稅*/
                    ];
                    // dump($arData);           

            // 3.送出
                $arParameters = $this->oService->CreatePostData($arData);   /*產生post資料*/
                $szResult = $this->oService->ServerPost($arParameters);     /*傳遞參數至遠端*/

            // 4.將Data解密
                $Return_Info = $this->oService->DecodeReturnInfo($szResult);
                // dump($Return_Info);

            // 5.更新資料庫
                if($Return_Info['RtnCode']){
                    $ticket_data = [
                        'ticket'        => $Return_Info['InvoiceNo'],       /*發票號碼*/
                        'ticket_rand'   => $Return_Info['RandomNumber'],    /*發票隨機碼*/
                        'ticketdate'    => $Return_Info['InvoiceDate'] ? strtotime($Return_Info['InvoiceDate']) : "",   /*發票日期*/
                    ];
                    // dump($ticket_data);
                    D($money_table)->where("id='".$payment['id']."'")->data($ticket_data)->save();
                }
        }
        catch (Exception $e){
            // 例外錯誤處理。
            $sMsg = $e->getMessage();
            $Return_Info = [
                'RelateNumber'=> "",
                'InvoiceDate' => "",
                'InvoiceNo' => "",
                'RandomNumber' => "",
                'RtnCode' => 0,
                'RtnMsg'=> $sMsg,
                'CheckMacValue' => "",
            ];
        }

        return $Return_Info;
    }

    /*作廢電子發票*/
    public function delete_invoice($money_table="", $payment_id=""){
        $Return_Info = [
            'RtnCode' => 0,
            'RtnMsg'=> "",
            'InvoiceNo' => "",
        ];

        if(self::$control_ecpay_invoice!=1){
            $Return_Info['RtnMsg'] = '未使用電子發票功能';
            return $Return_Info ;
        }
        if(!$money_table || !$payment_id){
            $Return_Info['RtnMsg'] = '資料提供不完整';
            return $Return_Info ;
        }
        $payment = D($money_table)->where("id='".$payment_id."'")->find();
        if(!$payment){
            $Return_Info['RtnMsg'] = '無此付款紀錄';
            return $Return_Info ;
        }

        // 1.設定請求網址
            $this->oService->ServiceURL .= 'B2CInvoice/Invalid'; /*B2C作廢發票*/

        // 2.寫入發票相關資訊
            $arData = array(
                'MerchantID' => $this->oService->MerchantID,
                'InvoiceNo'=> $payment['ticket'], /*發票號碼*/
                'InvoiceDate'=> date('Y-m-d', $payment['ticketdate']), /*發票開立日期*/
                'Reason'     => '發票作廢'
            );
        
        // 3.送出
            $arParameters = $this->oService->CreatePostData($arData);   /*產生post資料*/
            $szResult = $this->oService->ServerPost($arParameters);     /*傳遞參數至遠端*/

        // 4.將Data解密
            $Return_Info = $this->oService->DecodeReturnInfo($szResult);
            // dump($Return_Info);

        // 5.更新資料庫
            $ticket_data = [
                'ticket'        => "",  /*發票號碼*/
                'ticket_rand'   => "",  /*發票隨機碼*/
                'ticketdate'    => "",  /*發票日期*/
            ];
            D($money_table)->where("id='".$payment['id']."'")->data($ticket_data)->save();

        return $Return_Info;
    }


    /*列印發票(取得發票樣章)*/
    public function print_invoice($money_table="", $payment_id=""){
        $Return_Info = [
            'RtnCode' => 0,
            'RtnMsg'=> "",
            'InvoiceHtml' => "",
        ];

        if(self::$control_ecpay_invoice!=1){
            $Return_Info['RtnMsg'] = '未使用電子發票功能';
            return $Return_Info ;
        }
        if(!$money_table || !$payment_id){
            $Return_Info['RtnMsg'] = '資料提供不完整';
            return $Return_Info ;
        }
        $payment = D($money_table)->where("id='".$payment_id."'")->find();
        if(!$payment){
            $Return_Info['RtnMsg'] = '無此付款紀錄';
            return $Return_Info ;
        }

        // 1.設定請求網址
            $this->oService->ServiceURL .= 'B2CInvoice/InvoicePrint'; /*B2C列印發票*/

        // 2.寫入發票相關資訊
            $arData = array(
                'MerchantID' => $this->oService->MerchantID,
                'InvoiceNo'=> $payment['ticket'], /*發票號碼*/
                'InvoiceDate'=> date('Y-m-d', $payment['ticketdate']), /*發票開立日期*/
            );
        
        // 3.送出
            $arParameters = $this->oService->CreatePostData($arData);   /*產生post資料*/
            $szResult = $this->oService->ServerPost($arParameters);     /*傳遞參數至遠端*/

        // 4.將Data解密
            $Return_Info = $this->oService->DecodeReturnInfo($szResult);
            // dump($Return_Info);exit;

        return $Return_Info;
    }
}
?>