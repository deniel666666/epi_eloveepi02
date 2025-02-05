# EIP系統
傳訊光內控系統/快手特助/erp2000/eip

以ThinkPHP 3.多版開發的內控系統，請搭配php7.0版使用。<br>
需建立資料庫：eip，相關語法皆放置於sql資料夾內，請按名稱排序<b>由小到大</b>逐個匯入<br>
<b>如需使用SEO功能，則須建立第二個資料庫</b>(一般來說用不到)：eip_seo_key_rank，相關語法也放置於sql資料夾內<br>
<br>
另外，使用此程式前還請記得修改以下內容：<br>
<ol>
    <li>
        <b>安裝PHP套件</b>：<br />
        開啟CMD介面，cd到此專案的跟目錄執行安裝語法：composer install<br />
        完成後應該會於 vendor 資料夾中下載相關套件檔並生成 autoload.php<br />
    </li>
    <li>
        index.php：<br />
        如欲關閉報錯畫面，請將 APP_DEBUG 、 NO_CACHE_RUNTIME 設定為 false<br />
    </li>
    <li>
        customer.php：<br />
        如欲關閉報錯畫面，請將 APP_DEBUG 、 NO_CACHE_RUNTIME 設定為 false<br />
    </li>
    <li>
        example.Auto.php：<br />
        請複製一個此檔案，並修刪除檔名開頭的example.(自動執行程式，本地測試請忽略)<br />
        <ul>
            <li>請修改自動化處理程式的主網址參數($main_url)，其中 eip.test 請改為架設購物車的主網址。</li>
            <li>請根據主機設定 https 或 http，若有強制跳轉https則請務必@設為https，否則無法執行。</li>
            <li>
                內部包含以下自動程式，可修改時間參數，控制觸發的時間：<br>
                <ol>
                    <li>自動出貨，每日執行一次(autoTime_create_sale)，預設09:00:00，意思是「每日9點」</li>
                    <li>未付款款提醒，每日執行一次(autoTime_not_pay_remind)，預設10:00:00，意思是「每日10點」</li>
                    <li>自動給特休，每日執行一次(autoTime_set_special_rest)，預設02:00:00，意思是「每日凌晨2點」</li>
                </ol>
                以上程式若不需執行，可直接註解掉<br>
            </li>
            <li>另外也請記得至cpanel>Cron Jobs設定排程工作，設定語法可參考Auto.php內註釋。</li>
        </ul>
    </li>
    <li>
        example.db.config.php：<br />
        請複製一個此檔案，並修刪除檔名開頭的example.<br />
        <ul>
            <li>設定系統信標題前綴、寄件者： MAIL_FROM_TITLE 、 MAIL_FROM_ADDRESS<br /></li>
            <li>設定稅率： TAX_RATE (預設 0.05 )<br /></li>
            <li>
                修改綠界電子發票設定：
                <ol>
                    <li>Invoice_Url => 環境設定，測試環境：https://einvoice-stage.ecpay.com.tw/， 正式環境：https://einvoice.ecpay.com.tw/<br /></li>
                    <li>MerchantID => 綠界之特電代號，當環境為測試時，請使用測試值<br /></li>
                    <li>HashKey => 綠界串接之HashKey，當環境為測試時，請使用測試值<br /></li>
                    <li>HashIV => 綠界串接之HashIV，當環境為測試時，請使用測試值<br /></li>
                    <li>相關修改請以註解&取消註解方式處理<br /></li>
                </ol>
            </li>
            <li>請修改資料庫連線設定 DB_NAME、DB_USER、DB_PWD<br /></li>
            <li>請修改 DB_SEO_RANK 連線設定(格式:使用者:密碼@localhost:3306/資料庫)<br /></li>
            <li>請修改 推播通知設定<br /></li>
            <li>請修改 GOOGLE儲存空間<br /></li>
        </ul>
    </li>
    <li>
        google-account-credentials.json：<br />
        請複製一個此檔案，並修刪除檔名開頭的example.<br />
        <ul>
            <li>因上傳檔案之附件儲存於 Google storage，相關帳號可參閱 EIP 系統 > 人事行政 > 重要文件 > 各式帳號+平台 > google雲儲存空間<br /></li>
            <li>本地環境請使用 eiperp2000com 桶(bucket)來作測試，新增金鑰後匯出，將 json 檔內容貼至此檔案<br /></li>
        </ul>
    </li>
    <li>
        匯入資料庫後修改系統設定(相關語法建議製作成XXX_customized.sql，儲存於sql/eip中)：<br />
        <ol>
            <li>
                資料表 eip_company ，id=1 ，系統商資料：<br />
                top_id      => 自己公司(傳訊光自用請設：18426)<br />
                top_teamid  => 最高權限組別(傳訊光自用請設：23)<br />
                top_adminid => 最高權限帳號(傳訊光自用請設：32)<br />
                其他參數請參照 傳訊光聯絡資訊 設定(顯示於EIP使用者操作的頁面)<br />
            </li>
            <li>
                資料表 eip_company ，id=2 ，客戶資料(顯示於EIP使用者對其客戶展示的頁面)：<br />
                name        => 公司名稱<br />
                en_name     => 公司名稱_英文<br />
                tel         => 公司電話<br />
                fax         => 公司傳真<br />
                addr        => 公司地址<br />
                addr_link   => 公司地址連結<br />
            </li>
            <li>
                資料表 system_parameter：<br />
                a. id=1 CRM欄位名稱對應語言版： 目前固定為1，因此需確保有此語言版文件(lang/1/system_parameter.json)<br />
                b. id=11 公司位置緯、經度、距離公尺，請改為客戶的設定 (傳訊光自用請設：25.02500316260215, 121.55338708314407, 200)<br />
                c. id=12 公司正常上下班時間，請改為客戶的設定 (傳訊光自用請設：09:00:00, 18:15:00)<br />
                d. 其他id 系統功能： 功能名稱及設定方式請參考note說明<br />
            </li>
            <li>
                資料表 powercat ，設定開關功能：<br />
                父層的status如果設為0(關閉)，那子層也會關閉。<br />
                如需使用在行銷功能，再行銷系統(id=104)的link(外連網址)需修改成對應的再行銷系統網址(程式在eip_email中)。
            </li>
            <li>
                資料表 km_types ，設定開關功能：<br />
                此區為預設的文章功能，如不使用，status設為0即可。<br />
            </li>
        </ol>
    </li>
    <li>
        lang/1/system_parameter.json：<br />
        請複製一個此檔案，並修刪除檔名開頭的example.<br />
        key值為欄位、value值為顯示名稱，需注意不可刪除key值，部分value值設定為空會有隱藏效果(做用欄位請參考 lang/1/system_parameter.txt)<br />
    </li>
</ol>
