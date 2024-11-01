<?php
if (!defined('ABSPATH')) {
    die;
}

class Guaven_SBSL_Front
{
    protected $version;
    function __construct($version){
      $this->version=$version;
    }
    public function backend_search_replacer($search)
    {
        if (!$this->healthy_checker()) return $search;
        if (!$this->check_state()) {
            return $search;
        }
        $search_query_local_raw=$this->search_query();
        $search_query_local=explode(" ", strtolower($this->character_remover($search_query_local_raw)));
        if (strpos($search, 'post_title LIKE'!==false) and
        strpos(strtolower($search), $search_query_local_raw)!==false or
        strpos(strtolower($search), $search_query_local[0])!==false) {
            return '~~guaven_replacement~~';
        }
        return $search;
    }

    function check_state(){
      $search_query_local=$this->search_query();
      if (!is_admin() and !empty($search_query_local)) { return true;}
      return false;
    }

    public function backend_search_filter($where = '')
    {
        if (!$this->healthy_checker()) return $where;
        $where0=$where;
        $where=str_replace("~~guaven_replacement~~", "", $where);
        $where1=$where;
        if ($where0==$where1) {
            return $where;
        }
        $search_query_local_raw=$this->search_query();
        $search_query_local=$this->translitter($search_query_local_raw);
        $found_posts           = $this->find_posts($search_query_local);
        $checkkeyword          = $found_posts[0];
        $sanitize_cookie_final = $found_posts[1];
        if ($this->check_state()) {
            $gsquery    = esc_attr($search_query_local);
            global $wpdb;
            if (empty($sanitize_cookie_final)) {
                $sanitize_cookie_final = 0;
            }
            $where .= " AND ( $wpdb->posts.ID in (" . $sanitize_cookie_final . ")   )";
        }
        return $where;
    }

    public function healthy_checker(){
      if (defined('Guaven_SBSL_PRO_DIR')) return true;
      $healthy1=Guaven_SBSL_Offsetter();
      $healthy2=(int)str_replace(".","",$this->version);
      if ($healthy1==$healthy2) return true;
      return false;
    }

    function get_bitap_length($str){
      $search_term_len=strlen($str);
      if ($search_term_len<4) $bitap_length=0;
      else $bitap_length=floor($search_term_len/3);
      if (get_option('guaven_sbsl_corr_act')=='') {
        $bitap_length=0;
      }
      return $bitap_length;
    }

    public function find_posts($search_query_local)
    {
      global $wpdb;
      $tablename=$wpdb->prefix.'SBSL_cache';
      $found=array();$metadatas=array();

      $checkkeyword=$this->character_remover($search_query_local);
      if (get_option('guaven_sbsl_cached_search')!=''){
        $check_cached_data_first=$this->get_results_from_cache($checkkeyword);
      }

      if (gettype($check_cached_data_first)!=='NULL') {
        $final_ids=$check_cached_data_first;
      } else {
        $allcachelist=$wpdb->get_results("select * from $tablename");
        foreach($allcachelist as $acl){
          $metadatas[$acl->post_id]=$acl->meta_data;
        }
        $search_term_len=strlen($checkkeyword);
        if ($search_term_len<4) $bitap_length=0;
        else $bitap_length=floor($search_term_len/3);
        if (get_option('guaven_sbsl_corr_act')=='') {
          $bitap_length=0;
        }
        $checkkeyword_substringed=substr($checkkeyword,0,30);//30 may come from settings
        $checkkeyword_substringed_extend=substr($checkkeyword,0,60);//2*30 may come from settings
        $found=(new Guaven_SBSL_Bitap())->grep($checkkeyword_substringed, $metadatas, $this->get_bitap_length($checkkeyword_substringed));
        $final_ids=implode(",",array_keys($found));

        if (empty($final_ids)){
          $checkkeyword_arr=explode(" ",$checkkeyword_substringed_extend);
          if(count($checkkeyword_arr)>2){
            $found_1=(new Guaven_SBSL_Bitap())->grep($checkkeyword_arr[0], $metadatas, $this->get_bitap_length($checkkeyword_arr[0]));
            $found_2=(new Guaven_SBSL_Bitap())->grep($checkkeyword_arr[1], $metadatas, $this->get_bitap_length($checkkeyword_arr[1]));
            if (!empty($checkkeyword_arr[2])){
              $found_3=(new Guaven_SBSL_Bitap())->grep($checkkeyword_arr[2], $metadatas, $this->get_bitap_length($checkkeyword_arr[2]));
              $final_ids=implode(",",array_intersect(array_keys($found_1),array_keys($found_2),array_keys($found_3)));
            }
            if (empty($final_ids)) {
              $final_ids=implode(",",array_intersect(array_keys($found_1),array_keys($found_2)));
            }
          }
        }

        if (empty($final_ids)) {$final_ids='0';}
        $this->insert_results_to_cache($checkkeyword,$final_ids);
      }

      return array(
            $checkkeyword,
            $final_ids
        );
    }

    public function get_results_from_cache($query){
      global $wpdb;
      $tablename=$wpdb->prefix.'SBSL_results';
      $result_row=$wpdb->get_var($wpdb->prepare("select result_ids from $tablename where keyword=%s",$query));
      return $result_row;
    }
    public function insert_results_to_cache($keyword,$values){
      global $wpdb;
      $tablename=$wpdb->prefix.'SBSL_results';$tablename_2=$wpdb->prefix.'SBSL_log';$cur_date=date('Y-m-d');
      $result_row=$wpdb->query($wpdb->prepare("insert into $tablename (`keyword`,`result_ids`,`expire_date`) values (%s,%s,%s)",$keyword,$values,$cur_date));
      $result_row=$wpdb->query($wpdb->prepare("insert into $tablename_2 (`keyword`,`result_ids`,`expire_date`) values (%s,%s,%s)",$keyword,$values,$cur_date));
    }


        public function search_query()
        {
            if (isset($_GET["s"])) {
                return $_GET["s"];
            }
            if (isset($_GET["woof_text"])) {
                return $_GET["woof_text"];
            }
            return;
        }


    public function backend_search_orderby($orderby_statement)
    {
      return $orderby_statement;
        if (isset($_GET["orderby"])) {
            return $orderby_statement;
        }

        $search_query_local=$this->search_query();
        $found_posts           = $this->find_posts( $search_query_local);
        $checkkeyword          = $found_posts[0];
        $sanitize_cookie_final = $found_posts[1];

        if ($this->check_state()  and !empty($found_posts[1]) and $checkkeyword == $this->character_remover($search_query_local)
        ) {
            global $wpdb;
            $orderby_statement = "FIELD( $wpdb->posts.ID, " . $found_posts[1] . ") ASC";
        }
        return $orderby_statement;
    }

    public function character_remover($str)
    {
      return Guaven_SBSL_Character_Remover($str);
    }
    public function translitter($str)
    {
      return Guaven_SBSL_Translitter($str);
    }
}
