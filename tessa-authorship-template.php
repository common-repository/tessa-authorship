<?php
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! function_exists( 'tessa_get_authorship' ) ) {
/**
 * Wrapper function to get the authorship from the Tessa_Authorship class.
 * @param  string/array $args  Arguments.
 * @since 0.1.0
 * @return array/boolean       Array if true or false for no results.
 * @modified 0.6.1 get rid off the bad idea to remove all filters for posts_orderby and post_limits
 */
function tessa_get_authorship ( $args = '' ) {
	global $tessa_authorship;
	return $tessa_authorship->get_authorship( $args );
} // End tessa_get_authorship()
}

/**
 * Enable the usage of do_action( 'tessa_authorship' ) to display authorship within a theme/plugin.
 *
 * @since 0.1.0
 */
add_action( 'tessa_authorship', 'tessa_authorship' );
add_action( 'the_post', 'tessa_authorship_the_post', 99, 1);
add_action( 'tas_send_private_message', 'tas_send_private_message' );
add_action( 'template_redirect', 'tas_send_private_message_request', 9 );

if ( defined( 'RECAPTCHA_PRIVATE_KEY' ) ) {
	add_action( 'tas_get_message_captcha', 'tas_get_message_captcha' );
	add_filter( 'tas_message_check', 'tas_message_check', 10, 3 );
}

add_action('wp_enqueue_scripts', 'tessa_authorship_use_packaged_social_icons');


if ( ! function_exists( 'tessa_authorship_the_post') ) {
/**
 * Hook into the_post to set filter after query for post has executed
 * @param  string content.
 * @since  0.3.0
 * @return void
 * @modified 0.5.0
 */
function tessa_authorship_the_post($post_object) {
	global $tasrpas_options, $wp_query, $tessa_authorship;
	$tasrpas_options = get_option('tasrpas_settings');
	if ( !is_admin() && ( is_single() || is_page() ) ) :
		//@TODO: consolidate the load of tasrpas_option to minimize db interactions
		if ( isset ( $tasrpas_options['content_filter'] ) && $tasrpas_options['content_filter'] ) :
			add_filter( 'the_content', 'tessa_authorship_the_content', 999 );
		else :
			remove_filter('the_content', 'tessa_authorship_the_content');
		endif;
		
		if ( $post_object->post_type == 'authorship' && isset($tasrpas_options['show_email_form']) && $tasrpas_options['show_email_form'] ) :
			wp_register_script( 'tasrpas-email-js', plugin_dir_url( __FILE__ ). 'assets/js/tasrpas_email.js', array( 'jquery' ), '20121205' );
			add_action( 'wp_footer', 'tessa_authorship_email_form' );
			wp_enqueue_script( 'tasrpas-email-js' );
			$recaptcha__options = array( 'lang' => tas_get_base_recaptcha_lang_code() );
			wp_localize_script('tasrpas-email-js', 'recaptcha_options', $recaptcha__options);			
		endif;
		
		
	endif;
	
	//add custom fields and data to our authorship post object
	//make this available on all contexts for authorship posts
	if ( !is_admin() && isset($wp_query) && $wp_query->is_main_query() && $post_object->post_type == 'authorship' ) :
		$meta = get_post_custom( $post_object->ID );
		$custom_fields = $tessa_authorship->get_custom_fields_settings();
		if (class_exists('MultiPostThumbnails') && MultiPostThumbnails::has_post_thumbnail('authorship', 'tas', $post_object->ID ) ) :
			$width = 50;
			$height = 50;
			if ( isset($tasrpas_options['thumb_image_width']) && isset($tasrpas_options['thumb_image_height']) && 0 < intval($tasrpas_options['thumb_image_width']) && 0 < intval($tasrpas_options['thumb_image_height']) ) :
				//this is what Multiple Post Thumbnails looks for in some contexts... as image size identifier... $this->post_type}-{$this->id}-thumbnail
				$width = $tasrpas_options['thumb_image_width'];
				$height = $tasrpas_options['thumb_image_height'];
			endif;
			$post_object->image = '<span class="entry-format-badge-author">' . MultiPostThumbnails::get_the_post_thumbnail('authorship', 'tas', $post_object->ID, array($width, $height), array( 'class' => 'avatar' ), false ) . '</span>';
		else :
			$post_object->image = '<span class="entry-format-badge-noauthorimg genericon"><span class="screen-reader-text">' . __('Generic author image', 'tessa-authorship') . '</span></span>';			
		endif;
		foreach ( (array)$custom_fields as $i => $j ) :
			if ( isset( $meta['_' . $i] ) && ( '' != $meta['_' . $i][0] ) ) :
				$post_object->$i = $meta['_' . $i][0];
			else :
				$post_object->$i = $j['default'];
			endif;					
		endforeach;
		//offer a prepared social object
		$post_object->social_links = tessa_authorship_social($post_object);

	endif;		

}
}

