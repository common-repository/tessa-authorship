<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly.

/**
 * Tessa Authorship Class
 *
 * Nearly all functionality pertaining to the Authorship Post-Type setup and retrieval.
 *
 * @package WordPress
 * @subpackage tessa_authorship
 * @category Plugin
 * @author Uli Hake
 * @since 0.1.0
 */
class tessa_authorship {
	private $dir;
	private $assets_dir;
	private $assets_url;
	private $token;
	public $version;
	private $file;

	/**
	 * Constructor function.
	 *
	 * @access public
	 * @since 0.1.0
	 * @return void
	 */
	public function __construct( $file ) {
		$this->dir = dirname( $file );
		$this->file = $file;
		$this->assets_dir = trailingslashit( $this->dir ) . 'assets';
		$this->assets_url = esc_url( trailingslashit( plugins_url( '/assets/', $file ) ) );
		$this->token = 'authorship';
		$this->load_plugin_textdomain();
		add_action( 'init', array( $this, 'load_localisation' ), 0 );

		// Run this on activation.
		register_activation_hook( $this->file, array( $this, 'activation' ) );

		
		add_action( 'init', array( $this, 'register_post_type' ) );
		add_action( 'init', array( $this, 'register_taxonomy' ) );	

		//let's register our stuff
		add_action( 'init', array($this, 'registerAuthorshipImageThumbnails'), 99);

		if ( is_admin() ) {
			global $pagenow;

			add_action( 'admin_menu', array( $this, 'meta_box_setup' ), 20 );
			add_action( 'save_post', array( $this, 'meta_box_save' ) );
			add_filter( 'enter_title_here', array( $this, 'enter_title_here' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ), 10 );
			add_filter( 'post_updated_messages', array( $this, 'updated_messages' ) );

			if ( $pagenow == 'edit.php' && isset( $_GET['post_type'] ) && esc_attr( $_GET['post_type'] ) == $this->token ) {
				add_filter( 'manage_edit-' . $this->token . '_columns', array( $this, 'register_custom_column_headings' ), 10, 1 );
				add_action( 'manage_posts_custom_column', array( $this, 'register_custom_columns' ), 10, 2 );
			}
			//late init, why not?
			add_action( 'plugins_loaded', array( $this, 'init_tasrpas' ), 99 );
			//set our thing to  translate with wpml
			//add_action( 'plugins_loaded', array( $this, 'translate_authorship_slug' ), 99 );
			//$this->translate_authorship_slug();
		}

		add_action( 'after_setup_theme', array( $this, 'ensure_post_thumbnails_support' ), 99 );
	} // End __construct()

	/**
	 * WPML support for our slugs.
	 * @TODO As of 0.7.1 Slug translation is still inconsistent in WPML
	 * Bellow will fix an error for the WPML settings page caused by not preloading the existing custom post-types 
	 * We still activate custom slug translation for authorship post-type and invite the administrator to always define the slug translations
	 * as WPML only respects the general setting for all custom post-types but not the individual one to turn it off and on
	 * @param string $slugupdate to set custom slug
	 * @access public
	 * @return void
	*/
    public function translate_authorship_slug($slugupdate = 'authorship') {
        global $sitepress, $wpdb, $sitepress_settings, $tasrpas_options;

        if(!defined('WOOCOMMERCE_VERSION') || (!isset($GLOBALS['ICL_Pro_Translation']) || is_null($GLOBALS['ICL_Pro_Translation']))){
            return;
        }
		$tasrpas_options = get_option('tasrpas_settings');
		
		$slug_single = isset($tasrpas_options['authorship_slug_single']) && $tasrpas_options['authorship_slug_single'] ? $tasrpas_options['authorship_slug_single'] : 'authorship';

        $slug = $slug_single;
		if (isset($slugupdate) && !empty($slugupdate) && $slugupdate != 'authorship') :
			$slug = $slugupdate;
		endif;
		
        $string = $wpdb->get_row($wpdb->prepare("SELECT id,status FROM {$wpdb->prefix}icl_strings WHERE name = %s AND value = %s ", 'URL slug: ' . $slug, $slug));

        if(!$string){
            icl_register_string('WordPress', 'URL slug: ' . $slug, $slug);
            $string = $wpdb->get_row($wpdb->prepare("SELECT id,status FROM {$wpdb->prefix}icl_strings WHERE name = %s AND value = %s ", 'URL slug: ' . $slug, $slug));
        }
        
        if(isset($sitepress_settings['posts_slug_translation']['types'])){
			$iclsettings['posts_slug_translation']['types'] = $sitepress_settings['posts_slug_translation']['types'];
        }
		
        if( empty($sitepress_settings['theme_localization_type']) || $sitepress_settings['theme_localization_type'] != 1 ){
            $sitepress->save_settings(array('theme_localization_type' => 1));
        }
		//@TODO: additional tests if slug changes are effective, should be like that now
        if($string->status != ICL_STRING_TRANSLATION_COMPLETE){
            //get translations from .mo files
            $current_language = $sitepress->get_current_language();
            $default_language = $sitepress->get_default_language();
            $active_languages = $sitepress->get_active_languages();
            $string_id = $string->id;
            if(empty($string_id)){
                $string_id = icl_register_string('WordPress', 'URL slug: ' . $slug, $slug);
            }
            foreach($active_languages as $language){
                if($default_language != $language['code']){
                    $sitepress->switch_lang($language['code']);
                    $text = $slug;
                    $context = 'slug';
                    $domain = 'tessa-authorship';
                    $this->load_plugin_textdomain();
                    $string_text = _x( $text, $context, $domain );
                    unload_textdomain($domain);
                    icl_add_string_translation($string_id,$language['code'],$string_text,ICL_STRING_TRANSLATION_COMPLETE,null);
                    $sitepress->switch_lang($current_language);
                }
            }
            $this->load_plugin_textdomain();
            $wpdb->update(
                $wpdb->prefix.'icl_strings',
                array(
                    'status' => ICL_STRING_TRANSLATION_COMPLETE
                ),
                array( 'id' => $string_id )
            );
        }
		
		//@TODO: WPML does still not respect the setting to turn off individual custom post-type slug translation. After some testing, the best solution is to always turn the slug translation on for the post type.		
		if (isset($sitepress_settings['posts_slug_translation']['on']) && $sitepress_settings['posts_slug_translation']['on'] ) :
			$iclsettings['posts_slug_translation']['types']['authorship'] = 1;
		else:
			$iclsettings['posts_slug_translation']['types']['authorship'] = 0;
		endif;
		
		$sitepress->save_settings($iclsettings);
    }
	
	
	
