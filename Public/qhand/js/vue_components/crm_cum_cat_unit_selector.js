/*Vue元件*/
/*合約項目選擇棄*/
Vue.component('crm_cum_cat_unit_selector', {
  template:`
  <div>
    <div class="send d-flex justify-content-between">
      <div>
        <select v-model="search_category_id">
          <option value="-1">全部</option>
          <option value="0">無</option>
          <option v-for="category in categorys" :value="category.id" v-text="category.name"></option>
        </select>
        <input type="text" v-model="searchKeyword" placeholder="請輸入商品代號/品名/規格" style="width:250px;">
        <a href="###" @click="do_search" class="btn addbtn">搜尋</a>
      </div>
      <crm_index_pages :change_page="change_page" :current_page="current_page" :total_pages="total_pages"></crm_index_pages>
    </div>
    <div class="edit_form">
      <table cellpadding="2" cellspacing="1" class="table edit_table" style="min-width: 850px;">
        <thead class="edit_table_thead">
          <tr class="edit_table tr ">
            <th style="width: 90px">
              <input type="checkbox" v-model="select_all" @click="toggle_select_all">
              項次
              <slot name="add_btn_html"></slot>
            </th>
            <th v-for="column in columns_show"
                :class="['list_price', 'sale_price', 'profit', 'orders'].indexOf(column.key)!=-1 ? 'text-right' : ''"
                v-text="column.name"
                width="100"
            ></th>
            <th style="width: 70px">操作</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(item,key) in units">
            <td>
              <input :id="'selector_ck_'+item.id" type="checkbox" :value="item" v-model="select_items">
              <label :for="'selector_ck_'+item.id" v-text="(current_page-1)*count_of_page + key+1"></label>
            </td>
            <td v-for="column in columns_show"
              :class="['list_price', 'sale_price', 'profit', 'orders'].indexOf(column.key)!=-1 ? 'text-right' : ''">
              <span class="item" v-text="item[column.key]"></span>
            </td>
            <td>
              <a href="###" @click="select_one(item)">
                <slot name="operate_btn">
                  <button class="btn sendbtn">選擇</button>
                </slot>
              </a>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    <a href="###" @click="select_batch">
      <slot name="operate_batch_btn"><button class="btn sendbtn">批次選擇</button></slot>
    </a>
  </div>
  `,
  data: function() {
    return {
      /*分頁功能*/
      current_page: 1,
      total_pages: 1,
      count_of_page: 0,

      /*列表搜尋相關參數*/
      searchKeyword: '',
      search_category_id: -1,

      categorys: [],      /*分類選項*/
		  units: [],          /*列表資料*/
      select_all: false,  /*全選狀態*/
      select_items: [],   /*選擇項目*/
    };
  },
  props: {
    do_select: Function,  /*選擇*/
    
    /*列表搜尋相關參數*/
    crm_id: Number,       /*依公司查詢*/
    crm_provide: Number,  /*公司是否提供(0.未提供 1.提供)*/
    get_or_pay: Number,   /*收付款*/

    columns: Array,       /*列表顯示的欄位*/
  },
  computed: {
    computed_get_or_pay: function(){
      return this.get_or_pay ? this.get_or_pay : 0;
    },
    get_list_url: function(){
      crm_id = 0;
      if(this.crm_id){ /*有傳入公司id*/
        /*依照供應商提供提供項目關係表取得列表資料*/
        return '/index.php/Ajax/get_cat_unit_ajax/status/1/get_or_pay/'+this.computed_get_or_pay;
      }else{
        /*取得全部目表資料*/
        return '/index.php/Ajax/get_cat_unit_ajax/status/1/get_or_pay/'+this.computed_get_or_pay;
      }
    },
    columns_show: function(){
      columns = [];
      if(this.columns){
        columns = this.columns.filter((item)=>{ return item.name!=''; });
      }
      return columns;
    },
  },
  created: async function(){
    res = await this.get_cat_unit_ajax();
    this.units=res.cat_units;
    this.units=res.cat_units;
    this.count_of_page = res.countOfPage;
    this.total_pages = res.totalPage;

    res = await $.ajax({
      method:'post',
      dataType:'json',
      url:"/index.php/Ajax/get_cat_unit_category_ajax/status/1/get_or_pay/"+this.computed_get_or_pay,
    });
    this.categorys = res.cat_units;
  },
  watch: {
  },
  methods: {
    do_search(){
      this.current_page = 1;
      this.get_cat_unit();
    },
    change_page(page){
      this.current_page = page;
      this.get_cat_unit();
    },
    async get_cat_unit() {
      res = await this.get_cat_unit_ajax();
      // console.log(res)
      this.units=res.cat_units;
      this.count_of_page = res.countOfPage;
      this.total_pages = res.totalPage;
    },
    get_cat_unit_ajax() { /*取得資料*/
      this.units=[];
      this.select_all = false;
      this.select_items = [];
      return $.ajax({
        method:'post',
        dataType:'json',
        url: this.get_list_url,
        data:{
          cond: {
            crm_id: this.crm_id,
            crm_provide: this.crm_provide,
            currentPage: this.current_page,
            search_category_id: this.search_category_id,
            searchKeyword: this.searchKeyword,
          },
        },
      });
    },

    toggle_select_all(){ /*切換全選*/
      if(!this.select_all){
        for (let i = 0; i < this.units.length; i++) {
          this.select_items.push(this.units[i]);
        }
      }else{
        this.select_items = [];
      }
    },

    select_one(item){ /*單一選擇*/
      this.trigger_do_select([item]);
    },
    select_batch(){ /*批次選擇*/
      this.trigger_do_select(this.select_items);
    },
    async trigger_do_select(items){ /*觸發外部選擇功能*/
      if (typeof this.do_select === 'function') {
        $('#body_block').show();
        res = await this.do_select(items);
        $('#body_block').hide();
        if(typeof res!='undefined'){
          if(res.status){
            this.get_cat_unit();
            Vue.toasted.show(res.info, { duration: 1500, className: ["toasted-primary", "bg-success"] });
          }else{
            Vue.toasted.show(res.info, { duration: 1500, className: ["toasted-primary", "bg-danger"] });
          }
        }
      }
    }
  },
});
