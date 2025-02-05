{
    // Vue.toasted.show(文字內容,{
    //   duration:1500, 停留時間 1000=1秒
    //   className:['toasted-primary','bg-danger'], 自訂class
    // });
    window.mobileCheck = function() {
      let check = false;
      (function(a){
        if(/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/i.test(a)||/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i.test(a.substr(0,4))) check = true;
        if(/(iPad)|(Android)/i.test(a)) check = true;
      })(navigator.userAgent||navigator.vendor||window.opera);
      return check;
    };

    /*同步轉軸轉動*/
    function init_scroll_event() {
        var async_scroll_x = $('.async_scroll_x');
        async_scroll_x.on('scroll', function(e){
            x_scroll = $(e.currentTarget)[0].scrollLeft;
            for (var i = 0; i < async_scroll_x.length; i++) {
                async_scroll_x[i].scrollLeft = x_scroll;
            }
        });
        var async_scroll_y = $('.async_scroll_y');
        async_scroll_y.on('scroll', function(e){
            y_scroll = $(e.currentTarget)[0].scrollTop;
            for (var i = 0; i < async_scroll_y.length; i++) {
                async_scroll_y[i].scrollTop = y_scroll;
            }
        });

        function init_async_height(){
            if(mobileCheck()){
                $('.async_height').css({'max-height':'calc(50vh + 1rem)'});
                $('.async_height_add_scrollbar').css({'max-height':'calc(50vh + 1rem)'});
            }else{
                $('.async_height').css({'max-height':'calc(50vh)'});
                $('.async_height_add_scrollbar').css({'max-height':'calc(50vh + 1rem)'});
            }
        }
        $(window).resize(function(){ init_async_height(); });
        init_async_height();
    }

    var ori_x = 0; // 紀錄原始位置
    var rightEnd = 0; // 工作日曆區最右位置
    var leftEnd = 0; // 工作日曆區最左位置
    var timeOut = null; // 用以儲存timeOute物件
    var hasMove = false; // 紀錄滑鼠是否有拖移
    var move_time_bar_area_scroll = function (number, absolute=false) {
        // 控制轉軸位置方法
        if(absolute){
            document.getElementById("time_bar_area").scrollLeft = number; /*更改轉軸位置*/
        }else{
            document.getElementById("time_bar_area").scrollLeft += number; /*更改轉軸位置()*/
        }
    };
    function record_time_bar_area_position() {
        // 紀錄工作日曆區最左右位置
        rightEnd = window.innerWidth - 15; // 紀錄工作最右位置
        leftEnd = $(".work_info_area").width() + 15; // 紀錄工作最左位置
    }
    function date_to_format_time(dateObj) {
        // 把date物件轉換成格式日期文字
        year = dateObj.getFullYear();
        month = dateObj.getMonth() + 1;
        date = dateObj.getDate();
        hours = dateObj.getHours();
        minutes = dateObj.getMinutes();
        format_time = year + "-" + month.toString().padStart(2, "0") + "-" + date.toString().padStart(2, "0") + " " + hours.toString().padStart(2, "0") + ":" + minutes.toString().padStart(2, "0");
        return format_time;
    }

    Vue.component('day_row', {
        template:`
            <div class="day_row text-center d-flex">
                <div v-for="day in calendar_days"
                        :class="['week_day border-left border-right d-flex align-items-center justify-content-center', 
                                day.holiday ? 'text-danger': '']"
                        :style="{width: unit_width+'px'}"
                >
                    <span v-if="show_content">
                        <span data-toggle="tooltip" data-placement="bottom" 
                                :dataOriginalTitle="day.description"
                                v-text="day.name" class="h_description"></span>
                        <br />
                        <span v-text="day.weekday"></span>
                    </span>
                </div>
                <div v-if="day_end_blank" class="day_end_blank"></div>
            </div>
        `,
        data: function() {
            return {
            };
        },
        props: {
            calendar_days : Array,
            unit_width: Number,
            show_content: Boolean,
            day_end_blank: Boolean,
        },
        created: async function(){
        },
        watch: {
        },
        methods: {
        },
    });

    workFlowData = {
        editable: true, /*是否允許編輯*/
        to_current_day: false, /*是將轉軸轉至當前日期*/

        unit2_width: 300, /*工作區寬度*/
        unit_width: 50 /*一單位長度(px)*/,
        unit_time: 86400 /*一單位時長(預設一天)*/,
        work_sort: "create_asc" /*工作流程排序方式*/,
        week_day_list: ["星期日", "星期一", "星期二", "星期三", "星期四", "星期五", "星期六"],
        hour_option: ["00", "01", "02", "03", "04", "05", "06", "07", "08", "09", "10", "11", "12", "13", "14", "15", "16", "17", "18", "19", "20", "21", "22", "23"],
        minute_option: ["00", "30"],
        calendar_days: [{ name: "", weekday: "", holiday: false, holiday_name: "", description: "" }],

        edit_panel_index : "",  /*時間編輯面板編輯對象*/
        edit_panel : {          /*時間編輯面板*/
            /* 控制用資料 */
            s_date:'',
            s_hour:'',
            s_minute:'',
            e_date:'',
            e_hour:'',
            e_minute:'',
            
            /* 事件資料 */
            eve_id: "0", 
            step_order: "0", 
            step_id: "0", 
            name: "", 
            u_name:"", 
            sdate: "", 
            edate: "", 
            state: "", 
            price:"", 
            time_type:"0",
        },

        /*今日日期區間*/
        current_day: { sdate: "0000-01-01 00:00", edate: "0000-01-01 00:00" },

        search: { /*搜尋參數*/
            all:false, eve_id:"", apartmentid:"", user_id:"", search:"", show_examine_step:0, show_time_type:"1",
        },

        /*api需提供以下資料*/
        calendar: { sdate: "2021-06-01 00:00", edate: "2021-06-30 00:00" } /*所有工作最早開始&最晚結束日期*/,
        works: [
            /*各個工作的資料*/
            { eve_id: "", step_order: "", step_id: "", name: "", u_name:"", sdate: "", edate: "", state: "", price:"" },
        ],

        /*EIP額外功能*/
        events: [], /*事件搜尋下拉選*/
    };
    /*初始化今日時間區間*/
    {
        var dateObj = new Date();
        current_s = dateObj.getFullYear() + "-" + (dateObj.getMonth() + 1) + "-" + dateObj.getDate() + " 00:00";
        var current_obj = new Date(current_s);
        workFlowData.current_day.sdate = date_to_format_time(current_obj);
        current_obj.setDate(current_obj.getDate() + 1); /*再加1天作為結束時間*/
        workFlowData.current_day.edate = date_to_format_time(current_obj);
    }
    let slider2 = null;
    function init_vue() {
        var workFlowVM = new Vue({
            el: "#workFlow",
            data: workFlowData,
            computed: {},
            created: function () {
                this.$nextTick(function () {
                    /*初始化滑桿*/ 
                    $(".slider").slider({
                        value: 50,
                        min: 50,
                        max: 400,
                        slide: function (event, ui) {
                            workFlowVM.unit_width = ui.value;
                        },
                    });
                    slider2 = $(".slider2").slider({
                        value: 300,
                        min: 100,
                        max: 300,
                        slide: function (event, ui) {
                            workFlowVM.unit2_width = ui.value;
                        },
                    });
                });
            },
            updated: function () {
                this.$nextTick(function () {
                    $(".week_day .h_description, .eve_title, .work_time").tooltip({
                        title: function () {
                            console.log($(this).attr('dataOriginalTitle'));
                            return $(this).attr('dataOriginalTitle');
                        },
                        html: true // 啟用 HTML 格式的支持
                    }); /*初始化tooltip*/
                    // Code that will run only after the entire view has been re-rendered
                    $(".thead").css("height", $(".week_day").height() + "px"); /*設定左右兩區塊表頭同高*/
                
                });

                $('#time_bar_area .current_day_mark').height($('#all_time_bar').height());

                /*更新滑桿最大值*/ 
                slider2.slider("option", "max", $('.input_area').width()*2);
            },
            methods: {
                to_time_stamp: function (format_date) {
                    d = new Date(format_date);
                    return d.getTime() / 1000;
                },
                date_obj_to_format_time(dateObj) {
                    format_time = date_to_format_time(dateObj);
                    return format_time;
                },
                date_to_input_value_date: function (format_date) {
                    text = format_date.slice(0, 10);
                    return text;
                },
                date_to_input_value_hour: function (format_date) {
                    text = format_date.slice(11, 13);
                    text = text.length>0 ? text : '00';
                    return text;
                },
                date_to_input_value_minute: function (format_date) {
                    text = format_date.slice(14, 16);
                    if(text==''){
                        text = '00';
                    }else{
                        text = Number(text)>=30 ? '30' : '00';
                    }
                    return text;
                },
                input_change_time: function (event, index, time_type, work) {
                    parent = $(event.target).parent();
                    date = parent.find("input[name='date']").val();
                    hour = parent.find("select[name='hour']").val();
                    minute = parent.find("select[name='minute']").val();
                    newdate = date + " " + hour + ":" + minute;
                    // console.log(newdate);
                    /*檢查與更新某工作開/始結束時間*/

                    result = workFlowVM.check_and_update_work_time(newdate, index, time_type, work);
                    if (result) {
                        Vue.toasted.show("修改時間成功", { duration: 1500, className: ["toasted-primary", "bg-success"] });
                    }
                },
                get_work_width: function (work) {
                    return ((this.to_time_stamp(work.edate) - this.to_time_stamp(work.sdate)) / this.unit_time) * this.unit_width;
                },
                get_work_position: function (work) {
                    return ((this.to_time_stamp(work.sdate) - this.to_time_stamp(this.calendar.sdate)) / this.unit_time) * this.unit_width;
                },
                init_calendar:function (){ /*初始化calendar，依工作起始日設定日立最初、最終日*/
                    self = this;
                    for (var i = 0; i < self.works.length; i++) {
                        if(self.works[i].edate > self.calendar.edate && self.works[i].edate){ self.calendar.edate = self.works[i].edate; }
                        if(self.works[i].sdate < self.calendar.sdate && self.works[i].sdate){ self.calendar.sdate = self.works[i].sdate; }
                    }
                    let dateObj = new Date(workFlowData.calendar.sdate);
                    adj_sdate = dateObj.getFullYear() + "-" + (dateObj.getMonth() + 1).toString().padStart(2, "0") + "-" + dateObj.getDate().toString().padStart(2, "0") + " 00:00";
                    workFlowData.calendar.sdate = adj_sdate;
                    console.log(adj_sdate)
                },
                get_calendar: function (scroll_to_end=false, scroll_current_day=false) {
                    self = this;
                    self.init_calendar();
                    // console.log(self.calendar);
                    if(self.to_current_day){ /*需配合當前日期來顯示*/
                        if (self.current_day.sdate > self.calendar.edate) self.calendar.edate = self.current_day.edate;
                        if (self.current_day.edate < self.calendar.sdate) self.calendar.sdate = self.current_day.sdate;
                    }

                    cols = (self.to_time_stamp(self.calendar.edate) - self.to_time_stamp(self.calendar.sdate)) / self.unit_time;
                    cObj = [];
                    for (var i = 0; i < Array(cols).length; i++) {
                        /*製作日曆單日資料*/
                        d = new Date(self.calendar.sdate);
                        d.setSeconds(d.getSeconds() + i * self.unit_time);
                        day = {
                            name: d.getMonth() + 1 + "/" + d.getDate(),
                            weekday: self.week_day_list[d.getDay()],
                            holiday: false,
                            holiday_name: "",
                            holiday_category: "",
                            description: "",
                        };

                        /*比對假日*/
                        format_date = self.date_obj_to_format_time(d).slice(0, 10);
                        format_date = format_date.replaceAll('-', '');
                        // console.log(format_date);
                        sindex = holiday_data_string.indexOf(format_date);
                        if (sindex != -1) {
                            /*找出目標紀錄的開頭*/
                            indexOf_start = sindex - 50;
                            pre_date_index = holiday_data_string.indexOf('{"', indexOf_start);
                            while (pre_date_index==sindex-8) {
                                indexOf_start -= 10;
                                if(indexOf_start<=0){
                                    pre_date_index = 0; break;
                                }else{
                                    pre_date_index = holiday_data_string.indexOf('{"', indexOf_start);
                                }
                            }
                            sindex_pre = pre_date_index==0 ? 0 : pre_date_index;

                            eindex = sindex + holiday_data_string.substring(sindex).indexOf('}') + 1;
                            holiday_s = holiday_data_string.slice(sindex_pre, eindex);
                            // console.log(holiday_s);
                            holiday = JSON.parse(holiday_s);
                            // console.log(holiday);
                            day["holiday_name"] = holiday["chinese"];
                            day["description"] = day["holiday_name"] ? day["holiday_name"] + " " : "";
                            day["description"] += holiday["description"] ? holiday["holidaycategory"] + ":" + holiday["description"] : holiday["holidaycategory"];
                            if(day["description"]!='補行上班日'){
                                day["holiday"] = true;
                            }
                        }
                        cObj.push(day);
                        delete d;
                    }
                    self.calendar_days = cObj;
                    // console.log(self.calendar_days)

                    if (scroll_to_end) {
                        /*是否需要移動轉軸至末端*/
                        setTimeout(function () {
                            move_time_bar_area_scroll($(".day_row").width());
                        }, 100);
                    }

                    // console.log(self.to_current_day);
                    // console.log($('.current_day_mark').css('left'));
                    if(scroll_current_day){
                        setTimeout(function () {
                            /*轉移轉軸至今日*/;
                            self.move_to_current_date();
                        }, 100);
                    }
                },
                move_to_current_date: function(){
                    const current_day_mark = $('.current_day_mark');
                    if(current_day_mark.length>0){
                        move_time_bar_area_scroll(current_day_mark.css('left').slice(0,-2), absolute=true);
                    }else{
                        Vue.toasted.show("工作排程中並未顯示今日日期", { duration: 1500 });
                    }
                },
                get_diff_minutes_by_move: function (new_x) {
                    if (new_x >= rightEnd) {
                        /*如果移動超過工作日曆區最右側*/
                        diff_minutes = this.unit_time / 60; /*回傳單位時間的分鐘數*/
                        move_time_bar_area_scroll(this.unit_width); /*轉軸向右移動單位寬度*/
                    } else if (new_x <= leftEnd) {
                        /*如果移動超過工作日曆區最左側*/
                        diff_minutes = (this.unit_time / 60) * -1; /*回傳單位時間的分鐘數*/
                        move_time_bar_area_scroll(this.unit_width * -1); /*轉軸向左移動單位寬度*/
                    } else {
                        /*否則按移動比例計算*/
                        diff = new_x - ori_x;
                        diff_seconds = (diff / this.unit_width) * this.unit_time;
                        diff_minutes = Math.round(diff_seconds / 60 / 30) * 30; /*限制每次調整以30分鐘為單位*/
                    }

                    return diff_minutes;
                },
                change_width: function (index, work, time_type) {
                    if(!this.editable){ return; }

                    // Vue.toasted.show("調整時間開始", { duration: 1500 });
                    /*滑鼠放掉時清空html對滑鼠移動的事件*/
                    $("html").one("mouseup", function (e) {
                        // Vue.toasted.show("調整時間結束", { duration: 1500 });
                        $("html").off("mousemove");
                        ori_x = 0;
                    });

                    record_time_bar_area_position(); // 紀錄工作日曆區最左右位置

                    /*定義滑鼠在html上移動的事件*/
                    $("html").on("mousemove", function (e) {
                        if (ori_x != 0) {
                            diff_minutes = workFlowVM.get_diff_minutes_by_move(e.clientX);

                            if (diff_minutes) {
                                /*如果有調整到時間*/
                                var newdate = new Date(work[time_type]);
                                newdate.setMinutes(newdate.getMinutes() + diff_minutes);
                                new_format_time = workFlowVM.date_obj_to_format_time(newdate);
                                // console.log(new_format_time);
                                /*檢查與更新某工作開/始結束時間*/
                                workFlowVM.check_and_update_work_time(new_format_time, index, time_type, work);
                                ori_x = e.clientX; /*更新紀錄起始點位置*/
                            }
                        } else {
                            ori_x = e.clientX; /*更新紀錄起始點位置*/
                        }
                    });
                },
                move_work: function (index, work) {
                    if(!this.editable){ return; }

                    /*滑鼠放掉時清空html對滑鼠移動的事件*/
                    $("html").one("mouseup", function (e) {
                        if (hasMove) {
                            Vue.toasted.show("拖移工作完成", { duration: 1500, className: ["toasted-primary", "bg-success"] });
                        }
                        $("html").off("mousemove");
                        ori_x = 0;
                    });

                    record_time_bar_area_position(); // 紀錄工作日曆區最左右位置

                    /*定義滑鼠在html上移動的事件*/
                    $("html").on("mousemove", function (e) {
                        if (!hasMove) Vue.toasted.show("拖移工作開始", { duration: 1500 });
                        hasMove = true; /*有拖移*/
                        if (ori_x != 0) {
                            diff_minutes = workFlowVM.get_diff_minutes_by_move(e.clientX);

                            if (diff_minutes) {
                                /*如果有調整到時間*/
                                /*更新結束時間*/
                                var newdate = new Date(work["edate"]);
                                newdate.setMinutes(newdate.getMinutes() + diff_minutes);
                                new_format_time = workFlowVM.date_obj_to_format_time(newdate);
                                /*檢查與更新某工作開/始結束時間*/
                                workFlowVM.check_and_update_work_time(new_format_time, index, "edate", work);

                                /*更新開始時間*/
                                var newdate = new Date(work["sdate"]);
                                newdate.setMinutes(newdate.getMinutes() + diff_minutes);
                                new_format_time = workFlowVM.date_obj_to_format_time(newdate);
                                /*檢查與更新某工作開/始結束時間*/
                                workFlowVM.check_and_update_work_time(new_format_time, index, "sdate", work);

                                ori_x = e.clientX; /*更新紀錄起始點位置*/
                            }
                        } else {
                            ori_x = e.clientX; /*更新紀錄起始點位置*/
                        }
                    });
                },
                check_and_update_work_time: function (new_format_time, index, time_type, work) {
                    result = true;
                    if (time_type == "sdate") {
                        /*修改的是開始時間*/
                        if (new_format_time > work["edate"]) {
                            /*不允許比結束時間更晚*/
                            new_format_time = work["edate"];
                            Vue.toasted.show("開始時間不可超過結束時間", { duration: 1500, className: ["toasted-primary", "bg-danger"] });
                            result = false;
                            $("html").off("mousemove");
                        }
                    } else if (time_type == "edate") {
                        /*修改的是結束時間*/
                        if (new_format_time < work["sdate"]) {
                            /*不允許比開始時間更早*/
                            new_format_time = work["sdate"];
                            Vue.toasted.show("結束時間不可早於開始時間", { duration: 1500, className: ["toasted-primary", "bg-danger"] });
                            result = false;
                            $("html").off("mousemove");
                        }
                    }
                    workFlowVM.works[index][time_type] = new_format_time; /*更改此工作的時間*/

                    workFlowVM.get_area_s_e_date(); /*更新日期區的開始結束時間*/
                    return result;
                },
                click_one_time: function (index, work) {
                    if(!this.editable){ return; }

                    if (!hasMove) {
                        clearTimeout(timeOut); /*清除計時器，停止其他單擊的執行*/
                        timeOut = setTimeout(() => {
                            /*延後執行，以便確認是單擊還是雙擊*/
                            // this.chage_work_state(index, work); /*更新事件狀態*/
                            this.open_change_panel(index, work); /*開啟編輯面板*/
                        }, 300); // 大概時間300ms
                    } else {
                        hasMove = false; /*設定為位移動*/
                    }
                },
                chage_work_state: function (index, work) {
                    if (work["state"] == "hurry") {
                        new_state = "";
                        Vue.toasted.show("修改成一般工作", { duration: 1500, className: ["toasted-primary", "bg-success"] });
                    } else {
                        new_state = "hurry";
                        Vue.toasted.show("修改成緊急工作", { duration: 1500, className: ["toasted-primary", "bg-success"] });
                    }
                    this.works[index]["state"] = new_state;
                },
                open_change_panel: function(index, work){ 
                    $('#edit_panel_btn').click();
                    setTimeout(function(){$($('.modal-backdrop')[0]).remove();}, 300);
                    this.edit_panel_index = index;
                    this.edit_panel = Object.assign({}, work);
                    this.edit_panel.s_date = this.date_to_input_value_date(this.edit_panel.sdate);
                    this.edit_panel.s_hour = this.date_to_input_value_hour(this.edit_panel.sdate);
                    this.edit_panel.s_minute = this.date_to_input_value_minute(this.edit_panel.sdate);
                    this.edit_panel.e_date = this.date_to_input_value_date(this.edit_panel.edate);
                    this.edit_panel.e_hour = this.date_to_input_value_hour(this.edit_panel.edate);
                    this.edit_panel.e_minute = this.date_to_input_value_minute(this.edit_panel.edate);
                },
                do_edit_panel: function(){
                    if(this.edit_panel_index !== ""){
                        new_sdate = this.edit_panel.s_date + " " + this.edit_panel.s_hour + ":" + this.edit_panel.s_minute;
                        new_edate = this.edit_panel.e_date + " " + this.edit_panel.e_hour + ":" + this.edit_panel.e_minute;
                        if(new_sdate.length!=16 || new_edate.length!=16){
                            Vue.toasted.show("時間設定有誤", { duration: 1500, className: ["toasted-primary", "bg-danger"] });
                            return;
                        }
                        else if(new_sdate > new_edate){
                            Vue.toasted.show("結束時間不可小於開始時間", { duration: 1500, className: ["toasted-primary", "bg-danger"] });
                            return;
                        }else{
                            this.works[this.edit_panel_index].sdate = new_sdate;
                            this.works[this.edit_panel_index].edate = new_edate;
                            this.works[this.edit_panel_index].estimated_time = this.edit_panel.estimated_time;
                            this.works[this.edit_panel_index].exact_time = this.edit_panel.exact_time;
                            this.works[this.edit_panel_index].time_type = this.edit_panel.time_type;
                            this.edit_panel_index = "";
                            // this.save_date();
                        }
                    }else{
                        Vue.toasted.show("未選擇編輯對象", { duration: 1500, className: ["toasted-primary", "bg-danger"] });
                    }
                    workFlowVM.get_area_s_e_date(); /*更新日期區的開始結束時間*/
                    $('#edit_panel').modal('hide');
                },
                set_work_notime: function (index) {
                    if(!this.editable){ return; }

                    clearTimeout(timeOut); /*清除計時器，停止單擊的執行*/
                    if (confirm("確定取消排程？")) {
                        this.works[index]["edate"] = this.works[index]["sdate"];
                        Vue.toasted.show("取消排程成功", { duration: 1500, className: ["toasted-primary", "bg-success"] });
                    }
                },
                get_area_s_e_date: function () {
                    temp_s = "9999/12/31 23:59";
                    temp_e = "0000/00/00 00:00";
                    for (var i = 0; i < this.works.length; i++) {
                        /*檢查開始時間*/
                        temp_t = this.works[i]["sdate"];
                        if (temp_s > temp_t && temp_t != "") temp_s = temp_t; /*紀錄較小的開始時間*/

                        /*檢查結束時間*/
                        temp_t = this.works[i]["edate"];
                        if (temp_e < temp_t && temp_t != "") temp_e = temp_t; /*紀錄較大的結束時間*/
                    }

                    /*當日歷開始結束有大小改變時才處理更新*/
                    if (this.calendar.sdate > temp_s || this.calendar.edate < temp_e) {
                        ori_edate = this.calendar.edate;

                        if(this.calendar.sdate > temp_s){
                            var sdateObj = new Date(temp_s);
                            sdateObj.setDate(sdateObj.getDate() - 1); /*設定日歷比最早工作小一天*/
                            sdateObj.setHours(0);
                            sdateObj.setMinutes(0);
                            this.calendar.sdate = this.date_obj_to_format_time(sdateObj); /*更新日歷開始日*/
                        }
                        if(this.calendar.edate < temp_e){
                            var edateObj = new Date(temp_e);
                            edateObj.setDate(edateObj.getDate() + 1); /*設定日歷比最晚工作大一天*/
                            edateObj.setHours(0);
                            edateObj.setMinutes(0);
                            this.calendar.edate = this.date_obj_to_format_time(edateObj); /*更新日歷結束日*/
                        }

                        let scroll_to_end = ori_edate < temp_e;
                        this.get_calendar(scroll_to_end, false); /*更新日歷*/
                    }
                },
                renew_work_sort: function () {
                    var new_works = [];
                    if (this.works) {
                        new_works.push(this.works[0]);
                    }

                    switch (this.work_sort) {
                        case "create_asc" /*依建立順序，由舊至新*/:
                            this.works.sort(function(a, b) {
                                // 首先比较 eve_id
                                var eveIdComparison = Number(a.eve_id) - Number(b.eve_id);
                                // 如果 eve_id 相等，再比较 step_order
                                if (eveIdComparison === 0) {
                                  return Number(a.step_order) - Number(b.step_order);
                                }
                                return eveIdComparison;
                            });
                            break;

                        case "create_desc" /*依建立順序，由新至舊*/:
                            this.works.sort(function(a, b) {
                                // 首先比较 eve_id
                                var eveIdComparison = Number(b.eve_id) - Number(a.eve_id);
                                // 如果 eve_id 相等，再比较 step_order
                                if (eveIdComparison === 0) {
                                  return Number(a.step_order) - Number(b.step_order);
                                }
                                return eveIdComparison;
                            });
                            break;

                        case "worktime_asc" /*依排程順序，由近至遠*/:
                            this.works.sort(function(a, b) {
                                // 首先比较 sdate
                                var sdateComparison = a.sdate.localeCompare(b.sdate);
                              
                                // 如果 sdate 相等，再比较 eve_id
                                if (sdateComparison === 0) {
                                  var eveIdComparison = Number(a.eve_id) - Number(b.eve_id);
                                  
                                  // 如果 eve_id 相等，最后比较 step_order
                                  if (eveIdComparison === 0) {
                                    return Number(a.step_order) - Number(b.step_order);
                                  }
                              
                                  return eveIdComparison;
                                }
                                
                                return sdateComparison;
                            });
                            break;

                        default:
                            Vue.toasted.show("無此排序方式", { duration: 1500, className: ["toasted-primary", "bg-danger"] });
                    }
                    // this.works = new_works;
                },
                save_date: function () {
                    $.ajax({
                        url: "/index.php/Fig/update_working_steps",
                        type: "POST",
                        datatype: "json",
                        data: { steps: workFlowVM.works },
                        success: function (response) {
                            bg_class = response.status == 1 ? "bg-success" : "bg-danger";
                            Vue.toasted.show(response.info, { duration: 1500, className: ["toasted-primary", bg_class] });
                        },
                    });
                },
                get_work_data: function(){
                    self = this;
                    self.search.fig_time_s = $('[name="fig_time_s"]').val();
                    self.search.fig_time_e = $('[name="fig_time_e"]').val();
                    if(self.search.fig_time_s || self.search.fig_time_e){
                        self.to_current_day = false;
                        self.search.all = true;
                    }else if(self.search.fig_time_s=='' && self.search.fig_time_e==''){
                        self.search.all = false;
                        // self.to_current_day = true;
                    }

                    $.ajax({
                        url: "/index.php/Fig/aj_get_working_steps",
                        type: "GET",
                        datatype: "json",
                        data: self.search,
                        success: function (res) {
                            // console.log(res);
                            workFlowVM.calendar.sdate = res.sdate;
                            workFlowVM.calendar.edate = res.edate;
                            workFlowVM.works = res.steps_data;
                            workFlowVM.events = res.events;

                            workFlowVM.get_calendar(scroll_to_end=false, self.to_current_day);
                            Vue.toasted.show("搜尋完畢", { duration: 1500, className: ["toasted-primary", "bg-success"] });
                        },
                    });
                },
            },
        });
        // workFlowVM.get_work_data(); /*取得工作排程*/
        // workFlowVM.get_calendar(); /*初始化日歷*/
        // move_time_bar_area_scroll($('.current_day_mark').css('left')) /*轉移轉軸至今日*/;

        return workFlowVM;
    }
}
