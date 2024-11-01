<?php
if ( ! defined( 'ABSPATH' ) || ! function_exists( 'tessa_authorship' ) ) exit; // Exit if accessed directly.

/**
 * Tessa Authorship Widget
 *
 * A Tessa standardized authorship widget.
 *
 * @package WordPress
 * @subpackage tessa_authorship
 * @category Widgets
 * @author Uli Hake
 * @since 0.1.0
 *
 * TABLE OF CONTENTS
 *
 * protected $tessa_widget_cssclass
 * protected $tessa_widget_description
 * protected $tessa_widget_idbase
 * protected $tessa_widget_title
 *
 * - __construct()
 * - widget()
 * - update()
 * - form()
 * - get_orderby_options()
 */
class Tessa_Authorship_Widget extends WP_Widget {
	protected $tessa_widget_cssclass;
	protected $tessa_widget_description;
	protected $tessa_widget_idbase;
	protected $tessa_widget_title;

	/**
	 * Constructor function.
	 * @since  0.1.0
	 * @return  void
	 */
	public function __construct() {
		/* Widget variable settings. */
		$this->tessa_widget_cssclass = 'widget_tessa_authorship';
		$this->tessa_widget_description = __( 'Recent authorship on your site.', 'tessa-authorship' );
		$this->tessa_widget_idbase = 'tessa_authorship';
		$this->tessa_widget_title = __( 'Tessa Authorship', 'tessa-authorship' );

		/* Widget settings. */
		$widget_ops = array( 'classname' => $this->tessa_widget_cssclass, 'description' => $this->tessa_widget_description );

		/* Widget control settings. */
		$control_ops = array( 'width' => 250, 'height' => 350, 'id_base' => $this->tessa_widget_idbase );

		/* Create the widget. */
		$this->WP_Widget( $this->tessa_widget_idbase, $this->tessa_widget_title, $widget_ops, $control_ops );
	} // End __construct()

