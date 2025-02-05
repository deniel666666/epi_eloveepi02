/*Vue元件*/
/*分頁*/
Vue.component('crm_index_pages', {
  template:`
    <div class="page">
      <a v-if="current_page-1 > 0" href="###" @click="trigger_change_page(current_page-1)" class="prev">上頁</a>
      <a v-else class="prev" style="opacity: 0.5;">上頁</a>
      <sapn v-for="page in pages">
        <a v-if="page != current_page" href="###" class="num" v-text="page" @click="trigger_change_page(page)"></a>
        <span v-if="page == current_page" class="current" v-text="page">5</span>
      </sapn>
      <a v-if="current_page+1 <= computed_page_num" href="###" @click="trigger_change_page(current_page+1)" class="next">下頁</a>
      <a v-else class="next" style="opacity: 0.5;">上頁</a>
    </div>
  `,
  data: function() {
    return {
      pages: [1],
    };
  },
  props: {
    change_page: Function,  /*換頁*/
    current_page: Number,   /*當前頁數*/
    
    count_of_items: Number, /*項目總數(計算總頁數用)*/
    count_of_page: Number,  /*一頁數量(計算總頁數用)*/
    
    total_pages: Number,    /*總頁數*/
  },
  computed: {
    computed_page_num: function(){
      page_num = 1;
      if(this.total_pages){ /*有傳入總頁數*/
        page_num = this.total_pages;
      }else if(this.count_of_items && this.count_of_page){ /*有傳入一頁數量&項目總數*/
        page_num = Math.ceil( this.count_of_items / this.count_of_page);
      }
      return page_num;
    },
  },
  watch: {
    current_page: {
      immediate: true, // 立即执行一次监听器
      handler: function() { this.updatePages(); },
    },
    count_of_items: {
      handler: function() { this.updatePages(); },
    },
    count_of_page: {
      handler: function() { this.updatePages(); },
    },
    total_pages: {
      handler: function() { this.updatePages(); },
    },
  },
  methods: {
    updatePages() { /*根據傳入最大頁數生成新的頁數列表*/
      var pages = [];
      for (var i=-5; i<5; i++) {
        if(i+this.current_page > 0 && i+this.current_page <= this.computed_page_num){
          pages.push(i+this.current_page);
        }
      }
      this.pages = pages;
    },
    trigger_change_page(page){
      if (typeof this.change_page === 'function') {
        if(page > 0 && page <= this.computed_page_num){
          this.change_page(page);
        }
      }
    }
  },
});
