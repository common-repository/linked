<?php
/*
Plugin Name: Linked
Plugin URI: http://www.skullbit.com
Description: Auto-link emails, URLs, post and page titles, categories and your own list of pre-configured keywords
Author: Skullbit
Author URI: http://www.skullbit.com
Version: 1.0
*/
if( !class_exists('LinkedPlugin') ){
	class LinkedPlugin{
		function LinkedPlugin(){
			//Actions
			add_action( 'admin_menu', array($this, 'AddPanel') );
			if( $_POST['action'] == 'linked_update' )
				add_action( 'init', array($this,'SaveSettings') );
			if( $_GET['page'] == 'linked' ){
				wp_enqueue_script('jquery');
				add_action( 'admin_head', array($this, 'JQuery') );
			}
			add_action( 'init', array($this, 'DefaultSettings') );
			register_deactivation_hook( __FILE__, array($this, "UnsetSettings") );
			//Filters
			add_filter( 'the_content', array($this, 'MakeLinks') );
			add_filter( 'the_excerpt', array($this, 'MakeLinks') );
		}
		function AddPanel(){
			add_management_page( 'Linked', 'Linked', 10, 'linked', array($this, 'LinkedPanel') );
		}
		
		function DefaultSettings () {
			$defaults = array(
							  	'WordPress' => 'www.wordpress.org',
								'Google' => 'www.google.com',
								'Skullbit' => 'skullbit.com'
							  );
			if( !get_option("linked_keywords") )
				add_option("linked_keywords", $defaults);
			if( !get_option("linked_options") )
				add_option("linked_options", array('email', 'url', 'post', 'category'));
		}
		
		function UnsetSettings () {
			  delete_option("linked_keywords");
			  delete_option("linked_options");
		}
		
		function SaveSettings(){			
			check_admin_referer('linked-update-options');
			$keys = $_POST['keyword'];
			$urls = $_POST['url'];
			foreach($urls as $u){
				$url[] = str_replace('http://', '', $u);
			}
			$linked = array_combine($keys, $url);
			update_option("linked_keywords", $linked);
			update_option("linked_options", $_POST['linked_options']);
			

			$_POST['notice'] = $err . __('Keywords Updated.','linked');
		}	
		
		function JQuery(){
			?>
<script type="text/javascript">
<!--
// set_add_del makes sure that we dont show a [-] button 
// if there is only 1 element
// and that we only show a [+] button on the last element
function set_add_del(){
	jQuery('.remove_cat').show();
	jQuery('.add_cat').hide();
	jQuery('.add_cat:last').show();
	jQuery(".category_block:only-child > .remove_cat").hide();
}
function selrem(clickety){
	jQuery(clickety).parent().remove(); 
	set_add_del(); 
	return false;
}
function seladd(clickety){
	jQuery('.category_block:last').after(
    	jQuery('.category_block:last').clone());
	jQuery('.category_block:last input').attr('value', '');

	set_add_del(); 
	return false;
}
jQuery(document).ready(function(){
	set_add_del();
});
-->
</script>
<style type="text/css">
.keyword{
	width: 120px;
}
.url{
	width:280px;
}
</style>
            <?php
		}
		function LinkedPanel(){
			if( $_POST['notice'] )
				echo '<div id="message" class="updated fade"><p><strong>' . $_POST['notice'] . '</strong></p></div>';
				
			$plugin_url = trailingslashit(get_option('siteurl')) . 'wp-content/plugins/' . basename(dirname(__FILE__)) .'/';
			$keywords = get_option('linked_keywords');
			foreach($keywords as $key=>$url){
				$row .= '<div class="category_block"><input type="text" name="keyword[]" value="' . $key . '" class="keyword" /> <strong>=></strong> <input type="text" name="url[]" value="' . $url . '" class="url" /><a href="#" onClick="return selrem(this);" class="remove_cat"><img src="'. $plugin_url . 'removeBtn.gif" alt="' . __("Remove Row","linked") . '" title="' . __("Remove Row","linked") . '" /></a> <a href="#" onClick="return seladd(this);" class="add_cat"><img src="'. $plugin_url . 'addBtn.gif" alt="' .  __("Add Row","linked") . '" title="' . __("Add Row","linked") . '" /></a></div>';
			}
			?>
            <div class="wrap">
            	<h2><?php _e('Linked Keywords', 'linked')?></h2>
                <form method="post" action="">
                	<?php if( function_exists( 'wp_nonce_field' )) wp_nonce_field( 'linked-update-options'); ?>
                    <table class="form-table" id="product_categories">
                        <tbody>
                        	<tr valign="top" class="fields">
                       			 <th scope="row"><?php _e('Keyword => URL:', 'linked');?></th>
                        		<td>
                                <?php if($row){echo $row;} else{?>
                                <div class="category_block"> 
                                <input type="text" name="keyword[]" value="" class="keyword" /> <strong>=></strong> <input type="text" name="url[]" value="" class="url" /><a href="#" onClick="return selrem(this);" class="remove_cat"><img src="<?php echo $plugin_url; ?>removeBtn.gif" alt="<?php _e("Remove Row","linked")?>" title="<?php _e("Remove Row","linked")?>" /></a>
						<a href="#" onClick="return seladd(this);" class="add_cat"><img src="<?php echo $plugin_url; ?>addBtn.gif" alt="<?php _e("Add Row","linked")?>" title="<?php _e("Add Row","linked")?>" /></a>
                                </div>
                                <?php } ?>
                                </td>
                        	</tr>
                            
                            <tr valign="top">
                            	<th scope="row"><?php _e('Auto Link:', 'linked');?></th>
                                <td><label><input type="checkbox" name="linked_options[]" value="email" <?php if( in_array('email', get_option('linked_options')) ) echo 'checked="checked"';?> /> Email Addresses</label><br />
                                <label><input type="checkbox" name="linked_options[]" value="url" <?php if( in_array('url', get_option('linked_options')) ) echo 'checked="checked"';?> /> URLs</label><br />
                                <label><input type="checkbox" name="linked_options[]" value="post" <?php if( in_array('post', get_option('linked_options')) ) echo 'checked="checked"';?> /> Post/Page Titles</label><br />
                                <label><input type="checkbox" name="linked_options[]" value="category" <?php if( in_array('category', get_option('linked_options')) ) echo 'checked="checked"';?> /> Category Titles</label></td>
                            </tr>

                        </tbody>
                 	</table>
                    <p class="submit"><input name="Submit" value="<?php _e('Save Changes','linked');?>" type="submit" />
                    <input name="action" value="linked_update" type="hidden" />
                </form>
            </div>
            <?php
		}
		
		function PostTitles(){
			global $wpdb;
			$posts = $wpdb->get_results("SELECT ID,post_title FROM $wpdb->posts WHERE post_status='publish' AND (post_type='page' OR post_type='post')");
			return $posts;
		}
		
		function CategoryTitles(){
			global $wpdb;
			$cats = wp_list_categories('title_li=&style=&echo=0');
			//<a href="http://www.farnelldevelopments.com/category/uncategorized/" title="View all posts filed under Uncategorized">Uncategorized</a><br>
			$cats = explode('<br>', $cats);
			foreach($cats as $cat){
				$url[] = $cat;
				$title = explode('">', $cat);
				$title = explode('</a>',$title[1]);
				$title = $title[0];
				$cat_title[] = $title;
			}
			$output = array_combine($cat_title, $url);
			return $output;
		}
		
		function MakeLinks($content){			
			
			foreach(get_option('linked_keywords') as $key=>$url){
				$replace = ' <a href="http://' . $url . '">' . $key . '</a> ';
				$pattern = '/(' . $key . ')[\s\.<]/';
				$content = preg_replace($pattern, $replace, $content);
			}
			if( in_array('post', get_option("linked_options")) ){
				foreach($this->PostTitles() as $p){
					$replace = ' <a href="' . get_permalink($p->ID) . '">' . $p->post_title . '</a> ';
					$pattern = '/(' . $p->post_title . ')[\s\.<]/';
					$content = preg_replace($pattern, $replace, $content);
				}
			}
			if( in_array('category', get_option("linked_options")) ){
				foreach($this->CategoryTitles() as $title => $link){
					$replace = ' ' . $link . ' ';
					$pattern = '/(' . $title . ')[\s\.<]/';
					$content = preg_replace($pattern, $replace, $content);
				}
			}
			if( in_array('email', get_option("linked_options")) ){
				//Email Links
				$content = preg_replace('((>|\s)([a-zA-Z0-9_\.]+@[a-zA-Z_]+?\.[a-zA-Z]{2,6}))', '<a href="mailto:$2">$2</a>', $content);
			}
			if( in_array('url', get_option("linked_options")) ){
				//WWW links
				$content = preg_replace('((>|\s)([a-zA-Z0-9]+\.[a-zA-Z_]+?\.[a-zA-Z]{2,6}))', '<a href="http://$2">$2</a>', $content);
				//http links
				$content = preg_replace('((>|\s)(http:\/\/[a-zA-Z0-9]+(\.[a-zA-Z_]+)+))', '<a href="$2">$2</a>', $content);
			}
			
			return $content;
		}
	}
}// END Class LinkedPlugin

if( class_exists('LinkedPlugin') )
	$linked = new LinkedPlugin();
	
if( !function_exists('array_combine') ){
	function array_combine($arr1,$arr2) {
	   $out = array();
	   foreach($arr1 as $key1 => $value1){
			$out[$value1] = $arr2[$key1];
	   }
	   return $out;
	} 
}
?>