if ( ! function_exists( 'tessa_authorship_the_content') ) {
/**
 * Display or return HTML-formatted authorship on single and page
 * @param  string content.
 * @since  0.2.0
 * @return string
 * @modified 0.7.0
 */
function tessa_authorship_the_content($content = '') {
	global $tasrpas_options;
	//should be available
	$post_type = get_post_type(get_the_ID());
	//prepare for worst
	$tasrpas_options['post_types'] = !empty( $tasrpas_options['post_types'] ) ? $tasrpas_options['post_types'] : array();
	if ( !empty($post_type) && $post_type == 'authorship' ) :
		$args = array(
			'id' => get_post_meta(get_the_ID(), '_saprsatids', true),
			'authorship' => false,
			'echo' => false,
		);
		if ( isset($tasrpas_options['add_social_info']) && $tasrpas_options['add_social_info'] ) :
			$content = tessa_authorship_social_info() . $content;
		endif;
		$content .= tessa_authorship($args);
	elseif ( !empty($post_type) && in_array($post_type, $tasrpas_options['post_types']) ) :
		$args = array(
			'id' => get_post_meta(get_the_ID(), '_tasrpasids', true),
			'authorship' => true,
			'echo' => false,
		);
		$content .= tessa_authorship($args);
	endif;
	return $content;
} 
}

if ( !function_exists('tessa_authorship_use_packaged_social_icons') ) {
/**
 * Display packaged social links
 * @param  string content.
 * @since  0.7.0
 * @return string
 */
function tessa_authorship_use_packaged_social_icons() {
	if ( !is_admin() ) :
		global $tasrpas_options, $wp_query, $tessa_authorship;
		$tasrpas_options = get_option('tasrpas_settings');
		if ( isset ( $tasrpas_options['use_social_icons'] ) && $tasrpas_options['use_social_icons'] ) :
			wp_enqueue_style('tas-social-icons', plugins_url( 'assets/css/social_icons.css', __FILE__ ));
		elseif ( isset ( $tasrpas_options['use_social_font'] ) && $tasrpas_options['use_social_font'] ) :
			wp_enqueue_style('tas-social-font', plugins_url( 'assets/css/social_font.css', __FILE__ ));
		endif;
	endif;
}
}

if ( !function_exists('tessa_authorship_social_info') ) {
/**
 * Add social links to title as commodity implementation
 * @since  0.7.0
 * @return string $socialinfo
 */
function tessa_authorship_social_info( ) {
	global $paged, $page, $post;
	$socialinfo = '';
	if ( isset($post->location) && $post->location != '' && isset($post->birthyear) && $post->birthyear != '' ) :
		$socialinfo .= '<div class="locationandyear">' . $post->location . ', ' . $post->birthyear . '</div>';
	elseif ( isset($post->location) && $post->location != '' ) :
		$socialinfo .= '<div class="locationandyear">' . $post->location . '</div>';
	elseif ( isset($post->birthyear) && $post->birthyear != '' ) :
		$socialinfo .= '<div class="locationandyear">' . $post->birthyear . '</div>';					
	endif;
	if ( isset($post->headline) && $post->headline != '' ) :
		$socialinfo .= '<div class="headline">' . $post->headline . '</div>';
	endif;
	if ( isset($post->social_links) && is_array($post->social_links) && count($post->social_links) > 0 ) :
		$socialinfo .= '<br /><div class="social"><ul class="social-links clear">' . implode('' ,$post->social_links) . '</ul></div>';
	endif;
	$socialinfo .= '<br />';
	return $socialinfo;
}
}

