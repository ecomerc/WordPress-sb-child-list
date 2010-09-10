<?php

/*
 Plugin Name: SB Child List
 Description: Plugin which enables a page/post hook to show a list of the child posts or pages. IE if you you a page called articles and then a load of articles below then maybe you want to show the child article titles on the articles page. This does that for you!
 Author: Sean Barton
 Plugin URI: http://www.sean-barton.co.uk
 Author URI: http://www.sean-barton.co.uk
 Version: 1.3

 Changelog:
 0.1:	Basic functionality.
 0.5:	Admin Page added.
 0.9:	Templating and nest limiting.
 1.0:	Added backlink from child to parent.
 1.1:	Added sb_cl_cat_list functionality
 1.2:	Now using get_permalink for the child list. Means the guid field is no longer relied on and links always work
 1.3:	Added post_thumb to the templating system. Uses the WP Post Thumbnail system. Contributed by a plugin user.
 */

$sb_cl_dir = str_replace('\\', '/', dirname(__FILE__));
$sb_cl_file = str_replace('\\', '/', __FILE__);

register_activation_hook($sb_cl_file, 'sb_cl_activate');
register_deactivation_hook($sb_cl_file, 'sb_cl_deactivate');

function sb_cl_activate() {
	sb_cl_get_settings();
}

function sb_cl_get_settings() {
	if (!$settings = get_option('sb_child_list_settings')) {
		$obj = new StdClass();
		$obj->child_list_start = '<ul>';
		$obj->child_list_loop_start = '<li>';
		$obj->child_list_loop_content = '<a href="[post_permalink]">[post_title]</a>';
		$obj->child_list_loop_end = '</li>';
		$obj->child_list_end = '</ul>';
		
		$obj->cat_list_start = '<ul>';
		$obj->cat_list_loop = '<li><a href="[post_permalink]">[post_title]</a></li>';
		$obj->cat_list_end = '</ul>';

		$obj->child_list_parent_link = '<div><a href="[post_permalink]">[post_title]</a></div>';
		$obj->child_list_nesting_level = 2;

		update_option('sb_child_list_settings', $obj);
		
		$settings = $obj;
	}
	
	return $settings;
}

function sb_cl_update_settings() {
	if (sb_cl_post('submit_settings')) {
		foreach (sb_cl_post('settings') as $key=>$value) {
			$settings->$key = stripcslashes($value);
		}

		if (update_option('sb_child_list_settings', $settings)) {
			sb_cl_display_feedback(__('Settings have been updated', 'sb'));
		}
	}
}

function sb_cl_deactivate() {
	//Do we really want to do this? Lets not for now
	//delete_option('sb_cl_child_list_settings');
}

function sb_cl_render_cat_list($category, $limit=false) {
	global $wp_query, $posts;
	
	if (!$limit) {
		$limit = 1000;
	}
	
	$temp_query = $wp_query;
	
	$settings = sb_cl_get_settings();
	$cat_id = sb_cl_get_cat_id_from_name($category);
        $qs = "cat=" . $cat_id . '&post_status=publish';
        $qs .= '&posts_per_page=' . $limit;
	
        $cat_posts = new WP_Query($qs);
        
	$html .= $settings->cat_list_start;
	
	while ($cat_posts->have_posts()) {
		$cat_posts->the_post();
		update_post_caches($posts);
		
		ob_start();
		the_permalink();
		$permalink = ob_get_clean();
		
                
		$template = $settings->cat_list_loop;
		$template = str_replace('[post_title]', get_the_title(), $template);
		$template = str_replace('[post_permalink]', $permalink, $template);
		$template = str_replace('[post_thumb]', get_the_post_thumbnail( $child->ID, 'thumbnail', array('class' => 'alignleft')), $template);

		$html .= $template;
	}
	
	$html .= $settings->cat_list_end;
	
	$wp_query = $temp_query;
	rewind_posts();
	the_post();
	
	return $html;
}

function sb_cl_get_cat_id_from_name($cat) {
        global $wpdb;
        
        $sql = 'SELECT term_id
                FROM ' . $wpdb->prefix . 'terms
                WHERE
                    name LIKE "' . mysql_real_escape_string($cat) . '"
                    OR slug LIKE "' . mysql_real_escape_string($cat) . '"
        ';
        $cat_id = $wpdb->get_var($sql);
        
        return $cat_id;
    }
    
