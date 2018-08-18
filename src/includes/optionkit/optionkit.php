<?php
namespace OptionKit;

if ( ! defined( 'ABSPATH' ) ) {
	return;
}

use OptionKit;

class MenuFields {

	var $frameworkName = "OptionKit";
	
	var $version = 0.1;

	var $menu = array();

	var $submenus = array();

	var $sections = array();

	var $fields = array();

	var $title = '';

	var $identifier = "";

	private static $instance;

	public static function getInstance() {

		if ( null === self::$instance ) {
            self::$instance = new self();
        }
 
        return self::$instance;
	}

	private function __construct() {}

	public function register() 
	{
		add_action('admin_menu', array($this, 'createOptionPage'));
		add_action('admin_init', array($this, 'createOptionFields') );
		add_action('admin_enqueue_scripts', array($this, 'adminEnqueueScript'));
	}

	function adminEnqueueScript() {
		wp_enqueue_script( 'optionkit-js', plugins_url('/assets/js/optionkit.js', __FILE__), array('jquery'), $this->version, false );
	}

	function createOptionPage() {

		// Add main menu
		if ( ! empty( $this->menu ) ): 
			foreach ( $this->menu as $menu ):
				add_menu_page( 
					$menu['page_title'],
					$menu['menu_title'], 
					$menu['capability'], 
					$menu['menu_slug'],
					$menu['callback'],
					$menu['icon_url'], 
					$menu['position']
				);
			endforeach;
		endif;

		//Submenu.
		if ( ! empty( $this->submenus ) ):
			foreach ( $this->submenus as $submenu ):
				add_submenu_page(
					$submenu['parent_slug'], 
					$submenu['page_title'], 
					$submenu['menu_title'], 
					$submenu['capability'], 
					$submenu['menu_slug'], 
					$submenu['function']
				);
			endforeach;
		endif;

		return;
	}

	public function createOptionFields(){

		// All of settings sections.
		foreach ( $this->sections as $section ) {

			add_settings_section(
				$section['id'],
				$section['label'],
				$section['callback'],
				$section['page']
			);

		}
		// Add all section fields.
		foreach ( $this->fields as $field ) {
			
			add_settings_field(
				$field['id'],
				$field['title'],
				$field['callback'],
				$field['page'],
				$field['section'],
				$field['args']
			);

			/*register_setting( $this->identifer, $field['id'], array(
				'sanitize_callback' => array($this, 'validate')  
			));*/
			register_setting( $field['page'], $field['id'], array(
				'show_in_rest' => false
			) );

		}
	}

	public function validateInt( $value ) {

		add_settings_error( 'settings_message', 'error', 'There is an error.', 'error' );

		return $value;
	}

	public function menu( $args = array() ) 
	{

		$defaults = array(
			'page_title' => '',
			'menu_title' => 'OptionKit Default Menu Title',
			'capability' => 'manage_options',
			'menu_slug' => '',
			'callback' => array( $this, 'wrap'),
			'icon_url' => '',
			'position' => 80
		);

		$menu = wp_parse_args( $args, $defaults );

		if ( empty( $menu['page_title'] ) ) {
			$menu['page_title'] = $menu['menu_title'];
		}

		$this->menu[] = $menu;

		if ( empty( $args['menu_slug'] ) ) {
			wp_die( $this->frameworkName . ' Error: \'menu_slug\' is empty or not defined.');
		}
		
		return $this->menu;
	}

	/**
	 * Registers a submenu.
	 * @param  array  $args The arguments.
	 * @return array  The submenu properties.
	 */
	public function submenu( $args = array() ) 
	{
		$defaults = array(
			'parent_slug' => 'options-general.php',
			'page_title' => '',
			'menu_title' => 'My Options',
			'capability' => 'manage_options',
			'menu_slug' => '',
			'function' => array( $this, 'wrap' ),
		);

		$submenu = wp_parse_args( $args, $defaults );

		if ( empty( $submenu['page_title'] ) ) {
			$submenu['page_title'] = $submenu['menu_title'];
		}

		$this->submenus[] = $submenu;

		return $this->submenus;
			
	}

	public function wrap() {
		$this->content();
	}

