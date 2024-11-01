<?php
if (!defined('ABSPATH')) {
    die;
}
?>
<div class="wrap guaven_sbsl_admin_container">
<div id="icon-options-general" class="icon32"><br></div><h2>Smart Backend Search Layer
<?php if (!defined('Guaven_SBSL_PRO_DIR')){ ?>
  <span style="float:right"> <a style="background:#e95b59;color:white" class="button" href="<?php echo $this->get_pro_link();?>">
    Get PRO version</a></span>
  <?php } ?>
  </h2>
<?php
settings_errors();
?>

<form action="" method="post" name="settings_form">
<?php
wp_nonce_field('guaven_sbsl_nonce', 'guaven_sbsl_nonce_f');
?>

<h3>Cache re/builder</h3>

<p>
This button does the needed indexation based on your posts and using parameters below.</p>
<?php
$guaven_sbsl_rebuild_via = get_option("guaven_sbsl_rebuild_via");
if (defined('W3TC') and $guaven_sbsl_rebuild_via == 'db') {
    echo '<p style="color:blue">It seems you are using W3 Total Cache which blocks rebuilding process by default (due to its Object Cache feature).
Please go to "Data Building" tab and chosse "Rebuild via Filesystem" option for "Rebuild the cache via" setting.
</p>';
}

?>
<div style="height:30px">
<input type="button" class="rebuilder inputrebuilder button button-primary" value="Rebuild the Cache" style="float:left"></div>

<div style="font-weight: bold;font-size:14px;background:#486b00;border-radius:3px;color:white;margin-top:10px;display:none;clear:both;padding: 10px" id="result_field"></div>



  <table class="form-table" id="box-table-a">
  <tbody>

      <tr valign="top">
      <th scope="row" class="titledesc">Main Features</th>
      <td scope="row">
      <p>
      <label>
              <input name="guaven_sbsl_corr_act" type="checkbox" value="1" class="tog" <?php
echo checked(get_option("guaven_sbsl_corr_act"), 'checked');
?>>
              Enable Fuzzy Autocorrected Search (which helps to understand misspellings, typos - usually should be checked) </label>
      <br>
      <small>For example, if a user types <i>ifone</i> instead of <i>iphone</i>, or <i>kidshoe</i> instead of <i>Kids Shoes</i> this feature will understand him/her and will suggest
      corresponding products.</small></p>
      <br>


      <p>
      <label>
              <input name="guaven_sbsl_cached_search" type="checkbox" value="1" class="tog" <?php
echo checked(get_option("guaven_sbsl_cached_search"), 'checked');
?>>
Enable Cached Search (Saves each search result data to help searches working faster  ) </label>
</p>
      <br>
      </td> </tr>


  <tr valign="top">
  <th scope="row" class="titledesc">Indexed data</th>
  <td scope="row">

    <p><label><input name="guaven_sbsl_index_title" type="checkbox" value="1" class="tog" <?php
echo checked(get_option("guaven_sbsl_index_title"), 'checked');
?>>Title (should always be checked) </label></p>
    <br>
    <p><label><input name="guaven_sbsl_index_excerpt" type="checkbox" value="1" class="tog" <?php
  echo checked(get_option("guaven_sbsl_index_excerpt"), 'checked');
  ?>>  Excerpt (not recommended for >10K posts) </label></p>
    <br>
    <p><label><input name="guaven_sbsl_index_content" type="checkbox" value="1" class="tog" <?php
  echo checked(get_option("guaven_sbsl_index_content"), 'checked');
  ?>>  Content (not recommended for >10K posts) </label></p>
    <br>

    <p><label><input name="guaven_sbsl_index_comments" type="checkbox" value="1" class="tog" <?php
  echo checked(get_option("guaven_sbsl_index_comments"), 'checked');
  ?>>  Comments</label></p>
    <br>



  <p>
  <label>
Taxonomies:
  <input name="guaven_sbsl_index_taxonomies" type="text" id="guaven_sbsl_index_taxonomies"
  value='<?php echo $this->kses(get_option("guaven_sbsl_index_taxonomies"));?>' class="small-text" style="width:500px"  placeholder=''>
  </label><br>
  <small>Taxonomies (comma separated), f.e. post_tag,category,product_cat </small>
  </p><br>


  <p>
  <label>
Indexed post types:
  <input name="guaven_sbsl_index_posttypes" type="text" id="guaven_sbsl_index_posttypes"
  value='<?php echo $this->kses(get_option("guaven_sbsl_index_posttypes"));?>' class="small-text" style="width:500px"  placeholder=''>
</label><br>
<small>Post types (comma separated), f.e. page,post,product</small>
  </p><br>

  <p>
  <label>
Meta data:
  <input name="guaven_sbsl_index_metafields" type="text" id="guaven_sbsl_index_metafields"
  value='<?php echo $this->kses(get_option("guaven_sbsl_index_metafields"));?>' class="small-text" style="width:500px"  placeholder=''>
</label><br>
  <small>Custom field names (comma separated)</small>
  </p><br>

  <p>
  <label>
Exclude list:<br>
  <textarea name="guaven_sbsl_index_exclude" id="guaven_sbsl_index_exclude"  class="small-text" style="width:500px"  placeholder='' rows="2"><?php
  echo $this->kses(get_option("guaven_sbsl_index_exclude"));?></textarea>
</label><br>
  <small>Ignore entered words if they exist in search input (comma separated), f.e.  how,from,to,or,around</small>
  </p><br>

  <p>
  <label>
Synonym replacements:<br>
<textarea name="guaven_sbsl_synonyms" id="guaven_sbsl_synonyms"  class="small-text" style="width:500px"  placeholder='' rows="2"><?php
echo $this->kses(get_option("guaven_sbsl_synonyms"));?></textarea><br>
<small>Use comma separated couples. f.e. <i>boots-shoes,automobile-car,television-tv</i>.</small>
</label></p>

      </td>
  </tr>


  <tr valign="top">
  <th scope="row" class="titledesc">Other Settings</th>
  <td scope="row">
    <p>
    <label>
            <input name="guaven_sbsl_autoclean" type="checkbox" value="1" class="tog" <?php
echo checked(get_option("guaven_sbsl_autoclean"), 'checked');
?>>
Rebuild Search Cache and Indexed Data after any post(post,page, custom post type...) edited/added/removed via WP-Admin. </label>
</p>
    <br>

    </td>
  </tr>


  </tbody> </table>


<p>
<input type="submit" class="button button-primary" value="Save settings">
</p>
</form>

<?php if (!defined('Guaven_SBSL_PRO_DIR')){ ?>
<hr>
<p>
  <h4>Free vs PRO version</h4>
  Current free version of the plugin works just fine and includes all major features, but if you need more features and remove limits you can purchase our PRO version.
  Here are some main differences:

  <ol><li>PRO version can index <b>unlimited post/page/products</b> etc., default version can index up to <b>1000 posts</b>. </li>
  <li>PRO version can understand transliteration (special letters such as ç,ü,é,ä,ö,... + cyrillic->latin, greek->latin etc.)</li>
  <li>Coming soon for PRO: Synonym support</li>
</ol>
<span style="float:left"> <a style="background:#e95b59;color:white" class="button" href="<?php echo $this->get_pro_link();?>">
  Get PRO version</a></span>
</p>
  <br>
<?php } ?>

</div>
