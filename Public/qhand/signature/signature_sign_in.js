
// Vue.toasted.show(文字內容,{
//   duration:1500, 停留時間 1000=1秒
//   className:['toasted-primary','bg-danger'], 自訂class
// });
var aeModel_empty = { 
    id: 0, fields_set_id:'0', prod_id:'0', title: "", type: "text", 
    required: 0, special: 0, limit: "", discription: "", 
    options:[], order_id: 0, online: 1, staff_only: 1,
}

var signature_sign_in_data = {
    adminId: adminId,

    id: "",
    sn: "",
    invoice: "",
    c_name:"",
    allmoney:"",
    content:"",

    complete: "0",
    imgs: [],
    signatures: [],
    edit_view:{},

    /*eip功能*/
    questions:[],
    edit_view_question:{},
    data_types:{
        text:"單行文字",
        textarea: "多行文字",
        radio: "單選題",
        // radio_box: "單選題_開視窗",
        checkbox: "多選題",
        // checkbox_time: "時間選",
        // checkbox_box: "多選題_開視窗",
        select: "下拉選單",
        number: "數字題",
        file: "檔案上傳",
        img: "圖片上傳",
        date: "日期",
    },
    types_file: [],
    types_need_option: [],
    types_need_limit: [],
};
var signature_sign_inVM = new Vue({
    el: '#signature_sign_in', 
    data: signature_sign_in_data,
    computed: {
        unsign_num: function(){
            count = 0;
            for (var i = 0; i < this.signatures.length; i++) {
                if(typeof(this.signatures[i].sign)=='undefined'){
                    count += 1;
                    continue;
                }
                if(this.signatures[i].sign==""){
                    count += 1;
                    continue;
                }
            }
            return count;
        },
    },
    updated: function () {
      this.$nextTick(function () {
        // Code that will run only after the
        // entire view has been re-rendered
      })
    },
    methods: {
        get_data: function(id){
            self = this;
            $.ajax({
                method:'post',
                dataType: 'json',
                data:{id: id},
                url:"/customer.php/Contract/aj_contract_content.html",
                success:function(res){
                    self.types_file = res['types_file'];
                    self.types_need_limit = res['types_need_limit'];
                    self.types_need_option = res['types_need_option'];

                    self.id = res['id'];
                    self.sn = res['sn'];
                    self.invoice = res['invoice'];
                    self.c_name = res['c_name'];
                    self.allmoney = res['allmoney'];
                    self.content = res['content'];
                    content = res['content'] ? res['content'] : '';
                    // editor.html(content);

                    self.complete = res['flag'];

                    /*還原比例成固定px*/
                    self.imgs = res['imgs'];
                    setTimeout(function(){
                        imgs = $('.img_content img');
                        max_img_width = 0;
                        for (var i = 0; i < imgs.length; i++) {
                            if(max_img_width<$(imgs[i]).width()){
                                max_img_width = $(imgs[i]).width();
                            }
                        }
                        for (var i = 0; i < imgs.length; i++) {
                            $(imgs[i]).css('width', max_img_width+'px');
                        }

                        var signatures = res['signatures'] ? res['signatures'] : [];
                        all_w = $('.img_content img').width();
                        all_h = $('.img_content').height();
                        for (var i = 0; i < signatures.length; i++) {
                            signatures[i].w = signatures[i].w * all_w;
                            signatures[i].h = signatures[i].h * all_h;
                            signatures[i].p_x = signatures[i].p_x * all_w;
                            signatures[i].p_y = signatures[i].p_y * all_h;
                            if(typeof(signatures[i].sign)=='undefined'){
                                signatures[i].sign = "";
                            }
                        }
                        self.signatures = signatures;

                        /*eip功能*/
                        var questions = res['questions'] ? res['questions'] : [];
                        all_w = $('.img_content img').width();
                        all_h = $('.img_content').height();
                        for (var i = 0; i < questions.length; i++) {
                            questions[i].w = questions[i].w * all_w;
                            questions[i].h = questions[i].h * all_h;
                            questions[i].p_x = questions[i].p_x * all_w;
                            questions[i].p_y = questions[i].p_y * all_h;
                        }
                        self.questions = questions;
                    },1000);
                }
            });
        },
        
        reset_edit_view: function(){
            this.edit_view = {
                i: "",
                sign: "",
            };
            sigPad.clearCanvas();
        },
        eidt_signature: function(index){
            this.edit_view.i = index;
            this.edit_view.sign = this.signatures[index].sign;

            /*添加畫板*/
            var win_w = $(window).width();
            var target_sign = $('#signature_click_' + index);
            if(is_computer()){
                win_w += 17; /*電腦會有轉軸，寬度需外加*/
            }
            if(win_w < 576){
                can_w = win_w - (0.5 + 1) * 2 * 16
            }else{
                can_w = 498  - (1) * 2 * 16;
            }
            var can_h = can_w / target_sign.width() * target_sign.height();
            $('canvas').attr('width', can_w);
            $('canvas').attr('height', can_h);

            // if(this.complete=='0'){
                document.getElementById('signatureEditView_btn').click();
            // }
        },
        save_signature: function(){
            if(this.complete!='0'){ 
                Vue.toasted.show("不可修改", { duration: 1500, className: ["toasted-primary", "bg-danger"] });
                return;
            }
            this.signatures[this.edit_view.i].sign = sigPad.getSignatureImage();

            $('#signatureEditView .close').click();
            this.reset_edit_view();
        },

        submit: function(){
            if(confirm('送出後就不可再更改，確定送出嗎？')){
                this.do_submit('send');
            }
        },
        submit_setting: function(){
            this.do_submit('setting');
        },
        do_submit: async function(submit_type='send'){
            self = this;
            var data = { 
                submit_type: submit_type,

                /*EIP功能*/
                questions: self.questions,
                // content: editor.html(),
                
                id: self.id,
                signatures: self.signatures,
            };           
            /*送出資料*/
            $.ajax({
                method:'post',
                dataType: 'json',
                url:"/customer.php/Contract/update.html",
                data: data,
                success:function(res){
                    bg = res.status == 1 ? "bg-success" : "bg-danger";
                    Vue.toasted.show(res.info, { duration: 1500, className: ["toasted-primary", bg] });
                    if(res.status == 1 && submit_type=='send'){
                        // self.content = editor.html();
                        self.get_data(contract_id);
                    }
                }
            });
        },

        print: async function(){
            self = this;

            var items = $('.question_red, .signature_red');
            for (let index = 0; index < items.length; index++) {
                const element = items[index];
                $(element).addClass('border-0 text-dark');
            }

            $('#body_block').show();
            all_w = $('.img_content img').width();
            $('#print_area').html($('#canvas_img').html());
            $('#print_area').css('width', all_w+'px');
            const imgs = $('#print_area').find('img');
            for (var i = 0; i < imgs.length; i++) {
                const img = imgs[i];
                src = $(img).attr('src');
                if(!src){ $(img).remove(); continue; }
                if(src.indexOf('googleapis')!=-1){
                    result = await $.ajax({
                        method:'post',
                        dataType: 'json',
                        url:"/customer.php/Contract/get_base64_data.html",
                        data: {url: src},
                    });
                    if(result.status && result.info){ 
                        $(img).attr('src', result.info);
                        await new Promise(resolve => setTimeout(resolve, 1000));
                    }else{
                        $(img).remove();
                    }
                }
            }

            html2canvas(document.querySelector("#print_area")).then(canvas => {
                document.body.appendChild(canvas);
                var dataURL = canvas.toDataURL();
                // console.log(dataURL);

                var img = document.createElement("img");
                img.style="width: 100%; max-width: fit-content;";
                img.src = dataURL;
                $('#canvas_img').html(img);
                $('.sign_in_area').addClass('d-none');
                $('#signature_sign_in').off('contextmenu');
                // console.log(img)

                var a = document.createElement("a");
                a.href = dataURL;
                a.download = self.sn + '_' + self.c_name;
                a.click();
                a.remove();

                setTimeout(()=>{
                    canvas.remove();
                    $('#print_area').html('');
                    $('#body_block').hide();
                }, 1000)
            });

            for (let index = 0; index < items.length; index++) {
                const element = items[index];
                $(element).removeClass('border-0 text-dark');
            }
        },

        /*EIP功能*/
        reset_edit_view_question: function(){
            editor_question.html("");
            editor_question.readonly(true);
            edit_view_textarea.html("");

            var init_data = JSON.parse(JSON.stringify(aeModel_empty));
            this.edit_view_question = init_data;
        },
        eidt_question: function(index){
            const data = JSON.parse(JSON.stringify(this.questions[index]));
            data.i = index;
            this.edit_view_question = data;

            if(this.edit_view_question.type=='textarea'){
                edit_view_textarea.html(this.edit_view_question.ans);
            }else{
                edit_view_textarea.html("");
            }
            if( this.complete!='0' || 
                (this.edit_view_question.staff_only=='1' && !this.adminId)
            ){
                edit_view_textarea.readonly(true);
            }else{
                edit_view_textarea.readonly(false);
            }
            
            editor_question.html(this.edit_view_question.discription);

            // if(this.complete=='0'){
                document.getElementById('signatureEditView_question_btn').click();
            // }
        },
        save_question: function(){
            if( this.complete!='0' || 
                (this.edit_view_question.staff_only=='1' && !this.adminId)
            ){
                Vue.toasted.show("不可修改", { duration: 1500, className: ["toasted-primary", "bg-danger"] });
                return;
            }

            if(this.edit_view_question.type=='textarea'){
                this.edit_view_question.ans = edit_view_textarea.html();
            }
            const data = JSON.parse(JSON.stringify(this.edit_view_question));
            this.questions[this.edit_view_question.i] = data;

            $('#signatureEditView_question .close').click();
            this.reset_edit_view_question();
        },
        previewFiles: function(e){
            self = this;
            files = e.currentTarget.files;
            if(files){
                file = files[0];
                // console.log(file);
                self.edit_view_question['ans']['file_name'] = file.name;
                
                src = URL.createObjectURL(file);
                // console.log(src);
                self.edit_view_question['ans']['blob_link'] = src;

                const reader = new FileReader();
                reader.readAsDataURL(file);
                reader.onload = () => {
                    // console.log(reader.result);
                    self.edit_view_question['ans']['data'] = reader.result;
                };
            }else{
                delete self.edit_view_question['ans']['blob_link'];
                self.edit_view_question['ans']['file_name'] = '';
                self.edit_view_question['ans']['data'] = '';
            }
        },
        cancel_file: function(){
            $('input[type="file"]').val('');
            delete self.edit_view_question['ans']['blob_link'];
            self.edit_view_question['ans']['file_name'] = '';
            self.edit_view_question['ans']['data'] = '';
        },

        select_file: function(index){
            self = this;
            var add_img_input = $('#input_' + index);
            const [file] = add_img_input[0].files
            self.questions[index].file_name = file.name;
            if (file) {
                var reader = new FileReader();
                reader.onload = function (data) {
                    self.questions[index].ans = data.target.result;
                    // console.log(data.target.result);
                };
                reader.readAsDataURL(file);
            }else{
                Vue.toasted.show("請選擇檔案", { duration: 1500, className: ["toasted-primary", "bg-danger"] });
            }
        },
        has_question: function(i_s, i_e){
            var has = false;
            if(this.questions){
                for (var i = 0; i < this.questions.slice(i_s, i_e).length; i++) {
                    if(this.questions[i_s + i].online == '1' && this.questions[i_s + i].name != ''){
                        has = true;
                        break;
                    }
                }
            }
            return has;
        },
    },
});

