<?php

namespace IgniteKit\WP\OptionBuilder;

/**
 * OptionBuilder Settings class.
 *
 * This class loads all the methods and helpers specific to a Settings page.
 */
class Settings {

	/**
	 * An array of options.
	 *
	 * @var array
	 */
	private $options;

	/**
	 * Page hook for targeting admin page.
	 *
	 * @var string
	 */
	private $page_hook;

	/**
	 * Constructor
	 *
	 * @param array $args An array of options.
	 *
	 * @access public
	 * @since 1.0.0
	 */
	public function __construct( $args ) {

		$this->options = $args;

		// Return early if not viewing an admin page or no options.
		if ( ! is_admin() || ! is_array( $this->options ) ) {
			return;
		}

		// Load everything.
		$this->hooks();
	}

	/**
	 * Execute the WordPress Hooks
	 *
	 * @access public
	 * @since 1.0.0
	 */
	public function hooks() {

		/**
		 * Filter the `admin_menu` action hook priority.
		 *
		 * @param int $priority The priority. Default '10'.
		 *
		 * @since 1.0.0
		 *
		 */
		$priority = apply_filters( 'opb_admin_menu_priority', 10 );

		// Add pages & menu items.
		add_action( 'admin_menu', array( $this, 'add_page' ), $priority );

		// Register sections.
		add_action( 'admin_init', array( $this, 'add_sections' ) );

		// Register settings.
		add_action( 'admin_init', array( $this, 'add_settings' ) );

		// Reset options.
		add_action( 'admin_init', array( $this, 'reset_options' ), 10 );

		// Initialize settings.
		add_action( 'admin_init', array( $this, 'initialize_settings' ), 11 );
	}

	/**
	 * Loads each admin page
	 *
	 * @return bool
	 *
	 * @access public
	 * @since 1.0.0
	 */
	public function add_page() {

		// Loop through options.
		foreach ( (array) $this->options as $option ) {

			// Loop through pages.
			foreach ( (array) $this->get_pages( $option ) as $page ) {

				/**
				 * Theme Check... stop nagging me about this kind of stuff.
				 * The damn admin pages are required for OT to function, duh!
				 */
				$theme_check_bs  = 'add_menu_' . 'page'; // phpcs:ignore
				$theme_check_bs2 = 'add_submenu_' . 'page'; // phpcs:ignore

				// Load page in WP top level menu.
				if ( ! isset( $page['parent_slug'] ) || empty( $page['parent_slug'] ) ) {
					$page_hook = $theme_check_bs(
						$page['page_title'],
						$page['menu_title'],
						$page['capability'],
						$page['menu_slug'],
						array( $this, 'display_page' ),
						$page['icon_url'],
						$page['position']
					);

					// Load page in WP sub menu.
				} else {
					$page_hook = $theme_check_bs2(
						$page['parent_slug'],
						$page['page_title'],
						$page['menu_title'],
						$page['capability'],
						$page['menu_slug'],
						array( $this, 'display_page' )
					);
				}

				// Only load if not a hidden page.
				if ( ! isset( $page['hidden_page'] ) ) {

					// Associate $page_hook with page id.
					$this->page_hook[ $page['id'] ] = $page_hook;

					// Add scripts.
					add_action( 'admin_print_scripts-' . $page_hook, array( $this, 'scripts' ) );

					// Add styles.
					add_action( 'admin_print_styles-' . $page_hook, array( $this, 'styles' ) );

					// Add contextual help.
					add_action( 'load-' . $page_hook, array( $this, 'help' ) );
				}
			}
		}

		return false;
	}

	/**
	 * Loads the scripts
	 *
	 * @access public
	 * @since 1.0.0
	 */
	public function scripts() {
		Bootstrap::admin_scripts();
	}

	/**
	 * Loads the styles
	 *
	 * @access public
	 * @since 1.0.0
	 */
	public function styles() {
		Bootstrap::admin_styles();
	}

