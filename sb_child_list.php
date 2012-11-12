<?php

/*
 Plugin Name: SB Child List
 Description: The total in-page navigation solution for Wordpress. Using the shortcodes and widgets provided you can display navigation between your parent, child and sibling items in any format you can think of.
 Author: Sean Barton
 Plugin URI: http://www.sean-barton.co.uk
 Author URI: http://www.sean-barton.co.uk
 Version: 3.3

 Changelog:
 0.1:	Basic functionality.
 0.5:	Admin Page added.
 0.9:	Templating and nest limiting.
 1.0:	Added backlink from child to parent.
 1.1:	Added sb_cl_cat_list functionality
 1.2:	Now using get_permalink for the child list. Means the guid field is no longer relied on and links always work
 1.3:	Added post_thumb to the templating system. Uses the WP Post Thumbnail system. Contributed by a plugin user.
 1.4:	Fixed post_thumb option whereby the function didn't exist on some installs. Uses the get_the_post_thumb function to operate
 1.5:	Updated sb_parent permalink from guid to get_permalink
 1.6:	Added templating for the shortcodes (multiple instances of the shortcode in different formats now possible) and support for the_excerpt and SB Uploader output (custom fields called post_image and post_image2 will be recognised)
 1.7:	Forced page excerpt support in case it wasn't already added. Added tooltip for post_excerpt
 1.8:	Added ability to sort a child list by any field in the wp_posts table by adding order="field_name" to the shortcode
 1.9:	Added child list widget to show sub pages of current page or any other page of your choice.
 2.0: 	Fixed widget title issue whereby the title was being changed to 1,2,3 depending on the template used.
 2.1:	Child list and widget now shows ancestors if there are no children. Added parent link option to widget
 2.2:	Fixed issue with siblings showing in normal child list and then repeating themselves breaking the site.
 2.3:	Added two new shortcodes sb_sibling_next and sb_sibling_prev. Kind of like next and previous navigation for posts. Uses menu order for display followed by alphabetical post titles.
 2.4:	Added sb_grandparent so that you can feature one more level of parentage as a link back. Added getText format on "Back to" text for localisation.
 2.5:	When [post_class] is used and the item relates to the current page then a classname will be added: 'current_page_item sb_cl_current_page' to allow you to style individual rows using CSS making the current page stand out perhaps.
 2.6:	Added custom excerpt function so that when using [post_excerpt] in the template if you don't enter a manual one it will generate it from the post body as Wordpress does normally.
 2.7:	Minor update, added support for qTranslate
 2.8:	Minor update, added support for excerpt more tag if used.
 2.9:	Minor Update, added order parameter to sb_cat_list shortcode. Default ordering to post title.
 3.0:	Minor update. Added ability to fix the parent ID of a child using parent_id="" in sb_child_list shortcode
 3.1:	Minor update. Added template settings shortcode [post_thumb_url] and removed the default link to large image around [post_thumb]. Allows you to set up your own link around the thumb to go wherever you like.. be it larger image or the post itself
 3.2:	Bug Fix update. [post_image] didn't work from my 3.1 update because of the logic using the incorrect post ID. Sorted now.
 3.3:	Minor update. Added new option to turn off the siblings list on the lowest level. This means that when you get to the bottom of the page tree the child list will disappear if this option is utilised
 */

$sb_cl_dir = str_replace('\\', '/', dirname(__FILE__));
$sb_cl_file = str_replace('\\', '/', __FILE__);
$sb_cl_max_templates = 3;

register_activation_hook($sb_cl_file, 'sb_cl_activate');
register_deactivation_hook($sb_cl_file, 'sb_cl_deactivate');

add_post_type_support( 'page', 'excerpt' );

function sb_cl_activate() {
	sb_cl_get_settings();
}