	/**
	 * Display the widget on the frontend.
	 * @since  0.1.0
	 * @param  array $args     Widget arguments.
	 * @param  array $instance Widget settings for this instance.
	 * @return void
	 */
	public function widget( $args, $instance ) {
		extract( $args, EXTR_SKIP );

		/* Our variables from the widget settings. */
		$title = apply_filters('widget_title', $instance['title'], $instance, $this->id_base );

		/* Before widget (defined by themes). */
		$args = array();

		$args['before'] = $before_widget;
		$args['after'] = $after_widget;

		/* Display the widget title if one was input (before and after defined by themes). */
		if ( $title ) {
			$args['before_title'] = $before_title;
			$args['title'] = $title;
			$args['after_title'] = $after_title;
		}

		/* Widget content. */
		// Add actions for plugins/themes to hook onto.
		do_action( $this->tessa_widget_cssclass . '_top' );

		// Integer values.
		if ( isset( $instance['limit'] ) && ( 0 < count( $instance['limit'] ) ) ) { $args['limit'] = intval( $instance['limit'] ); }
		if ( isset( $instance['specific_id'] ) && ( 0 < count( $instance['specific_id'] ) ) ) { 
			//don't convert to intval here, does not make any sense, is done correctly in $tessa_authorship->get_authorship
			$args['id'] = $instance['specific_id']; 
		}
		if ( isset( $instance['post_type'] ) && ( 0 < count( $instance['post_type'] ) ) ) { 
			//don't convert to intval here, does not make any sense, is done correctly in $tessa_authorship->get_authorship
			$args['post_type'] = explode(',', $instance['post_type']); 
		}

		if ( isset( $instance['size'] ) && ( 0 < count( $instance['size'] ) ) ) { 
			$args['size'] = intval( $instance['size'] ); 
		} else {
			global $_wp_additional_image_sizes;
			if ( !array_key_exists( $args['size'] , $_wp_additional_image_sizes) ) : 
				$args['size'] = AUTHORSHIP_IMAGE_SIZE;
			endif;
		}
		
		// Arrays from here
		if ( isset( $instance['category'] ) ) $args['category'] = explode(',', $instance['category']);
		if ( isset( $instance['tags'] ) ) $args['tags'] = explode(',', $instance['tags']);

		// Boolean values.
		if ( isset( $instance['display_auto'] ) && ( 1 == $instance['display_auto'] ) ) { $args['display_auto'] = true; } else { $args['display_auto'] = false; }
		if ( isset( $instance['display_author'] ) && ( 1 == $instance['display_author'] ) ) { $args['display_author'] = true; } else { $args['display_author'] = false; }
		if ( isset( $instance['display_avatar'] ) && ( 1 == $instance['display_avatar'] ) ) { $args['display_avatar'] = true; } else { $args['display_avatar'] = false; }
		if ( isset( $instance['display_url'] ) && ( 1 == $instance['display_url'] ) ) { $args['display_url'] = true; } else { $args['display_url'] = false; }
		if ( isset( $instance['display_social'] ) && ( 1 == $instance['display_social'] ) ) { $args['display_social'] = true; } else { $args['display_social'] = false; }
		if ( isset( $instance['display_content'] ) && ( 1 == $instance['display_content'] ) ) { $args['display_content'] = true; } else { $args['display_content'] = false; }
		if ( isset( $instance['display_excerpt'] ) && ( 1 == $instance['display_excerpt'] ) ) { $args['display_excerpt'] = true; } else { $args['display_excerpt'] = false; }
		if ( isset( $instance['authorship'] ) && ( 1 == $instance['authorship'] ) ) { $args['authorship'] = true; } else { $args['authorship'] = false; }
		if ( isset( $instance['strict'] ) && ( 1 == $instance['strict'] ) ) { $args['strict'] = true; } else { $args['strict'] = false; }

		// Select boxes.
		if ( isset( $instance['orderby'] ) && in_array( $instance['orderby'], array_keys( $this->get_orderby_options() ) ) ) { $args['orderby'] = $instance['orderby']; }
		if ( isset( $instance['order'] ) && in_array( $instance['order'], array_keys( $this->get_order_options() ) ) ) { $args['order'] = $instance['order']; }

		// Display the authorship.
		tessa_authorship( $args );

		// Add actions for plugins/themes to hook onto.
		do_action( $this->tessa_widget_cssclass . '_bottom' );
	} // End widget()

	/**
	 * Method to update the settings from the form() method.
	 * @since  0.1.0
	 * @param  array $new_instance New settings.
	 * @param  array $old_instance Previous settings.
	 * @return array               Updated settings.
	 */
	public function update ( $new_instance, $old_instance ) {
		$instance = $old_instance;

		/* Strip tags for title and name to remove HTML (important for text inputs). */
		$instance['title'] = strip_tags( $new_instance['title'] );

		/* Make sure the integer values are definitely integers. */
		$instance['limit'] = intval( $new_instance['limit'] );
		
		//allow for more than one id, make unique and int
		if (0 < count($new_instance['specific_id'])) :
			$instance['specific_id'] = implode(',', wp_parse_id_list($new_instance['specific_id']));
		endif;
		
		//check post types
		$tasrpas_options = get_option('tasrpas_settings');
		$post_types = $tasrpas_options['post_types'];
		if (0 < count($new_instance['post_type']) )  :
			$checkarray = explode(',', $new_instance['post_type']);
			$accepted = array();
			foreach($checkarray as $pt) :
				if (in_array($pt, $post_types)):
					array_push($accepted, $pt);
				endif;
			endforeach;
			$instance['post_type'] = implode(',', $accepted);
		endif;
		if ( isset( $new_instance['size'] ) &&  ( 0 < intval( $new_instance['size'] ) ) ) : 
			$instance['size'] = intval( $new_instance['size'] );
		else:
			global $_wp_additional_image_sizes;
			if ( !array_key_exists( $new_instance['size'] , $_wp_additional_image_sizes) ) : 
				$instance['size'] = AUTHORSHIP_IMAGE_SIZE;
			endif;
		endif;
		
		//$instance['category'] = intval( $new_instance['category'] );
		
		if ($new_instance['tax_input']) :
			$instance['tags'] = implode(',', $new_instance['tax_input']['authorship-tags'] );
			$instance['category'] = implode(',', $new_instance['tax_input']['authorship-category'] );
		endif;

		/* The select box is returning a text value, so we escape it. */
		$instance['orderby'] = esc_attr( $new_instance['orderby'] );
		$instance['order'] = esc_attr( $new_instance['order'] );

		/* The checkbox is returning a Boolean (true/false), so we check for that. */
		$instance['display_auto'] = (bool) esc_attr( $new_instance['display_auto'] );
		$instance['display_author'] = (bool) esc_attr( $new_instance['display_author'] );
		$instance['display_avatar'] = (bool) esc_attr( $new_instance['display_avatar'] );
		$instance['display_url'] = (bool) esc_attr( $new_instance['display_url'] );
		$instance['display_social'] = (bool) esc_attr( $new_instance['display_social'] );
		$instance['display_content'] = (bool) esc_attr( $new_instance['display_content'] );
		$instance['display_excerpt'] = (bool) esc_attr( $new_instance['display_excerpt'] );
		$instance['authorship'] = (bool) esc_attr( $new_instance['authorship'] );
		$instance['strict'] = (bool) esc_attr( $new_instance['strict'] );

		return $instance;
	} // End update()