	/**
	 * Loads the contextual help for each page
	 *
	 * @return bool
	 *
	 * @access public
	 * @since 1.0.0
	 */
	public function help() {
		$screen = get_current_screen();

		// Loop through options.
		foreach ( (array) $this->options as $option ) {

			// Loop through pages.
			foreach ( (array) $this->get_pages( $option ) as $page ) {

				// Verify page.
				if ( ! isset( $page['hidden_page'] ) && $screen->id === $this->page_hook[ $page['id'] ] ) {

					// Set up the help tabs.
					if ( ! empty( $page['contextual_help']['content'] ) ) {
						foreach ( $page['contextual_help']['content'] as $contextual_help ) {
							$screen->add_help_tab(
								array(
									'id'      => esc_attr( $contextual_help['id'] ),
									'title'   => esc_attr( $contextual_help['title'] ),
									'content' => htmlspecialchars_decode( $contextual_help['content'] ),
								)
							);
						}
					}

					// Set up the help sidebar.
					if ( ! empty( $page['contextual_help']['sidebar'] ) ) {
						$screen->set_help_sidebar( htmlspecialchars_decode( $page['contextual_help']['sidebar'] ) );
					}
				}
			}
		}

		return false;
	}

	/**
	 * Loads the content for each page
	 *
	 * @access public
	 * @since 1.0.0
	 */
	public function display_page() {
		$screen = get_current_screen();

		// Loop through settings.
		foreach ( (array) $this->options as $option ) {

			// Loop through pages.
			foreach ( (array) $this->get_pages( $option ) as $page ) {

				// Verify page.
				if ( ! isset( $page['hidden_page'] ) && $screen->id === $this->page_hook[ $page['id'] ] ) {

					$show_buttons = isset( $page['show_buttons'] ) && false === $page['show_buttons'] ? false : true;

					// Update active layout content.
					if ( isset( $_REQUEST['settings-updated'] ) && true === filter_var( wp_unslash( $_REQUEST['settings-updated'] ), FILTER_VALIDATE_BOOLEAN ) ) { // phpcs:ignore

						$layouts = get_option( Utils::layouts_id() );

						// Has active layout.
						if ( isset( $layouts['active_layout'] ) ) {
							$option_builder                       = get_option( $option['id'], array() );
							$layouts[ $layouts['active_layout'] ] = Utils::encode( $option_builder );
							update_option( Utils::layouts_id(), $layouts );
						}
					}

					echo '<div class="wrap settings-wrap" id="page-' . esc_attr( $page['id'] ) . '">';

					echo '<h2>' . wp_kses_post( $page['page_title'] ) . '</h2>';

					echo Utils::alert_message( $page ); // phpcs:ignore

					settings_errors( 'option-builder' );

					// Header.
					echo '<div id="option-builder-header-wrap">';

					echo '<ul id="option-builder-header">';

					$link = '<a href="https://wordpress.org/plugins/option-builder/" target="_blank">' . esc_html__( 'OptionBuilder', 'option-builder' ) . '</a>';
					echo '<li id="option-builder-logo">' . wp_kses_post( apply_filters( 'opb_header_logo_link', $link, $page['id'] ) ) . '</li>';

					echo '<li id="option-builder-version"><span>' . esc_html( apply_filters( 'opb_header_version_text', 'OptionBuilder ' . OPB_VERSION, $page['id'] ) ) . '</span></li>';

					// Add additional theme specific links here.
					do_action( 'opb_header_list', $page['id'] );

					echo '</ul>';

					// Layouts form.
					if ( 'opb_theme_options' === $page['id'] && true === OPB_SHOW_NEW_LAYOUT ) {
						Utils::theme_options_layouts_form();
					}

					echo '</div>';

					// Remove forms on the custom settings pages.
					if ( $show_buttons ) {

						echo '<form action="options.php" method="post" id="option-builder-settings-api">';

						settings_fields( $option['id'] );
					} else {

						echo '<div id="option-builder-settings-api">';
					}

					// Sub Header.
					echo '<div id="option-builder-sub-header">';

					if ( $show_buttons ) {
						echo '<button class="option-builder-ui-button button button-primary right">' . esc_html( $page['button_text'] ) . '</button>';
					}

					echo '</div>';

					// Navigation.
					echo '<div class="ui-tabs">';

					// Check for sections.
					if ( isset( $page['sections'] ) && 0 < count( $page['sections'] ) ) {

						echo '<ul class="ui-tabs-nav">';

						// Loop through page sections.
						foreach ( (array) $page['sections'] as $section ) {
							echo '<li id="tab_' . esc_attr( $section['id'] ) . '"><a href="#section_' . esc_attr( $section['id'] ) . '">' . wp_kses_post( $section['title'] ) . '</a></li>';
						}

						echo '</ul>';
					}

					// Sections.
					echo '<div id="poststuff" class="metabox-holder">';

					echo '<div id="post-body">';

					echo '<div id="post-body-content">';

					$this->do_settings_sections( isset( $_GET['page'] ) ? $_GET['page'] : '' ); // phpcs:ignore

					echo '</div>';

					echo '</div>';

					echo '</div>';

					echo '<div class="clear"></div>';

					echo '</div>';

					// Buttons.
					if ( $show_buttons ) {

						echo '<div class="option-builder-ui-buttons">';

						echo '<button class="option-builder-ui-button button button-primary right">' . esc_html( $page['button_text'] ) . '</button>';

						echo '</div>';
					}

					echo $show_buttons ? '</form>' : '</div>';

					// Reset button.
					if ( $show_buttons ) {

						echo '<form method="post" action="' . esc_url_raw( str_replace( '&settings-updated=true', '', $_SERVER['REQUEST_URI'] ) ) . '">'; // phpcs:ignore

						// Form nonce.
						wp_nonce_field( 'option_builder_reset_form', 'option_builder_reset_nonce' );

						echo '<input type="hidden" name="action" value="reset" />';

						echo '<button type="submit" class="option-builder-ui-button button button-secondary left reset-settings" title="' . esc_html__( 'Reset Options', 'option-builder' ) . '">' . esc_html__( 'Reset Options', 'option-builder' ) . '</button>';

						echo '</form>';
					}

					echo '</div>';
				}
			}
		}

		return false;
	}