	/**
	 * Init Tessa Authorship Related Post Authors.
	 *
	 * @access public
	 * @return void
	 */
	public function init_tasrpas () {
		global $tasrpas_options;
		$tasrpas_options = get_option('tasrpas_settings');
		$this->tasrpas = new tasrpas_worker($this->file, $this->assets_dir, $this->assets_url, $this->version);			
	}
	/**
	 * Register the post type.
	 *
	 * @access public
	 * @param string $token
	 * @param string 'Authorship'
	 * @param string 'Authorship'
	 * @param array $supports
	 * @return void
	 * @modified 0.4.1
	 */
	public function register_post_type () {
		global $tasrpas_options;
		$tasrpas_options = get_option('tasrpas_settings');
		$tas_title = isset($tasrpas_options['authorship_generic_name']) && $tasrpas_options['authorship_generic_name'] ? $tasrpas_options['authorship_generic_name'] : 'Authorship';
		$tas_single = isset($tasrpas_options['authorship_single_name']) && $tasrpas_options['authorship_single_name'] ? $tasrpas_options['authorship_single_name'] : 'Author';

		$labels = array(
			'name' => _x( $tas_title, 'post type general name', 'tessa-authorship' ),
			'singular_name' => _x( $tas_single, 'post type singular name', 'tessa-authorship' ),
			'add_new' => _x( 'Add New', $tas_single, 'tessa-authorship' ),
			'add_new_item' => sprintf( __( 'Add New %s', 'tessa-authorship' ), __( $tas_single, 'tessa-authorship' ) ),
			'edit_item' => sprintf( __( 'Edit %s', 'tessa-authorship' ), __( $tas_single, 'tessa-authorship' ) ),
			'new_item' => sprintf( __( 'New %s', 'tessa-authorship' ), __( $tas_single, 'tessa-authorship' ) ),
			'all_items' => sprintf( __( 'All %s', 'tessa-authorship' ), __( $tas_title, 'tessa-authorship' ) ),
			'view_item' => sprintf( __( 'View %s', 'tessa-authorship' ), __( $tas_single, 'tessa-authorship' ) ),
			'search_items' => sprintf( __( 'Search %a', 'tessa-authorship' ), __( $tas_title, 'tessa-authorship' ) ),
			'not_found' =>  sprintf( __( 'No %s Found', 'tessa-authorship' ), __( $tas_single, 'tessa-authorship' ) ),
			'not_found_in_trash' => sprintf( __( 'No %s Found In Trash', 'tessa-authorship' ), __( $tas_single, 'tessa-authorship' ) ),
			'parent_item_colon' => '',
			'menu_name' => __( $tas_title, 'tessa-authorship' )
		);
		$slug_single = isset($tasrpas_options['authorship_slug_single']) && $tasrpas_options['authorship_slug_single'] ? $tasrpas_options['authorship_slug_single'] : 'authorship';
		$slug_plural = isset($tasrpas_options['authorship_slug_plural']) && $tasrpas_options['authorship_slug_plural'] ? $tasrpas_options['authorship_slug_plural'] : 'authorships';
		//$single_slug = apply_filters( 'tessa_authorship_single_slug', _x( $slug_single, 'single post url slug', 'tessa-authorship' ) );
		//$archive_slug = apply_filters( 'tessa_authorship_archive_slug', _x( $slug_plural, 'post archive url slug', 'tessa-authorship' ) );
		$single_slug = apply_filters( 'tessa_authorship_single_slug', $slug_single ); //WPML is inconsistent with slug translation, moreover we handle this in translate_authorship_slug
		$archive_slug = apply_filters( 'tessa_authorship_archive_slug', $slug_plural ); //WPML is inconsistent with slug translation, moreover we handle this in translate_authorship_slug

		//set WPML things here and not like before in plugins_loaded, init fires later and we need our personalizations register correctly 
		$this->translate_authorship_slug($single_slug);
		
		$args = array(
			'labels' => $labels,
			'public' => true,
			'publicly_queryable' => true,
			'show_ui' => true,
			'show_in_menu' => true,
			'query_var' => true,
			'rewrite' => array( 'slug' => $single_slug, 'with_front' => false ),
			'capability_type' => 'post',
			'has_archive' => $archive_slug,
			'hierarchical' => false,
			'supports' => array( 'title', 'author' ,'editor', 'thumbnail', 'page-attributes', 'comments', 'excerpt', 'post-formats' ),
			'menu_position' => 4,
			'menu_icon' => '',
			//'taxonomies' => array('authorship-group', 'post_tag')
		);
		register_post_type( $this->token, $args );	
		//register current version;
		$this->register_plugin_version();
		
	} // End register_post_type()

