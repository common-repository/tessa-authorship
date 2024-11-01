<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly.

/**
 * Tessa Authorship Class
 *
 * All functionality pertaining to the Authorship feature.
 *
 * @package WordPress
 * @subpackage tessa_authorship
 * @category Plugin
 * @author Uli Hake
 * @since 0.1.0
 */
class tasrpas_worker {
	/**
	 * The base file.
	 * @access  private
	 * @since   0.1.0
	 * @var     string
	 */
	private $base_file;
	/**
	 * The assets dir.
	 * @access  private
	 * @since   0.1.0
	 * @var     string
	 */
	private $assets_dir;
	/**
	 * The assets url.
	 * @access  private
	 * @since   0.1.0
	 * @var     string
	 */
	private $assets_url;
	/**
	 * The version.
	 * @access  private
	 * @since   0.1.0
	 * @var     string
	 */
	private $version;

	/**
	 * The author_placeholders
	 * author's placeholders.
	 * @access  private
	 * @since   0.1.0
	 * @var		array
	 */
	private $author_placeholders;

	/**
	 * The entry_placeholders
	 * author's placeholders.
	 * @access  private
	 * @since   0.1.0
	 * @var		array
	 */
	private $entry_placeholders;
	
	/**
	 * Constructor function.
	 *
	 * @access public
	 * @since 0.1.0
	 * @return void
	 */
	public function __construct($base_file, $assets_dir, $assets_url, $version ) {
		$this->base_file = $base_file;
		$this->assets_dir = $assets_dir;
		$this->assets_url = $assets_url;
		$this->version = $version;
		if ( is_admin() ) {
			global $pagenow;
			add_action( 'add_meta_boxes', array($this, 'tasrpas_add_meta_box') );
			
			add_action( 'admin_head-post.php', array($this, 'tasrpas_admin_head_post') );
			add_action( 'admin_head-post-new.php', array($this, 'tasrpas_admin_head_post') );
			add_action( 'admin_footer-post.php', array($this, 'tasrpas_admin_footer_post') );
			add_action( 'admin_footer-post-new.php', array($this, 'tasrpas_admin_footer_post') );
			add_action( 'save_post', array($this, 'tasrpas_save_post') );
			
			add_action( 'admin_menu', array($this, 'tasrpas_create_menu') );
			add_filter( 'plugin_action_links_' . plugin_basename( $this->base_file ), array($this, 'tasrpas_settings_action_links') );
			
			add_action( 'wp_ajax_tasrpas_ajax_find_authors', array($this, 'tasrpas_ajax_find_authors') );
			add_action( 'wp_ajax_tasrpas_ajax_find_content', array($this, 'tasrpas_ajax_find_content') );

			$this->author_placeholders = array('AVATAR','AUTHOR','HEADLINE','TEXT','SOCIAL');
			$this->entry_placeholders = array('IMAGE','TITLE','TEXT');
		}

	} // End __construct()


	/**
	 * The Author Search Box.
	 *
	 * @access public
	 * @since 0.1.0
	 * @return markup
	 */
	public function tasrpas_find_authors()
	{
		global $tasrpas_options;
	?>
		<div id="authorship" class="find-box" style="display:none;position:absolute;z-index:230492203948;background-color:#ccc;padding:10px 10px 10px 10px;border:1px solid #aaa;border-radius:5px">
			<div id="authorship-head" class="authorship-box-head"><?php _e( 'Find related authors', 'tessa-authorship' ); ?></div>
			<div class="authorship-box-inside">
				<div class="authorship-box-search">

					<input type="hidden" name="affected" id="affected" value="" />
					<?php wp_nonce_field( 'find-authors', '_tasrpas_ajax_nonce', false ); ?>
					<label class="screen-reader-text" for="authorship-input"><?php _e( 'Search' ); ?></label>
					<input type="text" id="authorship-input" name="ps" value="" />
					<input type="button" id="authorship-search" value="<?php esc_attr_e( 'Search' ); ?>" class="button" /> <span class="spinner"></span><br />
				</div>
				<div id="authorship-response"></div>
			</div>
			<div class="authorship-box-buttons">
				<input id="authorship-close" type="button" class="button alignleft" value="<?php esc_attr_e( 'Close' ); ?>" />
				<?php submit_button( __( 'Select' ), 'button-primary alignright', 'authorship-submit', false ); ?>
			</div>
		</div>
	<?php
	}
	/**
	 * The Content Search Box.
	 *
	 * @access public
	 * @since 0.4.0
	 * @return markup
	 */
	public function tasrpas_find_content()
	{
		global $tasrpas_options;
	?>
		<div id="authorship" class="find-box" style="display:none;position:absolute;z-index:230492203948;background-color:#ccc;;padding:10px 10px 10px 10px;border:1px solid #aaa;border-radius:5px">
			<div id="authorship-head" class="authorship-box-head"><?php _e( 'Find related content', 'tessa-authorship' ); ?></div>
			<div class="authorship-box-inside">
				<div class="authorship-box-search">

					<input type="hidden" name="affected" id="affected" value="" />
					<?php wp_nonce_field( 'find-content', '_saprsat_ajax_nonce', false ); ?>
					<label class="screen-reader-text" for="authorship-input"><?php _e( 'Search' ); ?></label>
					<input type="text" id="authorship-input" name="ps" value="" />
					<?php
					$post_types = get_post_types( array( 'public' => true, 'show_ui'=>true ), 'objects' );
					foreach ( $post_types as $post ):
						if ( !in_array( $post->name, $tasrpas_options['post_types'] ) ) :
							continue;
						endif;
						?>
						<input type="checkbox" name="find-what[]" id="find-content-<?php echo esc_attr( $post->name ); ?>" value="<?php echo esc_attr( $post->name ); ?>" <?php echo 'attachment' == $post->name ? '' : 'checked="checked"'; ?>/>
						<label for="find-posts-<?php echo esc_attr( $post->name ); ?>"><?php echo $post->label; ?></label>
						<?php
					endforeach;
					?>
					<input type="button" id="authorship-search" value="<?php esc_attr_e( 'Search' ); ?>" class="button" /> <span class="spinner"></span><br />
				</div>
				<div id="authorship-response"></div>
			</div>
			<div class="authorship-box-buttons">
				<?php submit_button( __( 'Select' ), 'button-primary alignright', 'authorship-submit', false ); ?>
				<input id="authorship-close" type="button" class="button" value="<?php esc_attr_e( 'Close' ); ?>" />
			</div>
		</div>
	<?php
	}
	
	
	/**
	 * Add meta box on activated post types.
	 *
	 * @access public
	 * @since 0.1.0
	 * @modified 0.4.0
	 */
	public function tasrpas_add_meta_box()
	{
		global $tasrpas_options;
		if( !empty( $tasrpas_options['post_types'] ) ) :
			foreach( $tasrpas_options['post_types'] as $apost_type ) :
				add_meta_box( 'tessa-authorship', __('Related Authors', 'tessa-authorship'), array($this, 'tasrpas_box'), $apost_type, 'side' );
			endforeach;
		endif;
		add_meta_box( 'tessa-authorship', __('Authorship Related Content', 'tessa-authorship'), array($this, 'saprsat_box'), 'authorship', 'side' );
		
	}

