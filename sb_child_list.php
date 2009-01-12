<?php

/*
 Plugin Name: SB Child List
 Description: Plugin which enables a page/post hook to show a list of the child posts or pages. IE if you you a page called articles and then a load of articles below then maybe you want to show the child article titles on the articles page. This does that for you!
 Author: Sean Barton, Cambridge New Media Services
 Plugin URI: http://www.sean-barton.co.uk
 Version: 1.0

 Changelog:
 0.1:	Basic functionality.
 0.5:	Admin Page added.
 0.9:	Templating and nest limiting.
 1.0:	Added backlink from child to parent.
 */

$sb_dir = str_replace('\\', '/', dirname(__FILE__));
$sb_file = str_replace('\\', '/', __FILE__);

register_activation_hook($sb_file, 'sb_activate');
register_deactivation_hook($sb_file, 'sb_deactivate');

function sb_activate() {
	if (!get_option('sb_child_list_settings')) {
		$obj = new StdClass();
		$obj->child_list_start = '<ul>';
		$obj->child_list_loop_start = '<li>';
		$obj->child_list_loop_content = '<a href="[post_permalink]">[post_title]</a>';
		$obj->child_list_loop_end = '</li>';
		$obj->child_list_end = '</ul>';

		$obj->child_list_parent_link = '<div><a href="[post_permalink]">[post_title]</a></div>';

		add_option('sb_child_list_settings', $obj);
	}
}

function sb_deactivate() {
	//Do we really want to do this? Lets not for now
	//delete_option('sb_child_list_settings');
}

function sb_render_child_list($id, $settings, $nest_level=0) {
	global $wpdb;

	$return = false;
	$nest_level++;

	$sql = 'SELECT ID, post_title, post_type
			FROM ' . $wpdb->posts . '
			WHERE
				post_status = \'publish\'
				AND post_parent = ' . $id;

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
				$template = str_replace('[post_permalink]', $p->guid, $template);

				$return .= $template;

				if (!$settings->child_list_nesting_level || $settings->child_list_nesting_level <= $nest_level) {
					$return .= sb_render_child_list($child->ID, $settings, $nest_level);
				}

				$return .= $settings->child_list_loop_end;
			}
		}

		$return .= $settings->child_list_end;
	}

	return $return;
}

function sb_display_feedback($msg) {
	echo '<div id="message" class="updated fade" style="margin-top: 5px; padding: 7px;">' . $msg . '</div>';
}

function sb_display_error($msg) {
	echo '<div id="error" class="error" style="margin-top: 5px; padding: 7px;">' . $msg . '</div>';
}

function sb_filter_post($atts) {
	/*	extract(shortcode_atts(array(
		'foo' => 'no foo',
		'bar' => 'default bar',
		), $atts)); // An extension for later maybe */

	$settings = get_option('sb_child_list_settings');
	return sb_render_child_list(get_the_id(), $settings);
}

function sb_filter_post_parent($atts) {
	return sb_render_parent(get_the_id());
}

function sb_render_parent($child_id) {
	global $wpdb;

	$settings = get_option('sb_child_list_settings');
	$page = get_page($child_id);
	$return = false;

	if ($parent_id = $page->post_parent) {
		$parent = get_page($parent_id);
		$return = $settings->child_list_parent_link;

		$return = str_replace('[post_title]', $parent->post_title, $return);
		$return = str_replace('[post_permalink]', $parent->guid, $return);
	}

	return $return;
}

function sb_init_admin_page() {
	global $sb_file;
	add_options_page('SB Child List Options', 'SB Child List', 8, $sb_file, 'sb_admin_page');
}

function sb_admin_page() {
	$settings = get_option('sb_child_list_settings');
	$detail_style = 'margin: 5px 0 5px 0; color: gray; width: 160px; font-size: 10px;';

	echo '<div class="wrap" id="poststuff">';

	if (sb_post('submit_settings')) {
		foreach (sb_post('settings') as $key=>$value) {
			$settings->$key = stripcslashes($value);
		}

		if (update_option('sb_child_list_settings', $settings)) {
			sb_display_feedback(__('Settings have been updated', 'sb'));
		}
	}

	echo sb_start_box('SB Child List Options');

	echo '<form method="POST">';
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
					<div style="' . $detail_style . '">' . __('Template for the loop part of the list. Use the hooks [post_title] and [post_permalink].', 'sb') . '</div>
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

	echo '	<tr>
				<td colspan="2" style="text-align: right;">
					<input type="submit" name="submit_settings" value="' . __('Update Settings', 'sb') . '" class="button" />
				</td>
			</tr>';

	echo '</table>';
	echo '</form>';

	echo sb_end_box();
	echo '<div>';
}

function sb_post($key, $default='', $strip_tags=false) {
	return sb_get_global($_POST, $key, $default, $strip_tags);
}

function sb_get($key, $default='', $strip_tags=false) {
	return sb_get_global($_GET, $key, $default, $strip_tags);
}

function sb_request($key, $default='', $strip_tags=false) {
	return sb_get_global($_REQUEST, $key, $default, $strip_tags);
}

function sb_get_global($array, $key, $default='', $strip_tags) {
	if (isset($array[$key])) {
		$default = $array[$key];

		if ($strip_tags) {
			$default = strip_tags($default);
		}
	}

	return $default;
}

function sb_start_box($title , $return=true){

	$html = '	<div class="postbox" style="margin: 5px 0px;">
					<h3>' . $title . '</h3>
					<div class="inside">';

	if ($return) {
		return $html;
	} else {
		echo $html;
	}
}

function sb_end_box($return=true) {
	$html = '</div>
		</div>';

	if ($return) {
		return $html;
	} else {
		echo $html;
	}
}

function sb_loaded() {
	add_shortcode('sb_child_list', 'sb_filter_post');
	add_shortcode('sb_parent', 'sb_filter_post_parent');

	//actions
	add_action('admin_menu', 'sb_init_admin_page');
}

add_action('plugins_loaded', 'sb_loaded');

?>