	/**
	 * The form on the widget control in the widget administration area.
	 * Make use of the get_field_id() and get_field_name() function when creating your form elements. This handles the confusing stuff.
	 * @since  0.1.0
	 * @param  array $instance The settings for this instance.
	 * @return void
	 * @TODO: implement better user interface support for post types, revision of the settings page in general
	 */
    public function form( $instance ) {

		/* Set up some default widget settings. */
		/* Make sure all keys are added here, even with empty string values. */
		$defaults = array(
			'title' => '',
			'limit' => 5,
			'orderby' => 'menu_order',
			'order' => 'DESC',
			'authorship' => true,
			'strict' => true,
			'post_type' => '',
			'specific_id' => '',
			'tax_input' => array(),
			'display_auto' => false,
			'display_social' => true,
			'display_author' => true,
			'display_avatar' => true,
			'display_content' => false,
			'display_excerpt' => false,
			'display_url' => true,
			'effect' => 'fade', // Options: 'fade', 'none'
			'pagination' => false,
			'size' => AUTHORSHIP_IMAGE_SIZE, //was 50
			'category' => 0,
			'tags' => 0
		);

		$instance = wp_parse_args( (array) $instance, $defaults );
?>
		<!-- Widget Title: Text Input -->
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title (optional):', 'tessa-authorship' ); ?></label>
			<input type="text" name="<?php echo $this->get_field_name( 'title' ); ?>"  value="<?php echo $instance['title']; ?>" class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" />
		</p>
		<!-- Widget Limit: Text Input -->
		<p>
			<label for="<?php echo $this->get_field_id( 'limit' ); ?>"><?php _e( 'Limit:', 'tessa-authorship' ); ?></label>
			<input type="text" name="<?php echo $this->get_field_name( 'limit' ); ?>"  value="<?php echo $instance['limit']; ?>" class="widefat" id="<?php echo $this->get_field_id( 'limit' ); ?>" />
		</p>
		<!-- Widget Image Size: Text Input -->
		<p>
			<label for="<?php echo $this->get_field_id( 'size' ); ?>"><?php _e( 'Image Size (in pixels):', 'tessa-authorship' ); ?></label>
			<input type="text" name="<?php echo $this->get_field_name( 'size' ); ?>"  value="<?php echo $instance['size']; ?>" class="widefat" id="<?php echo $this->get_field_id( 'size' ); ?>" />
		</p>
		<!-- Widget Order By: Select Input -->
		<p>
			<label for="<?php echo $this->get_field_id( 'orderby' ); ?>"><?php _e( 'Order By:', 'tessa-authorship' ); ?></label>
			<select name="<?php echo $this->get_field_name( 'orderby' ); ?>" class="widefat" id="<?php echo $this->get_field_id( 'orderby' ); ?>">
			<?php foreach ( $this->get_orderby_options() as $k => $v ) { ?>
				<option value="<?php echo $k; ?>"<?php selected( $instance['orderby'], $k ); ?>><?php echo $v; ?></option>
			<?php } ?>
			</select>
		</p>
		<!-- Widget Order: Select Input -->
		<p>
			<label for="<?php echo $this->get_field_id( 'order' ); ?>"><?php _e( 'Order Direction:', 'tessa-authorship' ); ?></label>
			<select name="<?php echo $this->get_field_name( 'order' ); ?>" class="widefat" id="<?php echo $this->get_field_id( 'order' ); ?>">
			<?php foreach ( $this->get_order_options() as $k => $v ) { ?>
				<option value="<?php echo $k; ?>"<?php selected( $instance['order'], $k ); ?>><?php echo $v; ?></option>
			<?php } ?>
			</select>
		</p>
		<!-- Widget Mode: Checkbox Input -->
		<p>
			<input id="<?php echo $this->get_field_id( 'authorship' ); ?>" name="<?php echo $this->get_field_name( 'authorship' ); ?>" type="checkbox"<?php checked( $instance['authorship'], 1 ); ?> />
			<label for="<?php echo $this->get_field_id( 'authorship' ); ?>"><?php _e( 'Display Authorships (Off: Displays author works)', 'tessa-authorship' ); ?></label>
		</p>
		<!-- Widget Use Strict: Checkbox Input -->
		<p>
			<input id="<?php echo $this->get_field_id( 'strict' ); ?>" name="<?php echo $this->get_field_name( 'strict' ); ?>" type="checkbox"<?php checked( $instance['strict'], 1 ); ?> />
			<label for="<?php echo $this->get_field_id( 'strict' ); ?>"><?php _e( 'Only configured relations', 'tessa-authorship' ); ?></label>
		</p>
		<!-- Widget Display Author: Checkbox Input -->
		<p>
			<input id="<?php echo $this->get_field_id( 'display_author' ); ?>" name="<?php echo $this->get_field_name( 'display_author' ); ?>" type="checkbox"<?php checked( $instance['display_author'], 1 ); ?> />
			<label for="<?php echo $this->get_field_id( 'display_author' ); ?>"><?php _e( 'Display Author', 'tessa-authorship' ); ?></label>
		</p>
		<!-- Widget Display Avatar: Checkbox Input -->
		<p>
			<input id="<?php echo $this->get_field_id( 'display_avatar' ); ?>" name="<?php echo $this->get_field_name( 'display_avatar' ); ?>" type="checkbox"<?php checked( $instance['display_avatar'], 1 ); ?> />
			<label for="<?php echo $this->get_field_id( 'display_avatar' ); ?>"><?php _e( 'Display Avatar', 'tessa-authorship' ); ?></label>
		</p>
		<!-- Widget Display Social: Checkbox Input -->
		<p>
			<input id="<?php echo $this->get_field_id( 'display_social' ); ?>" name="<?php echo $this->get_field_name( 'display_social' ); ?>" type="checkbox"<?php checked( $instance['display_social'], 1 ); ?> />
			<label for="<?php echo $this->get_field_id( 'display_social' ); ?>"><?php _e( 'Display Social Links', 'tessa-authorship' ); ?></label>
		</p>

		<!-- Widget Display URL: Checkbox Input -->
		<p>
			<input id="<?php echo $this->get_field_id( 'display_url' ); ?>" name="<?php echo $this->get_field_name( 'display_url' ); ?>" type="checkbox"<?php checked( $instance['display_url'], 1 ); ?> />
			<label for="<?php echo $this->get_field_id( 'display_url' ); ?>"><?php _e( 'Use Permalink', 'tessa-authorship' ); ?></label>
		</p>
		<!-- Widget Display Content: Checkbox Input -->
		<p>
			<input id="<?php echo $this->get_field_id( 'display_content' ); ?>" name="<?php echo $this->get_field_name( 'display_content' ); ?>" type="checkbox"<?php checked( $instance['display_content'], 1 ); ?> />
			<label for="<?php echo $this->get_field_id( 'display_content' ); ?>"><?php _e( 'Display Content', 'tessa-authorship' ); ?></label>
		</p>
		<!-- Widget Display Excerpt: Checkbox Input -->
		<p>
			<input id="<?php echo $this->get_field_id( 'display_excerpt' ); ?>" name="<?php echo $this->get_field_name( 'display_excerpt' ); ?>" type="checkbox"<?php checked( $instance['display_excerpt'], 1 ); ?> />
			<label for="<?php echo $this->get_field_id( 'display_excerpt' ); ?>"><?php _e( 'Display Excerpt', 'tessa-authorship' ); ?></label>
		</p>
		<!-- Widget Auto Context: Checkbox Input -->
		<p>
			<input id="<?php echo $this->get_field_id( 'display_auto' ); ?>" name="<?php echo $this->get_field_name( 'display_auto' ); ?>" type="checkbox"<?php checked( $instance['display_auto'], 1 ); ?> />
			<label for="<?php echo $this->get_field_id( 'display_auto' ); ?>"><?php _e( 'Related according to context', 'tessa-authorship' ); ?></label>
		</p>
		
	   	<!-- Widget Category: Select Input -->
		<p>
			<label for="<?php echo $this->get_field_id( 'category' ); ?>"><?php _e( 'Categories:', 'tessa-authorship' ); ?></label>
			<!-- assure that the thing don't gets endless since 0.2.0 -->
			<div style="max-height:200px;overflow-y:auto">
			<?php
				$selected_cats = explode(',', $instance['category']);
				$checked_ontop = true;
				$terms_args = array(
					'descendants_and_self'  => 0,
					'selected_cats'         => $selected_cats,
					'popular_cats'          => false,
					'walker'                => new Walker_Category_Authorship($this->get_field_id( 'tax_input' ), $this->get_field_name( 'tax_input' )),
					'taxonomy'              => (($instance['authorship']) ? 'authorship-category' : 'category'),
					'checked_ontop'         => true
				);
				wp_terms_checklist( 0, $terms_args );				
			?>
			</div>
		</p>
	   	<!-- Widget Tags: Select Input -->
		<p>
			<label for="<?php echo $this->get_field_id( 'tags' ); ?>"><?php _e( 'Tags:', 'tessa-authorship' ); ?></label>
			<!-- assure that the thing don't gets endless since 0.2.0 -->
			<div style="max-height:200px;overflow-y:auto">
			<?php
				$selected_cats = explode(',', $instance['tags']);
				$checked_ontop = true;
				$terms_args = array(
					'descendants_and_self'  => 0,
					'selected_cats'         => $selected_cats,
					'popular_cats'          => false,
					'walker'                => new Walker_Category_Authorship($this->get_field_id( 'tax_input' ), $this->get_field_name( 'tax_input' )),
					'taxonomy'              => (($instance['authorship']) ? 'authorship-tags' : 'post_tag'),
					'checked_ontop'         => true
				);
				wp_terms_checklist( 0, $terms_args );				
			?>
			</div>
		</p>
		<!-- Widget ID: Text Input -->
		<p>
			<label for="<?php echo $this->get_field_id( 'specific_id' ); ?>"><?php _e( 'Specific IDs (optional):', 'tessa-authorship' ); ?></label>
			<input type="text" name="<?php echo $this->get_field_name( 'specific_id' ); ?>"  value="<?php echo $instance['specific_id']; ?>" class="widefat" id="<?php echo $this->get_field_id( 'specific_id' ); ?>" />
		</p>
		<p><small><?php _e( 'Display specific authors or entries. You may specify more than one id separated by comma, e.g. 22,26,201', 'tessa-authorship' ); ?></small></p>
		<!-- Widget Post types: Text Input -->
		<p>
			<label for="<?php echo $this->get_field_id( 'post_type' ); ?>"><?php _e( 'Specific Post Types (optional):', 'tessa-authorship' ); ?></label>
			<input type="text" name="<?php echo $this->get_field_name( 'post_type' ); ?>"  value="<?php echo $instance['post_type']; ?>" class="widefat" id="<?php echo $this->get_field_id( 'post_type' ); ?>" />
		</p>
		<p><small>
		<?php
			$tasrpas_options = get_option('tasrpas_settings');
			$post_types = implode(',',  $tasrpas_options['post_types']);
			echo __( 'Display works (posts) for specific post types only. Not used when displaying authorships. Configured and hence supported post-types include:', 'tessa-authorship' ) . " $post_types"; ?></small></p>

		<?php
	} // End form()