	/**
	 * The Author Meta Box Content.
	 *
	 * @access public
	 * @since 0.1.0
	 * @return markup
	 */	
	public function tasrpas_box( $post )
	{
		global $tessa_authorship;
		
		$tasrpas_ids = $tessa_authorship->authorship_post_related_authors( $post->ID, false );
		$tasrpas_ids = is_array( $tasrpas_ids ) ? '' : $tasrpas_ids;
		?>
		<div>
			<input type="hidden" id="tasrpas_pid" value="<?php echo esc_attr( $post->ID ); ?>" />		
			<input type="hidden" id="tasrpas_ppt" value="<?php echo esc_attr( $post->post_type ); ?>" />		
			<input class="hide-if-js" type="text" name="tasrpas_ids" id="tasrpas_ids" value="<?php echo esc_attr( $tasrpas_ids ); ?>" />&nbsp;&nbsp;
			<?php echo '<input type="hidden" name="tasrpas_' . $post->post_type . '_' . $post->ID, '_nonce" id="tasrpas_' . $post->post_type . '_' . $post->ID . '_nonce" value="' . wp_create_nonce( plugin_basename( dirname( $this->base_file ) ) ) . '" />'; ?>			
			<div>
				<a href="javascript:void(0);" id="tasrpas_open_button" class="button-secondary hide-if-no-js"><?php _e( 'Add a related author', 'tessa-authorship' ); ?></a>
				<a href="javascript:void(0);" id="tasrpas_delete_related_author" class="button-secondary hide-if-no-js"><?php _e( 'Clear all', 'tessa-authorship' ); ?></a>
				<span class="hide-if-js"><?php _e( 'Add author IDs from authors you want to relate, comma separated.', 'tessa-authorship' ); ?></span>
			</div>
			<ul id="ul_tasrpas" class="tagchecklist">
				<?php
				if( !empty( $tasrpas_ids ) ):
					$tasrpas_ids = wp_parse_id_list( $tasrpas_ids );
					foreach( $tasrpas_ids as $id ) : ?>
						<li data-id="<?php echo (int)$id; ?>"><span style="float:none;"><a class="hide-if-no-js erase_tasrpas">X</a>&nbsp;&nbsp;<?php echo get_the_title( (int)$id ); ?></span></li>
				<?php endforeach;
				endif;?>
			</ul>
		</div>
		<?php
	}

	/**
	 * The Content Meta Box Content.
	 *
	 * @access public
	 * @since 0.4.0
	 * @return markup
	 */	
	public function saprsat_box( $post )
	{
		global $tessa_authorship;
		$saprsat_ids = $tessa_authorship->authorship_author_related_posts( $post->ID, false );
		$saprsat_ids = is_array( $saprsat_ids ) ? '' : $saprsat_ids;
		?>
		<div>
			<input type="hidden" id="saprsat_pid" value="<?php echo esc_attr( $post->ID ); ?>" />
			<input type="hidden" id="saprsat_ppt" value="<?php echo esc_attr( $post->post_type ); ?>" />		
			<input class="hide-if-js" type="text" name="saprsat_ids" id="saprsat_ids" value="<?php echo esc_attr( $saprsat_ids ); ?>" />&nbsp;&nbsp;
			<?php echo '<input type="hidden" name="saprsat_' . $post->post_type . '_' . $post->ID, '_nonce" id="saprsat_' . $post->post_type . '_' . $post->ID . '_nonce" value="' . wp_create_nonce( plugin_basename( dirname( $this->base_file ) ) ) . '" />'; ?>			
			<div>
				<a href="javascript:void(0);" id="saprsat_open_button" class="button-secondary hide-if-no-js"><?php _e( 'Add a related content', 'tessa-authorship' ); ?></a>
				<a href="javascript:void(0);" id="saprsat_delete_related_author" class="button-secondary hide-if-no-js"><?php _e( 'Clear all', 'tessa-authorship' ); ?></a>
				<span class="hide-if-js"><?php _e( 'Add content IDs you want to relate, comma separated.', 'tessa-authorship' ); ?></span>
			</div>
			<ul id="ul_saprsat" class="tagchecklist">
				<?php
				if( !empty( $saprsat_ids ) ):
					$saprsat_ids = wp_parse_id_list( $saprsat_ids );
					foreach( $saprsat_ids as $id ) : ?>
						<li data-id="<?php echo (int)$id; ?>"><span style="float:none;"><a class="hide-if-no-js erase_saprsat">X</a>&nbsp;&nbsp;<?php echo get_the_title( (int)$id ); ?></span></li>
				<?php endforeach;
				endif;?>
			</ul>
		</div>
		<?php
	}
	
	
	/**
	 * Enqueue admin script for meta box.
	 *
	 * @access public
	 * @since 0.1.0
	 * @return void
	 */
	public function tasrpas_admin_head_post()
	{
		global $post_type;
		if ( $post_type != 'authorship' ) :
			$file = "tasrpas.js";
			wp_enqueue_script( 'tasrpas_js', plugins_url( 'assets/js/' . $file, $this->base_file ), 'jquery', $this->version, true );
		else :
			$file = "saprsat.js";
			wp_enqueue_script( 'saprsat_js', plugins_url( 'assets/js/' . $file, $this->base_file ), 'jquery', $this->version, true );
		endif;
	}
	
	/**
	 * Enable Ajax callback for author search.
	 *
	 * @access public
	 * @since 0.1.0
	 * @return void
	 * @modified 0.4.0
	 */
	public function tasrpas_admin_footer_post()
	{
		global $tasrpas_options, $typenow;
		if ( $typenow != 'authorship' ) :		
			$tasrpas_options = get_option('tasrpas_settings');
			if( !empty( $tasrpas_options['post_types'] ) && in_array( $typenow, $tasrpas_options['post_types'] )) :
				$this->tasrpas_find_authors();
			endif;
		else :
			$this->tasrpas_find_content();
		endif;
	}