	/**
	 * Adds sections to the page
	 *
	 * @return bool
	 *
	 * @access public
	 * @since 1.0.0
	 */
	public function add_sections() {

		// Loop through options.
		foreach ( (array) $this->options as $option ) {

			// Loop through pages.
			foreach ( (array) $this->get_pages( $option ) as $page ) {

				// Loop through page sections.
				foreach ( (array) $this->get_sections( $page ) as $section ) {

					// Skip if empty
					if ( empty( $section ) ) {
						continue;
					}

					// Add each section.
					add_settings_section(
						$section['id'],
						$section['title'],
						array( $this, 'display_section' ),
						$page['menu_slug']
					);

				}
			}
		}

		return false;
	}

	/**
	 * Callback for add_settings_section()
	 *
	 * @access public
	 * @since 1.0.0
	 */
	public function display_section() {
		/* currently pointless */
	}

	/**
	 * Add settings the the page
	 *
	 * @return bool
	 *
	 * @access public
	 * @since 1.0.0
	 */
	public function add_settings() {

		// Loop through options.
		foreach ( (array) $this->options as $option ) {

			register_setting( $option['id'], $option['id'], array( $this, 'sanitize_callback' ) );

			// Loop through pages.
			foreach ( (array) $this->get_pages( $option ) as $page ) {

				// Loop through page settings.
				foreach ( (array) $this->get_the_settings( $page ) as $setting ) {

					// Skip if missing setting ID, label, or section.
					if ( ! isset( $setting['id'] ) || ! isset( $setting['label'] ) || ! isset( $setting['section'] ) ) {
						continue;
					}

					// Add get_option param to the array.
					$setting['get_option'] = $option['id'];

					// Add each setting.
					add_settings_field(
						$setting['id'],
						$setting['label'],
						array( $this, 'display_setting' ),
						$page['menu_slug'],
						$setting['section'],
						$setting
					);
				}
			}
		}

		return false;
	}

