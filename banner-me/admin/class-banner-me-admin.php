<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    Banner_Me
 * @subpackage Banner_Me/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Banner_Me
 * @subpackage Banner_Me/admin
 * @author     Your Name <email@example.com>
 */
class Banner_Me_Admin {

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
	 * @param      string    $banner_me       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $banner_me, $version ) {

		$this->banner_me = $banner_me;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the admin area.
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

		wp_enqueue_style( $this->banner_me, plugin_dir_url( __FILE__ ) . 'css/banner-me-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
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

		wp_enqueue_script( $this->banner_me, plugin_dir_url( __FILE__ ) . 'js/banner-me-admin.js', array( 'jquery' ), $this->version, false );

	}

	public function banner_options_page() {
		add_menu_page(
			'Banner',
			'Banner Me',
			'manage_options',
			'banner',
			'banner_options_page_html',
			'dashicons-table-row-before',
			65
		);
		init_menu_page();
	}

}

function banner_options_page_html() {
    ?>
    <div class="wrap">
      <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
      <form action="options.php" method="post">
        <?php
        // output security fields for the registered setting "banner_options"
        settings_fields( 'banner_option_group' );
        // output setting sections and their fields
        // (sections are registered for "banner", each field is registered to a specific section)
        do_settings_sections( 'banner' );
        // output save settings button
        submit_button( __( 'Save Settings', 'textdomain' ) );
        ?>
      </form>
    </div>
    <?php
}



function init_menu_page() {
	// register a new setting for "reading" page
	register_setting('banner_option_group', 'banner_text');
	register_setting('banner_option_group', 'banner_visible', array(
		'type'              => 'bool',
	));

	// register a new section in the "reading" page
	add_settings_section(
		'banner_settings_section',
		'', 
		'banner_settings_section_callback',
		'banner'
	);

	// register a new field in the "banner_settings_section" section, inside the "reading" page
	add_settings_field(
		'banner_text_field',
		'Banner tekst', 
		'banner_text_field_callback',
		'banner',
		'banner_settings_section'
	);

	// register a new field in the "banner_settings_section" section, inside the "reading" page
	add_settings_field(
		'banner_visible_field',
		'Banner zichtbaar?', 
		'banner_visible_field_callback',
		'banner',
		'banner_settings_section'
	);
}



/**
 * callback functions
 */

// section content cb
function banner_settings_section_callback() {
	// echo '<p>Banner Section Introduction.</p>';
}

// field content cb
function banner_text_field_callback() {
	// get the value of the setting we've registered with register_setting()
	$setting = get_option('banner_text');
	// output the field
	?>
	<input class=banner-text type="text" name="banner_text" value="<?php echo isset( $setting ) ? esc_attr( $setting ) : ''; ?>">
    <?php
}

function banner_visible_field_callback() {
	// get the value of the setting we've registered with register_setting()
	$setting = get_option('banner_visible');
	// output the field
	?>
	<label class="switch">
		<input name="banner_visible" <?php echo $setting ? 'checked' : ''; ?> type="checkbox">
		<span class="slider round"></span>
	</label>
    <?php
}


