<?php

if ( ! defined( 'ABSPATH' ) ) exit;

class Social_Monitor {

	/**
	 * The single instance of Social_Monitor.
	 * @var 	object
	 * @access  private
	 * @since 	1.0.0
	 */
	private static $_instance = null;

	/**
	 * Settings class object
	 * @var     object
	 * @access  public
	 * @since   1.0.0
	 */
	public $settings = null;

	/**
	 * The version number.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $_version;

	/**
	 * The token.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $_token;

	/**
	 * The main plugin file.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $file;

	/**
	 * The main plugin directory.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $dir;

	/**
	 * The plugin assets directory.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $assets_dir;

	/**
	 * The plugin assets URL.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $assets_url;

	/**
	 * Suffix for Javascripts.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $script_suffix;

	/**
	 * Constructor function.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function __construct ( $file = '', $version = '1.0.0' ) {
		$this->_version = $version;
		$this->_token = 'social_monitor';

		// Load plugin environment variables
		$this->file = $file;
		$this->dir = dirname( $this->file );
		$this->assets_dir = trailingslashit( $this->dir ) . 'assets';
		$this->assets_url = esc_url( trailingslashit( plugins_url( '/assets/', $this->file ) ) );

		$this->script_suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		register_activation_hook( $this->file, array( $this, 'install' ) );

		// Load frontend JS & CSS
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ), 10 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ), 10 );

		// Load admin JS & CSS
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ), 10, 1 );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_styles' ), 10, 1 );

		// Load API for generic admin functions
		if ( is_admin() ) {
			$this->admin = new Social_Monitor_Admin_API();
		}

		// Handle localisation
		$this->load_plugin_textdomain();
		add_action( 'init', array( $this, 'load_localisation' ), 0 );
	} // End __construct ()

	/**
	 * Wrapper function to register a new post type
	 * @param  string $post_type   Post type name
	 * @param  string $plural      Post type item plural name
	 * @param  string $single      Post type item single name
	 * @param  string $description Description of post type
	 * @return object              Post type class object
	 */
	public function register_post_type ( $post_type = '', $plural = '', $single = '', $description = '', $options = array() ) {

		if ( ! $post_type || ! $plural || ! $single ) return;

		$post_type = new Social_Monitor_Post_Type( $post_type, $plural, $single, $description, $options );

		return $post_type;
	}

	/**
	 * Wrapper function to register a new taxonomy
	 * @param  string $taxonomy   Taxonomy name
	 * @param  string $plural     Taxonomy single name
	 * @param  string $single     Taxonomy plural name
	 * @param  array  $post_types Post types to which this taxonomy applies
	 * @return object             Taxonomy class object
	 */
	public function register_taxonomy ( $taxonomy = '', $plural = '', $single = '', $post_types = array(), $taxonomy_args = array() ) {

		if ( ! $taxonomy || ! $plural || ! $single ) return;

		$taxonomy = new Social_Monitor_Taxonomy( $taxonomy, $plural, $single, $post_types, $taxonomy_args );

		return $taxonomy;
	}

	/**
	 * Load frontend CSS.
	 * @access  public
	 * @since   1.0.0
	 * @return void
	 */
	public function enqueue_styles () {
		wp_register_style( $this->_token . '-frontend', esc_url( $this->assets_url ) . 'css/frontend.css', array(), $this->_version );
		wp_enqueue_style( $this->_token . '-frontend' );
	} // End enqueue_styles ()