if ( ! function_exists( 'tessa_authorship' ) ) {
/**
 * Display or return HTML-formatted authorship.
 * @param  string/array $args  Arguments.
 * @since 0.1.0
 * @return string
 */
function tessa_authorship ( $args = '' ) {
	global $post, $tasrpas_options;
	if ( class_exists('SitePress') && defined('ICL_LANGUAGE_CODE') ) :
        global $sitepress;
		//get the lang code for the post as we are currently in default language
		$post_lang = $sitepress->get_language_for_element($post->ID, 'post_' . get_post_type($post->ID));
		//if ( isset($post_lang) && $post_lang ) :
		//	$lang_code = $post_lang;
		//endif;			
        $sitepress->switch_lang($post_lang);
    endif;
	
	$defaults = array(
		'limit' => 5,
		'per_row' => null,
		'orderby' => 'menu_order',
		'order' => 'DESC',
		'id' => 0,
		'authorship' => true, //Options: true->'authorship', false->'authorworks'
		'post_type' => '', //only if authorship is set to false, post-type will accept post-types as array
		'strict' => true,
		'display_auto' => false,
		'display_author' => true,
		'display_avatar' => true,
		'display_url' => true,
		'display_social' => true,
		'display_content' => false,
		'display_excerpt' => false,
		'effect' => 'fade', // Options: 'fade', 'none'
		'pagination' => false,
		'echo' => true,
		'size' => 'authorship-image-thumbnail', //50
		'title' => '',
		'before' => '<div class="widget widget_tessa_authorship">',
		'after' => '</div>',
		'before_title' => '<h2>',
		'after_title' => '</h2>',
		'category' => 0,
		'tags' => 0
	);

	$args = wp_parse_args( $args, $defaults );

	// Allow child themes/plugins to filter here.
	$args = apply_filters( 'tessa_authorship_args', $args );
	$html = '';

	do_action( 'tessa_authorship_before', $args );
	//$html .= '<hr />Attribute ID: ' . $args['id'] . '<hr />' . $_COOKIE['_icl_current_language'] . '<hr />';

	// The Query.
	$query = tessa_get_authorship( $args );

	// The Display.
	if ( ! is_wp_error( $query ) && is_array( $query ) && count( $query ) > 0 ) {

		$class = '';

		if ( is_numeric( $args['per_row'] ) ) {
			$class .= ' columns-' . intval( $args['per_row'] );
		}

		if ( 'none' != $args['effect'] ) {
			$class .= ' effect-' . $args['effect'];
		}

		$html .= $args['before'] . "\n";
		if ( '' != $args['title'] ) {
			$html .= $args['before_title'] . esc_html( $args['title'] ) . $args['after_title'] . "\n";
		}
		$html .= '<div class="authorship component' . esc_attr( $class ) . '">' . "\n";

		$html .= '<div class="authorship-list">' . "\n";
		
		/* @since 0.3.2 a test as well */
		$fixheight = '';
		$fiximage = '';
		$fixheader = '';
		
		// Begin templating logic.
		$tpl = '<div ' . $fixheight . ' id="quote-%%ID%%" class="%%CLASS%%">%%AVATAR%% %%AUTHOR%% %%HEADLINE%% %%TEXT%% %%SOCIAL%%<div class="fix"></div></div>';
		if ($args['authorship'] && isset($tasrpas_options['placeholders_author']) && !empty($tasrpas_options['placeholders_author']) && is_array($tasrpas_options['placeholders_author']) ) :
			$placeholders = $tasrpas_options['placeholders_author'];
			array_walk($placeholders, function(&$value, $key) {
				//no substitutions on authors
				$value = '%%' . $value . '%%';
			}); 
			$tpl = '<div ' . $fixheight . ' id="quote-%%ID%%" class="%%CLASS%%">'. implode(' ', $placeholders) . '<div class="fix"></div></div>';
			$tpl = apply_filters( 'tessa_authorship_item_template_author', $tpl, $args );
		elseif ($args['authorship']) :
			$tpl = apply_filters( 'tessa_authorship_item_template_author', $tpl, $args );
		elseif (!$args['authorship'] && isset($tasrpas_options['placeholders_entry']) && !empty($tasrpas_options['placeholders_entry']) && is_array($tasrpas_options['placeholders_entry']) ) :
			$placeholders = $tasrpas_options['placeholders_entry'];
			array_walk($placeholders, function(&$value, $key) {
				//fix human understandable substitutions from settings for entries
				$value = str_replace('TITLE','AUTHOR', $value);
				$value = str_replace('IMAGE','AVATAR', $value);
				$value = '%%' . $value . '%%';
			}); 
			$tpl = '<div ' . $fixheight . ' id="quote-%%ID%%" class="%%CLASS%%">'. implode(' ', $placeholders) . '<div class="fix"></div></div>';
			$tpl = apply_filters( 'tessa_authorship_item_template_entry', $tpl, $args );
		elseif (!$args['authorship'] ) :
			$tpl = '<div ' . $fixheight . ' id="quote-%%ID%%" class="%%CLASS%%">%%AVATAR%% %%AUTHOR%% %%TEXT%%<div class="fix"></div></div>';
			$tpl = apply_filters( 'tessa_authorship_item_template_entry', $tpl, $args );
		endif;
		/* @since 0.3.2 but deactivated for now avatar_bg will return 0 always via settings */
		if ( isset($tasrpas_options['avatar_bg']) && $tasrpas_options['avatar_bg'] && isset($args['display_avatar']) && $args['display_avatar'] ) :
			$tpl = str_replace('%%AVATAR%%', '', $tpl);
			$tpl = str_replace('"%%CLASS%%"', '"%%CLASS%%" %%AVATAR%%', $tpl);
		endif;
		$count = 0;
		
		
		foreach ( $query as $post ) { $count++;
			$template = $tpl;

			$css_class = 'quote';
			if ( ( is_numeric( $args['per_row'] ) && ( 0 == ( $count - 1 ) % $args['per_row'] ) ) || 1 == $count ) { $css_class .= ' first'; }
			if ( ( is_numeric( $args['per_row'] ) && ( 0 == $count % $args['per_row'] ) ) || count( $query ) == $count ) { $css_class .= ' last'; }

			// Add a CSS class if no image is available.
			if ( isset( $post->image ) && ( '' == $post->image ) ) {
				$css_class .= ' no-image';
			}
			
			setup_postdata( $post );
			
			//ini vars
			$author = '';
			$author_text = '';
			$headline = '';
			$social = '';
			$avatar = '';
			$content = '';
			$social_links = array();
			
			// If we need to display the author, get the data.
			if ( ( get_the_title( $post ) != '' ) && true == $args['display_author'] ) {
				$permalink = get_permalink( $post->ID );
				$intro = "";
				if ($args['authorship']) :
					$author .= '<div ' . $fixheader . ' class="related-author">';
					$css_class .= ' authorship-author';
					if ( isset($tasrpas_options['author_intro']) && $tasrpas_options['author_intro'] ) :
						$intro = '<span class="author-intro">' . $tasrpas_options['author_intro'] . '</span>';
					endif;
				else :
					$author .= '<div ' . $fixheader . ' class="related-entry">';
					$css_class .= ' authorship-entry';
					if ( isset($tasrpas_options['entry_intro']) && $tasrpas_options['entry_intro'] ) :
						$intro = '<span class="entry-intro">' . $tasrpas_options['entry_intro'] . '</span>';
					endif;						
				endif;
				$author_name = '<span class="authorship-title">' .get_the_title( $post ) . '</span>';

				if ( true == $args['display_url'] && '' != $permalink ) {
					$author .= ' <span class="permalink">' . $intro . '<a href="' . esc_url( $permalink ) . '">' . $author_name . '</a></span><!--/.excerpt-->' . "\n";
				}
				else {
					$author .= $intro . $author_name;
				}
				if ( isset( $post->headline ) && '' != $post->headline ) :
					$headline = '<div class="headline">' . $post->headline . '</div><!--/.headline-->' . "\n";
					$template = str_replace( '%%HEADLINE%%', $headline, $template );
				else :
					$template = str_replace( '%%HEADLINE%%', '', $template );
				endif;

				$author .= '</div><!--/.reference-->' . "\n";
				// Templating engine replacement.
				$template = str_replace( '%%AUTHOR%%', $author, $template );
			} else {
				$template = str_replace( '%%AUTHOR%%', '', $template );
			}
			if ($args['display_social'] && $args['authorship']) :
				$social_links = tessa_authorship_social($post);
				if ( 0 < count($social_links) ) :
					$social .= '<div class="social-links"><ul class="social-links-list">';
					if (isset($args['social_link_intro']) && '' != $args['social_link_intro']) :
						$social .= '<span id="social-intro-%%ID%%" class="social-intro">' . esc_html( __($args['social_link_intro'], 'tessa-authorship') ) . '</span>'; 
					endif;
					$social .= implode("\n", $social_links);
					$social .= '</ul></div>';	
					$social = apply_filters( 'tessa_authorship_social_links', $social, $social_links, $post, $args );						
					$template = str_replace( '%%SOCIAL%%', $social, $template );
				else:
					$social = apply_filters( 'tessa_authorship_social_links', $social, $social_links, $post, $args );											
					$template = str_replace( '%%SOCIAL%%', $social, $template );									
				endif;
			else :
				$template = str_replace( '%%SOCIAL%%', '', $template );				
			endif;
			// Templating logic replacement.
			$template = str_replace( '%%ID%%', get_the_ID(), $template );
			$template = str_replace( '%%CLASS%%', esc_attr( $css_class ), $template );

			if ( isset( $post->image ) && ( '' != $post->image ) && true == $args['display_avatar'] ) {
				if ( isset($tasrpas_options['avatar_bg']) && $tasrpas_options['avatar_bg'] ) :
					//we should have received a url from $tessa_authorship for this as avatar_bg is set to true
					$avatar = $post->image;
					if ( $avatar && !is_array($avatar) ) :
						
						$avatar = ' style="background-image: url(\'' . $avatar . '\');background-repeat:no-repeat;background-attachment:fixed;background-position:left top;min-height:50px;padding-left:50px;" ';
					else :
						$avatar = ' style="min-height:50px;padding-left:50px;" ';
					endif;
					$template = str_replace( '%%AVATAR%%', $avatar, $template );						
				else: 
					if ( true == $args['display_url'] && '' != $permalink ) :
						$avatar = '<a ' . $fiximage . ' href="' . esc_url( $permalink ) . '" class="avatar-link">' . $post->image . '</a>';
					else : 
						$avatar = '<span ' . $fiximage . ' class="avatar-link">' . $post->image . '</span>';
					endif;
					$template = str_replace( '%%AVATAR%%', $avatar, $template );
				endif;
			}
			else {
				/* offer empty span to allow consistent layout via css
				 * @TODO check html code for entries in conjunction with css manipulation
				 * maybe it's not a bad idea to offer a list format, too
				 * @since 0.3.0 
				 */
				if ( isset($tasrpas_options['avatar_bg']) && !$tasrpas_options['avatar_bg'] ) :
					$avatar = '<span class="avatar-link">&nbsp;</span>';
				else :
					$avatar = ' style="min-height:50px;padding-left:50px;" ';
				endif;
				$template = str_replace( '%%AVATAR%%', $avatar, $template );
				
			}

			// Remove any remaining %%AVATAR%% template tags.
			$template = str_replace( '%%AVATAR%%', '', $template );
			if ($args['display_content']) :
				$content = apply_filters( 'tessa_authorship_content', get_the_content(__($tasrpas_options['read_more'],'tessa-authorship')), $post, $args );				
				$template = str_replace( '%%TEXT%%', '<div class="authorship-text">' . $content . '</div>', $template );
			elseif ($args['display_excerpt']) :
				$content = apply_filters( 'tessa_authorship_content', get_the_excerpt(), $post, $args );				
				$template = str_replace( '%%TEXT%%', '<div class="authorship-text">' . $content . '</div>', $template );				
			endif;
			$template = str_replace( '%%TEXT%%', '', $template );
			
			//Give peace a chance
			$template = apply_filters( 'tessa_authorship_post_output', $template, $author, $headline, $social_links, $content, $post, $args );											
			
			// Assign for output.
			$html .= $template;

			if( is_numeric( $args['per_row'] ) && ( 0 == $count % $args['per_row'] ) ) {
				$html .= '<div class="fix"></div>' . "\n";
			}
		}

		wp_reset_postdata();

		$html .= '</div><!--/.authorship-list-->' . "\n";

		if ( $args['pagination'] == true && count( $query ) > 1 && $args['effect'] != 'none' ) {
			$html .= '<div class="pagination">' . "\n";
			$html .= '<a href="#" class="btn-prev">' . apply_filters( 'tessa_authorship_prev_btn', '&larr; ' . __( 'Previous', 'tessa-authorship' ) ) . '</a>' . "\n";
			$html .= '<a href="#" class="btn-next">' . apply_filters( 'tessa_authorship_next_btn', __( 'Next', 'tessa-authorship' ) . ' &rarr;' ) . '</a>' . "\n";
			$html .= '</div><!--/.pagination-->' . "\n";
		}
			$html .= '<div class="fix"></div>' . "\n";
		$html .= '</div><!--/.authorship-->' . "\n";
		$html .= $args['after'] . "\n";
	}

	// Allow child themes/plugins to filter here.
	$html = apply_filters( 'tessa_authorship_html', $html, $query, $args );

	if ( $args['echo'] != true ) { return $html; }

	// Should only run if "echo" is set to true.
	echo $html;

	do_action( 'tessa_authorship_after', $args ); // Only if "echo" is set to true.
} // End tessa_authorship()
}