	protected function content() 
	{
		// must check that the user has the required capability.
	    if (!current_user_can('manage_options'))
	    {
	      wp_die( __('You do not have sufficient permissions to access this page.') );
	    }

	    ?>
	    <div class="wrap">
	    	<?php
	    	// check if the user have submitted the settings
			// wordpress will add the "settings-updated" $_GET parameter to the url
			if ( isset( $_GET['settings-updated'] ) ) {
				// add settings saved message with the class of "updated"
				add_settings_error( 'settings_message', 'wporg_message', __( 'Settings Saved', 'wporg' ), 'updated' );
			}
			 
			// show error/update messages
			settings_errors( 'settings_message' );
	    	?>
	    	<h1 class="wp-heading-inline">
	    		<?php echo wp_kses_post( $this->getPageTitle() ); ?>
	    	</h1>

	    	<form action="options.php" method="post">
	    		<?php $page = ''; ?>
	    		<?php if ( isset( $_GET['page'] ) ): ?>
	    			<?php $page = $_GET['page']; ?>
	    		<?php endif; ?>
	    		<?php settings_fields( $page ); ?>
	    		<?php do_settings_sections( $_GET['page'] ); ?>
				<?php submit_button( 'Save Settings' ); ?>
	    	</form>
	    	
	    </div>
	    <?php

	}

	public function sectionCallback( $section )
	{
		foreach( $this->sections as $registered_section ) {
			if ( $registered_section['id'] === $section['id']) {
				if ( isset( $registered_section['desc'] ) ) {
					echo wp_kses_post( $registered_section['desc'] );
				}
				break;
			}
		}
	}

	public function fieldCallback( $args ) {
	
		$type = ! empty ( $args['type'] ) ? $args['type'] : 'text'; 
		
		if ( ! empty( $type ) ) {

			$path = sprintf( 'field-types/%s.php', sanitize_title( $type ) );
			
			$field_file = trailingslashit( plugin_dir_path(__FILE__) ) . $path;

			if ( file_exists( $field_file )) {

				require_once $field_file;
			} else {
				echo sprintf( esc_html__('The field type "%s" is not supported.', 'optionkit'), $type );
				return;
			}
		}
		switch ( $type ):

			case 'text':
				$field = new OptionKit\FieldTypes\Text($args);
			break;

			case 'email':
				$field = new OptionKit\FieldTypes\Email($args);
			break;

			case 'password':
				$field = new OptionKit\FieldTypes\Password($args);
			break;

			case 'textarea':
				$field = new OptionKit\FieldTypes\TextArea($args);
			break;

			case 'colorpicker':
				$field = new OptionKit\FieldTypes\ColorPicker($args);
			break;

			case 'select':
				$field = new OptionKit\FieldTypes\Select($args);
			break;

			case 'multiselect':
				$field = new OptionKit\FieldTypes\MultiSelect($args);
			break;

			case 'radio':
				$field = new OptionKit\FieldTypes\Radio($args);
			break;

			case 'checkbox':
				$field = new OptionKit\FieldTypes\CheckBox($args);
			break;

			case 'url':
				$field = new OptionKit\FieldTypes\Url($args);
			break;

			case 'number':
				$field = new OptionKit\FieldTypes\Number($args);
			break;

			case 'wysiwyg':
				$field = new OptionKit\FieldTypes\WYSIWYG($args);
			break;

			case 'range':
				$field = new OptionKit\FieldTypes\Range($args);
			break;

			case 'image-upload':
				$field = new OptionKit\FieldTypes\MediaUpload($args);
			break;

		endswitch;

		$field->display();

	}

	public function addSection( $args ) 
	{

		$defaults = array(
			'description' => '',
			'callback' => array( $this, 'sectionCallback' )
		);

		$this->sections[] = wp_parse_args( $args, $defaults );

		return $this->sections;
		
	}

	public function addField( $args ) {

		$args_defaults = array(
			'id' => '',
			'default' => '',
			'section' => '',
			'description' => '',
			'attributes' => array(),
			'callback' => array( $this, 'fieldCallback' ),
			'name' => $args['id'],
			'label_for' => $args['id'],
			'type' => ''
		);

		$args = wp_parse_args( $args, $args_defaults );

		// Convert attributes key to html element attributes format.
		if ( is_array( $args['attributes'] ) ) {
			$attribute = "";
			foreach( $args['attributes'] as $key => $val ) {
				$attribute .= sprintf('%s="%s" ', esc_attr( $key ), esc_attr( $val ) );
			}
			$args['attributes'] = " " . $attribute . " ";
		}

		$this->fields[] = wp_parse_args( array(
				'id' => $args['id'],
				'title' => $args['title'],
				'page' => $args['page'],
				'section' => $args['section'],
				'args' => $args,
			), $args_defaults );

		return $this->fields;
	}

	protected function getPageTitle() {
		
		return get_admin_page_title();
		
	}


}