	/**
	 * Get an array of the available orderby options.
	 * @since  0.1.0
	 * @return array
	 */
	protected function get_orderby_options () {
		return array(
					'none' => __( 'No Order', 'tessa-authorship' ),
					'ID' => __( 'Entry ID', 'tessa-authorship' ),
					'title' => __( 'Title', 'tessa-authorship' ),
					'date' => __( 'Date Added', 'tessa-authorship' ),
					'menu_order' => __( 'Specified Order Setting', 'tessa-authorship' ),
					'rand' => __( 'Random Order', 'tessa-authorship' )
					);
	} // End get_orderby_options()

	/**
	 * Get an array of the available order options.
	 * @since  0.1.0
	 * @return array
	 */
	protected function get_order_options () {
		return array(
					'ASC' => __( 'Ascending', 'tessa-authorship' ),
					'DESC' => __( 'Descending', 'tessa-authorship' )
					);
	} // End get_order_options()
} // End Class

/* Register the widget. */
add_action( 'widgets_init', create_function( '', 'return register_widget("Tessa_Authorship_Widget");' ), 1 );

/**
 * Class Walker_Category_Authorship.
 * @since   0.2.0
 * @param   string $field_id   The id of the widget instance field.
 * @param   string $field_name The name of the widget instance field.
 * @description Needed if we want to use wp_terms_checklist for multiple selects instead of dropdowns with exclusive select
 */