	/**
	 * Callback for add_settings_field() to build each setting by type
	 *
	 * @param array $args Setting object array.
	 *
	 * @access public
	 * @since 1.0.0
	 */
	public function display_setting( $args = array() ) {
		extract( $args ); // phpcs:ignore

		// Get current saved data.
		$options = get_option( $get_option, false );

		// Set field value.
		$field_value = isset( $options[ $id ] ) ? $options[ $id ] : '';

		// Set standard value.
		if ( isset( $std ) ) {
			$field_value = Utils::filter_std_value( $field_value, $std );
		}

		// Allow the descriptions to be filtered before being displayed.
		$desc = apply_filters( 'opb_filter_description', ( isset( $desc ) ? $desc : '' ), $id );

		// Build the arguments array.
		$_args = array(
			'type'               => $type,
			'field_id'           => $id,
			'field_name'         => $get_option . '[' . $id . ']',
			'field_value'        => $field_value,
			'field_desc'         => $desc,
			'field_std'          => isset( $std ) ? $std : '',
			'field_rows'         => isset( $rows ) && ! empty( $rows ) ? $rows : 15,
			'field_post_type'    => isset( $post_type ) && ! empty( $post_type ) ? $post_type : 'post',
			'field_taxonomy'     => isset( $taxonomy ) && ! empty( $taxonomy ) ? $taxonomy : 'category',
			'field_min_max_step' => isset( $min_max_step ) && ! empty( $min_max_step ) ? $min_max_step : '0,100,1',
			'field_condition'    => isset( $condition ) && ! empty( $condition ) ? $condition : '',
			'field_operator'     => isset( $operator ) && ! empty( $operator ) ? $operator : 'and',
			'field_class'        => isset( $class ) ? $class : '',
			'field_choices'      => isset( $choices ) && ! empty( $choices ) ? $choices : array(),
			'field_settings'     => isset( $settings ) && ! empty( $settings ) ? $settings : array(),
			'post_id'            => Utils::get_media_post_ID(),
			'get_option'         => $get_option,
		);

		// Limit DB queries for Google Fonts.
		if ( 'google-fonts' === $type ) {
			Utils::fetch_google_fonts();
			Utils::set_google_fonts( $id, $field_value );
		}

		// Get the option HTML.
		$option_types = new OptionTypes();
		$option_types->display_by_type( $_args ); // phpcs:ignore
	}

	/**
	 * Sets the option standards if nothing yet exists.
	 *
	 * @access public
	 * @since 1.0.0
	 */
	public function initialize_settings() {

		// Loop through options.
		foreach ( (array) $this->options as $option ) {

			// Skip if option is already set.
			if ( isset( $option['id'] ) && get_option( $option['id'], false ) ) {
				return false;
			}

			$defaults = array();

			// Loop through pages.
			foreach ( (array) $this->get_pages( $option ) as $page ) {

				// Loop through page settings.
				foreach ( (array) $this->get_the_settings( $page ) as $setting ) {

					if ( isset( $setting['std'] ) ) {

						$defaults[ $setting['id'] ] = Utils::validate_setting( $setting['std'], $setting['type'], $setting['id'] );
					}
				}
			}

			update_option( $option['id'], $defaults );
		}

		return false;
	}