if ( ! function_exists( 'tessa_authorship_social' ) ) {
/**
 * Generate the social stuff
 * @param  post.
 * @since  0.2.0
 * @return array
 */
function tessa_authorship_social($post) {
	global $tessa_authorship, $tasrpas_options;
	$link_list = array();
	$blankwin = $tasrpas_options['open_link_blank'] ? ' target="_blank" ' : '';
	foreach ( (array)$tessa_authorship->get_custom_fields_settings() as $i => $j ) :
		if ( isset( $post->$i ) && '' != $post->$i ) :
			$surl = "";
			
			switch($i) :
				//exclude our status fields
				case 'headline' :
				break;
				case 'birthyear' :
				break;
				case 'location' :
				break;	
				//handle email link and treat privacy
				case 'email_link' :
					if (is_email($post->email_link) && isset($tasrpas_options['pub_email_link']) && $tasrpas_options['pub_email_link']) :
						if (strpos($post->email_link, 'mailto:') !== false) :
							$surl = eae_encode_emails($post->email_link);
						else :
							$surl = eae_encode_emails('mailto:' . $post->email_link);						
						endif;
						$link_list[] = '<li class="'. $j['class'] . '"><a class="genericon" href="' . $surl . '" '. $blankwin . ' title="'. esc_attr( __($j['title'], 'tessa-authorship') ) .'"><span class="screen-reader-text">'. _x($j['name'], 'tessa-authorship') . '</span></a></li><!--/.' . $i .'-->' . "\n";
					elseif ( is_email($post->email_link) && isset($tasrpas_options['show_email_form']) && $tasrpas_options['show_email_form'] ) :
						$link_list[] = '<li class="'. $j['class'] . '">' . tessa_authorship_get_email_link( get_permalink( $post->ID ), '<span class="screen-reader-text">'. _x($j['name'], 'tessa-authorship') . '</span>', __( 'Click to send an email message', 'tessa-authorship' ), 'tas_message=email' ) . '</li><!--/.' . $i .'-->' . "\n";
					endif;
				break;
				default :
				//only if field contains possibly an url
				if ( parse_url($post->$i) ) :
					//sanitize url
					$link_list[] = '<li class="'. $j['class'] . '"><a class="genericon" href="' . esc_url( $post->$i ) . '" ' . $blankwin . ' title="'. esc_attr( __($j['title'], 'tessa-authorship') ) .'"><span class="screen-reader-text">'. $j['name'] . '</span></a></li><!--/.' . $i .'-->' . "\n";
				endif;
				break;			
			endswitch;

		endif;
	endforeach;
	return $link_list;
}
}

