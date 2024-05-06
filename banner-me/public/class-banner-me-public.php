<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    Banner_Me
 * @subpackage Banner_Me/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Banner_Me
 * @subpackage Banner_Me/public
 * @author     Your Name <email@example.com>
 */
class Banner_Me_Public {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $banner_me    The ID of this plugin.
	 */
	private $banner_me;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $banner_me       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $banner_me, $version ) {

		$this->banner_me = $banner_me;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Banner_Me_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Banner_Me_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->banner_me, plugin_dir_url( __FILE__ ) . 'css/banner-me-public.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Banner_Me_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Banner_Me_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->banner_me, plugin_dir_url( __FILE__ ) . 'js/banner-me-public.js', array( 'jquery' ), $this->version, false );

	}

	public function add_banner() {
		$text = get_option('banner_text');
		$visible = get_option('banner_visible');
		?>
		<div class="alert" <?php echo $visible ? '' : 'hidden'; ?>>
			<div class="alert-text">
				<span><?php echo isset( $text ) ? esc_attr( $text ) : ''; ?></span>
				<span class="alert-button" onclick="this.parentElement.style.display='none';">&times;</span>
			</div>
		</div> 
		<?php
	}

}


