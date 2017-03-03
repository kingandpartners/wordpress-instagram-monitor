<?php

if ( ! defined( 'ABSPATH' ) ) exit;

class Social_Monitor_Settings {

	/**
	 * The single instance of Social_Monitor_Settings.
	 * @var 	object
	 * @access  private
	 * @since 	1.0.0
	 */
	private static $_instance = null;

	/**
	 * The main plugin object.
	 * @var 	object
	 * @access  public
	 * @since 	1.0.0
	 */
	public $parent = null;

	/**
	 * Prefix for plugin settings.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $base = '';

	/**
	 * Available settings for plugin.
	 * @var     array
	 * @access  public
	 * @since   1.0.0
	 */
	public $settings = array();

	public function __construct ( $parent ) {
		$this->parent = $parent;

		$this->base = 'sm_';

		// Initialise settings
		add_action( 'init', array( $this, 'init_settings' ), 11 );

		// Register plugin settings
		add_action( 'admin_init' , array( $this, 'register_settings' ) );

		// Add settings page to menu
		add_action( 'admin_menu' , array( $this, 'add_menu_item' ) );

		// Add settings link to plugins page
		add_filter( 'plugin_action_links_' . plugin_basename( $this->parent->file ) , array( $this, 'add_settings_link' ) );
	}

	/**
	 * Initialise settings
	 * @return void
	 */
	public function init_settings () {
		$this->settings = $this->settings_fields();
	}

	/**
	 * Add settings page to admin menu
	 * @return void
	 */
	public function add_menu_item () {
		$page = add_options_page( __( 'Social Monitor', 'social-monitor' ) , __( 'Social Monitor', 'social-monitor' ) , 'manage_options' , $this->parent->_token . '_settings' ,  array( $this, 'settings_page' ) );
		add_action( 'admin_print_styles-' . $page, array( $this, 'settings_assets' ) );
	}

	/**
	 * Load settings JS & CSS
	 * @return void
	 */
	public function settings_assets () {

  	// We're including the WP media scripts here because they're needed for the image upload field
  	// If you're not including an image upload then you can leave this function call out
  	wp_enqueue_media();

  	wp_register_script( $this->parent->_token . '-settings-js', $this->parent->assets_url . 'js/settings' . $this->parent->script_suffix . '.js', array( 'jquery' ), '1.0.0' );
  	wp_enqueue_script( $this->parent->_token . '-settings-js' );
	}

	/**
	 * Add settings link to plugin list table
	 * @param  array $links Existing links
	 * @return array 		Modified links
	 */
	public function add_settings_link ( $links ) {
		$settings_link = '<a href="options-general.php?page=' . $this->parent->_token . '_settings">' . __( 'Settings', 'social-monitor' ) . '</a>';
  		array_push( $links, $settings_link );
  		return $links;
	}

	/**
	 * Build settings fields
	 * @return array Fields to be displayed on settings page
	 */
	private function settings_fields () {
    // $categories = get_categories(array('hide_empty' => false));
    $tags = get_terms('sm_social_tag', array('hide_empty' => false));
    $tags_list = array();
		
		foreach($tags as $tag ){
			
			$tags_list[$tag->ID] = $tag->name;
			
		}


		$settings['standard'] = array(
			'title'					=> __( 'Base', 'social-monitor' ),
			'description'			=> __( '', 'social-monitor' ),
			'fields'				=> array(
				array(
					'id' 			=> 'auto_publish',
          'label'			=> __( 'Auto Publish', 'social-monitor' ),		
					'description'	=> __( 'Enabling auto publish will automatically set newly imported posts to "published." Not recommended if importing posts from sources outside of your control.', 'social-monitor' ),		
					'type'			=> 'checkbox',		
					'default'		=> ''		
				)
			)
		);
    $settings['instagram'] = array(
      'title'         => __( 'Instagram', 'social-monitor' ),
      'description'   => __( 'This page controls how the plugin imports data from Instagram.'),
      'fields'        => array(
        array(
          'type'			=> 'checkbox',
					'id' 			=> 'enable',		
					'label'			=> __( 'Enable', 'social-monitor' ),		
					'description'	=> __( 'Enable or disable the Instagram importer.', 'social-monitor' ),		
					'default'		=> false
        ),
        array(
					'id' 			=> 'instagram_token',
					'label'			=> __( 'Instagram Access Token' , 'social-monitor' ),
					'description'	=> __( 'Don\'t change this if you don\'t know what it is! Get a new one here: http://instagram.pixelunion.net/', 'social-monitor' ),
					'type'			=> 'text',
					'default'		=> '',
					'placeholder'	=> __( '', 'social-monitor' )
				),
        array(
					'id' 			=> 'instagram_batch_size',
					'label'			=> __( 'Import Batch Size' , 'social-monitor' ),
					'description'	=> __( 'How many posts should each import attempt to bring in from Instagram\'s servers?', 'social-monitor' ),
					'type'			=> 'number',
					'default'		=> '40',
					'placeholder'	=> __( '', 'social-monitor' )
				),
        array(
					'id' 			=> 'instagram_hashtag',
					'label'			=> __( 'Targeted Tag' , 'social-monitor' ),
					'description'	=> __( 'Which tag do you want to target to pull posts from Instagram?', 'social-monitor' ),
					'type'			=> 'text',
					'default'		=> 'northofnyc',
					'placeholder'	=> __( '', 'social-monitor' )
        )
      )
    );
    
		$settings = apply_filters( $this->parent->_token . '_settings_fields', $settings );

		return $settings;
	}

