<?php

namespace IgniteKit\WP\OptionBuilder;

/**
 * OptionBuilder loader class.
 */
class Bootstrap {

	/**
	 * The path of the library
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	private $path;

	/**
	 * The url of the library
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	private $url;

	/**
	 * Class constructor.
	 *
	 * This method loads other methods of the class.
	 *
	 * @access public
	 *
	 * @since 1.0.0
	 */
	private function __construct() {

		/**
		 * The library path.
		 */
		$this->path = trailingslashit( dirname( __FILE__ ) );

		/**
		 * Determine the library URL.
		 * Note: This won't work if the library is outside of the wp-content directory
		 * and also contains multiple 'wp-content' words in the path.
		 */
		$content_dir = basename( untrailingslashit( WP_CONTENT_DIR ) );
		$library_uri = substr( strstr( trailingslashit( dirname( $this->path ) ), $content_dir ), strlen( $content_dir ) );
		$this->url   = untrailingslashit( WP_CONTENT_URL ) . $library_uri;

		/**
		 * Load option builder
		 */
		add_action( 'after_setup_theme', array( $this, 'load_option_builder' ), 1 );
	}

	/**
	 * The library instance
	 * @var null
	 */
	private static $instance = null;

	/**
	 * Bootstrap the library
	 * @return static|null
	 */
	public static function run() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new static();
		}

		return self::$instance;
	}

	/**
	 * OptionBuilder loads on the 'after_setup_theme' action.
	 *
	 * @access public
	 * @since 1.0.0
	 */
	public function load_option_builder() {

		// Setup the constants.
		$this->constants();

		// Include the required admin files.
		$this->admin_init();

		// Hook into WordPress.
		$this->hooks();
	}

	/**
	 * Constants.
	 *
	 * Defines the constants for use within OptionBuilder. Constants
	 * are prefixed with 'OPB_' to avoid any naming collisions.
	 *
	 * @access private
	 * @since 1.0.0
	 */
	private function constants() {

		/**
		 * Current Version number.
		 */
		define( 'OPB_VERSION', '1.0.2' );

		/**
		 * Define the library dir path
		 * @since 1.0.0
		 */
		define( 'OPB_DIR', $this->path );

		/**
		 * Define the library dir url
		 * @since 1.0.0
		 */
		define( 'OPB_URL', $this->url );

		/**
		 * For developers: Show OptionBuilder in admin interface.
		 *
		 * Run a filter and set to false if you want to hide the
		 * OptionBuilder in the admin area of WordPress.
		 *
		 * @since 1.0.0
		 */
		define( 'OPB_SHOW_PAGES', apply_filters( 'opb_show_pages', false ) );

		/**
		 * For developers: Show New Layout.
		 *
		 * Run a filter and set to false if you don't want to show the
		 * "New Layout" section at the top of the theme options page.
		 *
		 * @since 1.0.0
		 */
		define( 'OPB_SHOW_NEW_LAYOUT', apply_filters( 'opb_show_new_layout', false ) );

		/**
		 * Google Fonts API Key
		 * @since 1.0.0
		 */
		define( 'OPB_GFONTS_API_KEY', false );
	}

	/**
	 * Include admin files.
	 *
	 * These functions are included on admin pages only.
	 *
	 * @access private
	 * @since 1.0.0
	 */
	private function admin_init() {

		// Exit early if we're not on an admin page.
		if ( ! is_admin() ) {
			return;
		}
		// Registers the Settings page.
		if ( true === OPB_SHOW_PAGES ) {
			add_action( 'init', array( $this, 'register_settings_page' ) );
			// Global CSS.
			add_action( 'admin_head', array( $this, 'global_admin_css' ) );
		}
	}

	/**
	 * Execute the WordPress Hooks.
	 *
	 * @access public
	 * @since 1.0.0
	 */
	private function hooks() {

		// Load the Meta Box assets.
		// Add scripts for metaboxes to post-new.php & post.php.
		add_action( 'admin_print_scripts-post-new.php', array( $this, 'admin_scripts' ), 11 );
		add_action( 'admin_print_scripts-post.php', array( $this, 'admin_scripts' ), 11 );
		// Add styles for metaboxes to post-new.php & post.php.
		add_action( 'admin_print_styles-post-new.php', array( static::class, 'admin_styles' ), 11 );
		add_action( 'admin_print_styles-post.php', array( static::class, 'admin_styles' ), 11 );

		// Adds the Theme Option page to the admin bar.
		add_action( 'admin_bar_menu', array( $this, 'admin_bar_menu' ), 999 );

		// Prepares the after save do_action.
		add_action( 'admin_init', array( $this, 'admin_init_options_save' ), 1 );

		// Export.
		//add_action( 'admin_init', array( $this, 'export_php_settings_array' ), 5 );

		// Save settings.
		add_action( 'admin_init', array( $this, 'save_settings' ), 6 );

		// Save layouts.
		add_action( 'admin_init', array( $this, 'modify_layouts' ), 7 );

		// Create media post.
		add_action( 'admin_init', array( $this, 'create_media_post' ), 8 );

		// Google Fonts front-end CSS.
		add_action( 'wp_enqueue_scripts', array( $this, 'load_google_fonts_css' ), 1 );

		// Dynamic front-end CSS.
		add_action( 'wp_enqueue_scripts', array( $this, 'load_dynamic_css' ), 999 );

		// Insert theme CSS dynamically.
		add_action( 'opb_after_theme_options_save', array( $this, 'save_css' ) );

		// Google fonts
		add_filter( 'opb_recognized_font_families', array( $this, 'google_font_stack' ), 1, 2 );

		// Google fonts on save
		add_action( 'opb_after_theme_options_save', array( $this, 'update_google_fonts_after_save' ), 1 );

		// AJAX call to create a new section.
		add_action( 'wp_ajax_add_section', array( $this, 'add_section' ) );

		// AJAX call to create a new setting.
		add_action( 'wp_ajax_add_setting', array( $this, 'add_setting' ) );

		// AJAX call to create a new contextual help.
		add_action( 'wp_ajax_add_the_contextual_help', array( $this, 'add_the_contextual_help' ) );

		// AJAX call to create a new choice.
		add_action( 'wp_ajax_add_choice', array( $this, 'add_choice' ) );

		// AJAX call to create a new list item setting.
		add_action( 'wp_ajax_add_list_item_setting', array( $this, 'add_list_item_setting' ) );

		// AJAX call to create a new layout.
		add_action( 'wp_ajax_add_layout', array( $this, 'add_layout' ) );

		// AJAX call to create a new list item.
		add_action( 'wp_ajax_add_list_item', array( $this, 'add_list_item' ) );

		// AJAX call to create a new social link.
		add_action( 'wp_ajax_add_social_links', array( $this, 'add_social_links' ) );

		// AJAX call to retrieve Google Font data.
		add_action( 'wp_ajax_opb_google_font', array( $this, 'retrieve_google_font' ) );

		// Adds the temporary hacktastic shortcode.
		add_filter( 'media_view_settings', array( $this, 'shortcode' ), 10, 2 );

		// AJAX update.
		add_action( 'wp_ajax_gallery_update', array( $this, 'ajax_gallery_update' ) );

		// Modify the media uploader button.
		add_filter( 'gettext', array( $this, 'change_image_button' ), 10, 3 );
	}


	/**
	 * Helper function to remove unused options from the Google fonts array.
	 *
	 * @param array $options The array of saved options.
	 *
	 * @access public
	 * @since 1.0.0
	 */
	public function update_google_fonts_after_save( $options = array() ) {

		$opb_set_google_fonts = get_theme_mod( 'opb_set_google_fonts', array() );

		foreach ( $opb_set_google_fonts as $key => $set ) {
			if ( ! isset( $options[ $key ] ) ) {
				unset( $opb_set_google_fonts[ $key ] );
			}
		}
		set_theme_mod( 'opb_set_google_fonts', $opb_set_google_fonts );
	}


	/**
	 * Filters the typography font-family to add Google fonts dynamically.
	 *
	 * @param array $families An array of all recognized font families.
	 * @param string $field_id ID of the field being filtered.
	 *
	 * @return array
	 *
	 * @access public
	 * @since 1.0.0
	 */
	public function google_font_stack( $families, $field_id ) {

		if ( ! is_array( $families ) ) {
			return array();
		}

		$opb_google_fonts     = get_theme_mod( 'opb_google_fonts', array() );
		$opb_set_google_fonts = get_theme_mod( 'opb_set_google_fonts', array() );

		if ( ! empty( $opb_set_google_fonts ) ) {
			foreach ( $opb_set_google_fonts as $id => $sets ) {
				foreach ( $sets as $value ) {
					$family = isset( $value['family'] ) ? $value['family'] : '';
					if ( $family && isset( $opb_google_fonts[ $family ] ) ) {
						$spaces              = explode( ' ', $opb_google_fonts[ $family ]['family'] );
						$font_stack          = count( $spaces ) > 1 ? '"' . $opb_google_fonts[ $family ]['family'] . '"' : $opb_google_fonts[ $family ]['family'];
						$families[ $family ] = apply_filters( 'opb_google_font_stack', $font_stack, $family, $field_id );
					}
				}
			}
		}

		return $families;
	}

	/**
	 * Enqueue the dynamic CSS.
	 *
	 * @access public
	 * @since 1.0.0
	 */
	public function load_dynamic_css() {

		// Don't load in the admin.
		if ( is_admin() ) {
			return;
		}

		/**
		 * Filter whether or not to enqueue a `dynamic.css` file at the theme level.
		 *
		 * By filtering this to `false` OptionBuilder will not attempt to enqueue any CSS files.
		 *
		 * Example: add_filter( 'opb_load_dynamic_css', '__return_false' );
		 *
		 * @param bool $load_dynamic_css Default is `true`.
		 *
		 * @return bool
		 * @since 1.0.0
		 *
		 */
		if ( false === (bool) apply_filters( 'opb_load_dynamic_css', true ) ) {
			return;
		}

		// Grab a copy of the paths.
		$opb_css_file_paths = get_option( 'opb_css_file_paths', array() );
		if ( is_multisite() ) {
			$opb_css_file_paths = get_blog_option( get_current_blog_id(), 'opb_css_file_paths', $opb_css_file_paths );
		}

		if ( ! empty( $opb_css_file_paths ) ) {

			$last_css = '';

			// Loop through paths.
			foreach ( $opb_css_file_paths as $key => $path ) {

				if ( '' !== $path && file_exists( $path ) ) {

					$parts = explode( '/wp-content', $path );

					if ( isset( $parts[1] ) ) {

						$sub_parts = explode( '/', $parts[1] );

						if ( isset( $sub_parts[1] ) && isset( $sub_parts[2] ) ) {
							if ( 'themes' !== $sub_parts[1] && get_stylesheet() !== $sub_parts[2] ) {
								continue;
							}
						}

						$css = set_url_scheme( WP_CONTENT_URL ) . $parts[1];

						if ( $last_css !== $css ) {

							// Enqueue filtered file.
							wp_enqueue_style( 'opb-dynamic-' . $key, $css, false, OPB_VERSION );

							$last_css = $css;
						}
					}
				}
			}
		}

	}

	/**
	 * Helper function to update the CSS option type after save.
	 *
	 * This function is called during the `opb_after_theme_options_save` hook,
	 * which is passed the currently stored options array.
	 *
	 * @param array $options The current stored options array.
	 *
	 * @access public
	 * @since 1.0.0
	 */
	public function save_css( $options ) {

		// Grab a copy of the settings.
		$settings = get_option( Utils::settings_id() );

		// Has settings.
		if ( isset( $settings['settings'] ) ) {

			// Loop through sections and insert CSS when needed.
			foreach ( $settings['settings'] as $k => $setting ) {

				// Is the CSS option type.
				if ( isset( $setting['type'] ) && 'css' === $setting['type'] ) {

					// Insert CSS into dynamic.css.
					if ( isset( $options[ $setting['id'] ] ) && '' !== $options[ $setting['id'] ] ) {
						Utils::insert_css_with_markers( $setting['id'], $options[ $setting['id'] ] );

						// Remove old CSS from dynamic.css.
					} else {
						Utils::remove_old_css( $setting['id'] );
					}
				}
			}
		}
	}

	/**
	 * Enqueue the Google Fonts CSS.
	 *
	 * @access public
	 * @since 1.0.0
	 */
	function load_google_fonts_css() {

		/* don't load in the admin */
		if ( is_admin() ) {
			return;
		}

		$opb_google_fonts     = get_theme_mod( 'opb_google_fonts', array() );
		$opb_set_google_fonts = get_theme_mod( 'opb_set_google_fonts', array() );
		$families             = array();
		$subsets              = array();
		$append               = '';

		if ( ! empty( $opb_set_google_fonts ) ) {

			foreach ( $opb_set_google_fonts as $id => $fonts ) {

				foreach ( $fonts as $font ) {

					// Can't find the font, bail!
					if ( ! isset( $opb_google_fonts[ $font['family'] ]['family'] ) ) {
						continue;
					}

					// Set variants & subsets.
					if ( ! empty( $font['variants'] ) && is_array( $font['variants'] ) ) {

						// Variants string.
						$variants = ':' . implode( ',', $font['variants'] );

						// Add subsets to array.
						if ( ! empty( $font['subsets'] ) && is_array( $font['subsets'] ) ) {
							foreach ( $font['subsets'] as $subset ) {
								$subsets[] = $subset;
							}
						}
					}

					// Add family & variants to array.
					if ( isset( $variants ) ) {
						$families[] = str_replace( ' ', '+', $opb_google_fonts[ $font['family'] ]['family'] ) . $variants;
					}
				}
			}
		}

		if ( ! empty( $families ) ) {

			$families = array_unique( $families );

			// Append all subsets to the path, unless the only subset is latin.
			if ( ! empty( $subsets ) ) {
				$subsets = implode( ',', array_unique( $subsets ) );
				if ( 'latin' !== $subsets ) {
					$append = '&subset=' . $subsets;
				}
			}

			wp_enqueue_style( 'opb-google-fonts', esc_url( '//fonts.googleapis.com/css?family=' . implode( '%7C', $families ) ) . $append, false, null ); // phpcs:ignore
		}
	}

	/**
	 * Register custom post type & create the media post used to attach images.
	 *
	 * @access public
	 * @since 1.0.0
	 */
	public function create_media_post() {

		register_post_type(
			'option-builder',
			array(
				'labels'              => array( 'name' => esc_html__( 'Option Tree', 'option-builder' ) ),
				'public'              => false,
				'show_ui'             => false,
				'capability_type'     => 'post',
				'exclude_from_search' => true,
				'hierarchical'        => false,
				'rewrite'             => false,
				'supports'            => array( 'title', 'editor' ),
				'can_export'          => false,
				'show_in_nav_menus'   => false,
			)
		);

		// Look for custom page.
		$post_id = Utils::get_media_post_ID();

		// No post exists.
		if ( 0 === $post_id ) {

			// Insert the post into the database.
			wp_insert_post(
				array(
					'post_title'     => 'Media',
					'post_name'      => 'media',
					'post_status'    => 'private',
					'post_type'      => 'option-builder',
					'comment_status' => 'closed',
					'ping_status'    => 'closed',
				)
			);
		}
	}

	/**
	 * Save layouts array before the screen is displayed.
	 *
	 * @return bool Returns false or redirects.
	 *
	 * @access public
	 * @since 1.0.0
	 */
	public function modify_layouts() {

		// Check and verify modify layouts nonce.
		if ( isset( $_POST['option_builder_modify_layouts_nonce'] ) && wp_verify_nonce( $_POST['option_builder_modify_layouts_nonce'], 'option_builder_modify_layouts_form' ) ) { // phpcs:ignore

			// Previous layouts value.
			$option_builder_layouts = get_option( Utils::layouts_id() );

			// New layouts value.
			$layouts = isset( $_POST[ Utils::layouts_id() ] ) ? $_POST[ Utils::layouts_id() ] : ''; // phpcs:ignore

			// Rebuild layout array.
			$rebuild = array();

			// Validate layouts.
			if ( is_array( $layouts ) && ! empty( $layouts ) ) {

				// Setup active layout.
				if ( isset( $layouts['active_layout'] ) && ! empty( $layouts['active_layout'] ) ) {
					$rebuild['active_layout'] = $layouts['active_layout'];
				}

				// Add new and overwrite active layout.
				if ( isset( $layouts['_add_new_layout_'] ) && ! empty( $layouts['_add_new_layout_'] ) ) {
					$rebuild['active_layout']             = sanitize_title( $layouts['_add_new_layout_'] );
					$rebuild[ $rebuild['active_layout'] ] = Utils::encode( get_option( Utils::options_id(), array() ) );
				}

				$first_layout = '';

				// Loop through layouts.
				foreach ( $layouts as $key => $layout ) {

					// Skip over active layout key.
					if ( 'active_layout' === $key ) {
						continue;
					}

					// Check if the key exists then set value.
					if ( isset( $option_builder_layouts[ $key ] ) && ! empty( $option_builder_layouts[ $key ] ) ) {
						$rebuild[ $key ] = $option_builder_layouts[ $key ];
						if ( '' === $first_layout ) {
							$first_layout = $key;
						}
					}
				}

				if ( isset( $rebuild['active_layout'] ) && ! isset( $rebuild[ $rebuild['active_layout'] ] ) && ! empty( $first_layout ) ) {
					$rebuild['active_layout'] = $first_layout;
				}
			}

			// Default message.
			$message = 'failed';

			// Save & show success message.
			if ( is_array( $rebuild ) && 1 < count( $rebuild ) ) {

				$options = Utils::decode( $rebuild[ $rebuild['active_layout'] ] );

				if ( $options ) {

					$options_safe = array();

					// Get settings array.
					$settings = get_option( Utils::settings_id() );

					// Has options.
					if ( is_array( $options ) ) {

						// Validate options.
						if ( is_array( $settings ) ) {
							foreach ( $settings['settings'] as $setting ) {
								if ( isset( $options[ $setting['id'] ] ) ) {
									$options_safe[ $setting['id'] ] = Utils::validate_setting( wp_unslash( $options[ $setting['id'] ] ), $setting['type'], $setting['id'] );
								}
							}
						}

						// Execute the action hook and pass the theme options to it.
						do_action( 'opb_before_theme_options_save', $options_safe );

						update_option( Utils::options_id(), $options_safe );
					}
				}

				// Rebuild the layouts.
				update_option( Utils::layouts_id(), $rebuild );

				// Change message.
				$message = 'success';
			} elseif ( 1 >= count( $rebuild ) ) {

				// Delete layouts option.
				delete_option( Utils::layouts_id() );

				// Change message.
				$message = 'deleted';
			}

			// Redirect.
			if ( isset( $_REQUEST['page'] ) && apply_filters( 'opb_theme_options_menu_slug', 'opb-theme-options' ) === $_REQUEST['page'] ) {
				$query_args = esc_url_raw(
					add_query_arg(
						array(
							'settings-updated' => 'layout',
						),
						remove_query_arg(
							array(
								'action',
								'message',
							),
							wp_get_referer()
						)
					)
				);
			} else {
				$query_args = esc_url_raw(
					add_query_arg(
						array(
							'action'  => 'save-layouts',
							'message' => $message,
						),
						wp_get_referer()
					)
				);
			}
			wp_safe_redirect( $query_args );
			exit;
		}

		return false;
	}

	/**
	 * Save settings array before the screen is displayed.
	 *
	 * @return bool Redirects on save, false on failure.
	 *
	 * @access public
	 * @since 1.0.0
	 */
	public function save_settings() {

		// Check and verify import settings nonce.
		if ( isset( $_POST['option_builder_settings_nonce'] ) && wp_verify_nonce( $_POST['option_builder_settings_nonce'], 'option_builder_settings_form' ) ) { // phpcs:ignore

			// Settings value.
			$settings = isset( $_POST[ Utils::settings_id() ] ) ? wp_unslash( $_POST[ Utils::settings_id() ] ) : array(); // phpcs:ignore

			$settings_safe = Utils::validate_settings( $settings );

			// Default message.
			$message = 'failed';

			// Save & show success message.
			if ( ! empty( $settings_safe ) ) {
				update_option( Utils::settings_id(), $settings_safe );
				$message = 'success';
			}

			// Redirect.
			wp_safe_redirect(
				esc_url_raw(
					add_query_arg(
						array(
							'action'  => 'save-settings',
							'message' => $message,
						),
						wp_get_referer()
					)
				)
			);
			exit;
		}

		return false;
	}

	/**
	 * Export the Theme Mode theme-options.php
	 *
	 * @access public
	 * @since 1.0.0
	 */
	public function export_php_settings_array() {

		if ( ! isset( $_POST['export_settings_file_nonce'] ) || ! wp_verify_nonce( $_POST['export_settings_file_nonce'], 'export_settings_file_form' ) ) {
			die;
		}

		$content                 = '';
		$build_settings          = '';
		$contextual_help         = '';
		$sections                = '';
		$settings                = '';
		$option_builder_settings = get_option( Utils::settings_id(), array() );

		/**
		 * Domain string helper.
		 *
		 * @param string $string A string.
		 *
		 * @return string
		 */
		function opb_i18n_string( $string ) {
			if ( ! empty( $string ) && isset( $_POST['domain'] ) && ! empty( $_POST['domain'] ) ) { // phpcs:ignore
				$domain = str_replace( ' ', '-', trim( sanitize_text_field( wp_unslash( $_POST['domain'] ) ) ) ); // phpcs:ignore

				return "esc_html__( '$string', '$domain' )";
			}

			return "'$string'";
		}

		header( 'Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0' );
		header( 'Pragma: no-cache ' );
		header( 'Content-Description: File Transfer' );
		header( 'Content-Disposition: attachment; filename="theme-options.php"' );
		header( 'Content-Type: application/octet-stream' );
		header( 'Content-Transfer-Encoding: binary' );

		// Build contextual help content.
		if ( isset( $option_builder_settings['contextual_help']['content'] ) ) {
			$help = '';
			foreach ( $option_builder_settings['contextual_help']['content'] as $value ) {
				$_id      = isset( $value['id'] ) ? $value['id'] : '';
				$_title   = opb_i18n_string( isset( $value['title'] ) ? str_replace( "'", "\'", $value['title'] ) : '' );
				$_content = opb_i18n_string( isset( $value['content'] ) ? html_entity_decode( str_replace( "'", "\'", $value['content'] ) ) : '' );
				$help     .= "
				array(
					'id'      => '$_id',
					'title'   => $_title,
					'content' => $_content,
				),";
			}
			$contextual_help = "
			'content' => array($help
			),";
		}

		// Build contextual help sidebar.
		if ( isset( $option_builder_settings['contextual_help']['sidebar'] ) ) {
			$_sidebar        = opb_i18n_string( html_entity_decode( str_replace( "'", "\'", $option_builder_settings['contextual_help']['sidebar'] ) ) );
			$contextual_help .= "
			'sidebar' => $_sidebar,";
		}

		// Check that $contexual_help has a value and add to $build_settings.
		if ( '' !== $contextual_help ) {
			$build_settings .= "
		'contextual_help' => array($contextual_help
		),";
		}

		// Build sections.
		if ( isset( $option_builder_settings['sections'] ) ) {
			foreach ( $option_builder_settings['sections'] as $value ) {
				$_id      = isset( $value['id'] ) ? $value['id'] : '';
				$_title   = opb_i18n_string( isset( $value['title'] ) ? str_replace( "'", "\'", $value['title'] ) : '' );
				$sections .= "
			array(
				'id'    => '$_id',
				'title' => $_title,
			),";
			}
		}

		// Check that $sections has a value and add to $build_settings.
		if ( '' !== $sections ) {
			$build_settings .= "
		'sections'        => array($sections
		)";
		}

		/* build settings */
		if ( isset( $option_builder_settings['settings'] ) ) {
			foreach ( $option_builder_settings['settings'] as $value ) {
				$_id           = isset( $value['id'] ) ? $value['id'] : '';
				$_label        = opb_i18n_string( isset( $value['label'] ) ? str_replace( "'", "\'", $value['label'] ) : '' );
				$_desc         = opb_i18n_string( isset( $value['desc'] ) ? str_replace( "'", "\'", $value['desc'] ) : '' );
				$_std          = isset( $value['std'] ) ? str_replace( "'", "\'", $value['std'] ) : '';
				$_type         = isset( $value['type'] ) ? $value['type'] : '';
				$_section      = isset( $value['section'] ) ? $value['section'] : '';
				$_rows         = isset( $value['rows'] ) ? $value['rows'] : '';
				$_post_type    = isset( $value['post_type'] ) ? $value['post_type'] : '';
				$_taxonomy     = isset( $value['taxonomy'] ) ? $value['taxonomy'] : '';
				$_min_max_step = isset( $value['min_max_step'] ) ? $value['min_max_step'] : '';
				$_class        = isset( $value['class'] ) ? $value['class'] : '';
				$_condition    = isset( $value['condition'] ) ? $value['condition'] : '';
				$_operator     = isset( $value['operator'] ) ? $value['operator'] : '';

				$choices = '';
				if ( isset( $value['choices'] ) && ! empty( $value['choices'] ) ) {
					foreach ( $value['choices'] as $choice ) {
						$_choice_value = isset( $choice['value'] ) ? str_replace( "'", "\'", $choice['value'] ) : '';
						$_choice_label = opb_i18n_string( isset( $choice['label'] ) ? str_replace( "'", "\'", $choice['label'] ) : '' );
						$_choice_src   = isset( $choice['src'] ) ? str_replace( "'", "\'", $choice['src'] ) : '';
						$choices       .= "
					array(
						'value' => '$_choice_value',
						'label' => $_choice_label,
						'src'   => '$_choice_src',
					),";
					}
					$choices = "
				'choices'      => array($choices
				),";
				}

				$std = "'$_std'";
				if ( is_array( $_std ) ) {
					$std_array = array();
					foreach ( $_std as $_sk => $_sv ) {
						$std_array[] = "'$_sk' => '$_sv',";
					}
					$std = 'array(
' . implode( ",\n", $std_array ) . '
					)';
				}

				$setting_settings = '';
				if ( isset( $value['settings'] ) && ! empty( $value['settings'] ) ) {
					foreach ( $value['settings'] as $setting ) {
						$_setting_id           = isset( $setting['id'] ) ? $setting['id'] : '';
						$_setting_label        = opb_i18n_string( isset( $setting['label'] ) ? str_replace( "'", "\'", $setting['label'] ) : '' );
						$_setting_desc         = opb_i18n_string( isset( $setting['desc'] ) ? str_replace( "'", "\'", $setting['desc'] ) : '' );
						$_setting_std          = isset( $setting['std'] ) ? $setting['std'] : '';
						$_setting_type         = isset( $setting['type'] ) ? $setting['type'] : '';
						$_setting_rows         = isset( $setting['rows'] ) ? $setting['rows'] : '';
						$_setting_post_type    = isset( $setting['post_type'] ) ? $setting['post_type'] : '';
						$_setting_taxonomy     = isset( $setting['taxonomy'] ) ? $setting['taxonomy'] : '';
						$_setting_min_max_step = isset( $setting['min_max_step'] ) ? $setting['min_max_step'] : '';
						$_setting_class        = isset( $setting['class'] ) ? $setting['class'] : '';
						$_setting_condition    = isset( $setting['condition'] ) ? $setting['condition'] : '';
						$_setting_operator     = isset( $setting['operator'] ) ? $setting['operator'] : '';

						$setting_choices = '';
						if ( isset( $setting['choices'] ) && ! empty( $setting['choices'] ) ) {
							foreach ( $setting['choices'] as $setting_choice ) {
								$_setting_choice_value = isset( $setting_choice['value'] ) ? $setting_choice['value'] : '';
								$_setting_choice_label = opb_i18n_string( isset( $setting_choice['label'] ) ? str_replace( "'", "\'", $setting_choice['label'] ) : '' );
								$_setting_choice_src   = isset( $setting_choice['src'] ) ? str_replace( "'", "\'", $setting_choice['src'] ) : '';
								$setting_choices       .= "
							array(
								'value' => '$_setting_choice_value',
								'label' => $_setting_choice_label,
								'src'   => '$_setting_choice_src',
							),";
							}
							$setting_choices = "
						'choices'      => array($setting_choices
						),";
						}

						$setting_std = "'$_setting_std'";
						if ( is_array( $_setting_std ) ) {
							$setting_std_array = array();
							foreach ( $_setting_std as $_ssk => $_ssv ) {
								$setting_std_array[] = "'$_ssk' => '$_ssv'";
							}
							$setting_std = 'array(
' . implode( ",\n", $setting_std_array ) . '
							)';
						}

						$setting_settings .= "
					array(
						'id'           => '$_setting_id',
						'label'        => $_setting_label,
						'desc'         => $_setting_desc,
						'std'          => $setting_std,
						'type'         => '$_setting_type',
						'rows'         => '$_setting_rows',
						'post_type'    => '$_setting_post_type',
						'taxonomy'     => '$_setting_taxonomy',
						'min_max_step' => '$_setting_min_max_step',
						'class'        => '$_setting_class',
						'condition'    => '$_setting_condition',
						'operator'     => '$_setting_operator',$setting_choices
					),";
					}
					$setting_settings = "
				'settings'     => array( $setting_settings
				),";
				}
				$settings .= "
			array(
				'id'           => '$_id',
				'label'        => $_label,
				'desc'         => $_desc,
				'std'          => $std,
				'type'         => '$_type',
				'section'      => '$_section',
				'rows'         => '$_rows',
				'post_type'    => '$_post_type',
				'taxonomy'     => '$_taxonomy',
				'min_max_step' => '$_min_max_step',
				'class'        => '$_class',
				'condition'    => '$_condition',
				'operator'     => '$_operator',$choices$setting_settings
			),";
			}
		}

		// Check that $sections has a value and add to $build_settings.
		if ( '' !== $settings ) {
			$build_settings .= ",
		'settings'        => array($settings
		)";
		}

		$content .= "<?php