	public function registerAuthorshipImageThumbnails() {
		global $tasrpas_options, $tessa_authorship_thumbnails;
		if ( class_exists('MultiPostThumbnails') ) {
			$tasrpas_options = get_option('tasrpas_settings');
			global $tasrpas_options;
			$tasrpas_options = get_option('tasrpas_settings');
			$tas_title = isset($tasrpas_options['authorship_generic_name']) && $tasrpas_options['authorship_generic_name'] ? $tasrpas_options['authorship_generic_name'] : 'Authorship';
			$tas_single = isset($tasrpas_options['authorship_single_name']) && $tasrpas_options['authorship_single_name'] ? $tasrpas_options['authorship_single_name'] : 'Author';
			
			$args = array(
				'label' => __('Authorship Author Image', 'tessa-authorship'),
				'id' => 'tas',
				'post_type' => 'authorship',
				'base_file' => $this->file,
			);
			$theMPT = new MultiPostThumbnails($args);
			$theMPT->register($args);
			$tasrpas_options['post_types'] = !empty( $tasrpas_options['post_types'] ) ? $tasrpas_options['post_types'] : array();
			foreach( get_post_types( array( 'public'=>true, 'show_ui'=>true ), 'objects' ) as $apost_type ):
				if ($apost_type->name != 'authorship' && in_array( $apost_type->name, $tasrpas_options['post_types'] ) ) : 
					$args = array(
							'label' => __($tas_title . ' Post Image', 'tessa-authorship'),
							'id' => 'tas',
							'post_type' => $apost_type->name,
							'base_file' => $this->file,
						);
					new MultiPostThumbnails($args);
				endif;
			endforeach;
			
		}
	}
	
	
	/**
	 * Register the "authorship-category" taxonomy.
	 * @access public
	 * @since  0.1.0
	 * @return void
	 */
	public function register_taxonomy () {
		global $tasrpas_options;
		$tasrpas_options = get_option('tasrpas_settings');
		$tas_title = isset($tasrpas_options['authorship_generic_name']) && $tasrpas_options['authorship_generic_name'] ? $tasrpas_options['authorship_generic_name'] : 'Authorship';
		$tas_single = isset($tasrpas_options['authorship_single_name']) && $tasrpas_options['authorship_single_name'] ? $tasrpas_options['authorship_single_name'] : 'Author';
		$args = array( 'public' => true, 'hierarchical' => true, 'show_ui' => true, 'show_admin_column' => true, 'query_var' => true, 'show_in_nav_menus' => false, 'show_tagcloud' => false );
		$this->taxonomy_category = new tessa_authorship_Taxonomy($token = 'authorship-category', $singular = __($tas_single . ' Category', 'tessa-authorship'), $plural = __($tas_single . ' Categories', 'tessa-authorship'), $args);	
		//$this->taxonomy_category = new tessa_authorship_Taxonomy(); // Leave arguments empty, to use the default arguments.
		$this->taxonomy_category->register();
		$args = array( 'public' => true, 'hierarchical' => false, 'show_ui' => true, 'show_admin_column' => true, 'query_var' => true, 'show_in_nav_menus' => false, 'show_tagcloud' => false );
		$this->taxonomy_tags = new tessa_authorship_Taxonomy($token = 'authorship-tags', $singular = __($tas_single . ' Tags', 'tessa-authorship'), $plural = __($tas_single . ' Tags', 'tessa-authorship'), $args);
		$this->taxonomy_tags->register();
	} // End register_taxonomy()

	/**
	 * Add custom columns for the "manage" screen of this post type.
	 *
	 * @access public
	 * @param string $column_name
	 * @param int $id
	 * @since  0.1.0
	 * @return void
	 */
	public function register_custom_columns ( $column_name, $id ) {
		global $wpdb, $post;

		$meta = get_post_custom( $id );

		switch ( $column_name ) {

			case 'image':
				$value = '';
				
				$value = $this->get_image( $id, array(40, 40), get_post_type( $id ) );

				echo $value;
			break;

			default:
			break;

		}
	} // End register_custom_columns()

	/**
	 * Add custom column headings for the "manage" screen of this post type.
	 *
	 * @access public
	 * @param array $defaults
	 * @since  0.1.0
	 * @return void
	 */
	public function register_custom_column_headings ( $defaults ) {
		$new_columns = array( 'image' => __( 'Image', 'tessa-authorship' ) );

		$last_item = '';

		if ( isset( $defaults['date'] ) ) { unset( $defaults['date'] ); }

		if ( count( $defaults ) > 2 ) {
			$last_item = array_slice( $defaults, -1 );

			array_pop( $defaults );
		}
		$defaults = array_merge( $defaults, $new_columns );

		if ( $last_item != '' ) {
			foreach ( $last_item as $k => $v ) {
				$defaults[$k] = $v;
				break;
			}
		}

		return $defaults;
	} // End register_custom_column_headings()

	/**
	 * Update messages for the post type admin.
	 * @since  0.1.0
	 * @param  array $messages Array of messages for all post types.
	 * @return array           Modified array.
	 */
	public function updated_messages ( $messages ) {
		global $post, $post_ID, $tasrpas_options;
		$tasrpas_options = get_option('tasrpas_settings');
		$tas_title = isset($tasrpas_options['authorship_generic_name']) && $tasrpas_options['authorship_generic_name'] ? $tasrpas_options['authorship_generic_name'] : 'Authorship';
		$tas_single = isset($tasrpas_options['authorship_single_name']) && $tasrpas_options['authorship_single_name'] ? $tasrpas_options['authorship_single_name'] : 'Author';

	  $messages[$this->token] = array(
	    0 => '', // Unused. Messages start at index 1.
	    1 => sprintf( __( "%s updated. %sView %s", "tessa-authorship" ), $tas_single, '<a href="' . esc_url( get_permalink( $post_ID ) ) . '">', '</a>' ),
	    2 => __( 'Custom field updated.', 'tessa-authorship' ),
	    3 => __( 'Custom field deleted.', 'tessa-authorship' ),
	    4 => __( "$tas_single updated.", "tessa-authorship" ),
	    /* translators: %s: date and time of the revision */
	    5 => isset($_GET['revision']) ? sprintf( __( "%s restored to revision from %s", 'tessa-authorship' ), $tas_single, wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
	    6 => sprintf( __( "%s published. %sView %s", 'tessa-authorship' ), $tas_single, '<a href="' . esc_url( get_permalink( $post_ID ) ) . '">', '</a>' ),
	    7 => __('Authorship saved.'),
	    8 => sprintf( __( "%s submitted. %sPreview %s", 'tessa-authorship' ), $tas_single, '<a target="_blank" href="' . esc_url( add_query_arg( 'preview', 'true', get_permalink( $post_ID ) ) ) . '">', '</a>' ),
	    9 => sprintf( __( "%s scheduled for: %s. %s Preview %s", 'tessa-authorship' ),
	      // translators: Publish box date format, see http://php.net/date
	      $tas_single, '<strong>' . date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) ) . '</strong>', '<a target="_blank" href="' . esc_url( get_permalink($post_ID) ) . '">', '</a>' ),
	    10 => sprintf( __( "%s draft updated. %sPreview %s", 'tessa-authorship' ), $tas_single, '<a target="_blank" href="' . esc_url( add_query_arg( 'preview', 'true', get_permalink( $post_ID ) ) ) . '">', '</a>' ),
	  );