	/**
	 * Save post hook to establish authorship relations.
	 *
	 * @access public
	 * @since 0.1.0
	 * @update 0.3.0
	 * @return void
	 */
	public function tasrpas_save_post( $post_id )
	{
		//trigger_error(sprintf(__("Test error for '%s::%s()'", 'tessa-authorship'), __CLASS__, __FUNCTION__));

		if( $post_id ) :
		
			//@TODO: Think about storage and retrieval of values
			global $tessa_authorship, $tasrpas_options;
			$tasrpas_options = get_option("tasrpas_settings");
			if( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
				return $post_id;
			
			//fix save context and nonce as we work in two contexts, our own post-type and the post-types of the rest of the world
			$tasrpas_options = get_option('tasrpas_settings');
			if ( empty( $tasrpas_options['post_types'] ) ) :
				return $post_id;
			endif;
			$pt = get_post_type($post_id);
			if ( $pt != 'authorship' ) :
				if ( is_array($tasrpas_options['post_types']) && !in_array($pt, $tasrpas_options['post_types']) ) :
					return $post_id;
				endif;
				//now we know this goes with us
				if ( !isset($_POST['tasrpas_' . get_post_type() . '_' . $post_id . '_nonce']) || !wp_verify_nonce($_POST['tasrpas_' . get_post_type() . '_' . $post_id . '_nonce'], plugin_basename( dirname( $this->base_file ) ) ) ) :
					//but something went wrong
					return $post_id;
				endif;
			
				//if we've reached here, everything is fine
				//get the current state for synchro
				$former_state = $tessa_authorship->authorship_post_related_authors( $post_id, false );

				//easy and done
				$ids = implode( ',', wp_parse_id_list( $_POST['tasrpas_ids']  ) );
				update_post_meta( $post_id, '_tasrpasids', $ids );
				//Set reciprocal relation on authorship entry
				$authors = wp_parse_id_list($ids);
				foreach ($authors as $i => $authorship_id) :
					$post_ids = $tessa_authorship->authorship_author_related_posts( $authorship_id, false );
					if (is_array($post_ids) ) : //no related posts found for author, is an array, add the post
						$post_ids = array($post_id);
					elseif (empty($post_ids)) :
						$post_ids = array($post_id);
					else : //related posts exist, check and update if necessary
						//convert to array
						$post_ids = explode(',', $post_ids );
						//add to array
						$post_ids[] = $post_id;
					endif;
					//clean the array
					$post_ids = implode(',', wp_parse_id_list( implode(',', $post_ids ) ) );
					update_post_meta( $authorship_id, '_saprsatids', $post_ids );
				endforeach;
				//remove post id from authorship entries if necessary
				if (!is_array($former_state)) :
					$authors = wp_parse_id_list( $former_state );
					foreach ($authors as $i => $authorship_id) :
						$post_ids = $tessa_authorship->authorship_author_related_posts( $authorship_id, false );
						//only act if it's not an array
						if (!is_array($post_ids)) :
							$post_ids = wp_parse_id_list( $post_ids ) ;
							if( !empty($_POST['tasrpas_ids']) ):
								$ids = wp_parse_id_list( $_POST['tasrpas_ids'] );
								if (in_array($post_id, $post_ids) && !in_array($authorship_id, $ids)) :
									unset($post_ids[array_search($post_id, $post_ids)]);
								endif;
							else :
								unset($post_ids[array_search($post_id, $post_ids)]);
							endif;
							//@TODO: clean with filter if proves necessary
							update_post_meta( $authorship_id, '_saprsatids', implode( ',', $post_ids  ) );
						endif;
					endforeach;
				endif;	
			else :
				//now we know this goes with us
				if ( !isset($_POST['saprsat_' . get_post_type() . '_' . $post_id . '_nonce']) || !wp_verify_nonce($_POST['saprsat_' . get_post_type() . '_' . $post_id . '_nonce'], plugin_basename( dirname( $this->base_file ) ) ) ) :
					//but something went wrong
					return $post_id;
				endif;
				//if we've reached here, everything is fine
				//get the current state for synchro
				$former_state = $tessa_authorship->authorship_author_related_posts( $post_id, false );
				
				$ids = implode( ',', wp_parse_id_list( $_POST['saprsat_ids']  ) );
				update_post_meta( $post_id, '_saprsatids', $ids );
				//Set reciprocal relation on authorship entry
				$docs = wp_parse_id_list($ids);
				foreach ($docs as $i => $doc_id) :
					$author_ids = $tessa_authorship->authorship_post_related_authors( $doc_id, false );
					if (is_array($author_ids) ) : //no related posts found for author, is an array, add the post
						$author_ids = array($post_id);
					elseif (empty($author_ids)) :
						$author_ids = array($post_id);
					else : //related posts exist, check and update if necessary
						//convert to array
						$author_ids = explode(',', $author_ids );
						//add to array
						$author_ids[] = $post_id;
					endif;
					//clean the array
					$author_ids = implode(',', wp_parse_id_list( implode(',', $author_ids ) ) );
					update_post_meta( $doc_id, '_tasrpasids', $author_ids );
				endforeach;	
				//remove post id from content entries if necessary
				if (!is_array($former_state)) :
					$authors = wp_parse_id_list( $former_state );
					foreach ($docs as $i => $doc_id) :
						$author_ids = $tessa_authorship->authorship_post_related_authors( $doc_id, false );
						//only act if it's not an array
						if (!is_array($author_ids)) :
							$author_ids = wp_parse_id_list( $author_ids ) ;
							if( !empty($_POST['saprsat_ids']) ):
								$ids = wp_parse_id_list( $_POST['saprsat_ids'] );
								if (in_array($post_id, $author_ids) && !in_array($doc_id, $ids)) :
									unset($author_ids[array_search($post_id, $author_ids)]);
								endif;
							else :
								unset($author_ids[array_search($post_id, $author_ids)]);
							endif;
							//@TODO: clean with filter if proves necessary
							update_post_meta( $doc_id, '_tasrpasids', implode( ',', $author_ids  ) );
						endif;
					endforeach;
				endif;	
				
			endif;
		endif;
	}

	/**
	 * Put link to settings on plugin reference in plugins page.
	 *
	 * @access public
	 * @param links (existing links)
	 * @since 0.1.0
	 * @return array
	 */
	public function tasrpas_settings_action_links( $links )
	{
		$tasrpas_links = array(
			'<a href="edit.php?post_type=authorship&page=tessa-authorship">'.__('Settings').'</a>',
		);
		return array_merge( $tasrpas_links, $links );
	}
	
	/**
	 * Add settings page to Authorship Custom Post Type and register settings.
	 *
	 * @access public
	 * @since 0.1.0
	 * @return void
	 */	
	public function tasrpas_create_menu()
	{
		$page = add_submenu_page( 'edit.php?post_type=authorship', __( 'Authorship Settings', 'tessa-authorship' ), __( 'Authorship Settings', 'tessa-authorship' ), 'manage_options', 'tessa-authorship', array( $this, 'tasrpas_settings_page' ) );
		add_action( 'admin_print_styles-'. $page, array( &$this, 'admin_enqueue' ) );
		register_setting( 'tasrpas_options', 'tasrpas_settings', array($this, 'tasrpas_validate_options') );
	}
	
	/**
	 * Not used right now.
	 *
	 * @access public
	 * @since 0.1.0
	 * @return void
	 */
	public function admin_enqueue() {
	}	
	
	/**
	 * Tessa Authorship Settings page constructor.
	 *
	 * @access public
	 * @since 0.1.0
	 * @return markup
	 */
	public function tasrpas_settings_page()
	{
		global $tasrpas_options;
		$tasrpas_options = get_option('tasrpas_settings');
		add_settings_section( 'tasrpas_settings_page', __( 'Basic Configuration', 'tessa-authorship' ), array($this, 'tasrpas_validate_options'), 'tasrpas_settings' );
		add_settings_field( 'tasrpas_field_post_types', __( 'Enable authorship for the following post-types', 'tessa-authorship' ), array($this, 'tasrpas_post_types'), 'tasrpas_settings', 'tasrpas_settings_page' );

		add_settings_section( 'tasrpas_settings_page', __( 'Template Operations', 'tessa-authorship' ), array($this, 'tasrpas_validate_options'), 'tasrpas_settings_templates' );
		add_settings_field( 'tasrpas_field_templates', __( 'Define output fields and order', 'tessa-authorship' ), array($this, 'tasrpas_templates'), 'tasrpas_settings_templates', 'tasrpas_settings_page' );
		
		add_settings_section( 'tasrpas_settings_page', __( 'About', 'tessa-authorship' ), '__return_false', 'tasrpas_settings_about' );
		add_settings_field( 'tasrpas_field_about', '', array($this, 'tasrpas_about'), 'tasrpas_settings_about', 'tasrpas_settings_page' );
		?>
		<form method="post" action="options.php">
		
			<?php settings_fields( 'tasrpas_options' ); ?>
			<?php submit_button(); ?>
			<div class="tabs"><?php do_settings_sections( 'tasrpas_settings' ); ?></div>
			<div class="tabs"><?php do_settings_sections( 'tasrpas_settings_templates' ); ?></div>
			<?php submit_button(); ?>
			<div class="tabs"><?php do_settings_sections( 'tasrpas_settings_about' ); ?></div>
		</form>
		<?php
	}

	/**
	 * Validate settings submitted by admin.
	 *
	 * @access public
	 * @param array input
	 * @since 0.1.0
	 * @return array input
	 */
	public function tasrpas_validate_options($input)
	{
		$st = "";
		if (!empty($input['entry_intro']))
			$st = sanitize_text_field($input['entry_intro']);
		$st = "";
		if (!empty($input['author_intro']))
			$st = sanitize_text_field($input['author_intro']);
		$st = "";
		if (!empty($input['social_link_intro']))
			$st = sanitize_text_field($input['social_link_intro']);
		
		$st = isset($input['read_more']) ? $input['read_more'] : '';
		if (empty($st)) $st = "Read more..."; 		
		$input['read_more'] = $st;

		$st = isset($input['authorship_slug_single']) ? sanitize_title($input['authorship_slug_single'], 'authorship', 'save') : 'authorship';
		$input['authorship_slug_single'] = $st;
		
		$st = isset($input['authorship_slug_plural']) ? sanitize_title($input['authorship_slug_plural'], 'authorships', 'save') : 'authorships';
		$input['authorship_slug_plural'] = $st;

		$st = isset($input['authorship_generic_name']) ? sanitize_text_field($input['authorship_generic_name']) : 'Authorship';
		if (empty($st)) $st = 'Authorship';
		$input['authorship_generic_name'] = $st;
		$st = isset($input['authorship_single_name']) ? sanitize_text_field($input['authorship_single_name']) : 'Author';
		if (empty($st)) $st = 'Author';
		$input['authorship_single_name'] = $st;
		
		/* new since @since 0.3.1 */
		if ( !empty( $input['thumb_image_width'] ) &&  ( 0 < intval( $input['thumb_image_width'] ) ) ) : 
			$input['thumb_image_width'] = intval( $input['thumb_image_width'] );
		else:
			$input['thumb_image_width'] = 50;
		endif;
		if ( !empty( $input['thumb_image_height'] ) &&  ( 0 < intval( $input['thumb_image_height'] ) ) ) : 
			$input['thumb_image_height'] = intval( $input['thumb_image_height'] );
		else:
			$input['thumb_image_height'] = 50;
		endif;
		/* new @since 0.3.2 */
		//if (!isset($input['avatar_bg']) || empty($input['avatar_bg']))
		/* not clean disable */
		$input['avatar_bg'] = '0';
		
		/* new @since 0.3.2 */
		/*
		if (!isset($input['never_firstthumb']) || empty($input['never_firstthumb']))
			$input['never_firstthumb'] = '0';
		*/
		if (!isset($input['content_filter']) || empty($input['content_filter']))
			$input['content_filter'] = '0';

		if (!isset($input['activate_mpt']) || empty($input['activate_mpt']))
			$input['activate_mpt'] = '0';
		
		if (!isset($input['open_link_blank']) || empty($input['open_link_blank']))
			$input['open_link_blank'] = '0';

		if (!isset($input['pub_email_link']) || empty($input['pub_email_link']))
			$input['pub_email_link'] = '0';

		if (!isset($input['show_email_form']) || empty($input['show_email_form']))
			$input['show_email_form'] = '0';
			
		if (!isset($input['use_social_icons']) || empty($input['use_social_icons']))
			$input['use_social_icons'] = '0';

		if (!isset($input['use_social_font']) || empty($input['use_social_font']))
			$input['use_social_font'] = '0';

		if (!isset($input['add_social_info']) || empty($input['add_social_info']))
			$input['add_social_info'] = '0';

			$tmp = $this->author_placeholders;
		$st = "";
		if (!empty($input['placeholders_author']))
			$st = $input['placeholders_author'];
		$st = sanitize_text_field(preg_replace('/[^,A-Z]+/', '', $st));
		if (empty($st)) :
			$input['placeholders_author'] = $tmp;
		else :
			$utmp = explode(',', $st);
			$cmp = array();
			foreach($utmp as $k) :
				if (in_array($k, $tmp)) :
					array_push($cmp, $k);
				endif;
			endforeach;
			if (empty($cmp) )  $input['placeholders_author'] = $tmp;
			else $input['placeholders_author'] = $cmp;
		endif;

		$tmp = $this->entry_placeholders;		
		$st = "";
		if (!empty($input['placeholders_entry']))
			$st = $input['placeholders_entry'];
		$st = sanitize_text_field(preg_replace('/[^,A-Z]+/', '', $st));
		if (empty($st)) :
			$input['placeholders_entry'] = $tmp;
		else :
			$utmp = explode(',', $st);
			$cmp = array();
			foreach($utmp as $k) :
				if (in_array($k, $tmp)) :
					array_push($cmp, $k);
				endif;
			endforeach;
			if (empty($cmp) )  $input['placeholders_entry'] = $tmp;
			else $input['placeholders_entry'] = $cmp;
		endif;
		return $input;
	}	
	
	/**
	 * Fill Tessa Authorship settings page with options.
	 *
	 * @access public
	 * @since 0.1.0
	 * @return markup
	 */
	public function tasrpas_post_types()
	{
		global $tasrpas_options;
		$tasrpas_options['post_types'] = !empty( $tasrpas_options['post_types'] ) ? $tasrpas_options['post_types'] : array();
		foreach( get_post_types( array( 'public'=>true, 'show_ui'=>true ), 'objects' ) as $apost_type ):
		    if ($apost_type->name != 'authorship') : 
				echo '<label><input type="checkbox" '.checked( in_array( $apost_type->name, $tasrpas_options['post_types'] ) ? 'on' : '', 'on', false ).' name="tasrpas_settings[post_types][]" value="'.esc_attr( $apost_type->name ).'" /> '.esc_html( $apost_type->label ).'</label><br />';
			endif;
		endforeach;
		echo '<br />';
		if ( !in_array( 'multiple-post-thumbnails/multi-post-thumbnails.php', (array) get_option( 'active_plugins', array() ) ) ) :
			$tasrpas_options['activate_mpt'] = isset( $tasrpas_options['activate_mpt'] ) ? $tasrpas_options['activate_mpt'] : 0;
			echo '<label><input type="checkbox" '.checked( $tasrpas_options['activate_mpt'] ? 'on' : '', 'on', false ).' name="tasrpas_settings[activate_mpt]" value="1" /> '.__('Activate Authorship Thumbnails on posts', 'tessa-authorship').'</label><br />';
			echo '<small>' . __("We have not found the \"Multiple Post Thumbnails\" plugin. For this reason you can activate the bundled version. You can install and test the <a href=\"http://wordpress.org/plugins/multiple-post-thumbnails/stats/\" target=\"_blank\">original plugin</a> at any time. \"Tessa Authorship\" will detect its presence and fallback to the original version gracfully. \"Tessa Authorship\" will work without multiple thumbnails but offers more control to you on how related items show up in lists generated by \"Tessa Authorship\".", 'tessa-authorship') . '</small><br /><br />';	
		endif;
		$tasrpas_options['content_filter'] = isset( $tasrpas_options['content_filter'] ) ? $tasrpas_options['content_filter'] : 0;
		echo '<label><input type="checkbox" '.checked( $tasrpas_options['content_filter'] ? 'on' : '', 'on', false ).' name="tasrpas_settings[content_filter]" value="1" /> '.__('Activate "the_content" filter to display authorships on posts and pages or related content (pages and posts) on author\'s page automatically. (single posts and pages only.', 'tessa-authorship').'</label><br /><br />';
		echo "<fieldset style=\"border:1px solid #aaa;padding:10px;\"><legend style=\"margin-left:10px;\">" . __("Customize Authorship Post-Type Settings", "tessa-authorship") . "</legend><div>";
		$rmt = "authorship";
		if (!empty($tasrpas_options['authorship_slug_single'])) :
			$rmt = $tasrpas_options['authorship_slug_single'];
		endif;
		echo '<label for="tasrpas-authorship-slug-single">'.__('Slug for Single Tessa Authorship Entries', 'tessa-authorship').'</label><br /><input class="regular_text" size="60" type="text" value="'.esc_attr($rmt).'" name="tasrpas_settings[authorship_slug_single]" id="tasrpas-authorship-slug-single" /><br />';
		$rmt = "authorships";
		if (!empty($tasrpas_options['authorship_slug_plural'])) :
			$rmt = $tasrpas_options['authorship_slug_plural'];
		endif;
		echo '<label for="tasrpas-authorship-slug-plural">'.__('Slug for Tessa Authorship Archives', 'tessa-authorship').'</label><br /><input class="regular_text" size="60" type="text" value="'.esc_attr($rmt).'" name="tasrpas_settings[authorship_slug_plural]" id="tasrpas-authorship-slug-plural" /><br />';
		$rmt = "Authorship";
		if (!empty($tasrpas_options['authorship_generic_name'])) :
			$rmt = $tasrpas_options['authorship_generic_name'];
		endif;
		echo '<label for="tasrpas-authorship-generic-name">'.__('Generic Title for Tessa Authorship', 'tessa-authorship').'</label><br /><input class="regular_text" size="60" type="text" value="'.esc_attr($rmt).'" name="tasrpas_settings[authorship_generic_name]" id="tasrpas-authorship-generic-name" /><br />';
		$rmt = "Author";
		if (!empty($tasrpas_options['authorship_single_name'])) :
			$rmt = $tasrpas_options['authorship_single_name'];
		endif;
		echo '<label for="tasrpas-authorship-single-name">'.__('Single Title for Tessa Authorship', 'tessa-authorship').'</label><br /><input class="regular_text" size="60" type="text" value="'.esc_attr($rmt).'" name="tasrpas_settings[authorship_single_name]" id="tasrpas-authorship-single-name" /><br />';
		echo '<small>' . __("To use \"Tessa Authorship\" for other concepts and purposes you may want to redefine the single and plural slugs and the menu title. Defaults to <strong>authorship</strong>, <strong>authorships</strong> and <strong>Authorship</strong>. If you like to use \"Tessa Authorship\" to associate personal assistants to posts, pages and other content types, you would set in the three input fields <strong>assistant</strong>, <strong>assistants</strong> and <strong>Assistants</strong> for example.", 'tessa-authorship') . '</small><br /><br />';	
		echo "</div></fieldset>";
	}
	
	/**
	 * Fill Tessa Authorship settings page with options.
	 *
	 * @access public
	 * @since 0.1.0
	 * @return markup
	 */
	public function tasrpas_templates()
	{
		//for testing only
		//global $_wp_additional_image_sizes;
		//var_dump($_wp_additional_image_sizes);
		global $tasrpas_options;				
		$rmt = "50";
		$stlw = "50";
		if (!empty($tasrpas_options['thumb_image_width'])) :
			$stlw = $tasrpas_options['thumb_image_width'];
		endif;
		echo '<label for="tasrpas-thumb-image-width">'.__('Default Thumbnail Image Dimension', 'tessa-authorship').'</label><br /><input class="regular_text" size="25" maxlength="3" type="range" min="30" step="10" max="300" placeholder="'.esc_attr($rmt).'" value="'.esc_attr($stlw).'" name="tasrpas_settings[thumb_image_width]" id="tasrpas-thumb-image-width"  onchange="document.getElementById(\'tiw\').value = this.value;document.getElementById(\'tih\').value = document.getElementById(tasrpas-thumb-image-height).value;"/> x';
		$rmt = "50";
		$stlh = "50";
		if (!empty($tasrpas_options['thumb_image_height'])) :
			$stlh = $tasrpas_options['thumb_image_height'];
		endif;
		echo '<input class="regular_text" size="25" maxlength="3" type="range" min="30" step="10" max="300" placeholder="'.esc_attr($rmt).'" value="'.esc_attr($stlh).'" name="tasrpas_settings[thumb_image_height]" id="tasrpas-thumb-image-height" onchange="document.getElementById(\'tih\').value = this.value;document.getElementById(\'tiw\').value = document.getElementById(tasrpas-thumb-image-width).value;" /><br />';
		echo '<small>' . __("You can specify via the size parameter any string identifier for image sizes added to your theme via add_image_size or you can allow Tessa Authorship to set an image size for the identifier \"authorship-image-thumbnail\". Allowed value range: 30 to 300", "tessa-authorship") . '</small><br />'. __("Dimensions", "tessa-authorship") . ': <input id="tiw" type="text" disabled="disabled" value="' . esc_attr($stlw) . '"> x <input id="tih" type="text" disabled="disabled" value="' . esc_attr($stlh) . '"><br /><br />';

		
		$rmt = "Read more...";
		if (!empty($tasrpas_options['read_more'])) :
			$rmt = $tasrpas_options['read_more'];
		endif;
		echo '<label for="tasrpas-read-more">'.__('Read more link text', 'tessa-authorship').'</label><br /><input class="regular_text" size="60" type="text" value="'.esc_attr($rmt).'" name="tasrpas_settings[read_more]" id="tasrpas-read-more" /><br /><br />';
		
		$tmp = $this->author_placeholders;
		$pa = empty( $tasrpas_options['placeholders_author'] ) ? $tmp : $tasrpas_options['placeholders_author'];
		//@TODO: allow a bit of comfort
		echo '<label for="tasrpas-placeholders-author">'.__('Order placeholders for related authors on entries', 'tessa-authorship').'</label><br /><input size="60" class="regular_text" type="text" value="'.esc_attr(implode(',',$pa)).'" name="tasrpas_settings[placeholders_author]" id="tasrpas-placeholders-author" /><br />';
		echo '<small>' . sprintf(__("Please use only the following placeholders: <strong>%s</strong> and use comma as separator.", "tessa-authorship"), implode(',', $tmp)) . '</small><br /><br />';

		$tmp = $this->entry_placeholders;		
		$pe = empty( $tasrpas_options['placeholders_entry'] ) ? $tmp : $tasrpas_options['placeholders_entry'];
		//@TODO: allow a bit of comfort
		echo '<label for="tasrpas-placeholders-entry">'.__('Order placeholders for related entries on author\'s page', 'tessa-authorship').'</label><br /><input size="60" class="regular_text" type="text" value="'.esc_attr(implode(',',$pe)).'" name="tasrpas_settings[placeholders_entry]" id="tasrpas-placeholders-entry" /><br />';
		echo '<small>' . sprintf(__("Please use only the following placeholders: <strong>%s</strong> and use comma as separator.", "tessa-authorship"), implode(',', $tmp)) . '</small><br /><br />';
		/* not clean disable
 		$tasrpas_options['avatar_bg'] = isset( $tasrpas_options['avatar_bg'] ) ? $tasrpas_options['avatar_bg'] : 0;		
		echo '<label><input type="checkbox" '.checked( $tasrpas_options['avatar_bg'] ? 'on' : '', 'on', false ).' name="tasrpas_settings[avatar_bg]" value="1" /> '.__('Set avatar image as background', 'tessa-authorship').'</label><br />';
		echo '<small>' . __("If placed as background you gain more flexibility to style the entry in reference list generated by Tessa Authorship", "tessa-authorship") . '</small><br /><br />';
		*/
		$tasrpas_options['open_link_blank'] = isset( $tasrpas_options['open_link_blank'] ) ? $tasrpas_options['open_link_blank'] : 0;		
		echo '<label><input type="checkbox" '.checked( $tasrpas_options['open_link_blank'] ? 'on' : '', 'on', false ).' name="tasrpas_settings[open_link_blank]" value="1" /> '.__('Open Social Links in new Window', 'tessa-authorship').'</label><br />';
		
		$tasrpas_options['pub_email_link'] = isset( $tasrpas_options['pub_email_link'] ) ? $tasrpas_options['pub_email_link'] : 0;		
		echo '<label><input type="checkbox" '.checked( $tasrpas_options['pub_email_link'] ? 'on' : '', 'on', false ).' name="tasrpas_settings[pub_email_link]" value="1" /> '.__('Publish Author\'s Email', 'tessa-authorship').'</label><br />';

		if ( !$tasrpas_options['pub_email_link'] ) :
			$tasrpas_options['show_email_form'] = isset( $tasrpas_options['show_email_form'] ) ? $tasrpas_options['show_email_form'] : 0;		
			echo '<label><input type="checkbox" '.checked( $tasrpas_options['show_email_form'] ? 'on' : '', 'on', false ).' name="tasrpas_settings[show_email_form]" value="1" /> '.__('Show Email Form', 'tessa-authorship').'</label><br />';
		endif;
		$tasrpas_options['use_social_icons'] = isset( $tasrpas_options['use_social_icons'] ) ? $tasrpas_options['use_social_icons'] : 0;		
		echo '<label><input type="checkbox" '.checked( $tasrpas_options['use_social_icons'] ? 'on' : '', 'on', false ).' name="tasrpas_settings[use_social_icons]" value="1" /> '.__('Use packaged social icons', 'tessa-authorship').'</label><br />';
		echo '<small>' . __("If you don't want or if you're not able to use fonts like genericons to display social icons via css you may use the packaged social icons to display social links.", "tessa-authorship") . '</small><br />';
		if ( !$tasrpas_options['use_social_icons'] ) :
			$tasrpas_options['use_social_font'] = isset( $tasrpas_options['use_social_font'] ) ? $tasrpas_options['use_social_font'] : 0;		
			echo '<label><input type="checkbox" '.checked( $tasrpas_options['use_social_font'] ? 'on' : '', 'on', false ).' name="tasrpas_settings[use_social_font]" value="1" /> '.__('Use packaged css for genericons font', 'tessa-authorship').'</label><br />';
			echo '<small>' . __("Theme Twenty-Thirteen includes genericons font and you may include a chunk of css to display social icons with this font.", "tessa-authorship") . '</small><br />';
		endif;
		$tasrpas_options['add_social_info'] = isset( $tasrpas_options['add_social_info'] ) ? $tasrpas_options['add_social_info'] : 0;		
		echo '<label><input type="checkbox" '.checked( $tasrpas_options['add_social_info'] ? 'on' : '', 'on', false ).' name="tasrpas_settings[add_social_info]" value="1" /> '.__('Add social author information', 'tessa-authorship').'</label><br />';
		echo '<small>' . __("Add social info before content on authorship post-type pages.", "tessa-authorship") . '</small><br /><br />';
		
		$rmt = "My Links";
		$stl = "";
		if (!empty($tasrpas_options['social_link_intro'])) :
			$stl = $tasrpas_options['social_link_intro'];
		endif;
		echo '<label for="tasrpas-social-link-intro">'.__('Social Link Intro', 'tessa-authorship').'</label><br /><input class="regular_text" size="60" type="text" placeholder="'.esc_attr($rmt).'" value="'.esc_attr($stl).'" name="tasrpas_settings[social_link_intro]" id="tasrpas-social-link-intro" /><br /><br />';
		$rmt = "Author";
		$stl = "";
		if (!empty($tasrpas_options['author_intro'])) :
			$stl = $tasrpas_options['author_intro'];
		endif;
		echo '<label for="tasrpas-author-intro">'.__('Related Author Intro', 'tessa-authorship').'</label><br /><input class="regular_text" size="60" type="text" placeholder="'.esc_attr($rmt).'" value="'.esc_attr($stl).'" name="tasrpas_settings[author_intro]" id="tasrpas-author-intro" /><br /><br />';
		$rmt = "Authorship";
		$stl = "";
		if (!empty($tasrpas_options['entry_intro'])) :
			$stl = $tasrpas_options['entry_intro'];
		endif;
		echo '<label for="tasrpas-entry-intro">'.__('Related Content Intro', 'tessa-authorship').'</label><br /><input class="regular_text" size="60" type="text" placeholder="'.esc_attr($rmt).'" value="'.esc_attr($stl).'" name="tasrpas_settings[entry_intro]" id="tasrpas-entry-intro" /><br /><br />';
		
	}
	

	public function _tasrpas_search_terms_tidy( $t ) {
		return trim( $t, "\"'\n\r " );
	}	

	/**
	 * Ajax callback function to find authors.
	 *
	 * @access public
	 * @since 0.1.0
	 * @changed 0.7.7
	 * @return Object
	 * @modified 0.4.0 WPML Support
	 */
	public function tasrpas_ajax_find_authors()
	{
		global $tessa_authorship, $tasrpas_options, $wpdb, $sitepress;
		check_ajax_referer( 'find-authors' );

		if ( empty( $_POST['ps'] ) || empty( $_POST['pid'] ) || empty( $_POST['ppt'] ) ) :
			wp_die();
		endif;
		$wpml = false;		
		$lang_code = "";
		//@TODO if we want to allow more than one post type on other side in the future, we have to plug to these post types
		//right now it's ok with our one and only post-type authorship hard-coded
		//$what = 'authorship';
		$pt = array('authorship');
		$s = stripslashes($_POST['ps']);
		preg_match_all('/".*?("|$)|((?<=[\\s",+])|^)[^\\s",+]+/', $s, $matches);
		$search_terms = array_map( array($this, '_tasrpas_search_terms_tidy'), $matches[0] );
		
		$searchand = $search = '';
		foreach ( (array)$search_terms as $term ) {
			$term = esc_sql( $this->tasrpas_like_escape( $term ) );//like_escape( $term ) );
			$search .= "{$searchand}(($wpdb->posts.post_title LIKE '%{$term}%') OR ($wpdb->posts.post_content LIKE '%{$term}%'))";
			$searchand = ' AND ';
		}
		$term = esc_sql( $this->tasrpas_like_escape( $s ) ); //like_escape( $s ) );
		if ( count($search_terms) > 1 && $search_terms[0] != $s ) {
			$search .= " OR ($wpdb->posts.post_title LIKE '%{$term}%') OR ($wpdb->posts.post_content LIKE '%{$term}%')";
		}
		if ( class_exists('SitePress') && defined('ICL_LANGUAGE_CODE') ) :
			$wpml = true;
			//get the lang code for the post as we are currently in default language
			$post_lang = $this->tasrpas_get_language_for_element($_POST['pid'], 'post_' . $_POST['ppt']);
			//$def_lang = $sitepress->get_default_language();
			if ( isset($post_lang) && $post_lang ) :
				$lang_code = $post_lang;
			endif;			
			$db_icltable = $wpdb->base_prefix . 'icl_translations';
			$query_str = "
				SELECT DISTINCT $wpdb->posts.ID, $wpdb->posts.post_title, $wpdb->posts.post_status, $wpdb->posts.post_date, $wpdb->posts.post_type, b.language_code
				FROM $wpdb->posts, (SELECT DISTINCT $db_icltable.element_id AS id, $db_icltable.language_code FROM $db_icltable WHERE $db_icltable.language_code = '$lang_code' AND $db_icltable.element_type = 'post_authorship') AS b
				WHERE $wpdb->posts.post_type = 'authorship' AND $wpdb->posts.post_status != 'revision' AND $wpdb->posts.ID = b.id AND ($search)
				ORDER BY $wpdb->posts.post_date_gmt DESC LIMIT 50
			";
		else :
			$query_str = "
				SELECT DISTINCT $wpdb->posts.ID, $wpdb->posts.post_title, $wpdb->posts.post_status, $wpdb->posts.post_date, $wpdb->posts.post_type
				FROM $wpdb->posts
				WHERE $wpdb->posts.post_type = 'authorship' AND $wpdb->posts.post_status != 'revision' AND ($search)
				ORDER BY $wpdb->posts.post_date_gmt DESC LIMIT 50
			";		
		endif;
		$posts = $wpdb->get_results( $query_str );
		//try to get results if wpml is not enabled on our post-type
		if ( !$posts && $wpml ) :
			$wpml = false;
			$query_str = "
				SELECT DISTINCT $wpdb->posts.ID, $wpdb->posts.post_title, $wpdb->posts.post_status, $wpdb->posts.post_date, $wpdb->posts.post_type
				FROM $wpdb->posts
				WHERE $wpdb->posts.post_type = 'authorship' AND $wpdb->posts.post_status != 'revision' AND ($search)
				ORDER BY $wpdb->posts.post_date_gmt DESC LIMIT 50
			";		
			$posts = $wpdb->get_results( $query_str );
		endif;
		if ( !$posts ) :
			$posttype = get_post_type_object( $pt[0] );
			wp_die( $posttype->labels->not_found );
		endif;
		if ( $wpml ) :
			$html = '<table class="widefat" cellspacing="0"><thead style="display:block;width:100%"><tr><th class="found-radio"><br /></th><th>'.__('Title').'</th><th>'.__('Date').'</th><th>'.__('Language').'</th><th>'.__('Status').'</th></tr></thead><tbody style="display:block;height:200px;width:100%;overflow:auto">';
		else :
			$html = '<table class="widefat" cellspacing="0"><thead style="display:block;width:100%"><tr><th class="found-radio"><br /></th><th>'.__('Title').'</th><th>'.__('Date').'</th><th>'.__('Status').'</th></tr></thead><tbody style="display:block;height:200px;width:100%;overflow:auto">';
		endif;
		$stat = "";
		foreach ( $posts as $post ) {

			switch ( $post->post_status ) {
				case 'publish' :
				case 'private' :
					$stat = __('Published');
					break;
				case 'future' :
					$stat = __('Scheduled');
					break;
				case 'pending' :
					$stat = __('Pending Review');
					break;
				case 'draft' :
					$stat = __('Draft');
					break;
			}

			if ( '0000-00-00 00:00:00' == $post->post_date ) {
				$time = '';
			} else {
				$time = mysql2date( __('Y/m/d'), $post->post_date );
			}
			$posttype = get_post_type_object( $post->post_type );
			$posttype = $posttype->labels->singular_name;
			if ( $wpml ) :
				/* to expensive, we now retrieve via sql directly and filter language dependant.
				$language = wpml_get_language_information($post->ID);
				if (isset($language) && is_array($language)) :
					$language = $language['locale'];
				endif;
				*/
				$html .= '<tr class="authors-found"><td class="found-radio"><input type="checkbox" id="found-author-'.$post->ID.'" name="found_author_id[]" value="' . esc_attr($post->ID) . '"></td>';
				$html .= '<td><label for="found-'.$post->ID.'">'. $post->ID . ': ' .esc_html( $post->post_title ).'</label></td><td>'.esc_html( $lang_code ).'</td><td>'.esc_html( $time ).'</td><td>'.esc_html( $stat ).'</td></tr>'."\n\n";
			else :
				$html .= '<tr class="authors-found"><td class="found-radio"><input type="checkbox" id="found-author-'.$post->ID.'" name="found_author_id[]" value="' . esc_attr($post->ID) . '"></td>';
				$html .= '<td><label for="found-'.$post->ID.'">'.esc_html( $post->post_title ).'</label></td><td>'.esc_html( $time ).'</td><td>'.esc_html( $stat ).'</td></tr>'."\n\n";
			endif;
		
		}
		$html .= '</tbody></table>';
		$x = new WP_Ajax_Response();
		$x->add( array(
			'what' => 'post',
			'data' => $html
		));
		$x->send();
	}	

	/**
	 * Ajax callback function to find content.
	 * Essentially the same function as tasrpas_ajax_find_authors
	 * To keep things clear we separate the two circuits, this will allow us to hook in more post-types on the "other side" in the future
	 * @access public
	 * @since 0.4.0
	 * @changed 0.7.7	 
	 * @return Object
	 */
	public function tasrpas_ajax_find_content()
	{
		global $tessa_authorship, $tasrpas_options, $wpdb, $sitepress;
		check_ajax_referer( 'find-content' );
		if ( !isset($_POST['name_of_nonce_field']) || !wp_verify_nonce($_POST['name_of_nonce_field'],'name_of_my_action') )
		if ( empty( $_POST['ps'] ) || empty( $_POST['pid'] ) || empty( $_POST['ppt'] ) ) :
			wp_die();
		endif;
		
		$tasrpas_options = get_option('tasrpas_settings');
		$tasrpas_options['post_types'] = !empty( $tasrpas_options['post_types'] ) ? $tasrpas_options['post_types'] : array();
		if ( empty ( $tasrpas_options['post_types'] ) ) :
			wp_die();
		endif;
		
		$wpml = false;
		$wpml_fail = false;
		$lang_code = "";
		//$post_types = get_post_types( array( 'public' => true, 'show_ui'=>true ) );
		$pt = explode( ',', trim( $_POST['post_type'], ',' ) );
		$in_array = array_intersect( $pt, $tasrpas_options['post_types'] );
		if ( !empty($_POST['post_type'] ) && !empty( $in_array ) ) :
			//make compatible with php < 5.3
			$what = implode( ",", array_map( create_function( '$at', 'return "\'" . $at . "\'";'), $in_array ) );
		else :
			$what = 'post';
		endif;
		$s = stripslashes($_POST['ps']);
		preg_match_all('/".*?("|$)|((?<=[\\s",+])|^)[^\\s",+]+/', $s, $matches);
		$search_terms = array_map( array($this, '_tasrpas_search_terms_tidy'), $matches[0] );
		
		$searchand = $search = '';
		foreach ( (array)$search_terms as $term ) {
			$term = esc_sql( $this->tasrpas_like_escape( $term ) );//like_escape( $term ) );
			$search .= "{$searchand}(($wpdb->posts.post_title LIKE '%{$term}%') OR ($wpdb->posts.post_content LIKE '%{$term}%'))";
			$searchand = ' AND ';
		}
		$term = esc_sql( $this->tasrpas_like_escape( $s ) );//like_escape( $s ) );
		if ( count($search_terms) > 1 && $search_terms[0] != $s ) {
			$search .= " OR ($wpdb->posts.post_title LIKE '%{$term}%') OR ($wpdb->posts.post_content LIKE '%{$term}%')";
		}
		if ( class_exists('SitePress') && defined('ICL_LANGUAGE_CODE') ) :
			$wpml = true;
			//get the lang code for the post as we are currently in default language
			$post_lang = $this->tasrpas_get_language_for_element($_POST['pid'], 'post_' . $_POST['ppt']);
			if ( isset($post_lang) && $post_lang ) :
				$lang_code = $post_lang;
			endif;			
			
			$db_icltable = $wpdb->base_prefix . 'icl_translations';
			$pt_icl = explode( ",", $what );
			//make compatible with php < 5.3
			$what_icl = implode( ",", array_map( create_function( '$at', 'return "\'post_" . str_replace( "\'", "", $at ) . "\'";'), $pt_icl ) );
			
			$query_str = "
				SELECT DISTINCT $wpdb->posts.ID, $wpdb->posts.post_title, $wpdb->posts.post_status, $wpdb->posts.post_date, $wpdb->posts.post_type, b.language_code
				FROM $wpdb->posts, (SELECT DISTINCT $db_icltable.element_id AS id, $db_icltable.language_code FROM $db_icltable WHERE $db_icltable.language_code = '$lang_code' AND $db_icltable.element_type IN ($what_icl) ) AS b
				WHERE $wpdb->posts.post_type IN ($what) AND $wpdb->posts.post_status != 'revision' AND $wpdb->posts.ID = b.id AND ($search)
				ORDER BY $wpdb->posts.post_date_gmt DESC LIMIT 50
			";
		else :
			$query_str = "
				SELECT $wpdb->posts.ID, $wpdb->posts.post_title, $wpdb->posts.post_status, $wpdb->posts.post_date, $wpdb->posts.post_type
				FROM $wpdb->posts
				WHERE $wpdb->posts.post_type IN ($what) AND $wpdb->posts.post_status != 'revision' AND ($search)
				ORDER BY $wpdb->posts.post_date_gmt DESC LIMIT 50
			";
		endif;
		$posts = $wpdb->get_results( $query_str );
		//try to get results if wpml is not enabled on post-types
		if ( !$posts && $wpml ) :
			$wpml = false;
			$query_str = "
				SELECT $wpdb->posts.ID, $wpdb->posts.post_title, $wpdb->posts.post_status, $wpdb->posts.post_date, $wpdb->posts.post_type
				FROM $wpdb->posts
				WHERE $wpdb->posts.post_type IN ($what) AND $wpdb->posts.post_status != 'revision' AND ($search)
				ORDER BY $wpdb->posts.post_date_gmt DESC LIMIT 50
			";		
			$posts = $wpdb->get_results( $query_str );
		endif;
		if ( !$posts ) :
			$posttype = get_post_type_object( $pt[0] );
			wp_die( $posttype->labels->not_found );
		endif;

		if ( $wpml ) :
			$wpml_fail = true;
			$html = '<table class="widefat" cellspacing="0" border="1"><thead style="display:block;width:100%"><tr><th class="found-radio"><br /></th><th style="width:25%;">'.__('Title').'</th><th>'.__('Language').'</th><th>'.__('Post Type').'</th><th>'.__('Date').'</th><th>'.__('Status').'</th></tr></thead><tbody style="display:block;height:200px;width:100%;overflow:auto">';		
		else :
			$html = '<table class="widefat" cellspacing="0"><thead style="display:block;width:100%"><tr><th class="found-radio"><br /></th><th style="width:25%;">'.__('Title').'</th><th>'.__('Post Type').'</th><th>'.__('Date').'</th><th>'.__('Status').'</th></tr></thead><tbody style="display:block;height:200px;width:100%;overflow:auto">';
		endif;
		$stat = "";
		foreach ( $posts as $post ) {

			switch ( $post->post_status ) {
				case 'publish' :
				case 'private' :
					$stat = __('Published');
					break;
				case 'future' :
					$stat = __('Scheduled');
					break;
				case 'pending' :
					$stat = __('Pending Review');
					break;
				case 'draft' :
					$stat = __('Draft');
					break;
			}

			if ( '0000-00-00 00:00:00' == $post->post_date ) {
				$time = '';
			} else {
				$time = mysql2date( __('Y/m/d'), $post->post_date );
			}
			$posttype = get_post_type_object( $post->post_type );
			$posttype = $posttype->labels->singular_name;
			if ( $wpml ) :
				$html .= '<tr class="content-found"><td class="found-radio"><input type="checkbox" id="found-content-'.$post->ID.'" name="found_content_id[]" value="' . esc_attr($post->ID) . '"></td>';
				$html .= '<td style="width:25%;"><label for="found-'.$post->ID.'">'.esc_html( $post->post_title ).'</label></td><td>'.esc_html( $lang_code ).'</td><td>'.esc_html( $posttype ).'</td><td>'.esc_html( $time ).'</td><td>'.esc_html( $stat ).'</td></tr>'."\n\n";
			else :
				$html .= '<tr class="content-found"><td class="found-radio"><input type="checkbox" id="found-content-'.$post->ID.'" name="found_content_id[]" value="' . esc_attr($post->ID) . '"></td>';
				$html .= '<td style="width:25%;"><label for="found-'.$post->ID.'">'.esc_html( $post->post_title ).'</label></td><td>'.esc_html( $posttype ).'</td><td>'.esc_html( $time ).'</td><td>'.esc_html( $stat ).'</td></tr>'."\n\n";			
			endif;
		}
		$html .= '</tbody></table>';
		$x = new WP_Ajax_Response();
		$x->add( array(
			'what' => 'post',
			'data' => $html
		));
		$x->send();
	}	

	/**
	 * Get language for element.
	 * Sitepress function produces error on call sometimes
	 *
	 * @access public
	 * @since 0.4.3
	 * @return markup
	 */	
    function tasrpas_get_language_for_element($element_id, $el_type='post_post'){
        global $wpdb;
        return $wpdb->get_var("SELECT language_code FROM {$wpdb->prefix}icl_translations WHERE element_id='{$element_id}' AND element_type='{$el_type}'");
    }
	
	/**
	 * Fill Tessa Authorship settings page with our about stuff.
	 *
	 * @access public
	 * @since 0.1.0
	 * @return markup
	 */
	public function tasrpas_about() {
	?>
		<div class="postbox" style="display: block;width:670px;float:left;margin:10px;">
			<h3 class="hndle" style="padding:5px;"><span><?php _e("Useful links:", "tessa-authorship"); ?></span></h3>
			<div class="inside">
				<a href="http://wordpress.org/support/plugin/<?php echo TASRPAS_SLUG; ?>" class="button-secondary button-large"><?php _e("Support Forum", "tessa-authorship"); ?></a>
				<a href="http://wordpress.org/extend/plugins/<?php echo TASRPAS_SLUG; ?>" class="button-secondary" button-large><?php _e("Rate this plugin", "tessa-authorship"); ?></a>
				<a href="http://wordpress.org/support/view/plugin-reviews/<?php echo TASRPAS_SLUG; ?>" class="button-secondary button-large"><?php _e("Write a review about this plugin", "tessa-authorship"); ?></a>
				<a href="http://profiles.wordpress.org/ulih/"  class="button-secondary button-large" title="on WordPress.org"><?php _e("Wordpress Profile of the author", "tessa-authorship"); ?></a>
			</div>
		</div>	
		<div class="postbox" style="display: block;width:325px;float:left;margin:10px;">
			<h3 class="hndle" style="padding:5px;"><span>Like this plugin?</span></h3>
			<div class="inside">
				<p><a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=Y485CQJA5Y3PC" target="_blank"><img title="Thank you in advance!" src="https://www.paypalobjects.com/en_US/ES/i/btn/btn_donateCC_LG.gif" /></a></p>
			</div>
		</div>	
		<div class="postbox" style="display: block;width:670px;float:left;margin:10px;clear:left;">
			<h3 class="hndle" style="padding:5px;"><span><?php _e("Check out these themes and plugins too:", "tessa-authorship"); ?></span></h3>
			<div class="inside">
				<ul>
					<li><a href="http://takebarcelona.com/easy-digital-downloads-https/">Easy Digital Downloads HTTPS </a> - <em><?php _e("A https switcher for Easy Digital Downloads checkout page and other pages and posts of your choice."); ?></em></li>
					<li><a href="http://takebarcelona.com/stripe-for-easy-digital-downloads/">Stripe for Easy Digital Downloads </a> - <em><?php _e("A Stripe credit card payment gateway for Easy Digital Downloads."); ?></em></li>
					<li><a href="http://takebarcelona.com/woocommerce-poor-guys-swiss-knife/">WooCommerce Poor Guys Swiss Knife (WCPGSK) </a> - <em><?php _e("A swiss knife for WooCommerce to powerload standard WooCommerce installations."); ?></em></li>
					<li><a href="http://takebarcelona.com/woocommerce-rich-guys-swiss-knife/">WooCommerce Rich Guys Swiss Knife (WCRGSK) </a> - <em><?php _e("More tools for your WooCommerce Swiss Knife."); ?></em></li>
					<li><a href="http://takebarcelona.com/tessa-theme">Tessa</a> - <em><?php _e("A gallery, exposition and portfolio theme with built-in support for WooCommerce", "tessa-authorship"); ?></em></li>
				</ul>
			</div>
		</div>		
	<?php
	}

	/*
	 * Avoid deprecated for like_escape for WP 4.0+
	 * @since 0.7.7
	 * @param string $term
	 * @return string $term
	 */
	public function tasrpas_like_escape( $term ) {
		global $wpdb, $wp_version;
		if ( version_compare( $wp_version, '4.0', '>=' ) ) :
			return $wpdb->esc_like( $term );
		else :
			return like_escape( $term );
		endif;		
	}
	
}