/**
 * Initialize the custom theme options.
 */
add_action( 'init', 'custom_theme_options' );

/**
 * Build the custom settings & update OptionBuilder.
 */
function custom_theme_options() {

	// OptionBuilder is not loaded yet, or this is not an admin request.
	if ( ! function_exists( 'opb_settings_id' ) || ! is_admin() ) {
		return false;
	}

	// Get a copy of the saved settings array.
	\$saved_settings = get_option( OPB_Utils::settings_id(), array() );

	// Custom settings array that will eventually be passes to the OptionBuilder Settings API Class.
	\$custom_settings = array($build_settings
	);

	// Allow settings to be filtered before saving.
	\$custom_settings = apply_filters( OPB_Utils::settings_id() . '_args', \$custom_settings );

	// Settings are not the same update the DB.
	if ( \$saved_settings !== \$custom_settings ) {
		update_option( OPB_Utils::settings_id(), \$custom_settings );
	}
}
";

		echo $content; // phpcs:ignore
		die();
	}

	/**
	 * Runs directly after the Theme Options are save.
	 *
	 * @access public
	 * @since 1.0.0
	 */
	public function admin_init_options_save() {

		$page    = isset( $_REQUEST['page'] ) ? esc_attr( wp_unslash( $_REQUEST['page'] ) ) : ''; // phpcs:ignore
		$updated = isset( $_REQUEST['settings-updated'] ) && true === filter_var( wp_unslash( $_REQUEST['settings-updated'] ), FILTER_VALIDATE_BOOLEAN ); // phpcs:ignore

		// Only execute after the theme options are saved.
		if ( apply_filters( 'opb_theme_options_menu_slug', 'opb-theme-options' ) === $page && $updated ) {

			// Grab a copy of the theme options.
			$options = get_option( Utils::options_id() );

			// Execute the action hook and pass the theme options to it.
			do_action( 'opb_after_theme_options_save', $options );
		}
	}

	/**
	 * Registers the Theme Option page link for the admin bar.
	 *
	 * @access public
	 *
	 * @param object $wp_admin_bar The WP_Admin_Bar object.
	 *
	 * @since 1.0.0
	 *
	 */
	public function admin_bar_menu( $wp_admin_bar ) {

		if ( ! current_user_can( apply_filters( 'opb_theme_options_capability', 'edit_theme_options' ) ) || ! is_admin_bar_showing() ) {
			return;
		}

		$wp_admin_bar->add_node(
			array(
				'parent' => 'appearance',
				'id'     => apply_filters( 'opb_theme_options_menu_slug', 'opb-theme-options' ),
				'title'  => apply_filters( 'opb_theme_options_page_title', __( 'Theme Options', 'option-builder' ) ),
				'href'   => admin_url( apply_filters( 'opb_theme_options_parent_slug', 'themes.php' ) . '?page=' . apply_filters( 'opb_theme_options_menu_slug', 'opb-theme-options' ) ),
			)
		);
	}

	/**
	 * Setup the default admin styles
	 *
	 * @access public
	 * @since 1.0.0
	 */
	public static function admin_styles() {
		global $wp_styles, $post;

		// Execute styles before actions.
		do_action( 'opb_admin_styles_before' );

		// Load WP colorpicker.
		wp_enqueue_style( 'wp-color-picker' );

		// Load admin styles.
		wp_enqueue_style( 'opb-admin-css', OPB_URL . 'dist/css/opb-admin.css', false, OPB_VERSION );

		// Load the RTL stylesheet.
		$wp_styles->add_data( 'opb-admin-css', 'rtl', true );

		// Remove styles added by the Easy Digital Downloads plugin.
		if ( isset( $post->post_type ) && 'post' === $post->post_type ) {
			wp_dequeue_style( 'jquery-ui-css' );
		}

		/**
		 * Filter the screen IDs used to dequeue `jquery-ui-css`.
		 *
		 * @param array $screen_ids An array of screen IDs.
		 *
		 * @since 1.0.0
		 *
		 */
		$screen_ids = apply_filters(
			'opb_dequeue_jquery_ui_css_screen_ids',
			array(
				'toplevel_page_opb-settings',
				'optionbuilder_page_opb-documentation',
				'appearance_page_opb-theme-options',
			)
		);

		// Remove styles added by the WP Review plugin and any custom pages added through filtering.
		if ( in_array( get_current_screen()->id, $screen_ids, true ) ) {
			wp_dequeue_style( 'plugin_name-admin-ui-css' );
			wp_dequeue_style( 'jquery-ui-css' );
		}

		// Execute styles after actions.
		do_action( 'opb_admin_styles_after' );
	}


	/**
	 * Setup the default admin scripts.
	 *
	 * @uses add_thickbox() Include Thickbox for file uploads.
	 * @uses wp_enqueue_script() Add OptionBuilder scripts.
	 * @uses wp_localize_script() Used to include arbitrary Javascript data.
	 *
	 * @access public
	 * @since 1.0.0
	 */
	public static function admin_scripts() {

		// Execute scripts before actions.
		do_action( 'opb_admin_scripts_before' );

		if ( function_exists( 'wp_enqueue_media' ) ) {
			// WP 3.5 Media Uploader.
			wp_enqueue_media();
		} else {
			// Legacy Thickbox.
			add_thickbox();
		}

		// Load jQuery-ui slider.
		wp_enqueue_script( 'jquery-ui-slider' );

		// Load jQuery-ui datepicker.
		wp_enqueue_script( 'jquery-ui-datepicker' );

		// Load WP colorpicker.
		wp_enqueue_script( 'wp-color-picker' );

		// Load Ace Editor for CSS Editing.
		wp_enqueue_script( 'ace-editor', 'https://cdnjs.cloudflare.com/ajax/libs/ace/1.1.3/ace.js', null, '1.1.3', false );

		// Load jQuery UI timepicker addon.
		wp_enqueue_script( 'jquery-ui-timepicker', OPB_URL . 'dist/js/vendor/jquery/jquery-ui-timepicker.js', array(
			'jquery',
			'jquery-ui-slider',
			'jquery-ui-datepicker'
		), '1.4.3-patched', false );

		// Load all the required scripts.
		wp_enqueue_script( 'opb-admin-js', OPB_URL . 'dist/js/opb-admin.js', array(
			'jquery',
			'jquery-ui-tabs',
			'jquery-ui-sortable',
			'jquery-ui-slider',
			'wp-color-picker',
			'ace-editor',
			'jquery-ui-datepicker',
			'jquery-ui-timepicker'
		), OPB_VERSION, false );

		// Create localized JS array.
		$localized_array = array(
			'ajax'                  => admin_url( 'admin-ajax.php' ),
			'nonce'                 => wp_create_nonce( 'option_builder' ),
			'upload_text'           => apply_filters( 'opb_upload_text', __( 'Send to OptionBuilder', 'option-builder' ) ),
			'remove_media_text'     => esc_html__( 'Remove Media', 'option-builder' ),
			'reset_agree'           => esc_html__( 'Are you sure you want to reset back to the defaults?', 'option-builder' ),
			'remove_no'             => esc_html__( 'You can\'t remove this! But you can edit the values.', 'option-builder' ),
			'remove_agree'          => esc_html__( 'Are you sure you want to remove this?', 'option-builder' ),
			'activate_layout_agree' => esc_html__( 'Are you sure you want to activate this layout?', 'option-builder' ),
			'setting_limit'         => esc_html__( 'Sorry, you can\'t have settings three levels deep.', 'option-builder' ),
			'delete'                => esc_html__( 'Delete Gallery', 'option-builder' ),
			'edit'                  => esc_html__( 'Edit Gallery', 'option-builder' ),
			'create'                => esc_html__( 'Create Gallery', 'option-builder' ),
			'confirm'               => esc_html__( 'Are you sure you want to delete this Gallery?', 'option-builder' ),
			'date_current'          => esc_html__( 'Today', 'option-builder' ),
			'date_time_current'     => esc_html__( 'Now', 'option-builder' ),
			'date_close'            => esc_html__( 'Close', 'option-builder' ),
			'replace'               => esc_html__( 'Featured Image', 'option-builder' ),
			'with'                  => esc_html__( 'Image', 'option-builder' ),
		);

		// Localized script attached to 'option_builder'.
		wp_localize_script( 'opb-admin-js', 'option_builder', $localized_array );

		// Execute scripts after actions.
		do_action( 'opb_admin_scripts_after' );
	}

	/**
	 * Registers the Settings page.
	 *
	 * @access public
	 * @since 1.0.0
	 */
	public function register_settings_page() {

		// Create the filterable pages array.
		$opb_register_pages_array = array(
			array(
				'id'          => 'ot',
				'page_title'  => esc_html__( 'OptionBuilder', 'option-builder' ),
				'menu_title'  => esc_html__( 'OptionBuilder', 'option-builder' ),
				'capability'  => 'edit_theme_options',
				'menu_slug'   => 'opb-settings',
				'icon_url'    => null,
				'position'    => 61,
				'hidden_page' => true,
			),
			array(
				'id'              => 'settings',
				'parent_slug'     => 'opb-settings',
				'page_title'      => esc_html__( 'Settings', 'option-builder' ),
				'menu_title'      => esc_html__( 'Settings', 'option-builder' ),
				'capability'      => 'edit_theme_options',
				'menu_slug'       => 'opb-settings',
				'icon_url'        => null,
				'position'        => null,
				'updated_message' => esc_html__( 'Theme Options updated.', 'option-builder' ),
				'reset_message'   => esc_html__( 'Theme Options reset.', 'option-builder' ),
				'button_text'     => esc_html__( 'Save Settings', 'option-builder' ),
				'show_buttons'    => false,
				'sections'        => array(
					array(
						'id'    => 'create_setting',
						'title' => esc_html__( 'Options UI', 'option-builder' ),
					),
					array(
						'id'    => 'export',
						'title' => esc_html__( 'Export', 'option-builder' ),
					),
					array(
						'id'    => 'layouts',
						'title' => esc_html__( 'Layouts', 'option-builder' ),
					),
				),
				'settings'        => array(
					array(
						'id'      => 'theme_options_ui_text',
						'label'   => esc_html__( 'Theme Options UI Builder', 'option-builder' ),
						'type'    => 'theme_options_ui',
						'section' => 'create_setting',
					),
					array(
						'id'      => 'export_settings_file_text',
						'label'   => esc_html__( 'Settings PHP File', 'option-builder' ),
						'type'    => 'export-settings-file',
						'section' => 'export',
					),
					array(
						'id'      => 'modify_layouts_text',
						'label'   => esc_html__( 'Layout Management', 'option-builder' ),
						'type'    => 'modify-layouts',
						'section' => 'layouts',
					),
				),
			),
		);

		// Loop over the settings and remove as needed.
		foreach ( $opb_register_pages_array as $key => $page ) {

			// Remove various options from the Settings UI.
			if ( 'settings' === $page['id'] ) {

				// Remove the Layouts UI.
				if ( false === OPB_SHOW_NEW_LAYOUT ) {

					foreach ( $page['sections'] as $section_key => $section ) {
						if ( 'layouts' === $section['id'] ) {
							unset( $opb_register_pages_array[ $key ]['sections'][ $section_key ] );
						}
					}

					foreach ( $page['settings'] as $setting_key => $setting ) {
						if ( 'layouts' === $setting['section'] ) {
							unset( $opb_register_pages_array[ $key ]['settings'][ $setting_key ] );
						}
					}
				}
			}
		}

		$opb_register_pages_array = apply_filters( 'opb_register_pages_array', $opb_register_pages_array );

		// Register the pages.
		new Settings( array(
			array(
				'id'    => Utils::settings_id(),
				'pages' => $opb_register_pages_array,
			),
		) );

	}

	/**
	 * Adds CSS for the menu icon.
	 */
	public function global_admin_css() {
		?>
        <style>
            @font-face {
                font-family: "option-builder-font";
                src: url("<?php echo esc_url_raw( OPB_URL ); ?>dist/fonts/option-builder-font.eot");
                src: url("<?php echo esc_url_raw( OPB_URL ); ?>dist/fonts/option-builder-font.eot?#iefix") format("embedded-opentype"),
                url("<?php echo esc_url_raw( OPB_URL ); ?>dist/fonts/option-builder-font.woff") format("woff"),
                url("<?php echo esc_url_raw( OPB_URL ); ?>dist/fonts/option-builder-font.ttf") format("truetype"),
                url("<?php echo esc_url_raw( OPB_URL ); ?>dist/fonts/option-builder-font.svg#option-builder-font") format("svg");
                font-weight: normal;
                font-style: normal;
            }

            #adminmenu #toplevel_page_opb-settings .menu-icon-generic div.wp-menu-image:before {
                font: normal 20px/1 "option-builder-font" !important;
                speak: none;
                padding: 6px 0;
                height: 34px;
                width: 20px;
                display: inline-block;
                -webkit-font-smoothing: antialiased;
                -moz-osx-font-smoothing: grayscale;
                -webkit-transition: all .1s ease-in-out;
                -moz-transition: all .1s ease-in-out;
                transition: all .1s ease-in-out;
            }

            #adminmenu #toplevel_page_opb-settings .menu-icon-generic div.wp-menu-image:before {
                content: "\e785";
            }
        </style>
		<?php
	}

	/**
	 * AJAX utility function for adding a new section.
	 */
	public function add_section() {
		check_ajax_referer( 'option_builder', 'nonce' );

		$count  = isset( $_REQUEST['count'] ) ? absint( $_REQUEST['count'] ) : 0;
		$output = Utils::sections_view( Utils::settings_id() . '[sections]', $count );

		echo $output; // phpcs:ignore
		wp_die();
	}

	/**
	 * AJAX utility function for adding a new setting.
	 */
	public function add_setting() {
		check_ajax_referer( 'option_builder', 'nonce' );

		$name   = isset( $_REQUEST['name'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['name'] ) ) : '';
		$count  = isset( $_REQUEST['count'] ) ? absint( $_REQUEST['count'] ) : 0;
		$output = Utils::settings_view( $name, $count );

		echo $output; // phpcs:ignore
		wp_die();
	}

	/**
	 * AJAX utility function for adding a new list item setting.
	 */
	public function add_list_item_setting() {
		check_ajax_referer( 'option_builder', 'nonce' );

		$name   = isset( $_REQUEST['name'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['name'] ) ) : '';
		$count  = isset( $_REQUEST['count'] ) ? absint( $_REQUEST['count'] ) : 0;
		$output = Utils::settings_view( $name . '[settings]', $count );

		echo $output; // phpcs:ignore
		wp_die();
	}

	/**
	 * AJAX utility function for adding new contextual help content.
	 */
	public function add_the_contextual_help() {
		check_ajax_referer( 'option_builder', 'nonce' );

		$name   = isset( $_REQUEST['name'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['name'] ) ) : '';
		$count  = isset( $_REQUEST['count'] ) ? absint( $_REQUEST['count'] ) : 0;
		$output = Utils::contextual_help_view( $name, $count );

		echo $output; // phpcs:ignore
		wp_die();
	}

	/**
	 * AJAX utility function for adding a new choice.
	 */
	public function add_choice() {
		check_ajax_referer( 'option_builder', 'nonce' );

		$name   = isset( $_REQUEST['name'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['name'] ) ) : '';
		$count  = isset( $_REQUEST['count'] ) ? absint( $_REQUEST['count'] ) : 0;
		$output = Utils::choices_view( $name, $count );

		echo $output; // phpcs:ignore
		wp_die();
	}

	/**
	 * AJAX utility function for adding a new layout.
	 */
	public function add_layout() {
		check_ajax_referer( 'option_builder', 'nonce' );

		$count  = isset( $_REQUEST['count'] ) ? absint( $_REQUEST['count'] ) : 0;
		$output = Utils::layout_view( $count );

		echo $output; // phpcs:ignore
		wp_die();
	}

	/**
	 * AJAX utility function for adding a new list item.
	 */
	public function add_list_item() {
		check_ajax_referer( 'option_builder', 'nonce' );

		$name       = isset( $_REQUEST['name'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['name'] ) ) : '';
		$count      = isset( $_REQUEST['count'] ) ? absint( $_REQUEST['count'] ) : 0;
		$post_id    = isset( $_REQUEST['post_id'] ) ? absint( $_REQUEST['post_id'] ) : 0;
		$get_option = isset( $_REQUEST['get_option'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['get_option'] ) ) : '';
		$type       = isset( $_REQUEST['type'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['type'] ) ) : '';
		$settings   = isset( $_REQUEST['settings'] ) ? Utils::decode( sanitize_text_field( wp_unslash( $_REQUEST['settings'] ) ) ) : array();

		Utils::list_item_view( $name, $count, array(), $post_id, $get_option, $settings, $type );
		wp_die();
	}

	/**
	 * AJAX utility function for adding a new social link.
	 */
	public function add_social_links() {
		check_ajax_referer( 'option_builder', 'nonce' );

		$name       = isset( $_REQUEST['name'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['name'] ) ) : '';
		$count      = isset( $_REQUEST['count'] ) ? absint( $_REQUEST['count'] ) : 0;
		$post_id    = isset( $_REQUEST['post_id'] ) ? absint( $_REQUEST['post_id'] ) : 0;
		$get_option = isset( $_REQUEST['get_option'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['get_option'] ) ) : '';
		$type       = isset( $_REQUEST['type'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['type'] ) ) : '';
		$settings   = isset( $_REQUEST['settings'] ) ? Utils::decode( sanitize_text_field( wp_unslash( $_REQUEST['settings'] ) ) ) : array();

		Utils::social_links_view( $name, $count, array(), $post_id, $get_option, $settings, $type );
		wp_die();
	}

	/**
	 * Fake the gallery shortcode.
	 *
	 * The JS takes over and creates the actual shortcode with
	 * the real attachment IDs on the fly. Here we just need to
	 * pass in the post ID to get the ball rolling.
	 *
	 * @access public
	 *
	 * @param array $settings The current settings.
	 * @param object $post The post object.
	 *
	 * @return array
	 * @since 1.0.0
	 *
	 */
	public function shortcode( $settings, $post ) {
		global $pagenow;

		if ( in_array( $pagenow, array( 'upload.php', 'customize.php' ), true ) ) {
			return $settings;
		}

		// Set the OptionBuilder post ID.
		if ( ! is_object( $post ) ) {
			$post_id = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : ( isset( $_GET['post_ID'] ) ? absint( $_GET['post_ID'] ) : 0 ); // phpcs:ignore
			if ( 0 >= $post_id && class_exists( 'OPB_Utils' ) ) {
				$post_id = Utils::get_media_post_ID();
			}
			$settings['post']['id'] = $post_id;
		}

		// No ID return settings.
		if ( 0 >= $settings['post']['id'] ) {
			return $settings;
		}

		// Set the fake shortcode.
		$settings['opb_gallery'] = array( 'shortcode' => "[gallery id='{$settings['post']['id']}']" );

		// Return settings.
		return $settings;
	}

	/**
	 * AJAX to generate HTML for a list of gallery images.
	 *
	 * @access public
	 * @since 1.0.0
	 */
	public function ajax_gallery_update() {
		check_ajax_referer( 'option_builder', 'nonce' );

		if ( ! empty( $_POST['ids'] ) && is_array( $_POST['ids'] ) ) {

			$html = '';
			$ids  = array_filter( $_POST['ids'], 'absint' ); // phpcs:ignore

			foreach ( $ids as $id ) {

				$thumbnail = wp_get_attachment_image_src( $id, 'thumbnail' );

				$html .= '<li><img  src="' . esc_url_raw( $thumbnail[0] ) . '" width="75" height="75" /></li>';
			}

			echo $html; // phpcs:ignore
		}

		wp_die();
	}

	/**
	 * The JSON encoded Google fonts data, or false if it cannot be encoded.
	 *
	 * @access public
	 * @since 1.0.0
	 */
	public function retrieve_google_font() {
		check_ajax_referer( 'option_builder', 'nonce' );

		if ( isset( $_POST['field_id'], $_POST['family'] ) ) {

			Utils::fetch_google_fonts();

			$field_id = isset( $_POST['field_id'] ) ? sanitize_text_field( wp_unslash( $_POST['field_id'] ) ) : '';
			$family   = isset( $_POST['family'] ) ? sanitize_text_field( wp_unslash( $_POST['family'] ) ) : '';
			$html     = wp_json_encode(
				array(
					'variants' => Utils::recognized_google_font_variants( $field_id, $family ),
					'subsets'  => Utils::recognized_google_font_subsets( $field_id, $family ),
				)
			);

			echo $html; // phpcs:ignore
		}

		wp_die();
	}

	/**
	 * Filters the media uploader button.
	 *
	 * @access public
	 *
	 * @param string $translation Translated text.
	 * @param string $text Text to translate.
	 * @param string $domain Text domain. Unique identifier for retrieving translated strings.
	 *
	 * @return string
	 * @since 1.0.0
	 *
	 */
	public function change_image_button( $translation, $text, $domain ) {
		global $pagenow;

		if ( apply_filters( 'opb_theme_options_parent_slug', 'themes.php' ) === $pagenow && 'default' === $domain && 'Insert into post' === $text ) {

			// Once is enough.
			remove_filter( 'gettext', array( $this, 'opb_change_image_button' ) );

			return apply_filters( 'opb_upload_text', esc_html__( 'Send to OptionBuilder', 'option-builder' ) );

		}

		return $translation;
	}

	/**
	 * Load a file.
	 *
	 * @access private
	 *
	 * @param string $file Path to the file being included.
	 *
	 * @since 1.0.0
	 *
	 */
	private function load_file( $file ) {
		if ( file_exists( $file ) ) {
			include_once $file;
		}
	}
}