if ( !function_exists('tessa_authorship_get_email_link') ) {
/**
 * Get email link for email form if email is not publicly available
 * @param  string $url
 * @param  string $text
 * @param  string $title
 * @param  string $query
 * @param  string $id 
 * @since  0.6.0
 * @return string markup link
 */
function tessa_authorship_get_email_link( $url, $text, $title, $query = '', $id = false ) {
	$klasses = array( 'tas-email-form', 'tas-button', 'genericon' );

	if ( !empty( $query ) ) {
		if ( stripos( $url, '?' ) === false )
			$url .= '?'.$query;
		else
			$url .= '&amp;'.$query;
	}
	return sprintf(
		'<a rel="nofollow" class="%s" href="%s"%s title="%s"%s><span>%s</span></a>',
		implode( ' ', $klasses ),
		$url,
		'',
		$title,
		( $id ? ' id="' . esc_attr( $id ) . '"' : '' ),
		$text
	);
}
}

if ( !function_exists('tessa_authorship_email_form') ) {
/**
 * Prepare email form
 * @since  0.6.0
 * @echo markup email form
 */
function tessa_authorship_email_form() {
	global $current_user, $post;

	$visible = $status = false;
?>
	<div id="tessa_authorship_email_form" style="display: none;">
		<button id="tas_close_btn" class="genericon"><span class="screen-reader-text">X</button>
		<br />
		<form action="<?php echo esc_url( $_SERVER['REQUEST_URI'] ); ?>" method="post">
			<?php if ( is_user_logged_in() ) : ?>
				<input type="hidden" name="source_name" value="<?php echo esc_attr( $current_user->display_name ); ?>" />
				<input type="hidden" name="source_email" value="<?php echo esc_attr( $current_user->user_email ); ?>" />
			<?php else : ?>
				<label id="tas_sourcename" for="source_name"><?php _e( 'Your Name', 'tessa-authorship' ) ?></label>
				<input type="text" class="tas-message-input" name="source_name" id="source_name" value="" />
				<br />
				<label id="tas_sourceemail" for="source_email"><?php _e( 'Your Email Address', 'tessa-authorship' ) ?></label>
				<input type="text" class="tas-message-input" name="source_email" id="source_email" value="" />
				<br />
			<?php endif; ?>
			
			<label for="target_message"><?php echo __( 'Your message for ', 'tessa-authorship' ) . ' <strong>' . get_the_title($post->ID) . '</strong>';  ?></label>
			<textarea class="tas-message" name="target_message" id="target_message" rows="5"></textarea>
			
			<?php do_action( 'tas_get_message_captcha', 'tessa_authorship' ); ?>
			<div class="tas_form_buttons">
				<img style="float: right; display: none" class="loading" src="<?php echo plugin_dir_url( __FILE__ ) . 'assets/images/loading.gif'; ?>" alt="loading" width="16" height="16" />
				<a href="#cancel" class="tas_cancel"><?php _e( 'Cancel', 'tessa-authorship' ); ?></a>
				<input type="submit" value="<?php _e( 'Send Message', 'tessa-authorship' ); ?>" class="tas_send" />
			</div>
			<div class="errors errors-1" style="display: none;">
				<?php _e( 'Post was not sent. Email not valid!', 'tessa-authorship' ); ?>
			</div>

			<div class="errors errors-2" style="display: none;">
				<?php if ( defined( 'RECAPTCHA_PRIVATE_KEY' ) ) : ?>
					<?php _e( 'Something went wrong, could be the captcha. If you have difficulties request another captcha by using the topmost function icon next to the captcha input field. If you still encounter difficulties, please request help via the help button (?).', 'tessa-authorship' ); ?>
				<?php else : ?>
					<?php _e( 'Something went wrong. Please try again.', 'tessa-authorship' ); ?>				
				<?php endif; ?>
			</div>

			<div class="errors errors-3" style="display: none;">
				<?php _e( 'Sorry. You\'re not allowed to send messages.', 'tessa-authorship' ); ?>
			</div>
			<div class="errors errors-4" style="display: none;">
				<?php _e( 'Please check your message.', 'tessa-authorship' ); ?>
			</div>
			<div class="errors errors-5" style="display: none;">
				<?php _e( 'Recipient\'s email is not properly configured. You may comment this bellow to inform administration about this problem.', 'tessa-authorship' ); ?>
			</div>
			<div class="errors errors-8" style="display: none;">
				<?php _e( 'Please check your message. Moreover the recipient\'s email is not properly configured. You may comment this bellow to inform administration about this problem.', 'tessa-authorship' ); ?>
			</div>
		</form>
	</div>
<?php
	
}
}