	/**
	 * Sanitize callback for register_setting()
	 *
	 * @param mixed $input The setting input.
	 *
	 * @return string
	 *
	 * @access public
	 * @since 1.0.0
	 */
	public function sanitize_callback( $input ) {

		// Store the post global for use later.
		$post_global = $_POST; // phpcs:ignore

		// Loop through options.
		foreach ( (array) $this->options as $option ) {

			// Loop through pages.
			foreach ( (array) $this->get_pages( $option ) as $page ) {

				// Loop through page settings.
				foreach ( (array) $this->get_the_settings( $page ) as $setting ) {

					// Verify setting has a type & value.
					if ( isset( $setting['type'] ) && isset( $input[ $setting['id'] ] ) ) {

						// Get the defaults.
						$current_settings = get_option( Utils::settings_id() );
						$current_options  = get_option( $option['id'] );

						// Validate setting.
						if ( is_array( $input[ $setting['id'] ] ) && in_array( $setting['type'], array(
								'list-item',
								'slider'
							), true ) ) {

							// Required title setting.
							$required_setting = array(
								array(
									'id'        => 'title',
									'label'     => __( 'Title', 'option-builder' ),
									'desc'      => '',
									'std'       => '',
									'type'      => 'text',
									'rows'      => '',
									'class'     => 'option-builder-setting-title',
									'post_type' => '',
									'choices'   => array(),
								),
							);

							// Convert the settings to an array.
							$settings = isset( $post_global[ $setting['id'] . '_settings_array' ] ) ? Utils::decode( $post_global[ $setting['id'] . '_settings_array' ] ) : array();

							// Settings are empty for some odd reason get the defaults.
							if ( empty( $settings ) ) {
								$settings = 'slider' === $setting['type'] ? Utils::slider_settings( $setting['id'] ) : Utils::list_item_settings( $setting['id'] );
							}

							// Merge the two settings arrays.
							$settings = array_merge( $required_setting, $settings );

							foreach ( $input[ $setting['id'] ] as $k => $setting_array ) {

								$has_value = false;
								foreach ( $settings as $sub_setting ) {

									/* verify sub setting has a type & value */
									if ( isset( $sub_setting['type'] ) && isset( $input[ $setting['id'] ][ $k ][ $sub_setting['id'] ] ) ) {

										// Validate setting.
										$input[ $setting['id'] ][ $k ][ $sub_setting['id'] ] = Utils::validate_setting( $input[ $setting['id'] ][ $k ][ $sub_setting['id'] ], $sub_setting['type'], $sub_setting['id'] );
										$has_value                                           = true;
									}
								}

								if ( ! $has_value ) {
									unset( $input[ $setting['id'] ][ $k ] );
								}
							}
						} elseif ( is_array( $input[ $setting['id'] ] ) && 'social-links' === $setting['type'] ) {

							// Convert the settings to an array.
							$settings = isset( $post_global[ $setting['id'] . '_settings_array' ] ) ? Utils::decode( $post_global[ $setting['id'] . '_settings_array' ] ) : array();

							// Settings are empty get the defaults.
							if ( empty( $settings ) ) {
								$settings = Utils::social_links_settings( $setting['id'] );
							}

							foreach ( $input[ $setting['id'] ] as $k => $setting_array ) {

								$has_value = false;
								foreach ( $settings as $sub_setting ) {

									// Verify sub setting has a type & value.
									if ( isset( $sub_setting['type'] ) && isset( $input[ $setting['id'] ][ $k ][ $sub_setting['id'] ] ) ) {

										if ( 'href' === $sub_setting['id'] ) {
											$sub_setting['type'] = 'url';
										}

										// Validate setting.
										$input_safe = Utils::validate_setting( $input[ $setting['id'] ][ $k ][ $sub_setting['id'] ], $sub_setting['type'], $sub_setting['id'] );

										if ( ! empty( $input_safe ) ) {
											$input[ $setting['id'] ][ $k ][ $sub_setting['id'] ] = $input_safe;
											$has_value                                           = true;
										}
									}
								}

								if ( ! $has_value ) {
									unset( $input[ $setting['id'] ][ $k ] );
								}
							}
						} else {
							$input[ $setting['id'] ] = Utils::validate_setting( $input[ $setting['id'] ], $setting['type'], $setting['id'] );
						}
					}
				}
			}
		}

		return $input;
	}

	/**
	 * Helper function to get the pages array for an option
	 *
	 * @param array $option Option array.
	 *
	 * @return mixed
	 *
	 * @access public
	 * @since 1.0.0
	 */
	public function get_pages( $option = array() ) {

		if ( empty( $option ) ) {
			return false;
		}

		// Check for pages.
		if ( isset( $option['pages'] ) && ! empty( $option['pages'] ) ) {

			// Return pages array.
			return $option['pages'];

		}

		return false;
	}

	/**
	 * Helper function to get the sections array for a page
	 *
	 * @param array $page Page array.
	 *
	 * @return mixed
	 *
	 * @access public
	 * @since 1.0.0
	 */
	public function get_sections( $page = array() ) {

		if ( empty( $page ) ) {
			return false;
		}

		// Check for sections.
		if ( isset( $page['sections'] ) && ! empty( $page['sections'] ) ) {

			// Return sections array.
			return $page['sections'];

		}

		return false;
	}

	/**
	 * Helper function to get the settings array for a page
	 *
	 * @param array $page Page array.
	 *
	 * @return mixed
	 *
	 * @access public
	 * @since 1.0.0
	 */
	public function get_the_settings( $page = array() ) {

		if ( empty( $page ) ) {
			return false;
		}

		/* check for settings */
		if ( isset( $page['settings'] ) && ! empty( $page['settings'] ) ) {

			/* return settings array */
			return $page['settings'];

		}

		return false;
	}