	/**
	 * Load frontend Javascript.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function enqueue_scripts () {
		wp_register_script( $this->_token . '-frontend', esc_url( $this->assets_url ) . 'js/frontend' . $this->script_suffix . '.js', array( 'jquery' ), $this->_version );
		wp_enqueue_script( $this->_token . '-frontend' );
	} // End enqueue_scripts ()

	/**
	 * Load admin CSS.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function admin_enqueue_styles ( $hook = '' ) {
		wp_register_style( $this->_token . '-admin', esc_url( $this->assets_url ) . 'css/admin.css', array(), $this->_version );
		wp_enqueue_style( $this->_token . '-admin' );
	} // End admin_enqueue_styles ()

	/**
	 * Load admin Javascript.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function admin_enqueue_scripts ( $hook = '' ) {
		wp_register_script( $this->_token . '-admin', esc_url( $this->assets_url ) . 'js/admin' . $this->script_suffix . '.js', array( 'jquery' ), $this->_version );
		wp_enqueue_script( $this->_token . '-admin' );
	} // End admin_enqueue_scripts ()

	/**
	 * Load plugin localisation
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function load_localisation () {
		load_plugin_textdomain( 'social-monitor', false, dirname( plugin_basename( $this->file ) ) . '/lang/' );
	} // End load_localisation ()

	/**
	 * Load plugin textdomain
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function load_plugin_textdomain () {
	    $domain = 'social-monitor';

	    $locale = apply_filters( 'plugin_locale', get_locale(), $domain );

	    load_textdomain( $domain, WP_LANG_DIR . '/' . $domain . '/' . $domain . '-' . $locale . '.mo' );
	    load_plugin_textdomain( $domain, false, dirname( plugin_basename( $this->file ) ) . '/lang/' );
	} // End load_plugin_textdomain ()

	/**
	 * Main Social_Monitor Instance
	 *
	 * Ensures only one instance of Social_Monitor is loaded or can be loaded.
	 *
	 * @since 1.0.0
	 * @static
	 * @see Social_Monitor()
	 * @return Main Social_Monitor instance
	 */
	public static function instance ( $file = '', $version = '1.0.0' ) {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self( $file, $version );
		}
		return self::$_instance;
	} // End instance ()

	/**
	 * Cloning is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __clone () {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?' ), $this->_version );
	} // End __clone ()

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __wakeup () {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?' ), $this->_version );
	} // End __wakeup ()

	/**
	 * Installation. Runs on activation.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function install () {
		$this->_log_version_number();
	} // End install ()

	/**
	 * Log the plugin version number.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	private function _log_version_number () {
		update_option( $this->_token . '_version', $this->_version );
	} // End _log_version_number ()

  function get_post_next_url() {
    $last_id = get_posts( array(
      'post_type' => 'sm_social_post',
      'orderby' => 'meta_value',
      'metakey'=> 'created',
      'order' => 'ASC',
      'posts_per_page' => 1,
      'post_status' => array('draft', 'publish', 'future', 'pending')
    ) );
    if ($last_id) {
      $next_url = get_post_meta( $last_id[0]->ID, 'next_url', true );
      $url_params = parse_url($next_url);
      parse_str($url_params['query'], $params);
      $params['access_token'] = get_option('sm_instagram_token');
      $params['count'] = get_option('sm_instagram_batch_size');

      $next_url = $url_params['scheme'] . '://' . $url_params['host'] . $url_params['path'] . '?' . http_build_query($params);

      return $next_url;
    }
  }

  function get_instagram_posts($new_posts = true) {

    $access_token = get_option('sm_instagram_token');

    if( !$access_token ){

      return;

    }

    $args = array();
    $next_url = $this->get_post_next_url();

    if ($new_posts == true || $next_url == NULL) {
      $hashtag = get_option('sm_instagram_hashtag');
      $hashtag = ($hashtag ? $hashtag : 'northofnyc');
      $count = get_option('sm_instagram_batch_size');
      $args['count'] = ($count ? $count : 20);
      $args['access_token'] = $access_token;
      $url = 'https://api.instagram.com/v1/tags/' . $hashtag . '/media/recent';
    } else {
      $url = $next_url;
    }


    $request = new CURL_Request($url, array(
      'User-Agent' => 'King & Partners - Social Monitor'
    ));
    $request->set_query_parameters($args);

    $response = $request->GET();
    $return = array();

    if (isset($response->data)) {
      $return['posts'] = $response->data;
      $return['next_url'] = $response->pagination->next_url;
    }

    return $return;

  }

}

class CURL_Request {

	function __construct($url, $headers = array()) {
		$this->request = curl_init();
		$this->set_url($url);
		$this->set_option(CURLOPT_RETURNTRANSFER, true);

		if (count($headers))
			$this->set_headers($headers);
	}

	function __destruct() {
		curl_close($this->request);
	}

	function set_url($url) {
		$this->url = $url;
	}

	function set_headers($headers) {
		$this->headers = $headers;

		$header_pairs = array();
		foreach ($this->headers as $key => $val) {
			$header_pairs[] = $key.': '.$val;
		}

		$this->set_option(CURLOPT_HTTPHEADER, $header_pairs);
	}

	function set_query_parameters($query_parameters) {
		$this->query_parameters = $query_parameters;
	}

	function set_post_data($post_data) {
		$this->post_data = $post_data;
		$this->set_option(CURLOPT_POSTFIELDS, http_build_query($this->post_data));
	}

	function GET() {
		$this->response = $this->execute();
		return json_decode($this->response);
	}

	function POST() {
		$this->set_option(CURLOPT_POST, true);
		$this->response = $this->execute();
		return json_decode($this->response);
	}

	function execute() {
		$this->set_option(CURLOPT_URL, $this->make_request_url());

		$this->make_request_url();
		return curl_exec($this->request);
	}

	protected function set_option($key, $val) {
		curl_setopt($this->request, $key, $val);
	}

	protected function make_request_url() {

		$url = $this->url;

		if ($this->query_parameters && count($this->query_parameters)) {
			$query_string = http_build_query($this->query_parameters);
			$url = $this->url . '?' . $query_string;
		}

		return $url;

	}

}

class Social_Post {
	function __construct($title, $text, $author = null, $service, $photo_url, $video_url, $service_id = null, $original_url = null, $created = null) {
		$this->original_author = $author;
		$this->title = $title;
		$this->text = $text;
		$this->service = $service;
		$this->photo_url = $photo_url;
		$this->video_url = $video_url;
		$this->service_id = $service_id;
		$this->original_url = $original_url;
		$this->created = $created;
	}
	function add_attachments($attachments){

		$this->add_meta('attachments', $attachments);

	}
	function save() {

		$auto_publish = get_option('sp_auto_publish');
		$categories = get_option('sp_auto_categorize');

    $text = $this->text;
    if (strlen($text) > 54) {
      $text = substr($text, 0, 54) . '...';
    }

		$this->id = wp_insert_post(array(
			'post_title' => $text,
			'post_type' => 'sm_social_post',
			'post_status' => 'pending',
			'post_date' => date( 'Y-m-d H:i:s', $this->created )
		), true);

		/* pause for fraction of a second */
		usleep(62500);


		wp_set_post_terms( $this->id, $categories, 'category', true );

		$this->add_meta('text', $this->remove_emoji( $this->text ) );
		$this->add_meta('original_author', $this->original_author);
		$this->add_meta('service', $this->service);
		$this->add_meta('photo_url', $this->photo_url);
		$this->add_meta('video_url', $this->video_url);
		$this->add_meta('service_id', $this->service_id);
		$this->add_meta('original_url', $this->original_url);
		$this->add_meta('created', $this->created);
		$this->add_meta('published', ( $auto_publish ) ? 1 : 0 );
    $this->add_meta('next_url', $this->next_url);

		return $this->id;

	}
	function add_meta($key, $value, $unique = false) {
		add_post_meta($this->id, $key, $value, $unique);
	}
	function remove_emoji( $text ){

		$clean_text = '';

		// Match Emoticons
		$regexEmoticons = '/[\x{1F600}-\x{1F64F}]/u';
		$clean_text = preg_replace($regexEmoticons, '', $text);

		// Match Miscellaneous Symbols and Pictographs
		$regexSymbols = '/[\x{1F300}-\x{1F5FF}]/u';
		$clean_text = preg_replace($regexSymbols, '', $clean_text);

		// Match Transport And Map Symbols
		$regexTransport = '/[\x{1F680}-\x{1F6FF}]/u';
		$clean_text = preg_replace($regexTransport, '', $clean_text);

		// Match Miscellaneous Symbols
		$regexMisc = '/[\x{2600}-\x{26FF}]/u';
		$clean_text = preg_replace($regexMisc, '', $clean_text);

		// Match Dingbats
		$regexDingbats = '/[\x{2700}-\x{27BF}]/u';
		$clean_text = preg_replace($regexDingbats, '', $clean_text);

		return $clean_text;

	}
}

class Instagram_Post extends Social_Post {
	const SERVICE = 'instagram';
  function __construct($id, $text, $author, $photo_url, $video_url, $original_url, $created, $next_url) {
    $this->next_url = $next_url;
		parent::__construct('Instagram '.$id, $text, $author, self::SERVICE, $photo_url, $video_url, $id, $original_url, $created);
	}
}