function sb_cl_render_child_list($id=false, $nest_level=0) {
	global $wpdb;

	$return = false;
	$nest_level++;
	$settings = sb_cl_get_settings();
	if (!$id) {
		$id = get_the_ID();
	}

	$sql = 'SELECT ID, post_title, post_type
			FROM ' . $wpdb->posts . '
			WHERE
				post_status = \'publish\'
				AND post_parent = ' . $id . '
				AND post_type = \'page\'
			ORDER BY
				menu_order
				, post_title';

	if ($children = $wpdb->get_results($sql)) {
		$return .= $settings->child_list_start;

		foreach ($children as $i=>$child) {
			if ($child->post_type == 'post') {
				$p = get_post($child->ID);
			} else if ($child->post_type == 'page') {
				$p = get_page($child->ID);
			}

			if ($p) {
				$return .= $settings->child_list_loop_start;

				$template = $settings->child_list_loop_content;
				$template = str_replace('[post_title]', $p->post_title, $template);
				$template = str_replace('[post_permalink]', get_permalink($child->ID), $template);
				$template = str_replace('[post_thumb]', get_the_post_thumbnail( $child->ID, 'thumbnail', array('class' => 'alignleft')), $template);

				$return .= $template;

				if (!$settings->child_list_nesting_level || $nest_level < $settings->child_list_nesting_level) {
					$return .= sb_cl_render_child_list($child->ID, $settings, $nest_level);
				}

				$return .= $settings->child_list_loop_end;
			}
		}

		$return .= $settings->child_list_end;
	}

	return $return;
}

function sb_cl_display_feedback($msg) {
	echo '<div id="message" class="updated fade" style="margin-top: 5px; padding: 7px;">' . $msg . '</div>';
}

function sb_cl_display_error($msg) {
	echo '<div id="error" class="error" style="margin-top: 5px; padding: 7px;">' . $msg . '</div>';
}

function sb_cl_filter_post($atts, $content, $tag) {
	$return = '';

	switch ($tag) {
		case 'sb_child_list':
			$return = sb_cl_render_child_list();
			break;
		case 'sb_cat_list':
			$return = sb_cl_render_cat_list($atts['category'], $atts['limit']);
			break;
		case 'sb_parent':
			$return = sb_cl_render_parent();
			break;
	}
	
	return $return;
}

function sb_cl_render_parent($child_id=false) {
	global $wpdb;

	$settings = sb_cl_get_settings();
	$page = get_page($child_id);
	$return = false;
	if (!$child_id) {
		$child_id = get_the_ID();
	}

	if ($parent_id = $page->post_parent) {
		$parent = get_page($parent_id);
		$return = $settings->child_list_parent_link;

		$return = str_replace('[post_title]', $parent->post_title, $return);
		$return = str_replace('[post_permalink]', $parent->guid, $return);
	}

	return $return;
}

function sb_cl_init_admin_page() {
	global $sb_cl_file;
	add_options_page('SB Child List Options', 'SB Child List', 8, $sb_cl_file, 'sb_cl_admin_page');
}

