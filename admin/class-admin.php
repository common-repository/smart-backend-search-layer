<?php
if (!defined('ABSPATH')) {
    die;
}

class Guaven_SBSL_Admin
{
    public $guaven_sbsl_firstrun;
    public $argv1;

    public function __construct()
    {
        if (get_option('guaven_sbsl_firstrun') == '') {
            $this->guaven_sbsl_firstrun = 1;
        }
    }

    public function run()
    {
        $this->save_settings();
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/view.php';
    }

    public function save_settings()
    {

        if (get_option('guaven_sbsl_cache_table_built') == '') {
            $this->cache_db_construct(1);
            update_option('guaven_sbsl_cache_table_built', 1);
            add_settings_error('guaven_pnh_settings', esc_attr('settings_updated'), 'Just click to REBUILD button and it will start working with default settings.', 'updated');
        }
        if (get_option('guaven_sbsl_cache_table_built_2') == '') {
            $this->cache_db_construct(2);
              update_option('guaven_sbsl_cache_table_built_2', 1);
          }

        if (isset($_POST['guaven_sbsl_nonce_f']) and wp_verify_nonce($_POST['guaven_sbsl_nonce_f'], 'guaven_sbsl_nonce')) {
            $this->to_default_runner();
            add_settings_error('guaven_pnh_settings', esc_attr('settings_updated'), 'Success! All changes have been saved. Now just rebuild the cache.', 'updated');
        }  elseif (!empty($this->guaven_sbsl_firstrun)) {
            $this->to_default_runner();
            update_option('guaven_sbsl_firstrun', 1);
        }
    }

    private function to_default_runner()
    {
        $this->is_checked('guaven_sbsl_corr_act', 'checked');
        $this->is_checked('guaven_sbsl_cached_search', 'checked');
        $this->is_checked('guaven_sbsl_index_title', 'checked');
        $this->is_checked('guaven_sbsl_index_excerpt', '');
        $this->is_checked('guaven_sbsl_index_content', '');
        $this->is_checked('guaven_sbsl_index_comments', '');
        $this->is_checked('guaven_sbsl_autoclean', '');
        $this->string_setting('guaven_sbsl_index_posttypes', 'post,page');
        $this->string_setting('guaven_sbsl_index_exclude', '');
        $this->string_setting('guaven_sbsl_synonyms', '');
        $this->string_setting('guaven_sbsl_index_taxonomies', 'category,post_tag','justforthefirsttime');
        $this->string_setting('guaven_sbsl_index_metafields', '_sku','justforthefirsttime');
    }

    private function is_checked($par, $defval = '')
    {
        if (isset($_POST[$par])) {
            $k = 'checked';
        } elseif (empty($_POST['guaven_sbsl_nonce_f']) and $defval != '') {
            $k = $defval;
        } else {
            $k = '';
        }
        update_option($par, $k);
    }

    private function string_setting($par, $def,$firsttime='')
    {
        if (!empty($_POST[$par])) {
            $k = $_POST[$par];
        }
        elseif($firsttime!='' and get_option($par)!='' and empty($_POST[$par])){
          $k='';
        }
        else {
            $k = $def;
        }
          update_option($par, $k);
    }

    private function int_setting($par)
    {
        if (!empty($_POST[$par])) {
            $k = (int) $_POST[$par];
        } else {
            $k = 0;
        }
        update_option($par, $k);
    }

    public function edit_hook_rebuilder()
    {
        if (get_option('guaven_sbsl_autoclean')=='') return;
            update_option('do_sbsl_rebuild', time());
    }