	/**
	 * Register plugin settings
	 * @return void
	 */
	public function register_settings () {
		if ( is_array( $this->settings ) ) {

			// Check posted/selected tab
			$current_section = '';
			if ( isset( $_POST['tab'] ) && $_POST['tab'] ) {
				$current_section = $_POST['tab'];
			} else {
				if ( isset( $_GET['tab'] ) && $_GET['tab'] ) {
					$current_section = $_GET['tab'];
				}
			}

			foreach ( $this->settings as $section => $data ) {

				if ( $current_section && $current_section != $section ) continue;

				// Add section to page
				add_settings_section( $section, $data['title'], array( $this, 'settings_section' ), $this->parent->_token . '_settings' );

				foreach ( $data['fields'] as $field ) {

					// Validation callback for field
					$validation = '';
					if ( isset( $field['callback'] ) ) {
						$validation = $field['callback'];
					}

					// Register field
					$option_name = $this->base . $field['id'];
					register_setting( $this->parent->_token . '_settings', $option_name, $validation );

					// Add field to page
					add_settings_field( $field['id'], $field['label'], array( $this->parent->admin, 'display_field' ), $this->parent->_token . '_settings', $section, array( 'field' => $field, 'prefix' => $this->base ) );
				}

				if ( ! $current_section ) break;
			}
		}
	}

	public function settings_section ( $section ) {
		$html = '<p> ' . $this->settings[ $section['id'] ]['description'] . '</p>' . "\n";
		echo $html;
	}

	/**
	 * Load settings page content
	 * @return void
	 */
	public function settings_page () {

		// Build page HTML
		$html = '<div class="wrap" id="' . $this->parent->_token . '_settings">' . "\n";
			$html .= '<h2>' . __( 'Social Monitor Settings' , 'social-monitor' ) . '</h2>' . "\n";

			$tab = '';
			if ( isset( $_GET['tab'] ) && $_GET['tab'] ) {
				$tab .= $_GET['tab'];
			}

			// Show page tabs
			if ( is_array( $this->settings ) && 1 < count( $this->settings ) ) {

				$html .= '<h2 class="nav-tab-wrapper">' . "\n";

				$c = 0;
				foreach ( $this->settings as $section => $data ) {

					// Set tab class
					$class = 'nav-tab';
					if ( ! isset( $_GET['tab'] ) ) {
						if ( 0 == $c ) {
							$class .= ' nav-tab-active';
						}
					} else {
						if ( isset( $_GET['tab'] ) && $section == $_GET['tab'] ) {
							$class .= ' nav-tab-active';
						}
					}

					// Set tab link
					$tab_link = add_query_arg( array( 'tab' => $section ) );
					if ( isset( $_GET['settings-updated'] ) ) {
						$tab_link = remove_query_arg( 'settings-updated', $tab_link );
					}

					// Output tab
					$html .= '<a href="' . $tab_link . '" class="' . esc_attr( $class ) . '">' . esc_html( $data['title'] ) . '</a>' . "\n";

					++$c;
				}

				$html .= '</h2>' . "\n";
			}

			$html .= '<form method="post" action="options.php" enctype="multipart/form-data">' . "\n";

				// Get settings fields
				ob_start();
				settings_fields( $this->parent->_token . '_settings' );
				do_settings_sections( $this->parent->_token . '_settings' );
				$html .= ob_get_clean();

				$html .= '<p class="submit">' . "\n";
					$html .= '<input type="hidden" name="tab" value="' . esc_attr( $tab ) . '" />' . "\n";
					$html .= '<input name="Submit" type="submit" class="button-primary" value="' . esc_attr( __( 'Save Settings' , 'social-monitor' ) ) . '" />' . "\n";
				$html .= '</p>' . "\n";
			$html .= '</form>' . "\n";
		$html .= '</div>' . "\n";

		echo $html;
	}

	/**
	 * Main Social_Monitor_Settings Instance
	 *
	 * Ensures only one instance of Social_Monitor_Settings is loaded or can be loaded.
	 *
	 * @since 1.0.0
	 * @static
	 * @see Social_Monitor()
	 * @return Main Social_Monitor_Settings instance
	 */
	public static function instance ( $parent ) {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self( $parent );
		}
		return self::$_instance;
	} // End instance()

	/**
	 * Cloning is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __clone () {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?' ), $this->parent->_version );
	} // End __clone()

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __wakeup () {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?' ), $this->parent->_version );
	} // End __wakeup()

}