/*判斷是電腦還是手機*/
function is_computer() {
    var sUserAgent= navigator.userAgent.toLowerCase();
    var bIsIpad= sUserAgent.match(/ipad/i) == "ipad";
    var bIsIphoneOs= sUserAgent.match(/iphone os/i) == "iphone os";
    var bIsMidp= sUserAgent.match(/midp/i) == "midp";
    var bIsUc7= sUserAgent.match(/rv:1.2.3.4/i) == "rv:1.2.3.4";
    var bIsUc= sUserAgent.match(/ucweb/i) == "ucweb";
    var bIsAndroid= sUserAgent.match(/android/i) == "android";
    var bIsCE= sUserAgent.match(/windows ce/i) == "windows ce";
    var bIsWM= sUserAgent.match(/windows mobile/i) == "windows mobile";
    if (bIsIpad || bIsIphoneOs || bIsMidp || bIsUc7 || bIsUc || bIsAndroid || bIsCE || bIsWM) {
       return false;
    } else {
       return true;
    }
}

var editor;
editor = KindEditor.create('#note', {
    afterBlur: function(){this.sync();},
    langType : 'zh_TW',
    items:[
        'source', '|',
        'undo', 'redo', '|', 'preview', 'cut', 'copy', 'paste','plainpaste', 'wordpaste', '|',
        'justifyleft', 'justifycenter', 'justifyright','justifyfull', 'link', 'unlink', 'selectall', '|',
        'fontname', 'fontsize', '|',
        'forecolor','hilitecolor', 'bold', 'italic', 'underline', 'strikethrough', 'lineheight', 'removeformat', '|',
        'hr',  '|',
        'fullscreen', '|',
        'about'
    ],
    width:'100%',
    height:'300%',
    resizeType:0
});
/*初始化編輯器*/
var editor_question = null;
var edit_view_textarea = null;
KindEditor.ready(function(K) {
    editor_question = K.create('#editor_question', {
        langType : 'zh_TW',
        items:['source', '|', 'table', 'hr','|','emoticons','|','forecolor','bold', 'italic', 'underline','link', 'unlink',],
        width:'100%',
        height:'200px',
        resizeType:0
    });

    edit_view_textarea = K.create('#edit_view_textarea', {
        langType : 'zh_TW',
        items:['source', '|', 'table', 'hr','|','emoticons','|','forecolor','bold', 'italic', 'underline','link', 'unlink',],
        width:'100%',
        height:'200px',
        resizeType:0
    });
    
    signature_sign_inVM.get_data(contract_id);
    signature_sign_inVM.reset_edit_view();
    signature_sign_inVM.reset_edit_view_question();
});