    public function do_rebuilder_at_footer()
    {

        echo '<script>
    sbsl_dontclose=0;
    sbsl_data = {
      "action": "wp_sbsl_rebuild",
      "ajnonce": "' . wp_create_nonce('wp_sbsl_rebuild') . '"
  };
window.onbeforeunload=function(){if (sbsl_dontclose==0) return; return "Cache rebuilding process is in progress... Are you sure to cancel it and close the page?";}
  jQuery(".rebuilder").click(function($) {
    jQuery("#result_field").html("0% done...");
      guaven_sbsl_start_rebuild(sbsl_data);
  });
    function guaven_sbsl_start_rebuild(data) {
        jQuery(".Rebuild-SearchBox-Cache a").text("Rebuilding started...");
        jQuery(".inputrebuilder").val("Rebuilding started...");
        jQuery("#result_field").css("display","block");
        sbsl_dontclose=1;
       jQuery.post(ajaxurl, data, function(response) {
              jQuery("#result_field").html(response+"% done...");
               if (response.indexOf("success_message") ==-1) {console.log("WP Search Layer Cache Rebuilding: "+response+"% done...");
                 guaven_sbsl_start_rebuild(data);
               }
               else { jQuery("#result_field").html(response);
                 jQuery(".Rebuild-SearchBox-Cache a").text("Rebuilding done!");
                 jQuery(".inputrebuilder").val("Rebuilding done!");
                 sbsl_dontclose=0;
                 console.log("Search Cache Rebuilding has been completed!"); }
           }).fail(function() {
             jQuery("#result_field").html("Internal Server Error happened while building the cache data. It can be because of some limits of your server. Please contact to the plugin support team.");
             jQuery("#result_field").css("background","red");
             jQuery(".Rebuild-SearchBox-Cache a").text("Rebuilding failed!");
             jQuery(".inputrebuilder").val("Rebuilding failed!");
  });
    }
';
        if (get_option('do_sbsl_rebuild') != '') {
            echo 'guaven_sbsl_start_rebuild(sbsl_data);';
            update_option('do_sbsl_rebuild','');
        }
        echo '
jQuery(".Rebuild-SearchBox-Cache a").attr("href","javascript://");
</script>
  <style>.Rebuild-SearchBox-Cache a {background:#008ec2 !important}</style>';
    }



    public function wp_sbsl_rebuild_callback()
    {
        $step_size = 100;
        global $wpdb;
        $ptype=$this->get_post_types_sql();
        $pcount = $wpdb->get_var("select count(*) from $wpdb->posts where  ".$ptype." 1=1");

        check_ajax_referer('wp_sbsl_rebuild', 'ajnonce');

        $mcount = Guaven_SBSL_Offsetter();
        $pcount = min($mcount, $pcount);
        $all_steps = ceil($pcount / $step_size);
        $msteps = (int) get_transient('guaven_sbsl_crs') + 1;
        $offset = $step_size * ($msteps - 1);

        if ($msteps == 1) {
            $this->cache_clean();
        }

        set_transient('guaven_sbsl_crs', $msteps, 3600);
        $this->cache_rebuilder($offset, $step_size, 'guaven_sbsl_product_cache', $pcount);

        if ($all_steps <= $msteps) {
            delete_transient('guaven_sbsl_crs');
            echo 'Done!!! '.(!defined('Guaven_SBSL_PRO_DIR')?'Up to 1000 posts has been indexed. To index all possible posts/pages/custom posts upgrade to
            <a href="'.$this->get_pro_link().'">
            pro version</a> now.':'').'<span class="success_message"></span>';
            wp_die();
        }
        echo round($msteps * 10000 / $all_steps) / 100;
        wp_die();
    }

    function get_post_types_sql(){
      $guaven_sbsl_post_type_arr=explode(",",get_option('guaven_sbsl_index_posttypes'));
      $post_types='';
      if (!empty($guaven_sbsl_post_type_arr)){
        foreach($guaven_sbsl_post_type_arr as $key=>$value){
          $guaven_sbsl_post_type_arr[$key]="'".$value."'";
        }
        $guaven_sbsl_post_type_sql=implode(",",$guaven_sbsl_post_type_arr);
        $post_types="post_type in (".$guaven_sbsl_post_type_sql.") and";
      }
      return $post_types;
    }

    private function get_pro_link(){
      return 'https://guaven.com/our-products/smart-backend-search-layer-for-wordpress/?fr=sbsl_settingspage&website='.home_url();
    }

    private function cache_rebuilder($offset, $step_size, $op_name = 'guaven_sbsl_product_cache', $totalproducts = 0)
    {
      global $wpdb;
      $post_types=$this->get_post_types_sql();

      global $wpdb;
      $post_statuses="('publish')";
      $allposts=$wpdb->get_results("select * from $wpdb->posts where ".$post_types." post_status IN ".$post_statuses." order by ID asc limit ".$offset.",".$step_size);
      $eachpost=array();
      $ttba=explode(",",get_option('guaven_sbsl_index_taxonomies'));
      $cusfields=explode(",",get_option('guaven_sbsl_index_metafields'));
      $synonyms=get_option('guaven_sbsl_synonyms');
      $synonym_list     = explode(',', $synonyms);

      foreach($allposts as $ap){
      $metadata=$ap->post_title;
      if (get_option('guaven_sbsl_index_title')!=''){$metadata=strip_tags($ap->post_title);}
      if (get_option('guaven_sbsl_index_content')!=''){$metadata.=' '.strip_tags($ap->post_content);}
      if (get_option('guaven_sbsl_index_excerpt')!=''){$metadata.=' '.strip_tags($ap->post_excerpt);}
      if (get_option('guaven_sbsl_index_comments')!=''){
        $comments = get_comments('post_id='.$ap->ID);
        foreach($comments as $comment){
          $metadata.=' '.strip_tags($comment->comment_content).', ';
        }
      }

      $metadata.=$this->get_post_meta_data($ap->ID,$ttba,$cusfields);


      $corresp_synonyms = $this->synonym_list($ap->post_title,$synonym_list);
      if ($corresp_synonyms != '') {
        $metadata .= addslashes(' ' . str_replace(array("'",'"','’'), '', stripslashes($corresp_synonyms)) . ' ');
      }

      $finalmeta=$this->character_remover($metadata);
      $finalmeta=$this->translitter($finalmeta);
      $eachpost[]=array('post_id'=>$ap->ID,'meta_data'=>$finalmeta,'post_type'=>$ap->post_type);
      }

      $this->wp_insert_rows($eachpost,$wpdb->prefix.'SBSL_cache');

    }

    function get_post_meta_data($pid,$ttba,$cusfields){
      $term_list = wp_get_post_terms($pid, $ttba);
      $term_list_itstag=0;$tba_searchdata='';
      foreach ($term_list as $term_single) {
          if (!empty($term_single->name) and strpos($tba_searchdata, $term_single->name) === false) {
              $tba_searchdata .= $term_single->name.' ';
              $term_list_itstag = 1;
          }
          if ($term_single->parent > 0) {
              $pterm = get_term($term_single->parent);
              if (strpos($tba_searchdata, $pterm->name) === false) {
                  $tba_searchdata .= $pterm->name . ' ';
              }
          }
      }
      $postmetadata=get_metadata('post',$pid,'',true);
      foreach($cusfields as $ap){
      if (!empty($postmetadata[$ap])){
        $tba_searchdata .= $$postmetadata[$ap] . ' ';
      }
    }
      return ' [ '.$tba_searchdata.' ] ';
    }



    private function cache_clean()
    {
      global $wpdb;
      $wpdb->query("truncate ".($wpdb->prefix.'SBSL_cache'));
      $wpdb->query("truncate ".($wpdb->prefix.'SBSL_results'));
    }



    public function synonym_list($ptitle,$synonym_list)
    {
        $corresp_synonyms = array();
        $synonym_list_res = array();
        $title_elements   = explode(' ', addslashes(strtolower($ptitle)));
        foreach ($synonym_list as $syn) {
            $syn_lr = explode('-', strtolower($syn));

            if (in_array(trim(str_replace("_", "-", $syn_lr[0])), $title_elements) or (strpos($ptitle, ' ') !== false and strpos(addslashes(strtolower($ptitle)), trim(str_replace("_", "-", $syn_lr[0]))) !== false)) {
                $synonym_list_res[] = trim(str_replace("_", "-", $syn_lr[1]));
            } elseif (in_array(trim(str_replace("_", "-", $syn_lr[1])), $title_elements) or (strpos($ptitle, ' ') !== false and strpos(addslashes(strtolower($ptitle)), trim(str_replace("_", "-", $syn_lr[1]))) !== false)) {
                $synonym_list_res[] = trim(str_replace("_", "-", $syn_lr[0]));
            }
        }

        return implode(',', $synonym_list_res);
    }




    public function admin_menu()
    {
        add_submenu_page('options-general.php', 'Smart Search Layer', 'Smart Search Layer', 'manage_options', __FILE__, array(
            $this,
            'run'
        ));
    }

    public function kses($str)
    {
        return esc_attr(stripslashes($str));
    }

    public function fs_or_db()
    {
        if (get_option('guaven_sbsl_rebuild_via') != 'fs') {
            return;
        }
        return Guaven_SBSL_DIR . 'public/assets/guaven_sbsl_data_processing.js';
    }

    public function get_product_type($prdct)
    {
        if ($this->get_wc_version() < 3) {
            return $prdct->product_type;
        }
        return $prdct->get_type();
    }
    public function get_wc_version()
    {
        if (defined('WC_VERSION') and substr(WC_VERSION, 0, 1) == 3) {
            $guaven_sbsl_wooversion = 3;
        } else {
            $guaven_sbsl_wooversion = 2;
        }
        return $guaven_sbsl_wooversion;
    }



    private function cache_db_construct($ver)
    {
        global $wpdb;
        if ($ver==1){
          $tablename_1 = $wpdb->prefix . "SBSL_cache";
          $tablename_2 = $wpdb->prefix . "SBSL_results";
          $wpdb->query("
            DROP TABLE IF EXISTS `" . $tablename_1 . "`;");
          $wpdb->query("
          CREATE TABLE `" . $tablename_1 . "` (
            `ID` int(11) NOT NULL AUTO_INCREMENT,
            `post_id` int(11) NOT NULL,
            `meta_data` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
            `display_data` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
            `post_type` varchar(200) NOT NULL,
            PRIMARY KEY (`ID`)) ENGINE=InnoDB DEFAULT CHARSET=latin1;");
            $wpdb->query("
            DROP TABLE IF EXISTS `" . $tablename_2 . "`");
            $wpdb->query("
            CREATE TABLE `" . $tablename_2 . "` (
              `ID` int(11) NOT NULL AUTO_INCREMENT,
              `keyword` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
              `result_ids` text NOT NULL,
              `expire_date` date NOT NULL,
              PRIMARY KEY (`ID`)
              ) ENGINE=InnoDB DEFAULT CHARSET=latin1;
              ");
        }
        elseif($ver==2){
          $tablename = $wpdb->prefix . "SBSL_log";
          $wpdb->query("
          DROP TABLE IF EXISTS `" . $tablename . "`");
          $wpdb->query("
          CREATE TABLE `" . $tablename . "` (
            `ID` int(11) NOT NULL AUTO_INCREMENT,
            `keyword` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
            `result_ids` text NOT NULL,
            `expire_date` date NOT NULL,
            PRIMARY KEY (`ID`)
            ) ENGINE=InnoDB DEFAULT CHARSET=latin1;
            ");
        }

    }

     /*the author of the function - Ugur Mirza ZEYREK*/
    function wp_insert_rows($row_arrays = array(), $wp_table_name) {
    global $wpdb;
    $wp_table_name = esc_sql($wp_table_name);
    // Setup arrays for Actual Values, and Placeholders
    $values = array();
    $place_holders = array();
    $query = "";
    $query_columns = "";

    $query .= "INSERT INTO {$wp_table_name} (";

            foreach($row_arrays as $count => $row_array)
            {

                foreach($row_array as $key => $value) {

                    if($count == 0) {
                        if($query_columns) {
                        $query_columns .= ",".$key."";
                        } else {
                        $query_columns .= "".$key."";
                        }
                    }

                    $values[] =  $value;

                    if(is_numeric($value)) {
                        if(isset($place_holders[$count])) {
                        $place_holders[$count] .= ", '%d'";
                        } else {
                        $place_holders[$count] .= "( '%d'";
                        }
                    } else {
                        if(isset($place_holders[$count])) {
                        $place_holders[$count] .= ", '%s'";
                        } else {
                        $place_holders[$count] .= "( '%s'";
                        }
                    }
                }
                        // mind closing the GAP
                        $place_holders[$count] .= ")";
            }

    $query .= " $query_columns ) VALUES ";

    $query .= implode(', ', $place_holders);

    if($wpdb->query($wpdb->prepare($query, $values))){
        return true;
    } else {
        return false;
    }

}

public function character_remover($str){
  return Guaven_SBSL_Character_Remover($str);
}

public function translitter($str)
{
  return Guaven_SBSL_Translitter($str);
}


}
