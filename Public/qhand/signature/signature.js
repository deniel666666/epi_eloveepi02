
// Vue.toasted.show(文字內容,{
//   duration:1500, 停留時間 1000=1秒
//   className:['toasted-primary','bg-danger'], 自訂class
// });
var aeModel_empty = { 
    id: 0, fields_set_id:'0', prod_id:'0', title: "", type: "text", 
    required: 0, special: 0, limit: "", discription: "", 
    options:[], order_id: 0, online: 1, staff_only: 0,
}

var ori_x = 0; // 紀錄原始x位置
var ori_y = 0; // 紀錄原始y位置
var timeOut = null; // 用以儲存timeOute物件
var hasMove = false; // 紀錄滑鼠是否有拖移
var signature_data = {
    imgs: [],
    imgs_show: [],
    signatures: [],
    edit_view:{
        method: 'add',
        i: "",
        w: 0,
        h: 0,
        p_x: 0,
        p_y: 0,
        sign: "",
    },

    /*eip功能*/
    cat_id: 0,
    questions:[
    ],
    edit_view_question:{
        method: 'add',
        i: "",
        w: 0,
        h: 0,
        p_x: 0,
        p_y: 0,
    },
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
    types_need_option: [],
    types_need_limit: [],
};
var signatureVM = new Vue({
    el: '#signature', 
    data: signature_data,
    computed: {
    },
    updated: function () {
    this.$nextTick(function () {
        // Code that will run only after the
        // entire view has been re-rendered
    })
    },
    methods: {
        get_data: function(cat_id){
            self = this;
            self.cat_id = cat_id;
            $.ajax({
                dataType: 'json',
                method:'post',
                data:{cat_id: cat_id},
                url:"/index.php/Crmcumcat/aj_cate_content.html",
                success:function(res){
                    self.types_need_limit = res['types_need_limit'];
                    self.types_need_option = res['types_need_option'];

                    self.name = res.name;

                    content = res['content'] ? res['content'] : '';
                    editor.html(content);

                    /*還原比例成固定px*/
                    self.imgs = res['imgs'];
                    self.imgs_show = res['imgs_show'];
                    setTimeout(function(){
                        all_w = $('.overflow_hidden').width();
                        all_h = $('.overflow_hidden').height();

                        var signatures = res['signatures'];
                        for (var i = 0; i < signatures.length; i++) {
                            signatures[i].w = signatures[i].w * all_w;
                            signatures[i].h = signatures[i].h * all_h;
                            signatures[i].p_x = signatures[i].p_x * all_w;
                            signatures[i].p_y = signatures[i].p_y * all_h;
                        }
                        self.signatures = signatures;

                        /*eip功能*/
                        var questions = res['questions'] ? res['questions'] : [];
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
        
        add_img: function(){
            self = this;
            var add_img_input = $('#add_img');
            const [file] = add_img_input[0].files
            if (file) {
                var reader = new FileReader();
                reader.onload = function (data) {
                    self.imgs.push(data.target.result);
                    self.imgs_show.push(data.target.result);
                    // console.log(data.target.result);
                    add_img_input.val('');
                };
                reader.readAsDataURL(file);
            }else{
                Vue.toasted.show("請選擇圖片", { duration: 1500, className: ["toasted-primary", "bg-danger"] });
            }
        },
        cancel_img: function(index){
            this.imgs.splice(index, 1);
            this.imgs_show.splice(index, 1);
        },
        cancel_imgs: function(){
            this.imgs = [];
            this.imgs_show = [];
        },
        
        reset_edit_view: function(){
            this.edit_view = {
                method: 'add',
                i: "",
                w: 100,
                h: 40,
                p_x: 0,
                p_y: document.getElementById("view_area_content").scrollTop,
                sign: "",
            };
        },
        eidt_signature: function(index){
            this.edit_view = Object.assign({}, this.signatures[index]);
            this.edit_view.method = 'edit';
            this.edit_view.i = index;
        },
        save_signature: function(){
            if(this.imgs.length < 1){
                Vue.toasted.show("請先上傳圖片", { duration: 1500, className: ["toasted-primary", "bg-danger"] });
                $('#signatureEditView .close').click();
                this.reset_edit_view();
                return;
            }

            index = this.edit_view.i
            if(index===""){ /*新增*/
                this.signatures.push({
                    w: this.edit_view.w,
                    h: this.edit_view.h,
                    p_x: this.edit_view.p_x,
                    p_y: this.edit_view.p_y,
                    required: this.edit_view.required,
                });
            }else{ /*編輯*/
                this.signatures[index] = this.edit_view;
            }
            
            $('#signatureEditView .close').click();
            this.reset_edit_view();
        },
        cancel_signature: function(index){
            this.signatures.splice(index, 1);
            $('#signatureEditView .close').click();
            this.reset_edit_view();
        },
        cancel_signatures: function(){
            this.signatures = [];
        },
        move_signature: function (index, signature) {
            self = this;
            /*滑鼠放掉時清空html對滑鼠移動的事件*/
            $("html").one("mouseup", function (e) {
                if (hasMove) {
                    Vue.toasted.show("拖移結束", { duration: 1500, className: ["toasted-primary", "bg-success"] });
                }
                $("html").off("mousemove");
                ori_x = 0;
                ori_y = 0;
                signatureVM.$forceUpdate();
            });

            /*定義滑鼠在html上移動的事件*/
            $("html").on("mousemove", function (e) {
                if (!hasMove) Vue.toasted.show("拖移開始", { duration: 1500 });
                hasMove = true; /*有拖移*/
                if (ori_x != 0 || ori_y != 0) {
                    diff_x = self.get_move_diff(ori_x, e.clientX);
                    diff_y = self.get_move_diff(ori_y, e.clientY);
                    
                    // console.log([e.clientX, e.clientY])
                    // console.log([diff_x, diff_y])
                    maxw = $('.overflow_hidden').width() - signature.w;
                    maxh = $('.overflow_hidden').height() - signature.h;
                    if (diff_x || diff_y) { /*如果有調整到位置*/
                        /*更新位置*/
                        if(signature.p_x + diff_x >=0 && signature.p_x + diff_x <=maxw)
                            self.signatures[index].p_x += diff_x;
                        if(signature.p_y + diff_y >=0 && signature.p_y + diff_y <=maxh)
                            self.signatures[index].p_y += diff_y;

                        ori_x = e.clientX; /*更新紀錄起始點x位置*/
                        ori_y = e.clientY; /*更新紀錄起始點y位置*/
                    }
                } else {
                    ori_x = e.clientX; /*更新紀錄起始點x位置*/
                    ori_y = e.clientY; /*更新紀錄起始點y位置*/
                }
            });
        },

        reset_edit_view_question: function(){
            editor_question.html("");
            // editor_question.readonly(false);

            var init_data = JSON.parse(JSON.stringify(aeModel_empty));
            init_data = {
                ...init_data,
                ...{
                    method: 'add',
                    i: "",
                    w: 100,
                    h: 20,
                    p_x: 0,
                    p_y: document.getElementById("view_area_content").scrollTop,
                }
            }
            this.edit_view_question = init_data;
        },
        eidt_question: function(index){
            this.edit_view_question = Object.assign({}, this.questions[index]);
            editor_question.html(this.edit_view_question.discription);
            // if(this.edit_view_question.fields_set_id!=0){
            //     editor_question.readonly(true);
            // }
            this.edit_view_question.method = 'edit';
            this.edit_view_question.i = index;
            console.log(this.edit_view_question);
        },
        save_question: function(){
            if(this.imgs.length < 1){
                Vue.toasted.show("請先上傳圖片", { duration: 1500, className: ["toasted-primary", "bg-danger"] });
                $('#signatureEditView_question .close').click();
                this.reset_edit_view_question();
                return;
            }

            index = this.edit_view_question.i;
            var edit_data = this.copy_obj_data(this.edit_view_question);
            edit_data.discription = editor_question.html();
            if(index===""){ /*新增*/
                this.questions.push(edit_data);
            }else{ /*編輯*/
                this.questions[index] = edit_data;
            }
            
            $('#signatureEditView_question .close').click();
            this.reset_edit_view_question();
        },
        copy_question: function(index) {
            const new_data = JSON.parse(JSON.stringify(this.questions[index]));
            const current_top = document.getElementById("view_area_content").scrollTop;
            new_data.p_x = new_data.p_x-10 > 0 ? new_data.p_x-10 : 0;
            new_data.p_y = new_data.p_y-10 > current_top ? new_data.p_y-10 : current_top;
            this.questions.push(new_data);
            $('#signatureEditView_question .close').click();
            this.reset_edit_view_question();
        },
        cancel_question: function(index){
            this.questions.splice(index, 1);
            $('#signatureEditView_question .close').click();
            this.reset_edit_view_question();
        },
        cancel_questions: function(){
            this.questions = [];
        },
        move_question: function (index, question) {
            self = this;
            /*滑鼠放掉時清空html對滑鼠移動的事件*/
            $("html").one("mouseup", function (e) {
                if (hasMove) {
                    Vue.toasted.show("拖移結束", { duration: 1500, className: ["toasted-primary", "bg-success"] });
                }
                $("html").off("mousemove");
                ori_x = 0;
                ori_y = 0;
                signatureVM.$forceUpdate();
            });

            /*定義滑鼠在html上移動的事件*/
            $("html").on("mousemove", function (e) {
                if (!hasMove) Vue.toasted.show("拖移開始", { duration: 1500 });
                hasMove = true; /*有拖移*/
                if (ori_x != 0 || ori_y != 0) {
                    diff_x = self.get_move_diff(ori_x, e.clientX);
                    diff_y = self.get_move_diff(ori_y, e.clientY);
                    
                    // console.log([e.clientX, e.clientY])
                    // console.log([diff_x, diff_y])
                    maxw = $('.overflow_hidden').width() - question.w;
                    maxh = $('.overflow_hidden').height() - question.h;
                    if (diff_x || diff_y) { /*如果有調整到位置*/
                        /*更新位置*/
                        if(question.p_x + diff_x >=0 && question.p_x + diff_x <=maxw)
                            self.questions[index].p_x += diff_x;
                        if(question.p_y + diff_y >=0 && question.p_y + diff_y <=maxh)
                            self.questions[index].p_y += diff_y;
                        signatureVM.$forceUpdate();

                        ori_x = e.clientX; /*更新紀錄起始點x位置*/
                        ori_y = e.clientY; /*更新紀錄起始點y位置*/
                    }
                } else {
                    ori_x = e.clientX; /*更新紀錄起始點x位置*/
                    ori_y = e.clientY; /*更新紀錄起始點y位置*/
                }
            });
        },
        /*切換資料類型*/
        change_type: function(){
            if(this.types_need_limit.indexOf(this.edit_view_question.type) == -1){ /*切換至不須限定格式的類型*/
                this.edit_view_question.limit = "";
            }
        },
        /*添加選項*/
        add_option: function(){
            self = this;
            if(typeof(self.edit_view_question.options)=='undefined'){
                self.edit_view_question.options = [];
            }
            self.edit_view_question.options.push("");
            signatureVM.$forceUpdate();
        },
        /*刪除選項*/
        del_option: function(index){
            self = this;
            self.edit_view_question.options.splice(index, 1);
            signatureVM.$forceUpdate();
        },
        copy_obj_data: function(source){
            new_data = {};
            const keys = Object.keys(source);
            for (var i = 0; i < keys.length; i++) {
                key = keys[i];
                new_data[key] = JSON.parse(JSON.stringify(source[key]));
            }
            return new_data;
        },

        get_move_diff: function(o_p, n_p){
            diff = (n_p - o_p);
            return diff;
        },
        submit: function(){
            self = this;

            /*轉換w, h, x, y成比例*/
            var signatures = [];
            all_w = $('.overflow_hidden').width();
            all_h = $('.overflow_hidden').height();
            for (var i = 0; i < self.signatures.length; i++) {
                signatures.push({
                    w: self.signatures[i].w / all_w,
                    h: self.signatures[i].h / all_h,
                    p_x: self.signatures[i].p_x / all_w,
                    p_y: self.signatures[i].p_y / all_h,
                    required: self.signatures[i].required,
                });
            }

            /*EIP功能*/
            /*設定問題答案*/
            var questions = [];
            for (var i = 0; i < self.questions.length; i++) {
                if(self.questions[i].type=='checkbox'){
                    self.questions[i].ans = [];
                }
                push_item = self.copy_obj_data(self.questions[i]);
                push_item.w = self.questions[i].w / all_w;
                push_item.h = self.questions[i].h / all_h;
                push_item.p_x = self.questions[i].p_x / all_w;
                push_item.p_y = self.questions[i].p_y / all_h;
                questions.push(push_item);
            }

            var data = { 
                /*EIP功能*/
                name: $('#exampleModalCenter [name="name"]').val(),
                cat_id: $('#cat_id').val(),
                content: editor.html(),

                imgs: self.imgs,
                signatures: signatures,
                questions: questions,
            };

            /*送出資料*/
            $('#body_block').show();
            $.ajax({
                dataType: 'json',
                method:'post',
                url:"/index.php/Crmcumcat/update_cate.html",
                data: data,
                success:function(res){
                    if(res.status==0){
                        Vue.toasted.show(res.info, { duration: 1500, className: ["toasted-primary", "bg-danger"] });
                        $('#body_block').hide();
                    }else{
                        Vue.toasted.show('更新成功', { duration: 1500, className: ["toasted-primary", "bg-success"] });
                        self.get_data(self.cat_id);
                        $('#body_block').hide();
                        // setTimeout(function(){
                        //     location.reload();
                        // }, 200);
                    }
                },
                errors: function(res){
                    $('#body_block').hide();
                },
            });
        },
    },
});

/*初始化編輯器*/
var editor_question = KindEditor.ready(function(K) {
    editor_question = K.create('#editor_question', {
        langType : 'zh_TW',
        items:['source', '|', 'hr','|','emoticons','|','forecolor','bold', 'italic', 'underline','link', 'unlink',],
        width:'100%',
        height:'200px',
        resizeType:0
    });
    signatureVM.reset_edit_view();
    signatureVM.reset_edit_view_question();
});