function sb_cl_admin_page() {
	sb_cl_update_settings();
	
	$settings = sb_cl_get_settings();
	$detail_style = 'margin: 5px 0 5px 0; color: gray; width: 160px; font-size: 10px;';

	echo '<div class="wrap" id="poststuff">';

	echo '<form method="POST">';
	
	sb_cl_start_box('SB Child List Options');

	echo '<table style="width: 100%;">';

	echo '	<tr>
				<td style="vertical-align: top;">
					<div>' . __('Child List Start Template', 'sb') . '</div>
					<div style="' . $detail_style . '">' . __('Template for the start of the list', 'sb') . '</div>
				</td>
				<td style="vertical-align: top;">
					<textarea rows="2" cols="70" name="settings[child_list_start]">' . wp_specialchars($settings->child_list_start, true) . '</textarea>
				</td>
			</tr>';

	echo '	<tr>
				<td style="vertical-align: top;">
					<div>' . __('Child List Loop Start', 'sb') . '</div>
					<div style="' . $detail_style . '">' . __('Template for the start of the loop', 'sb') . '</div>
				</td>
				<td style="vertical-align: top;">
					<textarea rows="2" cols="70" name="settings[child_list_loop_start]">' . wp_specialchars($settings->child_list_loop_start, true) . '</textarea>
				</td>
			</tr>';

	echo '	<tr>
				<td style="vertical-align: top;">
					<div>' . __('Child List Loop Content', 'sb') . '</div>
					<div style="' . $detail_style . '">' . __('Template for the loop part of the list. Use the hooks [post_title], [post_thumb] and [post_permalink].', 'sb') . '</div>
				</td>
				<td style="vertical-align: top;">
					<textarea rows="2" cols="70" name="settings[child_list_loop_content]">' . wp_specialchars($settings->child_list_loop_content, true) . '</textarea>
				</td>
			</tr>';

	echo '	<tr>
				<td style="vertical-align: top;">
					<div>' . __('Child List Loop End', 'sb') . '</div>
					<div style="' . $detail_style . '">' . __('Template for the end of the loop', 'sb') . '</div>
				</td>
				<td style="vertical-align: top;">
					<textarea rows="2" cols="70" name="settings[child_list_loop_end]">' . wp_specialchars($settings->child_list_loop_end, true) . '</textarea>
				</td>
			</tr>';

	echo '	<tr>
				<td style="vertical-align: top;">
					<div>' . __('Child List End Template', 'sb') . '</div>
					<div style="' . $detail_style . '">' . __('Template for the end of the list', 'sb') . '</div>
				</td>
				<td style="vertical-align: top;">
					<textarea rows="2" cols="70" name="settings[child_list_end]">' . wp_specialchars($settings->child_list_end, true) . '</textarea>
				</td>
			</tr>';

	echo '	<tr>
				<td style="vertical-align: top;">
					<div>' . __('Child List Nesting Level', 'sb') . '</div>
					<div style="' . $detail_style . '">' . __('The number of levels to nest to. A level is defined as a post/page having children. Two levels of nesting can be defined as a child post also being a parent.', 'sb') . '</div>
				</td>
				<td style="vertical-align: top;">
					<select style="width: 100px;" name="settings[child_list_nesting_level]">';

	for ($i = 0; $i <= 5; $i++) {
		echo '<option value="' . $i . '" ' . ($settings->child_list_nesting_level == $i ? 'selected="selected"':'') . '>' . ($i ? $i:__('All Levels', 'sb')) . '</option>';
	}

	echo '		</td>
			</tr>';

	echo '	<tr>
			<td style="vertical-align: top;">
				<div>' . __('Child List Parent Link', 'sb') . '</div>
				<div style="' . $detail_style . '">' . __('Template for the block to show when a page has a parent', 'sb') . '</div>
			</td>
			<td style="vertical-align: top;">
				<textarea rows="5" cols="70" name="settings[child_list_parent_link]">' . wp_specialchars($settings->child_list_parent_link, true) . '</textarea>
			</td>
		</tr>';
		
	//----------- start cat list options		
		
	echo '	<tr>
				<td style="vertical-align: top;">
					<div>' . __('Category List Start Template', 'sb') . '</div>
					<div style="' . $detail_style . '">' . __('Template for the start of the category list', 'sb') . '</div>
				</td>
				<td style="vertical-align: top;">
					<textarea rows="2" cols="70" name="settings[cat_list_start]">' . wp_specialchars($settings->cat_list_start, true) . '</textarea>
				</td>
			</tr>';

	echo '	<tr>
				<td style="vertical-align: top;">
					<div>' . __('Category List Loop', 'sb') . '</div>
					<div style="' . $detail_style . '">' . __('Template for the loop part of the category list. Use the hooks [post_title] and [post_permalink].', 'sb') . '</div>
				</td>
				<td style="vertical-align: top;">
					<textarea rows="2" cols="70" name="settings[cat_list_loop]">' . wp_specialchars($settings->cat_list_loop, true) . '</textarea>
				</td>
			</tr>';

	echo '	<tr>
				<td style="vertical-align: top;">
					<div>' . __('Category List End Template', 'sb') . '</div>
					<div style="' . $detail_style . '">' . __('Template for the end of the category list', 'sb') . '</div>
				</td>
				<td style="vertical-align: top;">
					<textarea rows="2" cols="70" name="settings[cat_list_end]">' . wp_specialchars($settings->cat_list_end, true) . '</textarea>
				</td>
			</tr>';
			
	//----------- end cat list options

	echo '	<tr>
			<td colspan="2" style="text-align: right;">
				<input type="submit" name="submit_settings" value="' . __('Update Settings', 'sb') . '" class="button" />
			</td>
		</tr>';

	echo '</table>';

	sb_cl_end_box();
	
	echo '</form>';	
	echo '</div>';
}

function sb_cl_post($key, $default='', $strip_tags=false) {
	return sb_cl_get_global($_POST, $key, $default, $strip_tags);
}

function sb_cl_get($key, $default='', $strip_tags=false) {
	return sb_cl_get_global($_GET, $key, $default, $strip_tags);
}

function sb_cl_request($key, $default='', $strip_tags=false) {
	return sb_cl_get_global($_REQUEST, $key, $default, $strip_tags);
}

function sb_cl_get_global($array, $key, $default='', $strip_tags) {
	if (isset($array[$key])) {
		$default = $array[$key];

		if ($strip_tags) {
			$default = strip_tags($default);
		}
	}

	return $default;
}

function sb_cl_start_box($title , $return=false){

	$html = '	<div class="postbox" style="margin: 5px 0px;">
					<h3>' . $title . '</h3>
					<div class="inside">';

	if ($return) {
		return $html;
	} else {
		echo $html;
	}
}

function sb_cl_end_box($return=false) {
	$html = '</div>
		</div>';

	if ($return) {
		return $html;
	} else {
		echo $html;
	}
}

function sb_cl_loaded() {
	add_shortcode('sb_child_list', 'sb_cl_filter_post');
	add_shortcode('sb_cat_list', 'sb_cl_filter_post');
	add_shortcode('sb_parent', 'sb_cl_filter_post');

	//actions
	add_action('admin_menu', 'sb_cl_init_admin_page');
}

add_action('plugins_loaded', 'sb_cl_loaded');

?>