if ( !function_exists('tas_get_message_captcha') ) {
/**
 * Prepare the captcha widget
 * @since  0.6.0
 * @echo markup captcha
 */
function tas_get_message_captcha() {
	echo '<div class="recaptcha" id="tasrpas_recaptcha"></div><input type="hidden" name="recaptcha_public_key" id="recaptcha_public_key" value="'.(defined( 'RECAPTCHA_PUBLIC_KEY' ) ? esc_attr( RECAPTCHA_PUBLIC_KEY ) : '').'" />';
}
}


if ( !function_exists('tas_message_check') ) {
/**
 * Check captcha for the message
 * @since  0.6.0
 * @changed 0.7.6
 * @return boolean is_valid
 */
function tas_message_check( $true, $post, $data ) {
	//load lib only if it's not available by testing on function used
	if ( !function_exists('recaptcha_check_answer') ) :
		require_once plugin_dir_path( __FILE__ ).'recaptchalib.php';
	endif;
	$recaptcha_result = recaptcha_check_answer( RECAPTCHA_PRIVATE_KEY, $_SERVER["REMOTE_ADDR"], $data["recaptcha_challenge_field"], $data["recaptcha_response_field"] );
	return $recaptcha_result->is_valid;
}
}

if ( !function_exists('tas_get_base_recaptcha_lang_code') ) {
/**
 * Prepare the captcha lang code
 * @since  0.6.0
 * @return string captcha lang
 */
function tas_get_base_recaptcha_lang_code() {
	//@TODO, this is not really multilingual, WPML capable
	$base_recaptcha_lang_code_mapping = array(
		'en'    => 'en',
		'nl'    => 'nl',
		'fr'    => 'fr',
		'fr-be' => 'fr',
		'fr-ca' => 'fr',
		'fr-ch' => 'fr',
		'de'    => 'de',
		'pt'    => 'pt',
		'pt-br' => 'pt',
		'ru'    => 'ru',
		'es'    => 'es',
		'tr'    => 'tr'
	);

	$blog_lang_code = function_exists( 'get_blog_lang_code' ) ? get_blog_lang_code() : get_bloginfo( 'language' );
	if( isset( $base_recaptcha_lang_code_mapping[ $blog_lang_code ] ) )
		return $base_recaptcha_lang_code_mapping[ $blog_lang_code ];

	// if no base mapping is found return default 'en'
	return 'en';
}
}


