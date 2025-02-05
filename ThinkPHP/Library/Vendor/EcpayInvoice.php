<?php
/*電子發票SDK*/

/*服務類別************************************************************************************/
    /*呼叫網路服務的類別。*/
    class NetworkService {

        public $PlatformID = '';    /*平台id*/
        public $MerchantID = '';    /*特店代號*/
        public $HashKey = '';     /*HashKey*/
        public $HashIV = '';      /*HashIV*/
        public $szRqHeader = [];
        public $ServiceURL = 'ServiceURL'; /*網路服務類別呼叫的位址。*/
        
        public $oCrypter = []; /*加解密物件*/

        /*網路服務類別的建構式。*/
        function __construct() {
            $this->NetworkService();

            // 寫入基本介接參數
            $this->PlatformID = PlatformID;
            $this->MerchantID = MerchantID;
            $this->HashKey = HashKey;
            $this->HashIV = HashIV; 
            $this->szRqHeader = array(
                'Timestamp' => time(),
                'RqID' => guid(),
                'Revision' => '3.0.0',
            );

            $this->oCrypter = new \AESCrypter($this->HashKey, $this->HashIV);
        }
        
        /* 網路服務類別的實體。*/
        function NetworkService() {
        }

        /*提供伺服器端呼叫遠端伺服器 Web API 的方法。*/
        function ServerPost($parameters) {
            $ch = curl_init();
            
            curl_setopt($ch, CURLOPT_URL, $this->ServiceURL);
            curl_setopt($ch, CURLOPT_HEADER, FALSE);
            //curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($ch, CURLOPT_POST, TRUE);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $parameters);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json; charset=utf-8', 'Content-Length: ' . strlen($parameters)));
            $rs = curl_exec($ch);

            curl_close($ch);

            // 顯示接收的參數
            // echo "印出回傳結果<br>";
            // echo $rs,"<br>","<br>";
            return $rs;
        }

        /*創建 post data*/
        function CreatePostData($arData){
            $szData = json_encode($arData);
            //印出Data參數
            // echo "印出Data參數<br>";
            // echo $szData,"<br>","<br>";

            $szData = urlencode($szData);
            $szData = $this->oCrypter->Encrypt($szData);
            //印出Data加密結果
            // echo "印出Data加密結果<br>";
            // echo $szData,"<br>","<br>";

            $arParameters = array(
                'PlatformID' => $this->PlatformID,
                'MerchantID' => $this->MerchantID,
                'RqHeader' => $this->szRqHeader,
                'Data' => $szData
            );
            $arParameters = json_encode($arParameters);
            //印出POST參數
            // echo "印出POST參數<br>";
            // echo $arParameters,"<br>","<br>";

            return $arParameters;
        }

        /*將Data解密*/
        function DecodeReturnInfo($szResult){
            $Return_Info = [
                'RelateNumber'=> "",
                'InvoiceDate' => "",
                'InvoiceNo' => "",
                'RandomNumber' => "",
                'RtnCode' => 0,
                'RtnMsg'=> "",
                'CheckMacValue' => "",
            ];

            //判斷回傳是否為Json格式
            $ResultisJson=isJson($szResult);
            if($ResultisJson==TRUE){
                $DataisNull=json_decode($szResult,true);
                if(isset($DataisNull["Data"])){
                    if($DataisNull["Data"]!==''){
                        //將Data解密
                        $DataDec = $this->oCrypter->Decrypt($DataisNull["Data"]);
                        $DataDec1=json_decode($DataDec,true);
                        if(isset($DataDec1["RtnCode"])){ 
                            $Return_Info = $DataDec1;

                            //印出Data解密結果
                            // if($DataDec1["RtnCode"]===1){
                            //     echo "成功<br>";
                            //     echo $DataDec,"<br>";   
                            // }
                            // else{
                            //     echo "失敗<br>";
                            //     echo $DataDec,"<br>";
                            // }
                        }
                        else{
                            $Return_Info['RtnMsg'] = "Data未含有RtnCode";
                        }   
                    }
                    else{
                        $Return_Info['RtnMsg'] = "Data回傳空值";
                    }
                }
                else{
                    $Return_Info['RtnMsg'] = "回傳沒有Data";
                }
            }
            else {
                $Return_Info['RtnMsg'] = "回傳格錯誤，非Json格式";
            }

            return $Return_Info;
        }
    }

    /*AES 加解密服務的類別。*/
    class AesCrypter {
        private $Key = '';  /*建構時將被賦值*/
        private $IV = '';   /*建構時將被賦值*/
        
        /*AES 加解密服務類別的建構式。 */
        function __construct($key, $iv) {
            $this->AesCrypter($key, $iv);
        }

        /*AES 加解密服務類別的實體。*/
        function AesCrypter($key, $iv) {
            $this->Key = $key;
            $this->IV = $iv;
        }

        /*加密服務的方法。*/
        function Encrypt($data)
        {
            $szData = openssl_encrypt($data, 'AES-128-CBC', $this->Key, OPENSSL_RAW_DATA, $this->IV);
            $szData = base64_encode($szData);
            return $szData;
        }
        
        /*解密服務的方法。*/
        function Decrypt($data)
        {
            $szValue = openssl_decrypt(base64_decode($data), 'AES-128-CBC', $this->Key, OPENSSL_RAW_DATA, $this->IV);
            $szValue=urldecode($szValue);
            return $szValue;
        }
    }


/*額外fuction*********************************************************************************/
    /*產生GUID*/
    function guid(){
        mt_srand((double)microtime()*10000);//optional for php 4.2.0 and up.
        $charid = strtoupper(md5(uniqid(rand(), true)));
        $uuid = substr($charid, 0, 8)
            .substr($charid, 8, 4)
            .substr($charid,12, 4)
            .substr($charid,16, 4)
            .substr($charid,20,12);
        return $uuid;
    }

    /*判斷Json*/
    function isJson($data = '', $assoc = false) {
        $data = json_decode($data, $assoc);
        if ($data && (is_object($data)) || (is_array($data) && !empty($data))) {
            return $data;
        }
        return false;
    }
?>