	  return $messages;
	} // End updated_messages()

	/**
	 * Setup the meta box.
	 *
	 * @access public
	 * @since  0.1.0
	 * @return void
	 */
	public function meta_box_setup () {
		global $post, $post_ID, $tasrpas_options;
		$tasrpas_options = get_option('tasrpas_settings');
		$tas_title = isset($tasrpas_options['authorship_generic_name']) && $tasrpas_options['authorship_generic_name'] ? $tasrpas_options['authorship_generic_name'] : 'Authorship';
		$tas_single = isset($tasrpas_options['authorship_single_name']) && $tasrpas_options['authorship_single_name'] ? $tasrpas_options['authorship_single_name'] : 'Author';	
		add_meta_box( 'authorship-data', __( "The {$tas_single}'s Social Links", 'tessa-authorship' ), array( $this, 'meta_box_content' ), $this->token, 'normal', 'high' );
	} // End meta_box_setup()

	/**
	 * The contents of our meta box.
	 *
	 * @access public
	 * @since  0.1.0
	 * @return void
	 */
	public function meta_box_content () {
		global $post_id;
		$fields = get_post_custom( $post_id );
		$field_data = $this->get_custom_fields_settings();

		$html = '';
		$html .= '<input type="hidden" name="tasrpas_' . $this->token . '_nonce" id="tasrpas_' . $this->token . '_nonce" value="' . wp_create_nonce( plugin_basename( $this->dir ) ) . '" />';
		//$html .= wp_nonce_field( 'wp_' . $this->token . '_' . $post_id . '_nonced', 'wp_' . $this->token . '_' . $post_id . '_noncer', true, false );

		if ( 0 < count( $field_data ) ) {
			$html .= '<table class="form-table">' . "\n";
			$html .= '<tbody>' . "\n";

			foreach ( $field_data as $k => $v ) {
				$data = $v['default'];
				if ( isset( $fields['_' . $k] ) && isset( $fields['_' . $k][0] ) ) {
					$data = $fields['_' . $k][0];
				}

				$html .= '<tr valign="top"><th scope="row"><label for="' . esc_attr( $k ) . '">' . $v['name'] . '</label></th><td><input name="' . esc_attr( $k ) . '" type="text" id="' . esc_attr( $k ) . '" class="regular-text" value="' . esc_attr( $data ) . '" />' . "\n";
				$html .= '<p class="description">' . $v['description'] . '</p>' . "\n";
				$html .= '</td><tr/>' . "\n";
			}

			$html .= '</tbody>' . "\n";
			$html .= '</table>' . "\n";
		}

		echo $html;
	} // End meta_box_content()

	/**
	 * Save meta box fields.
	 *
	 * @access public
	 * @since  0.1.0
	 * @param int $post_id
	 * @return void
	 * @modified 0.4.0 add wpml support
	 */
	public function meta_box_save ( $post_id ) {
		global $post, $messages, $tasrpas_options;
		if( $post_id ):
			
			if ( ( get_post_type() != $this->token ) || ! wp_verify_nonce( $_POST['tasrpas_' . $this->token . '_nonce'], plugin_basename( $this->dir ) ) ) {
				return $post_id;
			}

			if ( 'page' == $_POST['post_type'] ) {
				if ( ! current_user_can( 'edit_page', $post_id ) ) {
					return $post_id;
				}
			} else {
				if ( ! current_user_can( 'edit_post', $post_id ) ) {
					return $post_id;
				}
			}

			$field_data = $this->get_custom_fields_settings();
			$fields = array_keys( $field_data );

			foreach ( $fields as $f ) {

				${$f} = strip_tags(trim($_POST[$f]));

				// Escape the URLs.
				if ( 'url' == $field_data[$f]['type'] ) {
					${$f} = esc_url( ${$f} );
				}

				if ( get_post_meta( $post_id, '_' . $f ) == '' ) {
					add_post_meta( $post_id, '_' . $f, ${$f}, true );
				} elseif( ${$f} != get_post_meta( $post_id, '_' . $f, true ) ) {
					update_post_meta( $post_id, '_' . $f, ${$f} );
				} elseif ( ${$f} == '' ) {
					delete_post_meta( $post_id, '_' . $f, get_post_meta( $post_id, '_' . $f, true ) );
				}
			}
			
			/* countercheck author's associated content 0.3.0 */
			/* make wpml compatible 0.4.0 */
			$post_ids = $this->authorship_author_related_posts( $post_id, false );
			if (!is_array($post_ids)) :
				$post_ids = array_filter( wp_parse_id_list( $post_ids ) );
				if ( is_array($post_ids) && 0 < count($post_ids) ) :
					foreach ($post_ids as $i => $id) :
						$author_list = $this->authorship_post_related_authors( $id, false );
						if (is_array($author_list)) :
							unset($post_ids[$i]);
						else :
							$author_list = array_filter( wp_parse_id_list( $author_list ) );
							if (!in_array($post_id, $author_list)) :
								unset($post_ids[$i]);
							endif;
						endif;
					endforeach;
					
					update_post_meta( $post_id, '_saprsatids', implode( ',', $post_ids  ) );
				endif;
			endif;
		endif;
	} // End meta_box_save()

	/**
	 * Customise the "Enter title here" text.
	 *
	 * @access public
	 * @since  0.1.0
	 * @param string $title
	 * @return void
	 */
	public function enter_title_here ( $title ) {
		if ( get_post_type() == $this->token ) {
			global $post, $post_ID, $tasrpas_options;
			$tasrpas_options = get_option('tasrpas_settings');
			$tas_title = isset($tasrpas_options['authorship_generic_name']) && $tasrpas_options['authorship_generic_name'] ? $tasrpas_options['authorship_generic_name'] : 'Authorship';
			$tas_single = isset($tasrpas_options['authorship_single_name']) && $tasrpas_options['authorship_single_name'] ? $tasrpas_options['authorship_single_name'] : 'Author';		
			$title = __( "Enter the {$tas_single}'s name here", 'tessa-authorship' );
		}
		return $title;
	} // End enter_title_here()

	/**
	 * Enqueue post type admin CSS.
	 *
	 * @access public
	 * @since   0.1.0
	 * @return   void
	 */
	public function enqueue_admin_styles ($hook_suffix) {
		if ( $hook_suffix == 'edit.php' || $hook_suffix == 'authorship_page_tessa-authorship' ) :
			wp_register_style( 'tessa-authorship-admin', $this->assets_url . '/css/admin.css', array(), '1.0.1' );
			wp_enqueue_style( 'tessa-authorship-admin' );
		endif;
	} // End enqueue_admin_styles()

	/**
	 * Get the settings for the custom fields.
	 * @since  0.1.0
	 * @return array
	 * @modified 0.4.5 two more standard fields, location and year of birth, for artists essential
	 * @modified 0.5.0 declare as static
	 */
	public static function get_custom_fields_settings () {
		global $post, $post_ID, $tasrpas_options;
		$tasrpas_options = get_option('tasrpas_settings');
		$tas_title = isset($tasrpas_options['authorship_generic_name']) && $tasrpas_options['authorship_generic_name'] ? $tasrpas_options['authorship_generic_name'] : 'Authorship';
		$tas_single = isset($tasrpas_options['authorship_single_name']) && $tasrpas_options['authorship_single_name'] ? $tasrpas_options['authorship_single_name'] : 'Author';
	
		$fields = array();
		$fields['headline'] = array(
		    'name' => __( 'Headline', 'tessa-authorship' ),
		    'description' => __( "Enter a headline for the current {$tas_single}.", 'tessa-authorship' ),
		    'type' => 'text',
		    'default' => '',
		    'section' => 'info'
		);

		$fields['location'] = array(
		    'name' => __( 'Location', 'tessa-authorship' ),
		    'description' => __( "Enter a location for the current {$tas_single}.", 'tessa-authorship' ),
		    'type' => 'text',
		    'default' => '',
		    'section' => 'info'
		);
		$fields['birthyear'] = array(
		    'name' => __( 'Year of birth', 'tessa-authorship' ),
		    'description' => __( "Enter year of birth for {$tas_single}.", 'tessa-authorship' ),
		    'type' => 'text',
		    'default' => '',
		    'section' => 'info'
		);

		$fields['email_link'] = array(
		    'name' => __( 'E-mail Address', 'tessa-authorship' ),
		    'description' => __( 'Enter an e-mail address.', 'tessa-authorship' ),
		    'title' => 'Send Email', //do not translate, will be __() on output
			'class' => 'email-link',
		    'type' => 'text',
		    'default' => '',
		    'section' => 'info'
		);

		$fields['twitter_link'] = array(
		    'name' => __( 'Twitter', 'tessa-authorship' ),
		    'description' => __( 'Enter Twitter url', 'tessa-authorship' ),
		    'title' => 'Follow on Twitter', //do not translate, will be __() on output
			'class' => 'twitter-link',
		    'type' => 'text',
		    'default' => '',
		    'section' => 'info'
		);

		$fields['facebook_link'] = array(
		    'name' => __( 'Facebook', 'tessa-authorship' ),
		    'description' => __( 'Enter Facebook url', 'tessa-authorship' ),
		    'title' => 'Follow on Facebook', //do not translate, will be __() on output
			'class' => 'facebook-link',
		    'type' => 'text',
		    'default' => '',
		    'section' => 'info'
		);

		$fields['googleplus_link'] = array(
		    'name' => __( 'Google+', 'tessa-authorship' ),
		    'description' => __( 'Enter Google+ url', 'tessa-authorship' ),
		    'title' => 'Follow on Google+', //do not translate, will be __() on output
			'class' => 'google-link',
		    'type' => 'text',
		    'default' => '',
		    'section' => 'info'
		);

		$fields['linkedin_link'] = array(
		    'name' => __( 'LinkedIn', 'tessa-authorship' ),
		    'description' => __( 'Enter LinkedIn url', 'tessa-authorship' ),
		    'title' => 'Follow on LinkedIn', //do not translate, will be __() on output
			'class' => 'linkedin-link',
		    'type' => 'text',
		    'default' => '',
		    'section' => 'info'
		);

		$fields['vimeo_link'] = array(
		    'name' => __( 'Vimeo', 'tessa-authorship' ),
		    'description' => __( 'Enter Vimeo url', 'tessa-authorship' ),
		    'title' => 'Watch on Vimeo', //do not translate, will be __() on output
			'class' => 'vimeo-link',
		    'type' => 'text',
		    'default' => '',
		    'section' => 'info'
		);

		$fields['youtube_link'] = array(
		    'name' => __( 'Youtube', 'tessa-authorship' ),
		    'description' => __( 'Enter Youtube url', 'tessa-authorship' ),
		    'title' => 'Watch on YouTube', //do not translate, will be __() on output
			'class' => 'youtube-link',
		    'type' => 'text',
		    'default' => '',
		    'section' => 'info'
		);

		$fields['soundcloud_link'] = array(
		    'name' => __( 'Soundcloud', 'tessa-authorship' ),
		    'description' => __( 'Enter Soundcloud url', 'tessa-authorship' ),
		    'title' => 'Listen on Soundcloud', //do not translate, will be __() on output
			'class' => 'sound-link',
		    'type' => 'text',
		    'default' => '',
		    'section' => 'info'
		);

		$fields['icompositions_link'] = array(
		    'name' => __( 'iCompositions', 'tessa-authorship' ),
		    'description' => __( 'Enter iCompositions url', 'tessa-authorship' ),
		    'title' => 'Listen on iCompositions', //do not translate, will be __() on output
			'class' => 'sound-link',
		    'type' => 'text',
		    'default' => '',
		    'section' => 'info'
		);
		
		$fields['pinterest_link'] = array(
		    'name' => __( 'Pinterest', 'tessa-authorship' ),
		    'description' => __( 'Enter Pinterest url', 'tessa-authorship' ),
		    'title' => 'Find on Pinterest', //do not translate, will be __() on output
			'class' => 'pinterest-link',
		    'type' => 'text',
		    'default' => '',
		    'section' => 'info'
		);

		$fields['digg_link'] = array(
		    'name' => __( 'Digg', 'tessa-authorship' ),
		    'description' => __( 'Enter Digg url', 'tessa-authorship' ),
		    'title' => 'Digg on Digg', //do not translate, will be __() on output
			'class' => 'digg-link',
		    'type' => 'text',
		    'default' => '',
		    'section' => 'info'
		);
		
		$fields['blog_link'] = array(
		    'name' => __( 'Blog', 'tessa-authorship' ),
		    'description' => __( 'Enter Blog url', 'tessa-authorship' ),
		    'title' => 'Read on blog', //do not translate, will be __() on output
			'class' => 'blog-link',
		    'type' => 'text',
		    'default' => '',
		    'section' => 'info'
		);

		$fields['web_url'] = array(
		    'name' => __( 'URL', 'tessa-authorship' ),
		    'description' => __( 'Enter a website (URL), e.g. http://takebarcelona.com/.', 'tessa-authorship' ),
		    'title' => 'Visit on the web', //do not translate, will be __() on output
			'class' => 'web-link',
		    'type' => 'url',
		    'default' => '',
		    'section' => 'info'
		);
		$recover = $fields;
		$fields = apply_filters('tessa_authorship_custom_field_settings', $fields);
		if (!is_array($fields)) :
			$fields = $recover;
		else :
			//store vital custom fields back into the area, at least headline shouldn't be missing, as well as email_link as these two are hardcoded into the plugin
			if (!in_array($recover['headline'], $fields)) :
				array_unshift($fields,$recover['headline']);
				$wpml = true;
			endif;
			if (!in_array($recover['email_link'], $fields)) :
				array_unshift($fields, $recover['email_link']);
				$wpml = true;
			endif;
			if (!in_array($recover['web_url'], $fields)) :
				array_unshift($fields,$recover['web_url']);
				$wpml = true;
			endif;
		endif;

		//0.4.0 WPML Support built-in, only when admin
		if (is_admin() && $fields != $recover && class_exists('SitePress') && defined('WPML_TM_VERSION')) :
			/* basic configuration in WPML file, but as the user can add or modify fields, we simply add the field support*/
			global $iclTranslationManagement;
			if ( isset( $iclTranslationManagement ) ) :
				foreach ( $fields as $key => $field ) :
					//0=don't translate, 1=ignore, 2=translate, we want to allow to translate all of our fields because somebody may include a website in another language, etc. Values can be transferred and wpml setup often is kind of burden, especially when it comes to dynamic fields that may change, so better prepare this here
					$iclTranslationManagement->settings['custom_fields_translation']['_' . $key] = 2;
				endforeach;
				$iclTranslationManagement->save_settings();
			endif;
		endif;
		return $fields;
	} // End get_custom_fields_settings()

	
	
	/**
	 * Get the image for the given ID. If no featured image, check for Gravatar e-mail.
	 * @param  int 				$id   Post ID.
	 * @param  string/array $size Image dimension. int is not supported anymore, if somebody calls directly, he specifies an existing image size identifier or an array
	 * @since  0.1.0
	 * @return string       	<img> tag.
	 * updated in 0.4.0 we predefine $size in get_authorship, no need to process this for every image when the result is always the same
	 */
	protected function get_image ( $id, $size, $post_type ) {
		global $mpt_available, $tasrpas_options;
		$response = '';
		//load gravatar_email here, this will allow us to reduce a unique fall-back as last ressort
		$gravatar_email = get_post_meta( $id, '_email_link', true );

		if ($mpt_available && class_exists('MultiPostThumbnails') && MultiPostThumbnails::has_post_thumbnail($post_type, 'tas', intval($id) ) ) :
			/* @since 0.3.2 */
			if ( isset($tasrpas_options['avatar_bg']) && $tasrpas_options['avatar_bg'] ) :
				$response = MultiPostThumbnails::get_post_thumbnail_url($post_type, 'tas', intval( $id ), $size);
			else :
				$response = MultiPostThumbnails::get_the_post_thumbnail($post_type, 'tas', intval( $id ), $size, array( 'class' => 'avatar' ),false );
			endif;	
		elseif ( has_post_thumbnail( $id ) ) : //second best
			/* @since 0.3.2 (@TODO: not in use right now) */
			if ( isset($tasrpas_options['avatar_bg']) && $tasrpas_options['avatar_bg'] ) :
				$response =  wp_get_attachment_image_src( get_post_thumbnail_id(intval( $id ) ), $size );
			else :
				$response = get_the_post_thumbnail( intval( $id ), $size, array( 'class' => 'avatar' ) );
			endif;	
		elseif ( '' != $gravatar_email && is_email( $gravatar_email ) ) :
			/* @since 0.3.2 */
			if ( isset($tasrpas_options['avatar_bg']) && $tasrpas_options['avatar_bg'] ) :
				$avatar = get_avatar( $gravatar_email, $size );
				preg_match('/src=["|\'](.*?)["|\']/i', $avatar, $matches);
				if ( isset($matches[1]) && !empty($matches[1]) ) :
					$response = $matches[1];
				endif;
			else :
				$response = get_avatar( $gravatar_email, $size );
			endif;	
		else : //@TODO: here we should handle where we could obtain suitable images, e.g. from a directory within the theme first (assets/images/authorship) and afterwards we could bundle some images into the plugin as well. this holds true for 
		endif;

		return $response;
	} // End get_image()

	/**
	 * Get authorship.
	 * @param  string/array $args Arguments to be passed to the query.
	 * @since  0.1.0
	 * @modified 0.4.4
	 * @return array/boolean      Array if true, boolean if false.
	 */
	public function get_authorship ( $args = '' ) {
		global $tasrpas_options;
		$tasrpas_options = get_option('tasrpas_settings');

		$defaults = array(
			'limit' => 5,
			'orderby' => 'menu_order',
			'order' => 'DESC',
			'id' => 0,
			'category' => 0
		);

		$args = wp_parse_args( $args, $defaults );

		// Allow child themes/plugins to filter here.
		$args = apply_filters( 'tessa_get_authorship_args', $args );

		// The Query Arguments.
		$query_args = array();
		$query_args['numberposts'] = $args['limit'];
		$query_args['orderby'] = $args['orderby'];
		$query_args['order'] = $args['order'];
		$query_args['suppress_filters'] = false;

		$ids = explode( ',', $args['id'] );
		//ids will be set via args
		//fix for WPML that runs into problems with $query_args['p'] for a single page
		if ( 0 < intval( $args['id'] ) && 0 < count( $ids ) ) {
			$ids = array_map( 'intval', $ids );
			//if ( 1 == count( $ids ) && is_numeric( $ids[0] ) && ( 0 < intval( $ids[0] ) ) ) {
			//	$query_args['p'] = intval( $args['id'] );
			//} else {
			$query_args['ignore_sticky_posts'] = 1;
			$query_args['post__in'] = $ids;
			//}
		}
		
		//@TODO: Fix WPML Problem with post_types as array and id set at the same time
		//That's not a problem, when we have p or post__in set, post_type isn't of much help
		if ($args['authorship'] == true) :
			$query_args['post_type'] = 'authorship';
		else :
			$tas_post_types = $tasrpas_options['post_types'];
			if ( !is_array($args['post_type']) && isset($tas_post_types) && is_array($tas_post_types) && count($tas_post_types) > 0 ) :
				$query_args['post_type'] = $tas_post_types;
			elseif ( is_array($args['post_type']) && count($args['post_type']) > 0 ) :
				$query_args['post_type'] = $args['post_type'];
			endif;
			//reassure things
			if (empty($query_args['post_type']) || !isset($query_args['post_type']) ) :
				$query_args['post_type'] = 'post';
			endif;
		endif;
		
		if ( $args['strict'] == true && (!$args['id'] || empty($args['id']) ) ) :
			$query_args['post__in'] = array(0);
		endif;
		// Whitelist checks.
		if ( ! in_array( $query_args['orderby'], array( 'none', 'ID', 'author', 'title', 'date', 'modified', 'parent', 'rand', 'comment_count', 'menu_order', 'meta_value', 'meta_value_num' ) ) ) {
			$query_args['orderby'] = 'date';
		}

		if ( ! in_array( $query_args['order'], array( 'ASC', 'DESC' ) ) ) {
			$query_args['order'] = 'DESC';
		}
		$tax_field_type = '';

		$tag_field_type = '';
		$tags = array();
		$term = array();
		// If the category ID is specified or an array from widget config.
		if ( (is_array($args['category']) && !empty($args['category'])) || ( is_numeric( $args['category'] ) && 0 < intval( $args['category'] ) ) ) {
			$tax_field_type = 'id';
			$term = is_array($args['category']) ? $args['category'] : explode(',', esc_html($args['category']));			
		}

		// If the category slug is specified.
		if ( ! is_numeric( $args['category'] ) && is_string( $args['category'] ) ) {
			$tax_field_type = 'slug';
			$term = is_array($args['category']) ? $args['category'] : explode(',', esc_html($args['category']));
		}

		// If the category ID is specified or an array from widget config.
		if ( (is_array($args['tags']) && !empty($args['tags'])) || ( is_numeric( $args['tags'] ) && 0 < intval( $args['tags'] ) ) ) {
			$tag_field_type = 'id';
			$tags = is_array($args['tags']) ? $args['tags'] : explode(',', esc_html($args['tags']));						
		}

		// If tags are specified.
		if ( ! is_numeric( $args['tags'] ) && is_string( $args['tags'] ) ) {
			$tag_field_type = 'slug';
			$tags = is_array($args['tags']) ? $args['tags'] : explode(',', esc_html($args['tags']));			
		}
		
		// Setup the taxonomy query.
		if ( '' != $tax_field_type && '' != $tag_field_type && is_array($term) && is_array($tags) && count($term) > 0 && count($tags) > 0 ) {
			//if ( is_string( $term ) ) { $term = esc_html( $term ); } else { $term = intval( $term ); }
			if ($args['authorship'] == true) :
				$query_args['tax_query'] = array(
					'relation' => 'OR',
					array( 
						'taxonomy' => 'authorship-category', 
						'field' => $tax_field_type, 
						'terms' => $term 
					),
					array(
						'taxonomy' => 'authorship-tags', 
						'field' => $tag_field_type, 
						'terms' => $tags 
					),
				);
			else : //won't work for custom post-types
				$query_args['tax_query'] = array(
					'relation' => 'OR',
					array( 
						'taxonomy' => 'category', 
						'field' => $tax_field_type, 
						'terms' => $term 
					),
					array(
						'taxonomy' => 'post_tag', 
						'field' => $tag_field_type, 
						'terms' => $tags 
					),
				);
			endif;
		}
		else if ( '' != $tax_field_type && is_array($term) && count($term) > 0 ) {
			//$term = is_array($args['category']) ? $args['category'] : explode(',', esc_html($args['category']));			
			if ($args['authorship'] == true) :
				$query_args['tax_query'] = array(
					array( 
						'taxonomy' => 'authorship-category', 
						'field' => $tax_field_type, 
						'terms' => $term 
					),
				);
			else :  //won't work for custom post-types
				$query_args['tax_query'] = array(
					array( 
						'taxonomy' => 'category', 
						'field' => $tax_field_type, 
						'terms' => $term 
					),
				);
			endif;		
		}
		else if ( '' != $tag_field_type && is_array($tags) && count($tags) > 0 ) {			
			if ($args['authorship'] == true) :
				$query_args['tax_query'] = array(
					array( 
						'taxonomy' => 'authorship-tags', 
						'field' => $tag_field_type, 
						'terms' => $tags 
					),
				);
			else :  //won't work for custom post-types
				$query_args['tax_query'] = array(
					array( 
						'taxonomy' => 'post_tag', 
						'field' => $tag_field_type, 
						'terms' => $tags 
					),
				);
			endif;		
		} else {
			//unset($query_args['tax_query']);
		}
		
		// The Query.
		//debug... $the_query = new WP_Query( $query_args );
		//debug... echo $the_query->request;
		//$the_query = new WP_Query( $query_args );
		//$query = $the_query->posts;
		$query = get_posts( $query_args );
		
		//Check the size arg again and prepare the image size to retrieve the image
		//put this here, all retrieved images will have the same size configuration
		$sizer = AUTHORSHIP_IMAGE_SIZE;
		if ( isset( $args['size'] ) &&  ( 0 < intval( $args['size'] ) ) ) : 
			$sizer = array( intval( $args['size'] ), intval( $args['size'] ) );
		else:
			global $_wp_additional_image_sizes;
			if ( !array_key_exists( $args['size'] , $_wp_additional_image_sizes) ) : 
				$width = 50;
				$height = 50;
				if ( isset($tasrpas_options['thumb_image_width']) && isset($tasrpas_options['thumb_image_height']) && 0 < intval($tasrpas_options['thumb_image_width']) && 0 < intval($tasrpas_options['thumb_image_height']) ) :
					//this is what Multiple Post Thumbnails looks for in some contexts... as image size identifier... $this->post_type}-{$this->id}-thumbnail
					$width = $tasrpas_options['thumb_image_width'];
					$height = $tasrpas_options['thumb_image_height'];
				endif;
				$sizer = array($width, $height);
			else :
				$sizer = array(50,50);
			endif;
		endif;

		// The Display.
		if ( ! is_wp_error( $query ) && is_array( $query ) && count( $query ) > 0 ) {
			foreach ( $query as $k => $v ) {
				$meta = get_post_custom( $v->ID );
				// Get the image.
				$query[$k]->image = $this->get_image( $v->ID, $sizer, $v->post_type );
				
				foreach ( (array)$this->get_custom_fields_settings() as $i => $j ) {
					if ( isset( $meta['_' . $i] ) && ( '' != $meta['_' . $i][0] ) ) {
						$query[$k]->$i = $meta['_' . $i][0];
					} else {
						$query[$k]->$i = $j['default'];
					}
				}
				
			}
		} else {
			$query = false;
		}

		return $query;
	} // End get_authorship()

	/**
	 * Load the plugin's localisation file.
	 * @access public
	 * @since 0.1.0
	 * @return void
	 */
	public function load_localisation () {
		load_plugin_textdomain( 'tessa-authorship', false, dirname( plugin_basename( $this->file ) ) . '/lang/' );
	} // End load_localisation()

	/**
	 * Load the plugin textdomain from the main WordPress "languages" folder.
	 * @since 0.1.0
	 * @return  void
	 */
	public function load_plugin_textdomain () {
	    $domain = 'tessa-authorship';
	    // The "plugin_locale" filter is also used in load_plugin_textdomain()
	    $locale = apply_filters( 'plugin_locale', get_locale(), $domain );

	    load_textdomain( $domain, WP_LANG_DIR . '/' . $domain . '/' . $domain . '-' . $locale . '.mo' );
	    load_plugin_textdomain( $domain, FALSE, dirname( plugin_basename( $this->file ) ) . '/lang/' );
	} // End load_plugin_textdomain()

	/**
	 * Run on activation.
	 * @access public
	 * @since 0.1.0
	 * @return void
	 */
	public function activation () {
		$this->register_plugin_version();
		$this->flush_rewrite_rules();
		global $tasrpas_options;
		add_option( 'tasrpas_settings', $tasrpas_options );
	} // End activation()

	/**
	 * Register the plugin's version.
	 * @access public
	 * @since 0.1.0
	 * @return void
	 */
	private function register_plugin_version () {
		if ( $this->version != '' ) {
			update_option( 'tessa-authorship' . '-version', $this->version );
		}
	} // End register_plugin_version()

	/**
	 * Flush the rewrite rules
	 * @access public
	 * @since 0.1.0
	 * @return void
	 */
	private function flush_rewrite_rules () {
		$this->register_post_type();
		flush_rewrite_rules();
	} // End flush_rewrite_rules()

	/**
	 * Ensure that "post-thumbnails" support is available for those themes that don't register it.
	 * @since 0.1.0
	 * @return  void
	 */
	public function ensure_post_thumbnails_support () {
		global $tasrpas_options;
		/* @changed
		 * @since 0.3.2 We have to add image size for each supported post type for naming conventions inside Multiple Post Thumbnails
		 * a hook would be great to assign a common size...
		 */
		if ( ! current_theme_supports( 'post-thumbnails' ) ) :
			add_theme_support( 'post-thumbnails' );
		endif;
		$tasrpas_options = get_option('tasrpas_settings');
		$width = 50;
		$height = 50;
		if ( isset($tasrpas_options['thumb_image_width']) && isset($tasrpas_options['thumb_image_height']) && 0 < intval($tasrpas_options['thumb_image_width']) && 0 < intval($tasrpas_options['thumb_image_height']) ) :
			//this is what Multiple Post Thumbnails looks for in some contexts... as image size identifier... $this->post_type}-{$this->id}-thumbnail
			$width = $tasrpas_options['thumb_image_width'];
			$height = $tasrpas_options['thumb_image_height'];
		endif;
		add_image_size('authorship-tas-thumbnail', $width, $height, true);
		$tasrpas_options['post_types'] = !empty( $tasrpas_options['post_types'] ) ? $tasrpas_options['post_types'] : array();
		foreach( get_post_types( array( 'public'=>true, 'show_ui'=>true ), 'objects' ) as $apost_type ):
			if ($apost_type->name != 'authorship' && in_array( $apost_type->name, $tasrpas_options['post_types'] ) ) :
				add_image_size($apost_type->name . '-tas-thumbnail', $width, $height, true);				
			endif;
		endforeach;
		
		add_image_size('authorship-admin-thumbnail', 40, 40, true);
	} // End ensure_post_thumbnails_support()
	
	/**
	 * Get authorship ids stored in post meta and filter for published if desired.
	 * @since 0.1.0
	 * @return  authorship ids
	 */
	public static function authorship_post_related_authors( $post_id, $only_published=true )
	{
		global $tasrpas_options;
		$ids = get_post_meta( $post_id, '_tasrpasids', true );
		
		if( $only_published )
			$ids = !empty( $ids ) != '' ? implode( ',', wp_parse_id_list( wp_list_pluck( get_posts( array( 'include' => $ids, 'post_type' => 'authorship' ) ), 'ID' ) ) ) : array();
		else
			$ids = !empty( $ids ) != '' ? implode( ',', wp_parse_id_list( $ids ) ) : array();
		return $ids;
	}
	
	/**
	 * Get post ids stored in authorship meta and filter for published if desired.
	 * @since 0.1.0
	 * @return  post ids
	 */	
	public static function authorship_author_related_posts( $post_id, $only_published=true )
	{
		global $tasrpas_options;
		$ids = get_post_meta( $post_id, '_saprsatids', true );
		if( $only_published )
			$ids = !empty( $ids ) != '' ? implode( ',', wp_parse_id_list( wp_list_pluck( get_posts( array( 'include' => $ids, 'post_type' => $tasrpas_options['post_types'] ) ), 'ID' ) ) ) : array();
		else
			$ids = !empty( $ids ) != '' ? implode( ',', wp_parse_id_list( $ids ) ) : array();
		return $ids;
	}
	
} // End Class