if ( !function_exists('tessa_authorship_process_email_form') ) {
/**
 * Processing logic for private messages
 * @since  0.6.0
 * @return ajax object or redirect
 */
function tessa_authorship_process_email_form( $post, array $post_data ) {
	$ajax = false;
	if ( isset( $_SERVER['HTTP_X_REQUESTED_WITH'] ) && strtolower( $_SERVER['HTTP_X_REQUESTED_WITH'] ) == 'xmlhttprequest' )
		$ajax = true;

	$source_email = $target_email = $source_name = $target_message = false;

	if ( isset( $post_data['source_email'] ) && is_email( $post_data['source_email'] ) )
		$source_email = $post_data['source_email'];

	if ( isset( $post_data['target_message'] ) && $post_data['target_message'] && strlen($post_data['target_message']) > 5 ) :
		$target_message = wp_kses($post_data['target_message'], 	
			array(
				'a' => array(
					'href' => array(),
					'title' => array()
				),
				'br' => array(),
				'em' => array(),
				'strong' => array()
			),
			array(
				'http', 
				'https'
			)
		);
		$target_message = wpautop($target_message, true);
    endif;
		
	if ( isset( $post_data['source_name'] ) && $post_data['source_name'] )
		$source_name = strip_tags($post_data['source_name']);

	$email_authorship = get_post_meta($post->ID, '_email_link', true);
	if ( isset( $email_authorship ) && $email_authorship && is_email($email_authorship) ) :
		$target_email = $email_authorship;
	endif;
	
	// Test email
	$error = 1;   // Failure in data
	if ( $source_email && $target_email && $target_message && $source_name ) {
		if ( apply_filters( 'tas_message_check', true, $post, $post_data ) ) {
			$data = array(
				'post'   => $post,
				'source' => $source_email,
				'target' => $target_email,
				'message' => $target_message,
				'name'   => $source_name
			);
			
			// offer a filter for additional checks and modifications
			if ( ( $data = apply_filters( 'tessa_authorship_message_filter', $data ) ) !== false ) {
				do_action( 'tas_send_private_message', $data );
			}

			// Return success via ajax or redirect
			if ( $ajax ) {
?>
<div class="response">
<div class="response-title"><?php _e( 'Your message has been sent!', 'tessa-authorship' ); ?></div>
<div class="response-sub"><?php echo esc_html( __( 'Thanks for contacting with ', 'tessa-authorship' ) . get_the_title($post->ID) ); ?></div>
<div class="response-close"><a href="#" class="tas_cancel"><?php _e( 'Close', 'tessa-authorship' ); ?></a></div>
</div>
<?php
			}
			else
				wp_safe_redirect( get_permalink( $post->ID ).'?tas_message=email' );

			die();
		}
		else
			$error = 2;   // Captcha test failed
	}

	if ( $ajax ) :
		//fine tuning of errors
		if ( ($target_message && strlen($target_message) < 6) || !$target_message ) :
			$error += 3;
		endif;
		if ( !$target_email ) :
			$error += 4;
		endif;
		echo $error;
	else :
		wp_safe_redirect( get_permalink( $post->ID ).'?tas_message=email&msg=fail' );
	endif;
	die();
}
}
if ( !function_exists('tas_send_private_message') ) {
/**
 * Send message via wp_mail (phpmailer)
 * @param object $data
 * @since  0.6.0
 * @return void
 */
function tas_send_private_message( $data ) {
	$content  = sprintf( __( '%1$s (%2$s) sends you a private message:'."\n\n", 'tessa-authorship' ), $data['name'], $data['source'] );
	$content .= $data['post']->post_title."\n";
	$content .= __('Your page: ', 'tessa-authorship') . get_permalink( $data['post']->ID )."\n";
	$content .= '<hr />';
	$content .= __('The message: ', 'tessa-authorship') . '<br />';
	$content .= $data['message'];
	add_filter( 'wp_mail_content_type', 'tas_set_html_content_type' );	
	wp_mail( $data['target'], '['.__( 'Private Message for ', 'tessa-authorship' ).'] '. $data['post']->post_title, $content );
	remove_filter( 'wp_mail_content_type', 'tas_set_html_content_type' );	
}
}

if ( !function_exists('tas_send_private_message_request') ) {
/**
 * Handle private message request 
 * @since  0.6.0
 * @call function after conditional check
 */
function tas_send_private_message_request() {
	global $post;
	// Only process if: single post and share=X defined
	if ( ( is_page() || is_single() ) && isset( $_GET['tas_message'] ) ) {
		tessa_authorship_process_email_form( $post, $_POST );
	}
}
}

if ( !function_exists('tas_set_html_content_type') ) {
/**
 * Set content type to html for private messages
 * @since  0.6.0
 * @return content type
 */
function tas_set_html_content_type() {

	return 'text/html';
}
}

