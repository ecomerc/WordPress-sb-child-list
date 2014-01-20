<?php

/*
 Plugin Name: SB Child List
 Description: The total in-page navigation solution for Wordpress. Using the shortcodes and widgets provided you can display navigation between your parent, child and sibling items in any format you can think of.
 Author: Sean Barton (Tortoise IT)
 Plugin URI: http://www.sean-barton.co.uk
 Author URI: http://www.sean-barton.co.uk
 Version: 4.3
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

function sb_cl_render_cat_list($category, $limit=false, $order=false, $template_id = 0, $thumb_size=false) {
	global $wp_query, $posts;
	
	$settings = sb_cl_get_settings();
	
	if (!$limit) {
		$limit = 1000;
	}
	
	if (!$thumb_size) {
		$thumb_size = 'thumb';
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
			  $thumb .= get_the_post_thumbnail($id, $thumb_size, array('class' => 'alignleft')); 
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

	if (isset($_GET['debug'])) {
		echo '<pre>';
		echo $sql;
		echo '<br />' . $cat_id;
		echo '</pre>';
	}
        
        return $cat_id;
}
    
function sb_cl_render_child_list($template_id = 1, $id=false, $nest_level=0, $orderby=false, $order=false, $thumb_size=false, $category=false, $limit=false) {
	global $wpdb;
	global $wp_query;
	
	$this_page_id = $wp_query->get_queried_object_id();

	$return = false;
	$nest_level++;
	$settings = sb_cl_get_settings();
	
	//for legacy
	if (in_array(strtoupper(trim($orderby)), array('ASC', 'DESC'))) {
		//because the name of the shortcode arguments have now been reversed we need this to correct them. In time it will be removed.
		$order_temp = $orderby;
		$orderby = $order;
		$order = $order_temp;
	}
	//end for legacy
	
	//legacy conversion
	if ($orderby == 'post_title') {
	    $orderby = 'title';
	}
	//end legacy conversion
	
	if (!$order) {
		$order = 'ASC';
	}
	
	if (!$limit) {
		$limit = -1;
	}
	
	if (!$thumb_size) {
		$thumb_size = 'thumb';
	}
	
	if (!trim($orderby)) {
		$orderby = 'menu_order';
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
	
//	global $wp_query;
//	$temp_query = $wp_query;
	
	$args = array(
		'post_type'=>'page'
		, 'post_status'=>'publish'
		, 'posts_per_page'=>$limit
		, 'post_parent'=>$id
		, 'orderby'=>$orderby
		, 'order'=>$order
	);
	
	$cat_id = sb_cl_get_cat_id_from_name($category);
	if ($category && $cat_id) {
		$args['cat'] = $cat_id;
	}
	
	if (isset($_GET['debug'])) {
		echo '<pre>';
		print_r(func_get_args());
		print_r($args);
		echo '</pre>';
	}
	
	$child_posts = new WP_Query($args);
	        
	if ($child_posts->have_posts()) {
		$return .= $template_start;
		
		while ($child_posts->have_posts()) {
			$child_posts->the_post();
	
			global $post;
			$p = $post;
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
			
			$post_image = get_post_meta($p->ID, 'post_image', true);
			$post_image2 = get_post_meta($p->ID, 'post_image2', true);
			$template = str_replace('[post_image]', ($post_image ? '<img class="list_post_item" src="' . $post_image . '" />':''), $template);
			$template = str_replace('[post_image2]', ($post_image2 ? '<img class="list_post_item" src="' . $post_image2 . '" />':''), $template);
			
			$template = str_replace('[post_permalink]', get_permalink($p->ID), $template);
			//if (function_exists('get_the_post_thumbnail')) {
				//$template = str_replace('[post_thumb]', get_the_post_thumbnail( $child->ID, 'thumbnail', array('class' => 'alignleft')), $template);
			//}

			$thumb = $large_image_url = '';
			if ( has_post_thumbnail($p->ID)) {
			  if ($large_image_url = wp_get_attachment_image_src( get_post_thumbnail_id($p->ID), 'large')) {
				$large_image_url = $large_image_url[0];
			  }
			  
			  $thumb .= get_the_post_thumbnail($p->ID, $thumb_size, array('class' => 'alignleft')); 
			}
			
			$template = str_replace('[post_thumb]', $thumb, $template);
			$template = str_replace('[post_thumb_url]', $large_image_url, $template);				

			if ($fields = sb_cl_str_pos_all($template, '[custom_field:')) {

				$custom_fields = array();
				
				foreach ($fields as $pos) {
					$custom_field_string = substr($template, $pos);
					$bracket_pos = strpos($custom_field_string, ']');
					$custom_field_instance = str_replace('[custom_field:', '', substr($custom_field_string, 0, $bracket_pos));
					$custom_fields[] = $custom_field_instance;
				}

				foreach ($custom_fields as $custom_field) {
					$template = str_replace('[custom_field:' . $custom_field . ']', get_post_meta($p->ID, $custom_field, true), $template);
				}
			}
			

			$return .= $template;

			if (!$settings->child_list_nesting_level || $nest_level < $settings->child_list_nesting_level) {
				$return .= sb_cl_render_child_list($template_id, $p->ID, $nest_level, $orderby);
			}

			$return .= $template_end_loop;
		}
		
		wp_reset_postdata();
		wp_reset_query();

		$return .= $template_end;
		
	} else if (!@$settings->no_siblings_on_bottom_level && $nest_level == 1) {
		$parent = get_page($id);
		if ($parent->post_parent) {
			$return .= sb_cl_render_child_list($template_id, $parent->post_parent, $nest_level, $orderby);
		}
	}

	return $return;
}

function sb_cl_str_pos_all($haystack, $needle){ 
    $s=0; 
    $i=0;
    
    $return = false; 
    
    while (is_integer($i)){ 
        $i = strpos($haystack, $needle, $s); 
        
        if (is_integer($i)) { 
            $aStrPos[] = $i; 
            $s = ($i + strlen($needle)); 
        } 
    }
    
    if (isset($aStrPos)) { 
        $return = $aStrPos; 
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
			$return = sb_cl_render_child_list($template, @$atts['parent_id'], @$atts['nest_level'], @$atts['orderby'], @$atts['order'], @$atts['thumb_size'], @$atts['category'], @$atts['limit']);
			break;
		case 'sb_cat_list':
			$return = sb_cl_render_cat_list($atts['category'], $atts['limit'], @$atts['order'], $template, @$atts['thumb_size']);
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
	
	sb_cl_start_box('Usage Instructions');
	
	echo '<p>This plugin is purely shortcode based although it does add a widget to help too. Use the following shortcodes to generate links to child, parent and sibling content in and post type:</p>
	<p>[sb_child_list] <-- Arguments allowed are: template, nest_level, orderby and order<br />
	[sb_parent]<br />
	[sb_grandparent]<br />
	[sb_cat_list]<br />
	[sb_sibling_next]<br />
	[sb_sibling_prev]</p>';
	
	sb_cl_end_box();
	
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
					<div style="' . $detail_style . '">' . __('Template for the loop part of the list. Use the hooks [post_title], [post_image] (SB Uploader), [post_image2] (SB Uploader Additional), [post_thumb] (WP), [post_thumb_url] (WP), [post_permalink], [post_excerpt]. The hook [post_class] can be used to output a classname only if the item relates to the current page. Good for highlighing the current page in a kind of menu structure. Custom fields can be added using [custom_field:field_key] where field_key is the meta_key of your custom field.', 'sb') . '</div>
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
        $template = (isset($instance['template_id']) ? $instance['template_id']:1);
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