	/**
	 * Prints out all settings sections added to a particular settings page
	 *
	 * @param string $page The slug name of the page whos settings sections you want to output.
	 *
	 * @return void
	 *
	 * @access public
	 * @global $wp_settings_sections Storage array of all settings sections added to admin pages.
	 * @global $wp_settings_fields   Storage array of settings fields and info about their pages/sections.
	 *
	 * @since 1.0.0
	 */
	public function do_settings_sections( $page ) {
		global $wp_settings_sections, $wp_settings_fields;

		if ( ! isset( $wp_settings_sections ) || ! isset( $wp_settings_sections[ $page ] ) ) {
			return;
		}

		foreach ( (array) $wp_settings_sections[ $page ] as $section ) {

			if ( ! isset( $section['id'] ) ) {
				continue;
			}

			$section_id = $section['id'];

			echo '<div id="section_' . esc_attr( $section_id ) . '" class="postbox ui-tabs-panel">';

			call_user_func( $section['callback'], $section );

			if ( ! isset( $wp_settings_fields ) || ! isset( $wp_settings_fields[ $page ] ) || ! isset( $wp_settings_fields[ $page ][ $section_id ] ) ) {
				continue;
			}

			echo '<div class="inside">';

			/**
			 * Hook to insert arbitrary markup before the `do_settings_fields` method.
			 *
			 * @param string $page The page slug.
			 * @param string $section_id The section ID.
			 *
			 * @since 1.0.0
			 *
			 */
			do_action( 'opb_do_settings_fields_before', $page, $section_id );

			$this->do_settings_fields( $page, $section_id );

			/**
			 * Hook to insert arbitrary markup after the `do_settings_fields` method.
			 *
			 * @param string $page The page slug.
			 * @param string $section_id The section ID.
			 *
			 * @since 1.0.0
			 *
			 */
			do_action( 'opb_do_settings_fields_after', $page, $section_id );

			echo '</div>';

			echo '</div>';
		}

	}

	/**
	 * Print out the settings fields for a particular settings section
	 *
	 * @param string $page Slug title of the admin page who's settings fields you want to show.
	 * @param string $section Slug title of the settings section who's fields you want to show.
	 *
	 * @return void
	 *
	 * @access public
	 * @global $wp_settings_fields Storage array of settings fields and their pages/sections
	 *
	 * @since 1.0.0
	 */
	public function do_settings_fields( $page, $section ) {
		global $wp_settings_fields;

		if ( ! isset( $wp_settings_fields ) || ! isset( $wp_settings_fields[ $page ] ) || ! isset( $wp_settings_fields[ $page ][ $section ] ) ) {
			return;
		}

		foreach ( (array) $wp_settings_fields[ $page ][ $section ] as $field ) {

			$conditions = '';

			if ( isset( $field['args']['condition'] ) && ! empty( $field['args']['condition'] ) ) {

				$conditions = ' data-condition="' . esc_attr( $field['args']['condition'] ) . '"';
				$conditions .= isset( $field['args']['operator'] ) && in_array( $field['args']['operator'], array(
					'and',
					'AND',
					'or',
					'OR'
				), true ) ? ' data-operator="' . esc_attr( $field['args']['operator'] ) . '"' : '';
			}

			// Build the setting CSS class.
			if ( isset( $field['args']['class'] ) && ! empty( $field['args']['class'] ) ) {

				$classes = explode( ' ', $field['args']['class'] );

				foreach ( $classes as $key => $value ) {
					$classes[ $key ] = $value . '-wrap';
				}

				$class = 'format-settings ' . implode( ' ', $classes );
			} else {

				$class = 'format-settings';
			}

			echo '<div id="setting_' . esc_attr( $field['id'] ) . '" class="' . esc_attr( $class ) . '"' . $conditions . '>'; // phpcs:ignore

			echo '<div class="format-setting-wrap">';

			if ( 'textblock' !== $field['args']['type'] && ! empty( $field['title'] ) ) {

				echo '<div class="format-setting-label">';

				echo '<h3 class="label">' . wp_kses_post( $field['title'] ) . '</h3>';

				echo '</div>';
			}

			call_user_func( $field['callback'], $field['args'] );

			echo '</div>';

			echo '</div>';
		}
	}

	/**
	 * Resets page options before the screen is displayed
	 *
	 * @access public
	 * @return bool
	 * @since 1.0.0
	 *
	 */
	public function reset_options() {

		// Check for reset action.
		if ( isset( $_POST['option_builder_reset_nonce'] ) && wp_verify_nonce( $_POST['option_builder_reset_nonce'], 'option_builder_reset_form' ) ) { // phpcs:ignore

			// Loop through options.
			foreach ( (array) $this->options as $option ) {

				// Loop through pages.
				foreach ( (array) $this->get_pages( $option ) as $page ) {

					// Verify page.
					if ( isset( $_GET['page'] ) && $_GET['page'] === $page['menu_slug'] ) {

						// Reset options.
						delete_option( $option['id'] );
					}
				}
			}
		}

		return false;
	}
}