if ( ! function_exists( 'tessa_authorship_shortcode' ) ) {
/**
 * The shortcode function.
 * @since 0.1.0
 * @param  array  $atts    Shortcode attributes.
 * @param  string $content If the shortcode is a wrapper, this is the content being wrapped.
 * @return string          Output using the template tag.
 */
function tessa_authorship_shortcode ( $atts, $content = null ) {
	$args = (array)$atts;

	$defaults = array(
		'limit' => 5,
		'per_row' => null,
		'orderby' => 'menu_order',
		'order' => 'DESC',
		'id' => 0,
		'authorship' => true, //Options: true->'authorship', false->'authorworks'
		'post_type' => '', //only if authorship is set to false, post-type will accept post-types as array (here as comma-separated list)
		'strict' => true,
		'display_auto' => false,
		'display_author' => true,
		'display_avatar' => true,
		'display_url' => true,
		'display_social' => true,
		'display_content' => false,
		'display_excerpt' => false,
		'effect' => 'fade', // Options: 'fade', 'none'
		'pagination' => false,
		'echo' => true,
		'size' => AUTHORSHIP_IMAGE_SIZE, //was 50
		'category' => 0,
		'tags' => 0
	);

	$args = shortcode_atts( $defaults, $atts );

	// Make sure we return and don't echo.
	$args['echo'] = false;

	// Fix integers.
	if ( isset( $args['limit'] ) ) $args['limit'] = intval( $args['limit'] );
	if ( isset( $args['size'] ) &&  ( 0 < intval( $args['size'] ) ) ) : 
		$args['size'] = intval( $args['size'] );
	else:
		global $_wp_additional_image_sizes;
		if ( !array_key_exists( $args['size'] , $_wp_additional_image_sizes) ) : 
			$args['size'] = AUTHORSHIP_IMAGE_SIZE;
		endif;
	endif;
	if ( isset( $args['category'] ) && is_numeric( $args['category'] ) ) $args['category'] = intval( $args['category'] );
	// Fix arrays
	if ( isset($args['post_type']) && !empty($args['post_type']) ) $args['post_type'] = explode(',', $args['post_type']);
	// Fix booleans.
	foreach ( array( 'strict', 'display_auto', 'display_content', 'display_excerpt', 'display_author', 'display_url', 'display_social', 'authorship', 'pagination', 'display_avatar' ) as $k => $v ) {
		if ( isset( $args[$v] ) && ( 'true' == $args[$v] || '1' == $args[$v] ) ) {
			$args[$v] = true;
		} else {
			$args[$v] = false;
		}
	}
	return tessa_authorship( $args );
} // End tessa_authorship_shortcode()
}

add_shortcode( 'tessa_authorship', 'tessa_authorship_shortcode' );

if ( ! function_exists( 'tessa_authorship_content_default_filters' ) ) {
/**
 * Adds default filters to the "tessa_authorship_content" filter point.
 * @since 0.1.0
 * @return void
 */
function tessa_authorship_content_default_filters () {
	add_filter( 'tessa_authorship_content', 'do_shortcode' );
} // End tessa_authorship_content_default_filters()

add_action( 'tessa_authorship_before', 'tessa_authorship_content_default_filters' );
}

if ( !function_exists('eae_encode_emails') ) {
/**
 * Searches for plain email addresses in given $string and
 * encodes them (by default) with the help of eae_encode_str().
 * 
 * Regular expression is based on based on John Gruber's Markdown.
 * http://daringfireball.net/projects/markdown/
 * 
 * @param string $string Text with email addresses to encode
 * @return string $string Given text with encoded email addresses
 * @since  0.2.0
 */
function eae_encode_emails($string) {

	// abort if $string doesn't contain a @-sign
	if (apply_filters('eae_at_sign_check', true)) {
		if (strpos($string, '@') === false) return $string;
	}

	// override encoding function with the 'eae_method' filter
	$method = apply_filters('eae_method', 'eae_encode_str');

	// override regex pattern with the 'eae_regexp' filter
	$regexp = apply_filters(
		'eae_regexp',
		'{
			(?:mailto:)?
			(?:
				[-!#$%&*+/=?^_`.{|}~\w\x80-\xFF]+
			|
				".*?"
			)
			\@
			(?:
				[-a-z0-9\x80-\xFF]+(\.[-a-z0-9\x80-\xFF]+)*\.[a-z]+
			|
				\[[\d.a-fA-F:]+\]
			)
		}xi'
	);

	return preg_replace_callback(
		$regexp,
		create_function(
            '$matches',
            'return '.$method.'($matches[0]);'
        ),
		$string
	);

}
}
if ( !function_exists('eae_encode_str') ) {
/**
 * Encodes each character of the given string as either a decimal
 * or hexadecimal entity, in the hopes of foiling most email address
 * harvesting bots.
 *
 * Based on Michel Fortin's PHP Markdown:
 *   http://michelf.com/projects/php-markdown/
 * Which is based on John Gruber's original Markdown:
 *   http://daringfireball.net/projects/markdown/
 * Whose code is based on a filter by Matthew Wickline, posted to
 * the BBEdit-Talk with some optimizations by Milian Wolff.
 *
 * @param string $string Text with email addresses to encode
 * @return string $string Given text with encoded email addresses
 * @since  0.2.0
 */
function eae_encode_str($string) {
	$chars = str_split($string);
	$seed = mt_rand(0, (int) abs(crc32($string) / strlen($string)));
	foreach ($chars as $key => $char) {
		$ord = ord($char);

		if ($ord < 128) { // ignore non-ascii chars

			$r = ($seed * (1 + $key)) % 100; // pseudo "random function"

			if ($r > 60 && $char != '@') ; // plain character (not encoded), if not @-sign
			else if ($r < 45) $chars[$key] = '&#x'.dechex($ord).';'; // hexadecimal
			else $chars[$key] = '&#'.$ord.';'; // decimal (ascii)

		}

	}

	return implode('', $chars);
}
}

?>