function sb_cl_get_settings() {
	if (!$settings = get_option('sb_child_list_settings')) {
		$obj = new StdClass();
		$obj->child_list_start = '<ul>';
		$obj->child_list_loop_start = '<li>';
		$obj->child_list_loop_content = '<a href="[post_permalink]" class="[post_class]">[post_title]</a>';
		$obj->child_list_loop_end = '</li>';
		$obj->child_list_end = '</ul>';
		
		$obj->child_list_start_2 = '<ul>';
		$obj->child_list_loop_start_2 = '<li>';
		$obj->child_list_loop_content_2 = '<a href="[post_permalink]" class="[post_class]">[post_title]</a>';
		$obj->child_list_loop_end_2 = '</li>';
		$obj->child_list_end_2 = '</ul>';
		
		$obj->child_list_start_3 = '<ul>';
		$obj->child_list_loop_start_3 = '<li>';
		$obj->child_list_loop_content_3 = '<a href="[post_permalink]" class="[post_class]">[post_title]</a>';
		$obj->child_list_loop_end_3 = '</li>';
		$obj->child_list_end_3 = '</ul>';		
		
		$obj->cat_list_start = '<ul>';
		$obj->cat_list_loop = '<li><a href="[post_permalink]">[post_title]</a></li>';
		$obj->cat_list_end = '</ul>';
		
		$obj->cat_list_start_2 = '<ul>';
		$obj->cat_list_loop_2 = '<li><a href="[post_permalink]">[post_title]</a></li>';
		$obj->cat_list_end_2 = '</ul>';
		
		$obj->cat_list_start_3 = '<ul>';
		$obj->cat_list_loop_3 = '<li><a href="[post_permalink]">[post_title]</a></li>';
		$obj->cat_list_end_3 = '</ul>';		

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

function sb_cl_get_the_excerpt($id=false) {
      global $post;

      $old_post = $post;
      if ($id != $post->ID) {
	  $post = get_page($id);
      }

      if (!$excerpt = trim($post->post_excerpt)) {
	  $excerpt = $post->post_content;
	  
	  if (!$more_pos = strpos($excerpt, '<!--more-->')) {
		if (function_exists('qtrans_useCurrentLanguageIfNotFoundUseDefaultLanguage')) {
		      $excerpt = qtrans_useCurrentLanguageIfNotFoundUseDefaultLanguage($excerpt);
		}
		
		$excerpt = strip_shortcodes( $excerpt );
		$excerpt = apply_filters('the_content', $excerpt);
		$excerpt = str_replace(']]>', ']]&gt;', $excerpt);
		$excerpt = strip_tags($excerpt);
		$excerpt_length = apply_filters('excerpt_length', 55);
		$excerpt_more = apply_filters('excerpt_more', ' ' . '[...]');
      
		$words = preg_split("/[\n\r\t ]+/", $excerpt, $excerpt_length + 1, PREG_SPLIT_NO_EMPTY);
		if ( count($words) > $excerpt_length ) {
		    array_pop($words);
		    $excerpt = implode(' ', $words);
		    $excerpt = $excerpt . $excerpt_more;
		} else {
		    $excerpt = implode(' ', $words);
		}
	  } else {
		$excerpt = substr($excerpt, 0, $more_pos);
	  }
      }

      $post = $old_post;

      return $excerpt;
  }

function sb_cl_render_cat_list($category, $limit=false, $order=false, $template_id = 0) {
	global $wp_query, $posts;
	
	$settings = sb_cl_get_settings();
	
	if (!$limit) {
		$limit = 1000;
	}
	
	if (!trim($order)) {
		$order = 'post_title';
	}
	
	if ($template_id <= 1) {
		$template_start = $settings->cat_list_start;
		$template_loop = $settings->cat_list_loop;
		$template_end = $settings->cat_list_end;
	} else {
		$func = 'cat_list_start_' . $template_id;
		$template_start = $settings->$func;
		$func = 'cat_list_loop_' . $template_id;
		$template_loop = $settings->$func;
		$func = 'cat_list_end_' . $template_id;
		$template_end = $settings->$func;		
	}
	
	$temp_query = $wp_query;
	
	$cat_id = sb_cl_get_cat_id_from_name($category);
        $qs = "cat=" . $cat_id . '&post_status=publish';
        $qs .= '&posts_per_page=' . $limit;
	$qs .= '&orderby=' . $order;
	
        $cat_posts = new WP_Query($qs);
        
	$html .= $template_start;
	
	while ($cat_posts->have_posts()) {
		$cat_posts->the_post();
		update_post_caches($posts);
		
		$id = get_the_ID();
		
		ob_start();
		the_permalink();
		$permalink = ob_get_clean();
		
		$template = $template_loop;
		$template = str_replace('[post_title]', get_the_title(), $template);
		$template = str_replace('[post_excerpt]', sb_cl_get_the_excerpt(), $template);
		$template = str_replace('[post_permalink]', $permalink, $template);
		
		$post_image = get_post_meta($id, 'post_image', true);
		$post_image2 = get_post_meta($id, 'post_image2', true);
		$template = str_replace('[post_image]', ($post_image ? '<img class="list_post_item" src="' . $post_image . '" />':''), $template);
		$template = str_replace('[post_image2]', ($post_image2 ? '<img class="list_post_item" src="' . $post_image2 . '" />':''), $template);
		
		if (function_exists('get_the_post_thumbnail')) {
			//$template = str_replace('[post_thumb]', get_the_post_thumbnail( $id, 'thumbnail', array('class' => 'alignleft')), $template);
			
			$thumb = $large_image_url = '';
			if ( has_post_thumbnail()) {
			  $large_image_url = wp_get_attachment_image_src( get_post_thumbnail_id($id), 'large');
			  $thumb .= get_the_post_thumbnail($id, 'thumbnail', array('class' => 'alignleft')); 
			}
			
			$template = str_replace('[post_thumb]', $thumb, $template);
			$template = str_replace('[post_thumb_url]', $large_image_url, $template);
		}		

		$html .= $template;
	}
	
	$html .= $template_end;
	
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
    
function sb_cl_render_child_list($template_id = 1, $id=false, $nest_level=0, $order=false) {
	global $wpdb, $wp_query;
	
	$this_page_id = $wp_query->get_queried_object_id();

	$return = false;
	$nest_level++;
	$settings = sb_cl_get_settings();
	
	if (!trim($order)) {
		$order = 'menu_order, post_title';
	}
	
	if ($template_id <= 1) {
		$template_start = $settings->child_list_start;
		$template_start_loop = $settings->child_list_loop_start;
		$template_content = $settings->child_list_loop_content;
		$template_end_loop = $settings->child_list_loop_end;
		$template_end = $settings->child_list_end;
	} else {
		$func = 'child_list_start_' . $template_id;
		$template_start = $settings->$func;
		$func = 'child_list_loop_start_' . $template_id;
		$template_start_loop = $settings->$func;
		$func = 'child_list_loop_content_' . $template_id;
		$template_content = $settings->$func;
		$func = 'child_list_loop_end_' . $template_id;
		$template_end_loop = $settings->$func;
		$func = 'child_list_end_' . $template_id;
		$template_end = $settings->$func;		
	}
	
	if (!$id) {
		$id = get_the_ID();
	}
	
	if (!$id) {
		return; //in the event the $id variable is still empty.
	}
	
	$sql = 'SELECT ID, post_title, post_type
			FROM ' . $wpdb->posts . '
			WHERE
				post_status = \'publish\'
				AND post_parent = ' . $id . '
				AND post_type = \'page\'
			ORDER BY ' . $order;

	if ($children = $wpdb->get_results($sql)) {
		$return .= $template_start;

		foreach ($children as $i=>$child) {
			if ($child->post_type == 'post') {
				$p = get_post($child->ID);
			} else {
				$p = get_page($child->ID);
			}
			
			if ($p) {
				$post_class = '';
				$return .= $template_start_loop;
				
				if ($p->ID == $this_page_id) {
					$post_class = 'current_page_item sb_cl_current_page';
				}
				
				$title = $p->post_title;
				
				if (function_exists('qtrans_useCurrentLanguageIfNotFoundUseDefaultLanguage')) {
				      $title = qtrans_useCurrentLanguageIfNotFoundUseDefaultLanguage($title);
				}				

				$template = $template_content;
				$template = str_replace('[post_title]', $title, $template);
				$template = str_replace('[post_class]', $post_class, $template);
				$template = str_replace('[post_excerpt]', sb_cl_get_the_excerpt($p->ID), $template);
				
				$post_image = get_post_meta($child->ID, 'post_image', true);
				$post_image2 = get_post_meta($child->ID, 'post_image2', true);
				$template = str_replace('[post_image]', ($post_image ? '<img class="list_post_item" src="' . $post_image . '" />':''), $template);
				$template = str_replace('[post_image2]', ($post_image2 ? '<img class="list_post_item" src="' . $post_image2 . '" />':''), $template);
				
				$template = str_replace('[post_permalink]', get_permalink($child->ID), $template);
				//if (function_exists('get_the_post_thumbnail')) {
					//$template = str_replace('[post_thumb]', get_the_post_thumbnail( $child->ID, 'thumbnail', array('class' => 'alignleft')), $template);
				//}
	
				$thumb = $large_image_url = '';
				if ( has_post_thumbnail($child->ID)) {
				  $large_image_url = wp_get_attachment_image_src( get_post_thumbnail_id($child->ID), 'large');
				  $thumb .= get_the_post_thumbnail($child->ID, 'thumbnail', array('class' => 'alignleft')); 
				}
				
				$template = str_replace('[post_thumb]', $thumb, $template);
				$template = str_replace('[post_thumb_url]', $large_image_url, $template);				

				$return .= $template;

				if (!$settings->child_list_nesting_level || $nest_level < $settings->child_list_nesting_level) {
					$return .= sb_cl_render_child_list($template_id, $child->ID, $nest_level, $order);
				}

				$return .= $template_end_loop;
			}
		}

		$return .= $template_end;
	} else if (!@$settings->no_siblings_on_bottom_level && $nest_level == 1) {
		$parent = get_page($id);
		if ($parent->post_parent) {
			$return .= sb_cl_render_child_list($template_id, $parent->post_parent, $nest_level, $order);
		}
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
	$template = (isset($atts['template']) ? $atts['template']:false);

	switch ($tag) {
		case 'sb_child_list':
			$return = sb_cl_render_child_list($template, @$atts['parent_id'], @$atts['nest_level'], @$atts['order']);
			break;
		case 'sb_cat_list':
			$return = sb_cl_render_cat_list($atts['category'], $atts['limit'], @$atts['order'], $template);
			break;
		case 'sb_parent':
			$return = sb_cl_render_parent();
			break;
		case 'sb_grandparent':
			$return = sb_cl_render_grandparent();
			break;
		case 'sb_sibling_prev':
			$return = sb_cl_render_sibling('prev');
			break;
		case 'sb_sibling_next':
			$return = sb_cl_render_sibling('next');
			break;
	}
	
	return $return;
}

function sb_cl_render_sibling($type='next') {
	global $post, $wpdb;
	$html = '';
	
	if ($post->post_parent) {
		$sql = 'SELECT ID, post_title, menu_order
			FROM ' . $wpdb->posts . '
			WHERE
				post_type = "page"
				AND post_status = "publish"
				AND post_parent = ' . $post->post_parent . '
			ORDER BY menu_order, post_title';
		if ($siblings = $wpdb->get_results($sql)) {
			$last = $current = $next = false;
			
			foreach ($siblings as $i=>$sibling) {
				if ($current) {
					$next = $sibling;
					break;
				} else if ($sibling->ID == $post->ID) {
					$current = true;
					
					if ($type != 'next') {
						break;
					}
				} else {
					$last = $sibling;
				}
			}
			
			if ($type == 'prev' && $last) {
				$html .= '<span class="sb_cl_prev_page sb_cl">&#0171; <a href="' . get_permalink($last->ID) . '">' . $last->post_title . '</a></span>';
			} else if ($type == 'next' && $next) {
				$html .= '<span class="sb_cl_next_page sb_cl"><a href="' . get_permalink($next->ID) . '">' . $next->post_title . '</a> &#0187;</span>';
			}
		}
	}
	
	return $html;
}

function sb_cl_render_grandparent($child_id=false) {
	$return = '';
	
	if (!$child_id) {
		$child_id = get_the_ID();
	}
	
	$page = get_page($child_id);
	if ($parent_id = $page->post_parent) {
		$return = sb_cl_render_parent($parent_id);
	}
	
	return $return;
}

function sb_cl_render_parent($child_id=false) {
	if (!$child_id) {
		$child_id = get_the_ID();
	}
	
	$settings = sb_cl_get_settings();
	$page = get_page($child_id);
	$return = false;

	if ($parent_id = $page->post_parent) {
		$parent = get_page($parent_id);
		$return = $settings->child_list_parent_link;

		$return = str_replace('[post_title]', $parent->post_title, $return);
		//$return = str_replace('[post_permalink]', $parent->guid, $return);
		$return = str_replace('[post_permalink]', get_permalink($parent_id), $return);
	}

	return $return;
}

function sb_cl_init_admin_page() {
	global $sb_cl_file;
	add_options_page('SB Child List Options', 'SB Child List', 'publish_posts', $sb_cl_file, 'sb_cl_admin_page');
}

function sb_cl_admin_page() {
	global $sb_cl_max_templates;
	sb_cl_update_settings();
	
	$max_templates = $sb_cl_max_templates;
	
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
					<textarea rows="6" cols="70" name="settings[child_list_start]">' . wp_specialchars($settings->child_list_start, true) . '</textarea>
				</td>
			</tr>';

	echo '	<tr>
				<td style="vertical-align: top;">
					<div>' . __('Child List Loop Start', 'sb') . '</div>
					<div style="' . $detail_style . '">' . __('Template for the start of the loop', 'sb') . '</div>
				</td>
				<td style="vertical-align: top;">
					<textarea rows="6" cols="70" name="settings[child_list_loop_start]">' . wp_specialchars($settings->child_list_loop_start, true) . '</textarea>
				</td>
			</tr>';

	echo '	<tr>
				<td style="vertical-align: top;">
					<div>' . __('Child List Loop Content', 'sb') . '</div>
					<div style="' . $detail_style . '">' . __('Template for the loop part of the list. Use the hooks [post_title], [post_image] (SB Uploader), [post_image2] (SB Uploader Additional), [post_thumb] (WP), [post_thumb_url] (WP), [post_permalink], [post_excerpt]. The hook [post_class] can be used to output a classname only if the item relates to the current page. Good for highlighing the current page in a kind of menu structure.', 'sb') . '</div>
				</td>
				<td style="vertical-align: top;">
					<textarea rows="6" cols="70" name="settings[child_list_loop_content]">' . wp_specialchars($settings->child_list_loop_content, true) . '</textarea>
				</td>
			</tr>';

	echo '	<tr>
				<td style="vertical-align: top;">
					<div>' . __('Child List Loop End', 'sb') . '</div>
					<div style="' . $detail_style . '">' . __('Template for the end of the loop', 'sb') . '</div>
				</td>
				<td style="vertical-align: top;">
					<textarea rows="6" cols="70" name="settings[child_list_loop_end]">' . wp_specialchars($settings->child_list_loop_end, true) . '</textarea>
				</td>
			</tr>';

	echo '	<tr>
				<td style="vertical-align: top;">
					<div>' . __('Child List End Template', 'sb') . '</div>
					<div style="' . $detail_style . '">' . __('Template for the end of the list', 'sb') . '</div>
				</td>
				<td style="vertical-align: top;">
					<textarea rows="6" cols="70" name="settings[child_list_end]">' . wp_specialchars($settings->child_list_end, true) . '</textarea>
				</td>
			</tr>';
			
			//subsequent templates
			
			for ($i = 2; $i<= $max_templates; $i++) {
				
				$func = 'child_list_start_' . $i;
				echo '	<tr>
						<td style="vertical-align: top;">
							<div>' . __('Child List Start Template', 'sb') . '</div>
							<div style="' . $detail_style . '">' . __('Template ' . $i . ' for the start of the list', 'sb') . '</div>
						</td>
						<td style="vertical-align: top;">
							<textarea rows="6" cols="70" name="settings[child_list_start_' . $i . ']">' . wp_specialchars($settings->$func, true) . '</textarea>
						</td>
					</tr>';
			
				$func = 'child_list_loop_start_' . $i;
				echo '	<tr>
						<td style="vertical-align: top;">
							<div>' . __('Child List Loop Start', 'sb') . '</div>
							<div style="' . $detail_style . '">' . __('Template ' . $i . ' for the start of the loop', 'sb') . '</div>
						</td>
						<td style="vertical-align: top;">
							<textarea rows="6" cols="70" name="settings[child_list_loop_start_' . $i . ']">' . wp_specialchars($settings->$func, true) . '</textarea>
						</td>
					</tr>';
			
				$func = 'child_list_loop_content_' . $i;
				echo '	<tr>
						<td style="vertical-align: top;">
							<div>' . __('Child List Loop Content', 'sb') . '</div>
							<div style="' . $detail_style . '">' . __('Template ' . $i . ' for the loop part of the list. Use the hooks [post_title], [post_image] (SB Uploader), [post_image2] (SB Uploader Additional), [post_thumb] (WP), [post_thumb_url] (WP), [post_permalink], [post_excerpt].', 'sb') . '</div>
						</td>
						<td style="vertical-align: top;">
							<textarea rows="6" cols="70" name="settings[child_list_loop_content_' . $i . ']">' . wp_specialchars($settings->$func, true) . '</textarea>
						</td>
					</tr>';
			
				$func = 'child_list_loop_end_' . $i;
				echo '	<tr>
						<td style="vertical-align: top;">
							<div>' . __('Child List Loop End', 'sb') . '</div>
							<div style="' . $detail_style . '">' . __('Template ' . $i . ' for the end of the loop', 'sb') . '</div>
						</td>
						<td style="vertical-align: top;">
							<textarea rows="6" cols="70" name="settings[child_list_loop_end_' . $i . ']">' . wp_specialchars($settings->$func, true) . '</textarea>
						</td>
					</tr>';
			
				$func = 'child_list_end_' . $i;
				echo '	<tr>
						<td style="vertical-align: top;">
							<div>' . __('Child List End Template', 'sb') . '</div>
							<div style="' . $detail_style . '">' . __('Template ' . $i . ' for the end of the list', 'sb') . '</div>
						</td>
						<td style="vertical-align: top;">
							<textarea rows="6" cols="70" name="settings[child_list_end_' . $i . ']">' . wp_specialchars($settings->$func, true) . '</textarea>
						</td>
					</tr>';
			}
			
			//end subsequent templates

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
					<div>' . __('Siblings at lowest level?', 'sb') . '</div>
					<div style="' . $detail_style . '">' . __('Should page siblings be shown at the lowest level or not (recommended yes)', 'sb') . '</div>
				</td>
				<td style="vertical-align: top;">
					<select style="width: 100px;" name="settings[no_siblings_on_bottom_level]">';

		echo '<option value="0" ' . ($settings->no_siblings_on_bottom_level == 0 ? 'selected="selected"':'') . '>Yes</option>';
		echo '<option value="1" ' . ($settings->no_siblings_on_bottom_level == 1 ? 'selected="selected"':'') . '>No</option>';
		

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
					<textarea rows="6" cols="70" name="settings[cat_list_start]">' . wp_specialchars($settings->cat_list_start, true) . '</textarea>
				</td>
			</tr>';

	echo '	<tr>
				<td style="vertical-align: top;">
					<div>' . __('Category List Loop', 'sb') . '</div>
					<div style="' . $detail_style . '">' . __('Template for the loop part of the category list. Use the hooks [post_title] and [post_permalink].', 'sb') . '</div>
				</td>
				<td style="vertical-align: top;">
					<textarea rows="6" cols="70" name="settings[cat_list_loop]">' . wp_specialchars($settings->cat_list_loop, true) . '</textarea>
				</td>
			</tr>';

	echo '	<tr>
				<td style="vertical-align: top;">
					<div>' . __('Category List End Template', 'sb') . '</div>
					<div style="' . $detail_style . '">' . __('Template for the end of the category list', 'sb') . '</div>
				</td>
				<td style="vertical-align: top;">
					<textarea rows="6" cols="70" name="settings[cat_list_end]">' . wp_specialchars($settings->cat_list_end, true) . '</textarea>
				</td>
			</tr>';
			
	for ($i = 2; $i<= $max_templates; $i++) {
	
	$func = 'cat_list_start_' . $i;
	echo '	<tr>
				<td style="vertical-align: top;">
					<div>' . __('Category List Start Template' . $i, 'sb') . '</div>
					<div style="' . $detail_style . '">' . __('Template for the start of the category list', 'sb') . '</div>
				</td>
				<td style="vertical-align: top;">
					<textarea rows="6" cols="70" name="settings[' . $func . ']">' . wp_specialchars($settings->$func, true) . '</textarea>
				</td>
			</tr>';

	$func = 'cat_list_loop_' . $i;
	echo '	<tr>
				<td style="vertical-align: top;">
					<div>' . __('Category List Loop' . $i, 'sb') . '</div>
					<div style="' . $detail_style . '">' . __('Template for the loop part of the category list. Use the hooks [post_title] and [post_permalink].', 'sb') . '</div>
				</td>
				<td style="vertical-align: top;">
					<textarea rows="6" cols="70" name="settings[' . $func . ']">' . wp_specialchars($settings->$func, true) . '</textarea>
				</td>
			</tr>';

	$func = 'cat_list_end_' . $i;
	echo '	<tr>
				<td style="vertical-align: top;">
					<div>' . __('Category List End Template' . $i, 'sb') . '</div>
					<div style="' . $detail_style . '">' . __('Template for the end of the category list', 'sb') . '</div>
				</td>
				<td style="vertical-align: top;">
					<textarea rows="6" cols="70" name="settings[' . $func . ']">' . wp_specialchars($settings->$func, true) . '</textarea>
				</td>
			</tr>';				
				
	}
			
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
	add_shortcode('sb_grandparent', 'sb_cl_filter_post');
	add_shortcode('sb_sibling_next', 'sb_cl_filter_post');
	add_shortcode('sb_sibling_prev', 'sb_cl_filter_post');

	//Actions
	add_action('admin_menu', 'sb_cl_init_admin_page');
	
	//Widget
	add_action('widgets_init', create_function('', 'return register_widget("sb_cl_pages_widget");'));
}

class sb_cl_pages_widget extends WP_Widget {
    function sb_cl_pages_widget() {
        parent::WP_Widget(false, 'SB Child List Widget');	
    }

    function widget($args, $instance) {
	global $sbu;
	
        extract($args);
        $title = apply_filters('widget_title', $instance['title']);
        $text = apply_filters('widget_text', $instance['text']);
        $template = (isset($instance['template_id']) ? $instance['text']:1);
	$child_list = sb_cl_render_child_list($template, false);
	
	if ($child_list) {
		echo $before_widget;
		
		if ($title) {
		    echo $before_title . $title . $after_title;
		}		    
	
		if ($text) {
			echo $text;
		}
		
		if ($instance['show_parent_link']) {
			global $post;
			if ($parent = $post->post_parent) {
				if ($parent = get_post($parent)) {
					echo '<ul><li><a href="' . get_permalink($parent->ID) . '">' . $parent->post_title . '</a></li></ul>';
				}
			}
		}
		
		echo $child_list;
		
		echo $after_widget;
	}
    }

    function update($new_instance, $old_instance) {
        return $new_instance;
    }

    function form($instance) {
	global $sbu, $sb_cl_max_templates;
	
        $title = esc_attr($instance['title']);
        $show_parent_link = esc_attr($instance['show_parent_link']);
	$text = trim(esc_attr($instance['text']));
	
        ?>
            <p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?> <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" /></label></p>
            <p><label for="<?php echo $this->get_field_id('show_parent_link'); ?>"><?php _e('Show Parent Link?:'); ?> <input id="<?php echo $this->get_field_id('show_parent_link'); ?>" name="<?php echo $this->get_field_name('show_parent_link'); ?>" type="checkbox" value="1" <?php checked($show_parent_link); ?> /></label></p>
	    <p><label for="<?php echo $this->get_field_id('text'); ?>"><?php _e('Intro Text (optional):'); ?> <textarea class="widefat" id="<?php echo $this->get_field_id('text'); ?>" name="<?php echo $this->get_field_name('text'); ?>"><?php echo $text; ?></textarea></label></p>
	
	<p>
		<label for="<?php echo $this->get_field_id('template_id'); ?>"><?php _e('Template:'); ?>
			<select id="<?php echo $this->get_field_id('template_id'); ?>" name="<?php echo $this->get_field_name('template_id'); ?>">
	<?php
	
	for ($i = 1; $i<= $sb_cl_max_templates; $i++) {
		echo '<option value="' . $i . '" ' . selected($i, $instance['template_id'], false) . '>' . $i . '</option>';
	}
	
	echo '		</select>
		</label>
	</p>';
    }
}

add_action('plugins_loaded', 'sb_cl_loaded');

?>