class Walker_Category_Authorship extends Walker_Category {
	private $field_id;
	private $field_name;
	/**
	 * Class constructor.
	 * @access  public
	 * @since   0.2.0
	 * @param   string $field_id   The id of the widget instance field.
	 * @param   string $field_name The name of the widget instance field.
	 */
	public function __construct ( $field_id, $field_name ) {
		$this->field_id = $field_id;
		$this->field_name = $field_name;
	} // End __construct()
   
	/**
	 * Start the element output.
	 *
	 * @see Walker::start_el()
	 *
	 * @since 0.2.0
	 *
	 * @param string $output   Passed by reference. Used to append additional content.
	 * @param object $category The current term object.
	 * @param int    $depth    Depth of the term in reference to parents. Default 0.
	 * @param array  $args     An array of arguments. @see wp_terms_checklist()
	 * @param int    $id       ID of the current term.
	 */
	function start_el( &$output, $category, $depth = 0, $args = array(), $id = 0 ) {
		extract($args);
		if ( empty($taxonomy) )
			$taxonomy = 'category';

		if ( $taxonomy == 'category' )
			$name = 'post_category';
		else
			$name = $this->field_name . '['.$taxonomy.']';

		$class = in_array( $category->term_id, $popular_cats ) ? ' class="popular-category"' : '';
		$output .= "\n<li style=\"list-style:none;\" id='{$taxonomy}-{$category->term_id}'$class>" . '<label class="selectit"><input value="' . $category->term_id . '" type="checkbox" name="'.$name.'[]" id="in-'.$taxonomy.'-' . $category->term_id . '"' . checked( in_array( $category->term_id, $selected_cats ), true, false ) . disabled( empty( $args['disabled'] ), false, false ) . ' /> ' . esc_html( apply_filters('the_category', $category->name )) . '</label>';
	}
}

?>