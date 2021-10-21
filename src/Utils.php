<?php
namespace IgniteKit\WP\OptionBuilder;


class Utils {

	/**
	 * Filter the array values recursively.
	 *
	 * @param array $values The value to sanitize.
	 *
	 * @return array
	 */
	public static function _sanitize_recursive( $values = array() ) {
		$result = array();
		foreach ( $values as $key => $value ) {
			if ( ! is_object( $value ) ) {
				if ( is_scalar( $value ) ) {
					$result[ $key ] = sanitize_textarea_field( $value );
				} else {
					$result[ $key ] = self::_sanitize_recursive( $value );
				}
			}
		}

		return $result;
	}

	/**
	 * Filter the allowed HTML and safe CSS styles.
	 *
	 * @param bool $add Whether to add or remove the filter.
	 *
	 * @since 1.0.0
	 *
	 */
	public static function _filter_wp_kses_post( $add = true ) {
		$css_filter = function ( $attr ) {
			array_push( $attr, 'display', 'visibility' );

			$attr = apply_filters( 'opb_safe_style_css', $attr );

			return $attr;
		};

		$html_filter = function ( $tags, $context ) {
			if ( 'post' === $context ) {
				if ( current_user_can( 'unfiltered_html' ) || apply_filters( 'opb_allow_unfiltered_html', false ) ) {
					$tags['script']   = array_fill_keys( array(
						'async',
						'charset',
						'defer',
						'src',
						'type'
					), true );
					$tags['style']    = array_fill_keys( array( 'media', 'type' ), true );
					$tags['iframe']   = array_fill_keys( array(
						'align',
						'allowfullscreen',
						'class',
						'frameborder',
						'height',
						'id',
						'longdesc',
						'marginheight',
						'marginwidth',
						'name',
						'sandbox',
						'scrolling',
						'src',
						'srcdoc',
						'style',
						'width'
					), true );
					$tags['noscript'] = true;

					$tags = apply_filters( 'opb_allowed_html', $tags );
				}
			}

			return $tags;
		};

		if ( $add ) {
			add_filter( 'safe_style_css', $css_filter );
			add_filter( 'wp_kses_allowed_html', $html_filter, 10, 2 );
		} else {
			remove_filter( 'safe_style_css', $css_filter );
			remove_filter( 'wp_kses_allowed_html', $html_filter );
		}
	}

	/**
	 * Validate the options by type before saving.
	 *
	 * This function will run on only some of the option types
	 * as all of them don't need to be validated, just the
	 * ones users are going to input data into; because they
	 * can't be trusted.
	 *
	 * @param mixed $input Setting value.
	 * @param string $type Setting type.
	 * @param string $field_id Setting field ID.
	 *
	 * @return mixed
	 *
	 * @access public
	 * @since 1.0.0
	 */
	public static function validate_setting( $input, $type, $field_id ) {

		// Exit early if missing data.
		if ( ! $input || ! $type || ! $field_id ) {
			return $input;
		}

		/**
		 * Filter to modify a setting field value before validation.
		 *
		 * This cannot be used to filter the returned value of a custom
		 * setting type. You must use the `opb_validate_setting_input_safe`
		 * filter to ensure custom setting types are saved to the database.
		 *
		 * @param mixed $input The setting field value.
		 * @param string $type The setting field type.
		 * @param string $field_id The setting field ID.
		 */
		$input = apply_filters( 'opb_validate_setting', $input, $type, $field_id );

		/**
		 * Filter to validate a setting field value.
		 *
		 * @param mixed $input_safe This is either null, or the filtered input value.
		 * @param mixed $input The setting field value.
		 * @param string $type The setting field type.
		 * @param string $field_id The setting field ID.
		 */
		$input_safe = apply_filters( 'opb_validate_setting_input_safe', null, $input, $type, $field_id );

		// The value was filtered and is safe to return.
		if ( ! is_null( $input_safe ) ) {
			return $input_safe;
		}

		/* translators: %1$s: the input id, %2$s: the field id */
		$string_nums = esc_html__( 'The %1$s input field for %2$s only allows numeric values.', 'option-builder' );

		if ( 'background' === $type ) {

			$input_safe = array();

			// Loop over array and check for values.
			foreach ( (array) $input as $key => $value ) {
				if ( 'background-color' === $key ) {
					$input_safe[ $key ] = self::validate_setting( $value, 'colorpicker', $field_id );
				} elseif ( 'background-image' === $key ) {
					$input_safe[ $key ] = self::validate_setting( $value, 'upload', $field_id );
				} else {
					$input_safe[ $key ] = sanitize_text_field( $value );
				}
			}
		} elseif ( 'border' === $type ) {

			$input_safe = array();

			// Loop over array and set errors or unset key from array.
			foreach ( $input as $key => $value ) {

				if ( empty( $value ) ) {
					continue;
				}

				// Validate width.
				if ( 'width' === $key ) {
					if ( ! is_numeric( $value ) ) {
						add_settings_error( 'option-builder', 'invalid_border_width', sprintf( $string_nums, '<code>width</code>', '<code>' . $field_id . '</code>' ), 'error' );
					} else {
						$input_safe[ $key ] = absint( $value );
					}
				} elseif ( 'color' === $key ) {
					$input_safe[ $key ] = self::validate_setting( $value, 'colorpicker', $field_id );
				} else {
					$input_safe[ $key ] = sanitize_text_field( $value );
				}
			}
		} elseif ( 'box-shadow' === $type ) {

			$input_safe = array();

			// Loop over array and check for values.
			foreach ( (array) $input as $key => $value ) {
				if ( 'inset' === $key ) {
					$input_safe[ $key ] = 'inset';
				} elseif ( 'color' === $key ) {
					$input_safe[ $key ] = self::validate_setting( $value, 'colorpicker', $field_id );
				} else {
					$input_safe[ $key ] = sanitize_text_field( $value );
				}
			}
		} elseif ( 'checkbox' === $type ) {

			$input_safe = array();

			// Loop over array and check for values.
			foreach ( (array) $input as $key => $value ) {
				if ( ! empty( $value ) ) {
					$input_safe[ $key ] = sanitize_text_field( $value );
				}
			}
		} elseif ( 'colorpicker' === $type ) {

			$input_safe = '';

			// Only strings are allowed.
			if ( is_string( $input ) ) {

				/* translators: %s: the field id */
				$string_color = esc_html__( 'The %s Colorpicker only allows valid hexadecimal or rgba values depending on the setting type.', 'option-builder' );

				if ( 0 === preg_match( '/^#([a-f0-9]{6}|[a-f0-9]{3})$/i', $input ) && 0 === preg_match( '/^rgba\(\s*([0-9]{1,3})\s*,\s*([0-9]{1,3})\s*,\s*([0-9]{1,3})\s*,\s*([0-9\.]{1,4})\s*\)/i', $input ) ) {
					add_settings_error( 'option-builder', 'invalid_hex_or_rgba', sprintf( $string_color, '<code>' . $field_id . '</code>' ), 'error' );
				} else {
					$input_safe = $input;
				}
			}
		} elseif ( 'colorpicker-opacity' === $type ) {
			$input_safe = self::validate_setting( $input, 'colorpicker', $field_id );
		} elseif ( in_array( $type, array(
			'category-checkbox',
			'custom-post-type-checkbox',
			'page-checkbox',
			'post-checkbox',
			'tag-checkbox',
			'taxonomy-checkbox'
		), true ) ) {

			$input_safe = array();

			// Loop over array and check for values.
			foreach ( (array) $input as $key => $value ) {
				if ( filter_var( $value, FILTER_VALIDATE_INT ) && 0 < $value ) {
					$input_safe[ $key ] = absint( $value );
				}
			}
		} elseif ( in_array( $type, array(
			'category-select',
			'custom-post-type-select',
			'page-select',
			'post-select',
			'tag-select',
			'taxonomy-select'
		), true ) ) {

			$input_safe = '';

			if ( filter_var( $input, FILTER_VALIDATE_INT ) && 0 < $input ) {
				$input_safe = absint( $input );
			}
		} elseif ( in_array( $type, array( 'css', 'javascript', 'text', 'textarea', 'textarea-simple' ), true ) ) {
			self::_filter_wp_kses_post( true );
			$input_safe = wp_kses_post( $input );
			self::_filter_wp_kses_post( false );
		} elseif ( 'date-picker' === $type || 'date-time-picker' === $type ) {
			if ( ! empty( $input ) && (bool) strtotime( $input ) ) {
				$input_safe = sanitize_text_field( $input );
			}
		} elseif ( 'dimension' === $type ) {

			$input_safe = array();

			// Loop over array and set errors.
			foreach ( $input as $key => $value ) {
				if ( ! empty( $value ) ) {
					if ( ! is_numeric( $value ) && 'unit' !== $key ) {
						add_settings_error( 'option-builder', 'invalid_dimension_' . $key, sprintf( $string_nums, '<code>' . $key . '</code>', '<code>' . $field_id . '</code>' ), 'error' );
					} else {
						$input_safe[ $key ] = sanitize_text_field( $value );
					}
				}
			}
		} elseif ( 'gallery' === $type ) {

			$input_safe = '';

			if ( '' !== trim( $input ) ) {
				$input_safe = sanitize_text_field( $input );
			}
		} elseif ( 'google-fonts' === $type ) {

			$input_safe = array();

			// Loop over array.
			foreach ( $input as $key => $value ) {
				if ( '%key%' === $key ) {
					continue;
				}

				foreach ( $value as $fk => $fvalue ) {
					if ( is_array( $fvalue ) ) {
						foreach ( $fvalue as $sk => $svalue ) {
							$input_safe[ $key ][ $fk ][ $sk ] = sanitize_text_field( $svalue );
						}
					} else {
						$input_safe[ $key ][ $fk ] = sanitize_text_field( $fvalue );
					}
				}
			}

			//array_values( $input_safe );
		} elseif ( 'link-color' === $type ) {

			$input_safe = array();

			// Loop over array and check for values.
			if ( is_array( $input ) && ! empty( $input ) ) {
				foreach ( $input as $key => $value ) {
					if ( ! empty( $value ) ) {
						$input_safe[ $key ] = self::validate_setting( $input[ $key ], 'colorpicker', $field_id . '-' . $key );
					}
				}
			}

			//array_filter( $input_safe );
		} elseif ( 'measurement' === $type ) {

			$input_safe = array();

			foreach ( $input as $key => $value ) {
				if ( ! empty( $value ) ) {
					$input_safe[ $key ] = sanitize_text_field( $value );
				}
			}
		} elseif ( 'numeric-slider' === $type ) {
			$input_safe = '';

			if ( ! empty( $input ) ) {
				if ( ! is_numeric( $input ) ) {
					add_settings_error( 'option-builder', 'invalid_numeric_slider', sprintf( $string_nums, '<code>' . esc_html__( 'slider', 'option-builder' ) . '</code>', '<code>' . $field_id . '</code>' ), 'error' );
				} else {
					$input_safe = sanitize_text_field( $input );
				}
			}
		} elseif ( 'on-off' === $type ) {
			$input_safe = '';

			if ( ! empty( $input ) ) {
				$input_safe = sanitize_text_field( $input );
			}
		} elseif ( 'radio' === $type || 'radio-image' === $type || 'select' === $type || 'sidebar-select' === $type ) {
			$input_safe = '';

			if ( ! empty( $input ) ) {
				$input_safe = sanitize_text_field( $input );
			}
		} elseif ( 'spacing' === $type ) {

			$input_safe = array();

			// Loop over array and set errors.
			foreach ( $input as $key => $value ) {
				if ( ! empty( $value ) ) {
					if ( ! is_numeric( $value ) && 'unit' !== $key ) {
						add_settings_error( 'option-builder', 'invalid_spacing_' . $key, sprintf( $string_nums, '<code>' . $key . '</code>', '<code>' . $field_id . '</code>' ), 'error' );
					} else {
						$input_safe[ $key ] = sanitize_text_field( $value );
					}
				}
			}
		} elseif ( 'typography' === $type && isset( $input['font-color'] ) ) {

			$input_safe = array();

			// Loop over array and check for values.
			foreach ( $input as $key => $value ) {
				if ( 'font-color' === $key ) {
					$input_safe[ $key ] = self::validate_setting( $value, 'colorpicker', $field_id );
				} else {
					$input_safe[ $key ] = sanitize_text_field( $value );
				}
			}
		} elseif ( 'upload' === $type ) {

			$input_safe = filter_var( $input, FILTER_VALIDATE_INT );

			if ( false === $input_safe && is_string( $input ) ) {
				$input_safe = esc_url_raw( $input );
			}
		} elseif ( 'url' === $type ) {

			$input_safe = '';

			if ( ! empty( $input ) ) {
				$input_safe = esc_url_raw( $input );
			}
		} else {

			/* translators: %1$s: the calling function, %2$s the filter name, %3$s the option type, %4$s the version number */
			$string_error = esc_html__( 'Notice: %1$s was called incorrectly. All stored data must be filtered through %2$s, the %3$s option type is not using this filter. This is required since version %4$s.', 'option-builder' );

			// Log a user notice that things have changed since the last version.
			add_settings_error( 'option-builder', 'opb_validate_setting_error', sprintf( $string_error, '<code>opb_validate_setting</code>', '<code>opb_validate_setting_input_safe</code>', '<code>' . $type . '</code>', '<code>2.7.0</code>' ), 'error' );

			$input_safe = '';

			/*
			 * We don't know what the setting type is, so fallback to `sanitize_textarea_field`
			 * on all values and do a best-effort sanitize of the user data before saving it.
			 */
			if ( ! is_object( $input ) ) {

				// Contains an integer, float, string or boolean.
				if ( is_scalar( $input ) ) {
					$input_safe = sanitize_textarea_field( $input );
				} else {
					$input_safe = self::_sanitize_recursive( $input );
				}
			}
		}

		/**
		 * Filter to modify the validated setting field value.
		 *
		 * It's important to note that the filter does not have access to
		 * the original value and can only modify the validated input value.
		 * This is a breaking change as of version 2.7.0.
		 *
		 * @param mixed $input_safe The setting field value.
		 * @param string $type The setting field type.
		 * @param string $field_id The setting field ID.
		 */
		$input_safe = apply_filters( 'opb_after_validate_setting', $input_safe, $type, $field_id );

		return $input_safe;
	}


	/**
	 * Returns the ID of a custom post type by post_title.
	 *
	 * @return int
	 *
	 * @access  public
	 * @since 1.0.0
	 * @updated 2.7.0
	 */
	public static function get_media_post_ID() { // phpcs:ignore

		// Option ID.
		$option_id = 'opb_media_post_ID';

		// Get the media post ID.
		$post_ID = get_option( $option_id, false );

		// Add $post_ID to the DB.
		if ( false === $post_ID || empty( $post_ID ) || ! is_integer( $post_ID ) ) {
			global $wpdb;

			// Get the media post ID.
			$post_ID = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts p WHERE p.post_title = %s AND p.post_type = %s AND p.post_status = %s", 'Media', 'option-builder', 'private' ) ); // phpcs:ignore

			// Add to the DB.
			if ( null !== $post_ID && 0 < $post_ID ) {
				update_option( $option_id, $post_ID );
			} else {
				$post_ID = 0;
			}
		}

		return $post_ID;
	}

	/**
	 * Helper function to validate all settings.
	 *
	 * This includes the `sections`, `settings`, and `contextual_help` arrays.
	 *
	 * @param array $settings The array of settings.
	 *
	 * @return array
	 *
	 * @access public
	 * @since 1.0.0
	 */
	public static function validate_settings( $settings = array() ) {

		// Store the validated settings.
		$settings_safe = array();

		// Validate sections.
		if ( isset( $settings['sections'] ) ) {

			// Fix numeric keys since drag & drop will change them.
			$settings['sections'] = array_values( $settings['sections'] );

			// Loop through sections.
			foreach ( $settings['sections'] as $k => $section ) {

				// Skip if missing values.
				if ( ( ! isset( $section['title'] ) && ! isset( $section['id'] ) ) || ( '' === $section['title'] && '' === $section['id'] ) ) {
					continue;
				}

				// Validate label.
				if ( '' !== $section['title'] ) {
					$settings_safe['sections'][ $k ]['title'] = wp_kses_post( $section['title'] );
				}

				// Missing title set to unfiltered ID.
				if ( ! isset( $section['title'] ) || '' === $section['title'] ) {

					$settings_safe['sections'][ $k ]['title'] = wp_kses_post( $section['id'] );

					// Missing ID set to title.
				} elseif ( ! isset( $section['id'] ) || '' === $section['id'] ) {

					$settings_safe['id'] = wp_kses_post( $section['title'] );
				}

				// Sanitize ID once everything has been checked first.
				$settings_safe['sections'][ $k ]['id'] = self::sanitize_option_id( wp_kses_post( $section['id'] ) );
			}
		}

		// Validate settings by looping over array as many times as it takes.
		if ( isset( $settings['settings'] ) ) {
			$settings_safe['settings'] = self::validate_settings_array( $settings['settings'] );
		}

		// Validate contextual_help.
		if ( isset( $settings['contextual_help']['content'] ) ) {

			// Fix numeric keys since drag & drop will change them.
			$settings['contextual_help']['content'] = array_values( $settings['contextual_help']['content'] );

			// Loop through content.
			foreach ( $settings['contextual_help']['content'] as $k => $content ) {

				// Skip if missing values.
				if ( ( ! isset( $content['title'] ) && ! isset( $content['id'] ) ) || ( '' === $content['title'] && '' === $content['id'] ) ) {
					continue;
				}

				// Validate label.
				if ( '' !== $content['title'] ) {
					$settings_safe['contextual_help']['content'][ $k ]['title'] = wp_kses_post( $content['title'] );
				}

				// Missing title set to unfiltered ID.
				if ( ! isset( $content['title'] ) || '' === $content['title'] ) {

					$settings_safe['contextual_help']['content'][ $k ]['title'] = wp_kses_post( $content['id'] );

					// Missing ID set to title.
				} elseif ( ! isset( $content['id'] ) || '' === $content['id'] ) {

					$content['id'] = wp_kses_post( $content['title'] );
				}

				// Sanitize ID once everything has been checked first.
				$settings_safe['contextual_help']['content'][ $k ]['id'] = self::sanitize_option_id( wp_kses_post( $content['id'] ) );

				// Validate textarea description.
				if ( isset( $content['content'] ) ) {
					$settings_safe['contextual_help']['content'][ $k ]['content'] = wp_kses_post( $content['content'] );
				}
			}
		}

		// Validate contextual_help sidebar.
		if ( isset( $settings['contextual_help']['sidebar'] ) ) {
			$settings_safe['contextual_help']['sidebar'] = wp_kses_post( $settings['contextual_help']['sidebar'] );
		}

		return $settings_safe;
	}

	/**
	 * Validate a settings array before save.
	 *
	 * This function will loop over a settings array as many
	 * times as it takes to validate every sub setting.
	 *
	 * @param array $settings The array of settings.
	 *
	 * @return array
	 *
	 * @access  public
	 * @since 1.0.0
	 * @updated 2.7.0
	 */
	public static function validate_settings_array( $settings = array() ) {

		// Field types mapped to their sanitize function.
		$field_types = array(
			'label'        => 'wp_kses_post',
			'id'           => 'opb_sanitize_option_id',
			'type'         => 'sanitize_text_field',
			'desc'         => 'wp_kses_post',
			'settings'     => array( 'OPB_Utils', 'validate_settings_array' ),
			'choices'      => array(
				'label' => 'wp_kses_post',
				'value' => 'sanitize_text_field',
				'src'   => 'sanitize_text_field',
			),
			'std'          => 'sanitize_text_field',
			'rows'         => 'absint',
			'post_type'    => 'sanitize_text_field',
			'taxonomy'     => 'sanitize_text_field',
			'min_max_step' => 'sanitize_text_field',
			'class'        => 'sanitize_text_field',
			'condition'    => 'sanitize_text_field',
			'operator'     => 'sanitize_text_field',
			'section'      => 'sanitize_text_field',
		);

		// Store the validated settings.
		$settings_safe = array();

		// Validate settings.
		if ( 0 < count( $settings ) ) {

			// Fix numeric keys since drag & drop will change them.
			$settings = array_values( $settings );

			// Loop through settings.
			foreach ( $settings as $sk => $setting ) {
				foreach ( $setting as $fk => $field ) {
					if ( isset( $field_types[ $fk ] ) ) {
						if ( 'choices' === $fk ) {
							foreach ( $field as $ck => $choice ) {
								foreach ( $choice as $vk => $value ) {
									$settings_safe[ $sk ][ $fk ][ $ck ][ $vk ] = call_user_func( $field_types[ $fk ][ $vk ], $value );
								}
							}
						} elseif ( 'std' === $fk && is_array( $field ) ) {
							$callback  = $field_types[ $fk ];
							$array_map = function ( $item ) use ( $array_map, $callback ) {
								return is_array( $item ) ? array_map( $array_map, $item ) : call_user_func( $callback, $item );
							};

							$settings_safe[ $sk ][ $fk ] = array_map( $array_map, $field );
						} else {
							$sanitized = call_user_func( $field_types[ $fk ], $field );
							if ( 'rows' === $fk && 0 === $sanitized ) {
								$sanitized = '';
							}
							$settings_safe[ $sk ][ $fk ] = $sanitized;
						}
					}
				}
			}
		}

		return $settings_safe;
	}

	/**
	 * Helper function to sanitize the option ID's.
	 *
	 * @param string $input The string to sanitize.
	 *
	 * @return string
	 *
	 * @access public
	 * @since 1.0.0
	 */
	public static function sanitize_option_id( $input ) {
		return preg_replace( '/[^a-z0-9]/', '_', trim( strtolower( $input ) ) );
	}


	/**
	 * Helper function to display alert messages.
	 *
	 * @param array $page Page array.
	 *
	 * @return mixed
	 *
	 * @access public
	 * @since 1.0.0
	 */
	public static function alert_message( $page = array() ) {

		if ( empty( $page ) ) {
			return false;
		}

		$before = apply_filters( 'opb_before_page_messages', '', $page );

		if ( $before ) {
			return $before;
		}

		$action  = isset( $_REQUEST['action'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['action'] ) ) : ''; // phpcs:ignore
		$message = isset( $_REQUEST['message'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['message'] ) ) : ''; // phpcs:ignore
		$updated = isset( $_REQUEST['settings-updated'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['settings-updated'] ) ) : ''; // phpcs:ignore

		if ( 'save-settings' === $action ) {

			if ( 'success' === $message ) {

				return '<div id="message" class="updated fade below-h2"><p>' . esc_html__( 'Settings updated.', 'option-builder' ) . '</p></div>';

			} elseif ( 'failed' === $message ) {

				return '<div id="message" class="error fade below-h2"><p>' . esc_html__( 'Settings could not be saved.', 'option-builder' ) . '</p></div>';

			}
		} elseif ( 'import-xml' === $action || 'import-settings' === $action ) {

			if ( 'success' === $message ) {

				return '<div id="message" class="updated fade below-h2"><p>' . esc_html__( 'Settings Imported.', 'option-builder' ) . '</p></div>';

			} elseif ( 'failed' === $message ) {

				return '<div id="message" class="error fade below-h2"><p>' . esc_html__( 'Settings could not be imported.', 'option-builder' ) . '</p></div>';

			}
		} elseif ( 'import-data' === $action ) {

			if ( 'success' === $message ) {

				return '<div id="message" class="updated fade below-h2"><p>' . esc_html__( 'Data Imported.', 'option-builder' ) . '</p></div>';

			} elseif ( 'failed' === $message ) {

				return '<div id="message" class="error fade below-h2"><p>' . esc_html__( 'Data could not be imported.', 'option-builder' ) . '</p></div>';

			}
		} elseif ( 'import-layouts' === $action ) {

			if ( 'success' === $message ) {

				return '<div id="message" class="updated fade below-h2"><p>' . esc_html__( 'Layouts Imported.', 'option-builder' ) . '</p></div>';

			} elseif ( 'failed' === $message ) {

				return '<div id="message" class="error fade below-h2"><p>' . esc_html__( 'Layouts could not be imported.', 'option-builder' ) . '</p></div>';

			}
		} elseif ( 'save-layouts' === $action ) {

			if ( 'success' === $message ) {

				return '<div id="message" class="updated fade below-h2"><p>' . esc_html__( 'Layouts Updated.', 'option-builder' ) . '</p></div>';

			} elseif ( 'failed' === $message ) {

				return '<div id="message" class="error fade below-h2"><p>' . esc_html__( 'Layouts could not be updated.', 'option-builder' ) . '</p></div>';

			} elseif ( 'deleted' === $message ) {

				return '<div id="message" class="updated fade below-h2"><p>' . esc_html__( 'Layouts have been deleted.', 'option-builder' ) . '</p></div>';

			}
		} elseif ( 'layout' === $updated ) {

			return '<div id="message" class="updated fade below-h2"><p>' . esc_html__( 'Layout activated.', 'option-builder' ) . '</p></div>';

		} elseif ( 'reset' === $action ) {

			return '<div id="message" class="updated fade below-h2"><p>' . $page['reset_message'] . '</p></div>';

		}

		do_action( 'opb_custom_page_messages', $page );

		if ( 'true' === $updated || true === $updated ) {
			return '<div id="message" class="updated fade below-h2"><p>' . $page['updated_message'] . '</p></div>';
		}

		return false;
	}

	/**
	 * Setup the default option types.
	 *
	 * The returned option types are filterable so you can add your own.
	 * This is not a task for a beginner as you'll need to add the function
	 * that displays the option to the user and validate the saved data.
	 *
	 * @return array
	 *
	 * @access public
	 * @since 1.0.0
	 */
	public static function option_types_array() {

		return apply_filters(
			'opb_option_types_array',
			array(
				'background'                => esc_html__( 'Background', 'option-builder' ),
				'border'                    => esc_html__( 'Border', 'option-builder' ),
				'box-shadow'                => esc_html__( 'Box Shadow', 'option-builder' ),
				'category-checkbox'         => esc_html__( 'Category Checkbox', 'option-builder' ),
				'category-select'           => esc_html__( 'Category Select', 'option-builder' ),
				'checkbox'                  => esc_html__( 'Checkbox', 'option-builder' ),
				'colorpicker'               => esc_html__( 'Colorpicker', 'option-builder' ),
				'colorpicker-opacity'       => esc_html__( 'Colorpicker Opacity', 'option-builder' ),
				'css'                       => esc_html__( 'CSS', 'option-builder' ),
				'custom-post-type-checkbox' => esc_html__( 'Custom Post Type Checkbox', 'option-builder' ),
				'custom-post-type-select'   => esc_html__( 'Custom Post Type Select', 'option-builder' ),
				'date-picker'               => esc_html__( 'Date Picker', 'option-builder' ),
				'date-time-picker'          => esc_html__( 'Date Time Picker', 'option-builder' ),
				'dimension'                 => esc_html__( 'Dimension', 'option-builder' ),
				'gallery'                   => esc_html__( 'Gallery', 'option-builder' ),
				'google-fonts'              => esc_html__( 'Google Fonts', 'option-builder' ),
				'javascript'                => esc_html__( 'JavaScript', 'option-builder' ),
				'link-color'                => esc_html__( 'Link Color', 'option-builder' ),
				'list-item'                 => esc_html__( 'List Item', 'option-builder' ),
				'measurement'               => esc_html__( 'Measurement', 'option-builder' ),
				'numeric-slider'            => esc_html__( 'Numeric Slider', 'option-builder' ),
				'on-off'                    => esc_html__( 'On/Off', 'option-builder' ),
				'page-checkbox'             => esc_html__( 'Page Checkbox', 'option-builder' ),
				'page-select'               => esc_html__( 'Page Select', 'option-builder' ),
				'post-checkbox'             => esc_html__( 'Post Checkbox', 'option-builder' ),
				'post-select'               => esc_html__( 'Post Select', 'option-builder' ),
				'radio'                     => esc_html__( 'Radio', 'option-builder' ),
				'radio-image'               => esc_html__( 'Radio Image', 'option-builder' ),
				'select'                    => esc_html__( 'Select', 'option-builder' ),
				'sidebar-select'            => esc_html__( 'Sidebar Select', 'option-builder' ),
				'slider'                    => esc_html__( 'Slider', 'option-builder' ),
				'social-links'              => esc_html__( 'Social Links', 'option-builder' ),
				'spacing'                   => esc_html__( 'Spacing', 'option-builder' ),
				'tab'                       => esc_html__( 'Tab', 'option-builder' ),
				'tag-checkbox'              => esc_html__( 'Tag Checkbox', 'option-builder' ),
				'tag-select'                => esc_html__( 'Tag Select', 'option-builder' ),
				'taxonomy-checkbox'         => esc_html__( 'Taxonomy Checkbox', 'option-builder' ),
				'taxonomy-select'           => esc_html__( 'Taxonomy Select', 'option-builder' ),
				'text'                      => esc_html__( 'Text', 'option-builder' ),
				'textarea'                  => esc_html__( 'Textarea', 'option-builder' ),
				'textarea-simple'           => esc_html__( 'Textarea Simple', 'option-builder' ),
				'textblock'                 => esc_html__( 'Textblock', 'option-builder' ),
				'textblock-titled'          => esc_html__( 'Textblock Titled', 'option-builder' ),
				'typography'                => esc_html__( 'Typography', 'option-builder' ),
				'upload'                    => esc_html__( 'Upload', 'option-builder' ),
			)
		);
	}


	/**
	 * Custom stripslashes from single value or array.
	 *
	 * @param mixed $input The string or array to stripslashes from.
	 *
	 * @return mixed
	 *
	 * @access public
	 * @since 1.0.0
	 */
	public static function stripslashes( $input ) {

		if ( is_array( $input ) ) {

			foreach ( $input as &$val ) {

				if ( is_array( $val ) ) {
					$val = self::stripslashes( $val );
				} else {
					$val = stripslashes( trim( $val ) );
				}
			}
		} else {
			$input = stripslashes( trim( $input ) );
		}

		return $input;
	}

	/**
	 * Returns an array of elements from start to limit, inclusive.
	 *
	 * Occasionally zero will be some impossibly large number to
	 * the "E" power when creating a range from negative to positive.
	 * This function attempts to fix that by setting that number back to "0".
	 *
	 * @param string $start First value of the sequence.
	 * @param string $limit The sequence is ended upon reaching the limit value.
	 * @param int $step If a step value is given, it will be used as the increment
	 *                       between elements in the sequence. step should be given as a
	 *                       positive number. If not specified, step will default to 1.
	 *
	 * @return array
	 *
	 * @access public
	 * @since 1.0.0
	 */
	public static function range( $start, $limit, $step = 1 ) {

		if ( $step < 0 ) {
			$step = 1;
		}

		$range = range( $start, $limit, $step );

		foreach ( $range as $k => $v ) {
			if ( strpos( $v, 'E' ) ) {
				$range[ $k ] = 0;
			}
		}

		return $range;
	}

	/**
	 * Helper function to return encoded strings.
	 *
	 * @param array $value The array to encode.
	 *
	 * @return string|bool
	 *
	 * @access  public
	 * @since 1.0.0
	 * @updated 2.7.0
	 */
	public static function encode( $value ) {
		if ( is_array( $value ) ) {
			return base64_encode( maybe_serialize( $value ) ); // phpcs:ignore
		}

		return false;
	}

	/**
	 * Helper function to return decoded arrays.
	 *
	 * @param string $value Encoded serialized array.
	 *
	 * @return array
	 *
	 * @access public
	 * @since 1.0.0
	 */
	public static function decode( $value ) {

		$fallback = array();
		$decoded  = base64_decode( $value ); // phpcs:ignore

		// Search for an array.
		preg_match( '/a:\d+:{.*?}/', $decoded, $array_matches, PREG_OFFSET_CAPTURE, 0 );

		// Search for an object.
		preg_match( '/O:\d+:"[a-z0-9_]+":\d+:{.*?}/i', $decoded, $obj_matches, PREG_OFFSET_CAPTURE, 0 );

		// Prevent object injection or non arrays.
		if ( $obj_matches || ! $array_matches ) {
			return $fallback;
		}

		// Convert the options to an array.
		$decoded = maybe_unserialize( $decoded );

		if ( is_array( $decoded ) ) {
			return $decoded;
		}

		return $fallback;
	}


	/**
	 * Recognized font families
	 *
	 * Returns an array of all recognized font families.
	 * Keys are intended to be stored in the database
	 * while values are ready for display in html.
	 * Renamed in version 2.0 to avoid name collisions.
	 *
	 * @param string $field_id ID that's passed to the filter.
	 *
	 * @return array
	 *
	 * @access  public
	 * @uses apply_filters()
	 *
	 * @since 1.0.0
	 * @updated 2.0
	 */
	public static function recognized_font_families( $field_id ) {

		$families = array(
			'arial'     => 'Arial',
			'georgia'   => 'Georgia',
			'helvetica' => 'Helvetica',
			'palatino'  => 'Palatino',
			'tahoma'    => 'Tahoma',
			'times'     => '"Times New Roman", sans-serif',
			'trebuchet' => 'Trebuchet',
			'verdana'   => 'Verdana',
		);

		return apply_filters( 'opb_recognized_font_families', $families, $field_id );
	}


	/**
	 * Recognized font sizes
	 *
	 * Returns an array of all recognized font sizes.
	 *
	 * @param string $field_id ID that's passed to the filter.
	 *
	 * @return array
	 *
	 * @access public
	 * @uses apply_filters()
	 *
	 * @since 1.0.0
	 */
	public static function recognized_font_sizes( $field_id ) {

		$range = Utils::range(
			apply_filters( 'opb_font_size_low_range', 0, $field_id ),
			apply_filters( 'opb_font_size_high_range', 150, $field_id ),
			apply_filters( 'opb_font_size_range_interval', 1, $field_id )
		);

		$unit = apply_filters( 'opb_font_size_unit_type', 'px', $field_id );

		foreach ( $range as $k => $v ) {
			$range[ $k ] = $v . $unit;
		}

		return apply_filters( 'opb_recognized_font_sizes', $range, $field_id );
	}


	/**
	 * Recognized font styles
	 *
	 * Returns an array of all recognized font styles.
	 * Renamed in version 2.0 to avoid name collisions.
	 *
	 * @param string $field_id ID that's passed to the filter.
	 *
	 * @return array
	 *
	 * @access  public
	 * @uses apply_filters()
	 *
	 * @since 1.0.0
	 * @updated 2.0
	 */
	public static function recognized_font_styles( $field_id ) {

		return apply_filters(
			'opb_recognized_font_styles',
			array(
				'normal'  => 'Normal',
				'italic'  => 'Italic',
				'oblique' => 'Oblique',
				'inherit' => 'Inherit',
			),
			$field_id
		);
	}


	/**
	 * Recognized font variants
	 *
	 * Returns an array of all recognized font variants.
	 * Renamed in version 2.0 to avoid name collisions.
	 *
	 * @param string $field_id ID that's passed to the filter.
	 *
	 * @return array
	 *
	 * @access  public
	 * @uses apply_filters()
	 *
	 * @since 1.0.0
	 * @updated 2.0
	 */
	public static function recognized_font_variants( $field_id ) {

		return apply_filters(
			'opb_recognized_font_variants',
			array(
				'normal'     => 'Normal',
				'small-caps' => 'Small Caps',
				'inherit'    => 'Inherit',
			),
			$field_id
		);
	}


	/**
	 * Recognized font weights
	 *
	 * Returns an array of all recognized font weights.
	 * Renamed in version 2.0 to avoid name collisions.
	 *
	 * @param string $field_id ID that's passed to the filter.
	 *
	 * @return array
	 *
	 * @access  public
	 * @uses apply_filters()
	 *
	 * @since 1.0.0
	 * @updated 2.0
	 */
	public static function recognized_font_weights( $field_id ) {

		return apply_filters(
			'opb_recognized_font_weights',
			array(
				'normal'  => 'Normal',
				'bold'    => 'Bold',
				'bolder'  => 'Bolder',
				'lighter' => 'Lighter',
				'100'     => '100',
				'200'     => '200',
				'300'     => '300',
				'400'     => '400',
				'500'     => '500',
				'600'     => '600',
				'700'     => '700',
				'800'     => '800',
				'900'     => '900',
				'inherit' => 'Inherit',
			),
			$field_id
		);
	}


	/**
	 * Recognized letter spacing
	 *
	 * Returns an array of all recognized line heights.
	 *
	 * @param string $field_id ID that's passed to the filter.
	 *
	 * @return array
	 *
	 * @access public
	 * @uses apply_filters()
	 *
	 * @since 1.0.0
	 */
	public static function recognized_letter_spacing( $field_id ) {

		$range = Utils::range(
			apply_filters( 'opb_letter_spacing_low_range', - 0.1, $field_id ),
			apply_filters( 'opb_letter_spacing_high_range', 0.1, $field_id ),
			apply_filters( 'opb_letter_spacing_range_interval', 0.01, $field_id )
		);

		$unit = apply_filters( 'opb_letter_spacing_unit_type', 'em', $field_id );

		foreach ( $range as $k => $v ) {
			$range[ $k ] = $v . $unit;
		}

		return apply_filters( 'opb_recognized_letter_spacing', $range, $field_id );
	}


	/**
	 * Recognized line heights
	 *
	 * Returns an array of all recognized line heights.
	 *
	 * @param string $field_id ID that's passed to the filter.
	 *
	 * @return array
	 *
	 * @access public
	 * @uses apply_filters()
	 *
	 * @since 1.0.0
	 */
	public static function recognized_line_heights( $field_id ) {

		$range = Utils::range(
			apply_filters( 'opb_line_height_low_range', 0, $field_id ),
			apply_filters( 'opb_line_height_high_range', 150, $field_id ),
			apply_filters( 'opb_line_height_range_interval', 1, $field_id )
		);

		$unit = apply_filters( 'opb_line_height_unit_type', 'px', $field_id );

		foreach ( $range as $k => $v ) {
			$range[ $k ] = $v . $unit;
		}

		return apply_filters( 'opb_recognized_line_heights', $range, $field_id );
	}


	/**
	 * Recognized text decorations
	 *
	 * Returns an array of all recognized text decorations.
	 * Keys are intended to be stored in the database
	 * while values are ready for display in html.
	 *
	 * @param string $field_id ID that's passed to the filter.
	 *
	 * @return array
	 *
	 * @access public
	 * @uses apply_filters()
	 *
	 * @since 1.0.0
	 */
	public static function recognized_text_decorations( $field_id ) {

		return apply_filters(
			'opb_recognized_text_decorations',
			array(
				'blink'        => 'Blink',
				'inherit'      => 'Inherit',
				'line-through' => 'Line Through',
				'none'         => 'None',
				'overline'     => 'Overline',
				'underline'    => 'Underline',
			),
			$field_id
		);
	}


	/**
	 * Recognized text transformations
	 *
	 * Returns an array of all recognized text transformations.
	 * Keys are intended to be stored in the database
	 * while values are ready for display in html.
	 *
	 * @param string $field_id ID that's passed to the filter.
	 *
	 * @return array
	 *
	 * @access public
	 * @uses apply_filters()
	 *
	 * @since 1.0.0
	 */
	public static function recognized_text_transformations( $field_id ) {

		return apply_filters(
			'opb_recognized_text_transformations',
			array(
				'capitalize' => 'Capitalize',
				'inherit'    => 'Inherit',
				'lowercase'  => 'Lowercase',
				'none'       => 'None',
				'uppercase'  => 'Uppercase',
			),
			$field_id
		);
	}


	/**
	 * Recognized background repeat
	 *
	 * Returns an array of all recognized background repeat values.
	 * Renamed in version 2.0 to avoid name collisions.
	 *
	 * @param string $field_id ID that's passed to the filter.
	 *
	 * @return array
	 *
	 * @access  public
	 * @uses apply_filters()
	 *
	 * @since 1.0.0
	 * @updated 2.0
	 */
	public static function recognized_background_repeat( $field_id ) {

		return apply_filters(
			'opb_recognized_background_repeat',
			array(
				'no-repeat' => 'No Repeat',
				'repeat'    => 'Repeat All',
				'repeat-x'  => 'Repeat Horizontally',
				'repeat-y'  => 'Repeat Vertically',
				'inherit'   => 'Inherit',
			),
			$field_id
		);
	}


	/**
	 * Recognized background attachment
	 *
	 * Returns an array of all recognized background attachment values.
	 * Renamed in version 2.0 to avoid name collisions.
	 *
	 * @param string $field_id ID that's passed to the filter.
	 *
	 * @return array
	 *
	 * @access  public
	 * @uses apply_filters()
	 *
	 * @since 1.0.0
	 * @updated 2.0
	 */
	public static function recognized_background_attachment( $field_id ) {

		return apply_filters(
			'opb_recognized_background_attachment',
			array(
				'fixed'   => 'Fixed',
				'scroll'  => 'Scroll',
				'inherit' => 'Inherit',
			),
			$field_id
		);
	}


	/**
	 * Recognized background position
	 *
	 * Returns an array of all recognized background position values.
	 * Renamed in version 2.0 to avoid name collisions.
	 *
	 * @param string $field_id ID that's passed to the filter.
	 *
	 * @return array
	 *
	 * @access  public
	 * @uses apply_filters()
	 *
	 * @since 1.0.0
	 * @updated 2.0
	 */
	public static function recognized_background_position( $field_id ) {

		return apply_filters(
			'opb_recognized_background_position',
			array(
				'left top'      => 'Left Top',
				'left center'   => 'Left Center',
				'left bottom'   => 'Left Bottom',
				'center top'    => 'Center Top',
				'center center' => 'Center Center',
				'center bottom' => 'Center Bottom',
				'right top'     => 'Right Top',
				'right center'  => 'Right Center',
				'right bottom'  => 'Right Bottom',
			),
			$field_id
		);

	}


	/**
	 * Returns an array of all available border style types.
	 *
	 * @param string $field_id ID that's passed to the filter.
	 *
	 * @return array
	 *
	 * @access public
	 * @uses apply_filters()
	 *
	 * @since 1.0.0
	 */
	public static function recognized_border_style_types( $field_id ) {

		return apply_filters(
			'opb_recognized_border_style_types',
			array(
				'hidden' => 'Hidden',
				'dashed' => 'Dashed',
				'solid'  => 'Solid',
				'double' => 'Double',
				'groove' => 'Groove',
				'ridge'  => 'Ridge',
				'inset'  => 'Inset',
				'outset' => 'Outset',
			),
			$field_id
		);

	}


	/**
	 * Returns an array of all available border unit types.
	 *
	 * @param string $field_id ID that's passed to the filter.
	 *
	 * @return array
	 *
	 * @access public
	 * @uses apply_filters()
	 *
	 * @since 1.0.0
	 */
	public static function recognized_border_unit_types( $field_id ) {

		return apply_filters(
			'opb_recognized_border_unit_types',
			array(
				'px' => 'px',
				'%'  => '%',
				'em' => 'em',
				'pt' => 'pt',
			),
			$field_id
		);
	}


	/**
	 * Returns an array of all available dimension unit types.
	 *
	 * @param string $field_id ID that's passed to the filter.
	 *
	 * @return array
	 *
	 * @access public
	 * @uses apply_filters()
	 *
	 * @since 1.0.0
	 */
	public static function recognized_dimension_unit_types( $field_id = '' ) {

		return apply_filters(
			'opb_recognized_dimension_unit_types',
			array(
				'px' => 'px',
				'%'  => '%',
				'em' => 'em',
				'pt' => 'pt',
			),
			$field_id
		);
	}


	/**
	 * Returns an array of all available spacing unit types.
	 *
	 * @param string $field_id ID that's passed to the filter.
	 *
	 * @return array
	 *
	 * @access public
	 * @uses apply_filters()
	 *
	 * @since 1.0.0
	 */
	public static function recognized_spacing_unit_types( $field_id ) {

		return apply_filters(
			'opb_recognized_spacing_unit_types',
			array(
				'px' => 'px',
				'%'  => '%',
				'em' => 'em',
				'pt' => 'pt',
			),
			$field_id
		);

	}


	/**
	 * Recognized Google font families
	 *
	 * @param string $field_id ID that's passed to the filter.
	 *
	 * @return array
	 *
	 * @access public
	 * @uses apply_filters()
	 *
	 * @since 1.0.0
	 */
	public static function recognized_google_font_families( $field_id ) {

		$families         = array();
		$opb_google_fonts = get_theme_mod( 'opb_google_fonts', array() );

		// Forces an array rebuild when we switch themes.
		if ( empty( $opb_google_fonts ) ) {
			$opb_google_fonts = Utils::fetch_google_fonts( true, true );
		}

		foreach ( (array) $opb_google_fonts as $key => $item ) {

			if ( isset( $item['family'] ) ) {
				$families[ $key ] = $item['family'];
			}
		}

		return apply_filters( 'opb_recognized_google_font_families', $families, $field_id );
	}


	/**
	 * Recognized Google font variants
	 *
	 * @param string $field_id ID that's passed to the filter.
	 * @param string $family The font family.
	 *
	 * @return array
	 *
	 * @access public
	 * @uses apply_filters()
	 *
	 * @since 1.0.0
	 */
	public static function recognized_google_font_variants( $field_id, $family ) {

		$variants         = array();
		$opb_google_fonts = get_theme_mod( 'opb_google_fonts', array() );

		if ( isset( $opb_google_fonts[ $family ]['variants'] ) ) {
			$variants = $opb_google_fonts[ $family ]['variants'];
		}

		return apply_filters( 'opb_recognized_google_font_variants', $variants, $field_id, $family );
	}


	/**
	 * Recognized Google font subsets
	 *
	 * @param string $field_id ID that's passed to the filter.
	 * @param string $family The font family.
	 *
	 * @return array
	 *
	 * @access public
	 * @uses apply_filters()
	 *
	 * @since 1.0.0
	 */
	public static function recognized_google_font_subsets( $field_id, $family ) {

		$subsets          = array();
		$opb_google_fonts = get_theme_mod( 'opb_google_fonts', array() );

		if ( isset( $opb_google_fonts[ $family ]['subsets'] ) ) {
			$subsets = $opb_google_fonts[ $family ]['subsets'];
		}

		return apply_filters( 'opb_recognized_google_font_subsets', $subsets, $field_id, $family );
	}

	/**
	 * Measurement Units
	 *
	 * Returns an array of all available unit types.
	 * Renamed in version 2.0 to avoid name collisions.
	 *
	 * @param string $field_id ID that's passed to the filter.
	 *
	 * @return array
	 *
	 * @access public
	 * @uses apply_filters()
	 *
	 * @since 1.0.0
	 * @since 1.0.0
	 */
	public static function measurement_unit_types( $field_id = '' ) {

		return apply_filters(
			'opb_measurement_unit_types',
			array(
				'px' => 'px',
				'%'  => '%',
				'em' => 'em',
				'pt' => 'pt',
			),
			$field_id
		);

	}


	/**
	 * Radio Images default array.
	 *
	 * Returns an array of all available radio images.
	 * You can filter this function to change the images
	 * on a per option basis.
	 *
	 * @param string $field_id ID that's passed to the filter.
	 *
	 * @return array
	 *
	 * @access public
	 * @uses apply_filters()
	 *
	 * @since 1.0.0
	 */
	public static function radio_images( $field_id ) {

		return apply_filters(
			'opb_radio_images',
			array(
				array(
					'value' => 'left-sidebar',
					'label' => esc_html__( 'Left Sidebar', 'option-builder' ),
					'src'   => OPB_URL . 'dist/images/layout/left-sidebar.png',
				),
				array(
					'value' => 'right-sidebar',
					'label' => esc_html__( 'Right Sidebar', 'option-builder' ),
					'src'   => OPB_URL . 'dist/images/layout/right-sidebar.png',
				),
				array(
					'value' => 'full-width',
					'label' => esc_html__( 'Full Width (no sidebar)', 'option-builder' ),
					'src'   => OPB_URL . 'dist/images/layout/full-width.png',
				),
				array(
					'value' => 'dual-sidebar',
					'label' => esc_html__( 'Dual Sidebar', 'option-builder' ),
					'src'   => OPB_URL . 'dist/images/layout/dual-sidebar.png',
				),
				array(
					'value' => 'left-dual-sidebar',
					'label' => esc_html__( 'Left Dual Sidebar', 'option-builder' ),
					'src'   => OPB_URL . 'dist/images/layout/left-dual-sidebar.png',
				),
				array(
					'value' => 'right-dual-sidebar',
					'label' => esc_html__( 'Right Dual Sidebar', 'option-builder' ),
					'src'   => OPB_URL . 'dist/images/layout/right-dual-sidebar.png',
				),
			),
			$field_id
		);

	}


	/**
	 * Default List Item Settings array.
	 *
	 * Returns an array of the default list item settings.
	 * You can filter this function to change the settings
	 * on a per option basis.
	 *
	 * @param string $field_id ID that's passed to the filter.
	 *
	 * @return array
	 *
	 * @access public
	 * @uses apply_filters()
	 *
	 * @since 1.0.0
	 */
	public static function list_item_settings( $field_id ) {

		return apply_filters(
			'opb_list_item_settings',
			array(
				array(
					'id'        => 'image',
					'label'     => esc_html__( 'Image', 'option-builder' ),
					'desc'      => '',
					'std'       => '',
					'type'      => 'upload',
					'rows'      => '',
					'class'     => '',
					'post_type' => '',
					'choices'   => array(),
				),
				array(
					'id'        => 'link',
					'label'     => esc_html__( 'Link', 'option-builder' ),
					'desc'      => '',
					'std'       => '',
					'type'      => 'text',
					'rows'      => '',
					'class'     => '',
					'post_type' => '',
					'choices'   => array(),
				),
				array(
					'id'        => 'description',
					'label'     => esc_html__( 'Description', 'option-builder' ),
					'desc'      => '',
					'std'       => '',
					'type'      => 'textarea-simple',
					'rows'      => 10,
					'class'     => '',
					'post_type' => '',
					'choices'   => array(),
				),
			),
			$field_id
		);
	}


	/**
	 * Default Slider Settings array.
	 *
	 * Returns an array of the default slider settings.
	 * You can filter this function to change the settings
	 * on a per option basis.
	 *
	 * @param string $field_id ID that's passed to the filter.
	 *
	 * @return array
	 *
	 * @access public
	 * @uses apply_filters()
	 *
	 * @since 1.0.0
	 */
	public static function slider_settings( $field_id ) {

		$settings = apply_filters(
			'image_slider_fields',
			array(
				array(
					'name'  => 'image',
					'type'  => 'image',
					'label' => esc_html__( 'Image', 'option-builder' ),
					'class' => '',
				),
				array(
					'name'  => 'link',
					'type'  => 'text',
					'label' => esc_html__( 'Link', 'option-builder' ),
					'class' => '',
				),
				array(
					'name'  => 'description',
					'type'  => 'textarea',
					'label' => esc_html__( 'Description', 'option-builder' ),
					'class' => '',
				),
			),
			$field_id
		);

		// Fix the array keys, values, and just get it 2.0 ready.
		foreach ( $settings as $_k => $setting ) {

			foreach ( $setting as $s_key => $s_value ) {

				if ( 'name' === $s_key ) {

					$settings[ $_k ]['id'] = $s_value;
					unset( $settings[ $_k ]['name'] );
				} elseif ( 'type' === $s_key ) {

					if ( 'input' === $s_value ) {

						$settings[ $_k ]['type'] = 'text';
					} elseif ( 'textarea' === $s_value ) {

						$settings[ $_k ]['type'] = 'textarea-simple';
					} elseif ( 'image' === $s_value ) {

						$settings[ $_k ]['type'] = 'upload';
					}
				}
			}
		}

		return $settings;
	}


	/**
	 * Default Social Links Settings array.
	 *
	 * Returns an array of the default social links settings.
	 * You can filter this function to change the settings
	 * on a per option basis.
	 *
	 * @param string $field_id ID that's passed to the filter.
	 *
	 * @return array
	 *
	 * @access public
	 * @uses apply_filters()
	 *
	 * @since 1.0.0
	 */
	public static function social_links_settings( $field_id ) {

		/* translators: %s: the http protocol */
		$string = esc_html__( 'Enter a link to the profile or page on the social website. Remember to add the %s part to the front of the link.', 'option-builder' );

		return apply_filters(
			'opb_social_links_settings',
			array(
				array(
					'id'    => 'name',
					'label' => esc_html__( 'Name', 'option-builder' ),
					'desc'  => esc_html__( 'Enter the name of the social website.', 'option-builder' ),
					'std'   => '',
					'type'  => 'text',
					'class' => 'option-builder-setting-title',
				),
				array(
					'id'    => 'title',
					'label' => 'Title',
					'desc'  => esc_html__( 'Enter the text shown in the title attribute of the link.', 'option-builder' ),
					'type'  => 'text',
				),
				array(
					'id'    => 'href',
					'label' => 'Link',
					'desc'  => sprintf( $string, '<code>http:// or https://</code>' ),
					'type'  => 'text',
				),
			),
			$field_id
		);
	}


	/**
	 * Inserts CSS with field_id markers.
	 *
	 * Inserts CSS into a dynamic.css file, placing it between
	 * BEGIN and END field_id markers. Replaces existing marked info,
	 * but still retains surrounding data.
	 *
	 * @param string $field_id The CSS option field ID.
	 * @param string $insertion The current option_builder array.
	 * @param bool $meta Whether or not the value is stored in meta.
	 *
	 * @return bool   True on write success, false on failure.
	 *
	 * @access  public
	 * @since 1.0.0
	 * @updated 2.5.3
	 */
	public static function insert_css_with_markers( $field_id = '', $insertion = '', $meta = false ) {

		// Missing $field_id or $insertion exit early.
		if ( '' === $field_id || '' === $insertion ) {
			return 0;
		}

		// Path to the dynamic.css file.
		$filepath = get_stylesheet_directory() . '/dynamic.css';
		if ( is_multisite() ) {
			$multisite_filepath = get_stylesheet_directory() . '/dynamic-' . get_current_blog_id() . '.css';
			if ( file_exists( $multisite_filepath ) ) {
				$filepath = $multisite_filepath;
			}
		}

		// Allow filter on path.
		$filepath = apply_filters( 'css_option_file_path', $filepath, $field_id );

		// Grab a copy of the paths array.
		$opb_css_file_paths = get_option( 'opb_css_file_paths', array() );
		if ( is_multisite() ) {
			$opb_css_file_paths = get_blog_option( get_current_blog_id(), 'opb_css_file_paths', $opb_css_file_paths );
		}

		// Set the path for this field.
		$opb_css_file_paths[ $field_id ] = $filepath;

		/* update the paths */
		if ( is_multisite() ) {
			update_blog_option( get_current_blog_id(), 'opb_css_file_paths', $opb_css_file_paths );
		} else {
			update_option( 'opb_css_file_paths', $opb_css_file_paths );
		}

		// Remove CSS from file, but ensure the file is actually CSS first.
		$file_parts = explode( '.', basename( $filepath ) );
		$file_ext   = end( $file_parts );
		if ( is_writeable( $filepath ) && 'css' === $file_ext ) {

			$insertion = Utils::normalize_css( $insertion );
			$regex     = '/{{([a-zA-Z0-9\_\-\#\|\=]+)}}/';
			$marker    = $field_id;

			// Match custom CSS.
			preg_match_all( $regex, $insertion, $matches );

			// Loop through CSS.
			foreach ( $matches[0] as $option ) {

				$value        = '';
				$option_array = explode( '|', str_replace( array( '{{', '}}' ), '', $option ) );
				$option_id    = isset( $option_array[0] ) ? $option_array[0] : '';
				$option_key   = isset( $option_array[1] ) ? $option_array[1] : '';
				$option_type  = Utils::get_option_type_by_id( $option_id );
				$fallback     = '';

				// Get the meta array value.
				if ( $meta ) {
					global $post;

					$value = get_post_meta( $post->ID, $option_id, true );

					// Get the options array value.
				} else {
					$options = get_option( Utils::options_id() );

					if ( isset( $options[ $option_id ] ) ) {
						$value = $options[ $option_id ];
					}
				}

				// This in an array of values.
				if ( is_array( $value ) ) {

					if ( empty( $option_key ) ) {

						// Measurement.
						if ( 'measurement' === $option_type ) {
							$unit = ! empty( $value[1] ) ? $value[1] : 'px';

							// Set $value with measurement properties.
							if ( isset( $value[0] ) && strlen( $value[0] ) > 0 ) {
								$value = $value[0] . $unit;
							}

							// Border.
						} elseif ( 'border' === $option_type ) {
							$border = array();

							$unit = ! empty( $value['unit'] ) ? $value['unit'] : 'px';

							if ( isset( $value['width'] ) && strlen( $value['width'] ) > 0 ) {
								$border[] = $value['width'] . $unit;
							}

							if ( ! empty( $value['style'] ) ) {
								$border[] = $value['style'];
							}

							if ( ! empty( $value['color'] ) ) {
								$border[] = $value['color'];
							}

							// Set $value with border properties or empty string.
							$value = ! empty( $border ) ? implode( ' ', $border ) : '';

							// Box Shadow.
						} elseif ( 'box-shadow' === $option_type ) {

							$value_safe = array();
							foreach ( $value as $val ) {
								if ( ! empty( $val ) ) {
									$value_safe[] = $val;
								}
							}
							// Set $value with box-shadow properties or empty string.
							$value = ! empty( $value_safe ) ? implode( ' ', $value_safe ) : '';

							// Dimension.
						} elseif ( 'dimension' === $option_type ) {
							$dimension = array();

							$unit = ! empty( $value['unit'] ) ? $value['unit'] : 'px';

							if ( isset( $value['width'] ) && strlen( $value['width'] ) > 0 ) {
								$dimension[] = $value['width'] . $unit;
							}

							if ( isset( $value['height'] ) && strlen( $value['height'] ) > 0 ) {
								$dimension[] = $value['height'] . $unit;
							}

							// Set $value with dimension properties or empty string.
							$value = ! empty( $dimension ) ? implode( ' ', $dimension ) : '';

							// Spacing.
						} elseif ( 'spacing' === $option_type ) {
							$spacing = array();

							$unit = ! empty( $value['unit'] ) ? $value['unit'] : 'px';

							if ( isset( $value['top'] ) && strlen( $value['top'] ) > 0 ) {
								$spacing[] = $value['top'] . $unit;
							}

							if ( isset( $value['right'] ) && strlen( $value['right'] ) > 0 ) {
								$spacing[] = $value['right'] . $unit;
							}

							if ( isset( $value['bottom'] ) && strlen( $value['bottom'] ) > 0 ) {
								$spacing[] = $value['bottom'] . $unit;
							}

							if ( isset( $value['left'] ) && strlen( $value['left'] ) > 0 ) {
								$spacing[] = $value['left'] . $unit;
							}

							// Set $value with spacing properties or empty string.
							$value = ! empty( $spacing ) ? implode( ' ', $spacing ) : '';

							// Typography.
						} elseif ( 'typography' === $option_type ) {
							$font = array();

							if ( ! empty( $value['font-color'] ) ) {
								$font[] = 'color: ' . $value['font-color'] . ';';
							}

							if ( ! empty( $value['font-family'] ) ) {
								foreach ( Utils::recognized_font_families( $marker ) as $key => $v ) {
									if ( $key === $value['font-family'] ) {
										$font[] = 'font-family: ' . $v . ';';
									}
								}
							}

							if ( ! empty( $value['font-size'] ) ) {
								$font[] = 'font-size: ' . $value['font-size'] . ';';
							}

							if ( ! empty( $value['font-style'] ) ) {
								$font[] = 'font-style: ' . $value['font-style'] . ';';
							}

							if ( ! empty( $value['font-variant'] ) ) {
								$font[] = 'font-variant: ' . $value['font-variant'] . ';';
							}

							if ( ! empty( $value['font-weight'] ) ) {
								$font[] = 'font-weight: ' . $value['font-weight'] . ';';
							}

							if ( ! empty( $value['letter-spacing'] ) ) {
								$font[] = 'letter-spacing: ' . $value['letter-spacing'] . ';';
							}

							if ( ! empty( $value['line-height'] ) ) {
								$font[] = 'line-height: ' . $value['line-height'] . ';';
							}

							if ( ! empty( $value['text-decoration'] ) ) {
								$font[] = 'text-decoration: ' . $value['text-decoration'] . ';';
							}

							if ( ! empty( $value['text-transform'] ) ) {
								$font[] = 'text-transform: ' . $value['text-transform'] . ';';
							}

							// Set $value with font properties or empty string.
							$value = ! empty( $font ) ? implode( "\n", $font ) : '';

							// Background.
						} elseif ( 'background' === $option_type ) {
							$bg = array();

							if ( ! empty( $value['background-color'] ) ) {
								$bg[] = $value['background-color'];
							}

							if ( ! empty( $value['background-image'] ) ) {

								// If an attachment ID is stored here fetch its URL and replace the value.
								if ( wp_attachment_is_image( $value['background-image'] ) ) {

									$attachment_data = wp_get_attachment_image_src( $value['background-image'], 'original' );

									// Check for attachment data.
									if ( $attachment_data ) {
										$value['background-image'] = $attachment_data[0];
									}
								}

								$bg[] = 'url("' . $value['background-image'] . '")';
							}

							if ( ! empty( $value['background-repeat'] ) ) {
								$bg[] = $value['background-repeat'];
							}

							if ( ! empty( $value['background-attachment'] ) ) {
								$bg[] = $value['background-attachment'];
							}

							if ( ! empty( $value['background-position'] ) ) {
								$bg[] = $value['background-position'];
							}

							if ( ! empty( $value['background-size'] ) ) {
								$size = $value['background-size'];
							}

							// Set $value with background properties or empty string.
							$value = ! empty( $bg ) ? 'background: ' . implode( ' ', $bg ) . ';' : '';

							if ( isset( $size ) ) {
								if ( ! empty( $bg ) ) {
									$value .= apply_filters( 'opb_insert_css_with_markers_bg_size_white_space', "\n\x20\x20", $option_id );
								}
								$value .= "background-size: $size;";
							}
						}
					} elseif ( ! empty( $value[ $option_key ] ) ) {
						$value = $value[ $option_key ];
					}
				}

				// If an attachment ID is stored here fetch its URL and replace the value.
				if ( 'upload' === $option_type && wp_attachment_is_image( $value ) ) {

					$attachment_data = wp_get_attachment_image_src( $value, 'original' );

					// Check for attachment data.
					if ( $attachment_data ) {
						$value = $attachment_data[0];
					}
				}

				// Attempt to fallback when `$value` is empty.
				if ( empty( $value ) ) {

					// We're trying to access a single array key.
					if ( ! empty( $option_key ) ) {

						// Link Color `inherit`.
						if ( 'link-color' === $option_type ) {
							$fallback = 'inherit';
						}
					} else {

						// Border.
						if ( 'border' === $option_type ) {
							$fallback = 'inherit';
						}

						// Box Shadow.
						if ( 'box-shadow' === $option_type ) {
							$fallback = 'none';
						}

						// Colorpicker.
						if ( 'colorpicker' === $option_type ) {
							$fallback = 'inherit';
						}

						// Colorpicker Opacity.
						if ( 'colorpicker-opacity' === $option_type ) {
							$fallback = 'inherit';
						}
					}

					/**
					 * Filter the `dynamic.css` fallback value.
					 *
					 * @param string $fallback The default CSS fallback value.
					 * @param string $option_id The option ID.
					 * @param string $option_type The option type.
					 * @param string $option_key The option array key.
					 *
					 * @since 1.0.0
					 *
					 */
					$fallback = apply_filters( 'opb_insert_css_with_markers_fallback', $fallback, $option_id, $option_type, $option_key );
				}

				// Let's fallback!
				if ( ! empty( $fallback ) ) {
					$value = $fallback;
				}

				// Filter the CSS.
				$value = apply_filters( 'opb_insert_css_with_markers_value', $value, $option_id );

				// Insert CSS, even if the value is empty.
				$insertion = stripslashes( str_replace( $option, $value, $insertion ) );
			}

			// Can't write to the file so we error out.
			if ( ! is_writable( $filepath ) ) {
				/* translators: %s: file path */
				$string = esc_html__( 'Unable to write to file %s.', 'option-builder' );
				add_settings_error( 'option-builder', 'dynamic_css', sprintf( $string, '<code>' . $filepath . '</code>' ), 'error' );

				return false;
			}

			// Open file.
			$f = @fopen( $filepath, 'w' ); // phpcs:ignore

			// Can't write to the file return false.
			if ( ! $f ) {
				/* translators: %s: file path */
				$string = esc_html__( 'Unable to open the %s file in write mode.', 'option-builder' );
				add_settings_error( 'option-builder', 'dynamic_css', sprintf( $string, '<code>' . $filepath . '</code>' ), 'error' );

				return false;
			}

			// Create array from the lines of code.
			$markerdata = explode( "\n", implode( '', file( $filepath ) ) );

			$searching = true;
			$foundit   = false;

			// Has array of lines.
			if ( ! empty( $markerdata ) ) {

				// Foreach line of code.
				foreach ( $markerdata as $n => $markerline ) {

					// Found begining of marker, set $searching to false.
					if ( "/* BEGIN {$marker} */" === $markerline ) {
						$searching = false;
					}

					// Keep searching each line of CSS.
					if ( true === $searching ) {
						if ( $n + 1 < count( $markerdata ) ) {
							fwrite( $f, "{$markerline}\n" ); // phpcs:ignore
						} else {
							fwrite( $f, "{$markerline}" ); // phpcs:ignore
						}
					}

					// Found end marker write code.
					if ( "/* END {$marker} */" === $markerline ) {
						fwrite( $f, "/* BEGIN {$marker} */\n" ); // phpcs:ignore
						fwrite( $f, "{$insertion}\n" ); // phpcs:ignore
						fwrite( $f, "/* END {$marker} */\n" ); // phpcs:ignore
						$searching = true;
						$foundit   = true;
					}
				}
			}

			// Nothing inserted, write code. DO IT, DO IT!
			if ( ! $foundit ) {
				fwrite( $f, "/* BEGIN {$marker} */\n" ); // phpcs:ignore
				fwrite( $f, "{$insertion}\n" ); // phpcs:ignore
				fwrite( $f, "/* END {$marker} */\n" ); // phpcs:ignore
			}

			// Close file.
			fclose( $f ); // phpcs:ignore

			return true;
		}

		return false;
	}


	/**
	 * Remove old CSS.
	 *
	 * Removes CSS when the textarea is empty, but still retains surrounding styles.
	 *
	 * @param string $field_id The CSS option field ID.
	 *
	 * @return bool   True on write success, false on failure.
	 *
	 * @access public
	 * @since 1.0.0
	 */
	public static function remove_old_css( $field_id = '' ) {

		// Missing $field_id string.
		if ( '' === $field_id ) {
			return false;
		}

		// Path to the dynamic.css file.
		$filepath = get_stylesheet_directory() . '/dynamic.css';

		// Allow filter on path.
		$filepath = apply_filters( 'css_option_file_path', $filepath, $field_id );

		// Remove CSS from file, but ensure the file is actually CSS first.
		$basename_parts = explode( '.', basename( $filepath ) );
		if ( is_writeable( $filepath ) && 'css' === end( $basename_parts ) ) {

			// Open the file.
			$f = @fopen( $filepath, 'w' ); // phpcs:ignore

			// Can't write to the file return false.
			if ( ! $f ) {
				/* translators: %s: file path */
				$string = esc_html__( 'Unable to open the %s file in write mode.', 'option-builder' );
				add_settings_error( 'option-builder', 'dynamic_css', sprintf( $string, '<code>' . $filepath . '</code>' ), 'error' );

				return false;
			}

			// Get each line in the file.
			$markerdata = explode( "\n", implode( '', file( $filepath ) ) );

			$searching = true;

			// Has array of lines.
			if ( ! empty( $markerdata ) ) {

				// Foreach line of code.
				foreach ( $markerdata as $n => $markerline ) {

					// Found beginning of marker, set $searching to false.
					if ( "/* BEGIN {$field_id} */" === $markerline ) {
						$searching = false;
					}

					// Searching is true, keep writing each line of CSS.
					if ( true === $searching ) {
						if ( $n + 1 < count( $markerdata ) ) {
							fwrite( $f, "{$markerline}\n" ); // phpcs:ignore
						} else {
							fwrite( $f, "{$markerline}" ); // phpcs:ignore
						}
					}

					// Found end marker delete old CSS.
					if ( "/* END {$field_id} */" === $markerline ) {
						fwrite( $f, '' ); // phpcs:ignore
						$searching = true;
					}
				}
			}

			// Close file.
			fclose( $f ); // phpcs:ignore

			return true;
		}

		return false;
	}


	/**
	 * Normalize CSS
	 *
	 * Normalize & Convert all line-endings to UNIX format.
	 *
	 * @param string $css The CSS styles.
	 *
	 * @return string
	 *
	 * @access  public
	 * @since 1.0.0
	 * @updated 2.0
	 */
	public static function normalize_css( $css ) {

		// Normalize & Convert.
		$css = str_replace( "\r\n", "\n", $css );
		$css = str_replace( "\r", "\n", $css );

		// Don't allow out-of-control blank lines .
		$css = preg_replace( "/\n{2,}/", "\n\n", $css );

		return $css;
	}


	/**
	 * Helper function to loop over the option types.
	 *
	 * @param string $type The current option type.
	 * @param bool $child Whether of not there are children elements.
	 *
	 * @return string
	 *
	 * @access public
	 * @since 1.0.0
	 */
	public static function loop_through_option_types( $type = '', $child = false ) {

		$content = '';
		$types   = Utils::option_types_array();

		if ( $child ) {
			unset( $types['list-item'] );
		}

		foreach ( $types as $key => $value ) {
			$content .= '<option value="' . esc_attr( $key ) . '" ' . selected( $type, $key, false ) . '>' . esc_html( $value ) . '</option>';
		}

		return $content;

	}


	/**
	 * Helper function to loop over choices.
	 *
	 * @param string $name The form element name.
	 * @param array $choices The array of choices.
	 *
	 * @return string
	 *
	 * @access public
	 * @since 1.0.0
	 */
	public static function loop_through_choices( $name, $choices = array() ) {

		$content = '';

		foreach ( (array) $choices as $key => $choice ) {
			if ( is_array( $choice ) ) {
				$content .= '<li class="ui-state-default list-choice">' . Utils::choices_view( $name, $key, $choice ) . '</li>';
			}
		}

		return $content;
	}

	/**
	 * Helper function to loop over sub settings.
	 *
	 * @param string $name The form element name.
	 * @param array $settings The array of settings.
	 *
	 * @return string
	 *
	 * @access public
	 * @since 1.0.0
	 */
	public static function loop_through_sub_settings( $name, $settings = array() ) {

		$content = '';

		foreach ( $settings as $key => $setting ) {
			if ( is_array( $setting ) ) {
				$content .= '<li class="ui-state-default list-sub-setting">' . Utils::settings_view( $name, $key, $setting ) . '</li>';
			}
		}

		return $content;
	}


	/**
	 * Helper function to display sections.
	 *
	 * This function is used in AJAX to add a new section
	 * and when section have already been added and saved.
	 *
	 * @param string $name The form element name.
	 * @param int $key The array key for the current element.
	 * @param array $section An array of values for the current section.
	 *
	 * @return string
	 *
	 * @access public
	 * @since 1.0.0
	 */
	public static function sections_view( $name, $key, $section = array() ) {

		/* translators: %s: Section Title emphasized */
		$str_title = esc_html__( '%s: Displayed as a menu item on the Theme Options page.', 'option-builder' );

		/* translators: %s: Section ID emphasized */
		$str_id = esc_html__( '%s: A unique lower case alphanumeric string, underscores allowed.', 'option-builder' );

		return '
		<div class="option-builder-setting is-section">
			<div class="open">' . ( isset( $section['title'] ) ? esc_attr( $section['title'] ) : 'Section ' . ( absint( $key ) + 1 ) ) . '</div>
			<div class="button-section">
				<a href="javascript:void(0);" class="option-builder-setting-edit option-builder-ui-button button left-item" title="' . esc_html__( 'edit', 'option-builder' ) . '">
					<span class="icon opb-icon-pencil"></span>' . esc_html__( 'Edit', 'option-builder' ) . '
				</a>
				<a href="javascript:void(0);" class="option-builder-setting-remove option-builder-ui-button button button-secondary light right-item" title="' . esc_html__( 'Delete', 'option-builder' ) . '">
					<span class="icon opb-icon-trash-o"></span>' . esc_html__( 'Delete', 'option-builder' ) . '
				</a>
			</div>
			<div class="option-builder-setting-body">
				<div class="format-settings">
					<div class="format-setting type-text">
						<div class="description">' . sprintf( $str_title, '<strong>' . esc_html__( 'Section Title', 'option-builder' ) . '</strong>', 'option-builder' ) . '</div>
						<div class="format-setting-inner">
							<input type="text" name="' . esc_attr( $name ) . '[' . esc_attr( $key ) . '][title]" value="' . ( isset( $section['title'] ) ? esc_attr( $section['title'] ) : '' ) . '" class="widefat option-builder-ui-input option-builder-setting-title section-title" autocomplete="off" />
						</div>
					</div>
				</div>
				<div class="format-settings">
					<div class="format-setting type-text">
						<div class="description">' . sprintf( $str_id, '<strong>' . esc_html__( 'Section ID', 'option-builder' ) . '</strong>', 'option-builder' ) . '</div>
						<div class="format-setting-inner">
							<input type="text" name="' . esc_attr( $name ) . '[' . esc_attr( $key ) . '][id]" value="' . ( isset( $section['id'] ) ? esc_attr( $section['id'] ) : '' ) . '" class="widefat option-builder-ui-input section-id" autocomplete="off" />
						</div>
					</div>
				</div>
			</div>
		</div>';
	}


	/**
	 * Helper function to display settings.
	 *
	 * This function is used in AJAX to add a new setting
	 * and when settings have already been added and saved.
	 *
	 * @param string $name The form element name.
	 * @param int $key The array key for the current element.
	 * @param array $setting An array of values for the current setting.
	 *
	 * @return string
	 *
	 * @access public
	 * @since 1.0.0
	 */
	public static function settings_view( $name, $key, $setting = array() ) {

		$child    = ( false !== strpos( $name, '][settings]' ) ) ? true : false;
		$type     = isset( $setting['type'] ) ? $setting['type'] : '';
		$std      = isset( $setting['std'] ) ? $setting['std'] : '';
		$operator = isset( $setting['operator'] ) ? esc_attr( $setting['operator'] ) : 'and';

		// Serialize the standard value just in case.
		if ( is_array( $std ) ) {
			$std = maybe_serialize( $std );
		}

		if ( in_array( $type, array( 'css', 'javascript', 'textarea', 'textarea-simple' ), true ) ) {
			$std_form_element = '<textarea class="textarea" rows="10" cols="40" name="' . esc_attr( $name ) . '[' . esc_attr( $key ) . '][std]">' . esc_html( $std ) . '</textarea>';
		} else {
			$std_form_element = '<input type="text" name="' . esc_attr( $name ) . '[' . esc_attr( $key ) . '][std]" value="' . esc_attr( $std ) . '" class="widefat option-builder-ui-input" autocomplete="off" />';
		}

		/* translators: %s: Label emphasized */
		$str_label = esc_html__( '%s: Displayed as the label of a form element on the Theme Options page.', 'option-builder' );

		/* translators: %s: ID emphasized */
		$str_id = esc_html__( '%s: A unique lower case alphanumeric string, underscores allowed.', 'option-builder' );

		/* translators: %s: Type emphasized */
		$str_type = esc_html__( '%s: Choose one of the available option types from the dropdown.', 'option-builder' );

		/* translators: %s: Description emphasized */
		$str_desc = esc_html__( '%s: Enter a detailed description for the users to read on the Theme Options page, HTML is allowed. This is also where you enter content for both the Textblock & Textblock Titled option types.', 'option-builder' );

		/* translators: %s: Choices emphasized */
		$str_choices = esc_html__( '%s: This will only affect the following option types: Checkbox, Radio, Select & Select Image.', 'option-builder' );

		/* translators: %s: Settings emphasized */
		$str_settings = esc_html__( '%s: This will only affect the List Item option type.', 'option-builder' );

		/* translators: %1$s: Standard emphasized, %2$s: visual path to documentation */
		$str_standard = esc_html__( '%1$s: Setting the standard value for your option only works for some option types. Read the %2$s for more information on which ones.', 'option-builder' );

		/* translators: %s: Rows emphasized */
		$str_rows = esc_html__( '%s: Enter a numeric value for the number of rows in your textarea. This will only affect the following option types: CSS, Textarea, & Textarea Simple.', 'option-builder' );

		/* translators: %s: Post Type emphasized */
		$str_post_type = esc_html__( '%s: Add a comma separated list of post type like \'post,page\'. This will only affect the following option types: Custom Post Type Checkbox, & Custom Post Type Select.', 'option-builder' );

		/* translators: %s: Taxonomy emphasized */
		$str_taxonomy = esc_html__( '%s: Add a comma separated list of any registered taxonomy like \'category,post_tag\'. This will only affect the following option types: Taxonomy Checkbox, & Taxonomy Select.', 'option-builder' );

		/* translators: %1$s: Min, Max, & Step emphasized, %2$s: format, %3$s: range, %4$s: minimum interval */
		$str_min_max_step = esc_html__( '%1$s: Add a comma separated list of options in the following format %2$s (slide from %3$s in intervals of %4$s). The three values represent the minimum, maximum, and step options and will only affect the Numeric Slider option type.', 'option-builder' );

		/* translators: %s: CSS Class emphasized */
		$str_css_class = esc_html__( '%s: Add and optional class to this option type.', 'option-builder' );

		/* translators: %1$s: Condition emphasized, %2$s: example value, %3$s: list of valid conditions */
		$str_condition = esc_html__( '%1$s: Add a comma separated list (no spaces) of conditions in which the field will be visible, leave this setting empty to always show the field. In these examples, %2$s is a placeholder for your condition, which can be in the form of %3$s.', 'option-builder' );

		/* translators: %s: Operator emphasized */
		$str_operator = esc_html__( '%s: Choose the logical operator to compute the result of the conditions.', 'option-builder' );

		return '
		<div class="option-builder-setting">
			<div class="open">' . ( isset( $setting['label'] ) ? esc_attr( $setting['label'] ) : 'Setting ' . ( absint( $key ) + 1 ) ) . '</div>
			<div class="button-section">
				<a href="javascript:void(0);" class="option-builder-setting-edit option-builder-ui-button button left-item" title="' . esc_html__( 'Edit', 'option-builder' ) . '">
					<span class="icon opb-icon-pencil"></span>' . esc_html__( 'Edit', 'option-builder' ) . '
				</a>
				<a href="javascript:void(0);" class="option-builder-setting-remove option-builder-ui-button button button-secondary light right-item" title="' . esc_html__( 'Delete', 'option-builder' ) . '">
					<span class="icon opb-icon-trash-o"></span>' . esc_html__( 'Delete', 'option-builder' ) . '
				</a>
			</div>
			<div class="option-builder-setting-body">
				<div class="format-settings">
					<div class="format-setting type-text wide-desc">
						<div class="description">' . sprintf( $str_label, '<strong>' . esc_html__( 'Label', 'option-builder' ) . '</strong>' ) . '</div>
						<div class="format-setting-inner">
							<input type="text" name="' . esc_attr( $name ) . '[' . esc_attr( $key ) . '][label]" value="' . ( isset( $setting['label'] ) ? esc_attr( $setting['label'] ) : '' ) . '" class="widefat option-builder-ui-input option-builder-setting-title" autocomplete="off" />
						</div>
					</div>
				</div>
				<div class="format-settings">
					<div class="format-setting type-text wide-desc">
						<div class="description">' . sprintf( $str_id, '<strong>' . esc_html__( 'ID', 'option-builder' ) . '</strong>' ) . '</div>
						<div class="format-setting-inner">
							<input type="text" name="' . esc_attr( $name ) . '[' . esc_attr( $key ) . '][id]" value="' . ( isset( $setting['id'] ) ? esc_attr( $setting['id'] ) : '' ) . '" class="widefat option-builder-ui-input" autocomplete="off" />
						</div>
					</div>
				</div>
				<div class="format-settings">
					<div class="format-setting type-select wide-desc">
						<div class="description">' . sprintf( $str_type, '<strong>' . esc_html__( 'Type', 'option-builder' ) . '</strong>' ) . '</div>
						<div class="format-setting-inner">
							<select name="' . esc_attr( $name ) . '[' . esc_attr( $key ) . '][type]" value="' . esc_attr( $type ) . '" class="option-builder-ui-select">
								' . Utils::loop_through_option_types( $type, $child ) . '
							</select>
						</div>
					</div>
				</div>
				<div class="format-settings">
					<div class="format-setting type-textarea wide-desc">
						<div class="description">' . sprintf( $str_desc, '<strong>' . esc_html__( 'Description', 'option-builder' ) . '</strong>' ) . '</div>
						<div class="format-setting-inner">
							<textarea class="textarea" rows="10" cols="40" name="' . esc_attr( $name ) . '[' . esc_attr( $key ) . '][desc]">' . ( isset( $setting['desc'] ) ? esc_html( $setting['desc'] ) : '' ) . '</textarea>
						</div>
					</div>
				</div>
				<div class="format-settings">
					<div class="format-setting type-textblock wide-desc">
						<div class="description">' . sprintf( $str_choices, '<strong>' . esc_html__( 'Choices', 'option-builder' ) . '</strong>' ) . '</div>
						<div class="format-setting-inner">
							<ul class="option-builder-setting-wrap option-builder-sortable" data-name="' . esc_attr( $name ) . '[' . esc_attr( $key ) . ']">
								' . ( isset( $setting['choices'] ) ? Utils::loop_through_choices( $name . '[' . $key . ']', $setting['choices'] ) : '' ) . '
							</ul>
							<a href="javascript:void(0);" class="option-builder-choice-add option-builder-ui-button button hug-left">' . esc_html__( 'Add Choice', 'option-builder' ) . '</a>
						</div>
					</div>
				</div>
				<div class="format-settings">
					<div class="format-setting type-textblock wide-desc">
						<div class="description">' . sprintf( $str_settings, '<strong>' . esc_html__( 'Settings', 'option-builder' ) . '</strong>' ) . '</div>
						<div class="format-setting-inner">
							<ul class="option-builder-setting-wrap option-builder-sortable" data-name="' . esc_attr( $name ) . '[' . esc_attr( $key ) . ']">
								' . ( isset( $setting['settings'] ) ? Utils::loop_through_sub_settings( $name . '[' . $key . '][settings]', $setting['settings'] ) : '' ) . '
							</ul>
							<a href="javascript:void(0);" class="option-builder-list-item-setting-add option-builder-ui-button button hug-left">' . esc_html__( 'Add Setting', 'option-builder' ) . '</a>
						</div>
					</div>
				</div>
				<div class="format-settings">
					<div class="format-setting type-text wide-desc">
						<div class="description">' . sprintf( $str_standard, '<strong>' . esc_html__( 'Standard', 'option-builder' ) . '</strong>', '<code>' . esc_html__( 'OptionBuilder->Documentation', 'option-builder' ) . '</code>' ) . '</div>
						<div class="format-setting-inner">
							' . $std_form_element . '
						</div>
					</div>
				</div>
				<div class="format-settings">
					<div class="format-setting type-text wide-desc">
						<div class="description">' . sprintf( $str_rows, '<strong>' . esc_html__( 'Rows', 'option-builder' ) . '</strong>' ) . '</div>
						<div class="format-setting-inner">
							<input type="text" name="' . esc_attr( $name ) . '[' . esc_attr( $key ) . '][rows]" value="' . ( isset( $setting['rows'] ) ? esc_attr( $setting['rows'] ) : '' ) . '" class="widefat option-builder-ui-input" />
						</div>
					</div>
				</div>
				<div class="format-settings">
					<div class="format-setting type-text wide-desc">
						<div class="description">' . sprintf( $str_post_type, '<strong>' . esc_html__( 'Post Type', 'option-builder' ) . '</strong>' ) . '</div>
						<div class="format-setting-inner">
							<input type="text" name="' . esc_attr( $name ) . '[' . esc_attr( $key ) . '][post_type]" value="' . ( isset( $setting['post_type'] ) ? esc_attr( $setting['post_type'] ) : '' ) . '" class="widefat option-builder-ui-input" autocomplete="off" />
						</div>
					</div>
				</div>
				<div class="format-settings">
					<div class="format-setting type-text wide-desc">
						<div class="description">' . sprintf( $str_taxonomy, '<strong>' . esc_html__( 'Taxonomy', 'option-builder' ) . '</strong>' ) . '</div>
						<div class="format-setting-inner">
							<input type="text" name="' . esc_attr( $name ) . '[' . esc_attr( $key ) . '][taxonomy]" value="' . ( isset( $setting['taxonomy'] ) ? esc_attr( $setting['taxonomy'] ) : '' ) . '" class="widefat option-builder-ui-input" autocomplete="off" />
						</div>
					</div>
				</div>
				<div class="format-settings">
					<div class="format-setting type-text wide-desc">
						<div class="description">' . sprintf( $str_min_max_step, '<strong>' . esc_html__( 'Min, Max, & Step', 'option-builder' ) . '</strong>', '<code>0,100,1</code>', '<code>0-100</code>', '<code>1</code>' ) . '</div>
						<div class="format-setting-inner">
							<input type="text" name="' . esc_attr( $name ) . '[' . esc_attr( $key ) . '][min_max_step]" value="' . ( isset( $setting['min_max_step'] ) ? esc_attr( $setting['min_max_step'] ) : '' ) . '" class="widefat option-builder-ui-input" autocomplete="off" />
						</div>
					</div>
				</div>
				<div class="format-settings">
					<div class="format-setting type-text wide-desc">
						<div class="description">' . sprintf( $str_css_class, '<strong>' . esc_html__( 'CSS Class', 'option-builder' ) . '</strong>' ) . '</div>
						<div class="format-setting-inner">
							<input type="text" name="' . esc_attr( $name ) . '[' . esc_attr( $key ) . '][class]" value="' . ( isset( $setting['class'] ) ? esc_attr( $setting['class'] ) : '' ) . '" class="widefat option-builder-ui-input" autocomplete="off" />
						</div>
					</div>
				</div>
				<div class="format-settings">
					<div class="format-setting type-text wide-desc">
						<div class="description">' . sprintf( $str_condition, '<strong>' . esc_html__( 'Condition', 'option-builder' ) . '</strong>', '<code>value</code>', '<code>field_id:is(value)</code>, <code>field_id:not(value)</code>, <code>field_id:contains(value)</code>, <code>field_id:less_than(value)</code>, <code>field_id:less_than_or_equal_to(value)</code>, <code>field_id:greater_than(value)</code>, or <code>field_id:greater_than_or_equal_to(value)</code>' ) . '</div>
						<div class="format-setting-inner">
							<input type="text" name="' . esc_attr( $name ) . '[' . esc_attr( $key ) . '][condition]" value="' . ( isset( $setting['condition'] ) ? esc_attr( $setting['condition'] ) : '' ) . '" class="widefat option-builder-ui-input" autocomplete="off" />
						</div>
					</div>
				</div>
				<div class="format-settings">
					<div class="format-setting type-select wide-desc">
						<div class="description">' . sprintf( $str_operator, '<strong>' . esc_html__( 'Operator', 'option-builder' ) . '</strong>' ) . '</div>
						<div class="format-setting-inner">
							<select name="' . esc_attr( $name ) . '[' . esc_attr( $key ) . '][operator]" value="' . esc_attr( $operator ) . '" class="option-builder-ui-select">
								<option value="and" ' . selected( $operator, 'and', false ) . '>' . esc_html__( 'and', 'option-builder' ) . '</option>
								<option value="or" ' . selected( $operator, 'or', false ) . '>' . esc_html__( 'or', 'option-builder' ) . '</option>
							</select>
						</div>
					</div>
				</div>
			</div>
		</div>
		' . ( ! $child ? '<input type="hidden" class="hidden-section" name="' . esc_attr( $name ) . '[' . esc_attr( $key ) . '][section]" value="' . ( isset( $setting['section'] ) ? esc_attr( $setting['section'] ) : '' ) . '" />' : '' );
	}


	/**
	 * Helper function to display setting choices.
	 *
	 * This function is used in AJAX to add a new choice
	 * and when choices have already been added and saved.
	 *
	 * @param string $name The form element name.
	 * @param int $key The array key for the current element.
	 * @param array $choice An array of values for the current choice.
	 *
	 * @return string
	 *
	 * @access public
	 * @since 1.0.0
	 */
	public static function choices_view( $name, $key, $choice = array() ) {

		return '
		<div class="option-builder-setting">
			<div class="open">' . ( isset( $choice['label'] ) ? esc_attr( $choice['label'] ) : 'Choice ' . ( absint( $key ) + 1 ) ) . '</div>
			<div class="button-section">
				<a href="javascript:void(0);" class="option-builder-setting-edit option-builder-ui-button button left-item" title="' . esc_html__( 'Edit', 'option-builder' ) . '">
					<span class="icon opb-icon-pencil"></span>' . esc_html__( 'Edit', 'option-builder' ) . '
				</a>
				<a href="javascript:void(0);" class="option-builder-setting-remove option-builder-ui-button button button-secondary light right-item" title="' . esc_html__( 'Delete', 'option-builder' ) . '">
					<span class="icon opb-icon-trash-o"></span>' . esc_html__( 'Delete', 'option-builder' ) . '
				</a>
			</div>
			<div class="option-builder-setting-body">
				<div class="format-settings">
					<div class="format-setting-label">
						<h5>' . esc_html__( 'Label', 'option-builder' ) . '</h5>
					</div>
					<div class="format-setting type-text wide-desc">
						<div class="format-setting-inner">
							<input type="text" name="' . esc_attr( $name ) . '[choices][' . esc_attr( $key ) . '][label]" value="' . ( isset( $choice['label'] ) ? esc_attr( $choice['label'] ) : '' ) . '" class="widefat option-builder-ui-input option-builder-setting-title" autocomplete="off" />
						</div>
					</div>
				</div>
				<div class="format-settings">
					<div class="format-setting-label">
						<h5>' . esc_html__( 'Value', 'option-builder' ) . '</h5>
					</div>
					<div class="format-setting type-text wide-desc">
						<div class="format-setting-inner">
							<input type="text" name="' . esc_attr( $name ) . '[choices][' . esc_attr( $key ) . '][value]" value="' . ( isset( $choice['value'] ) ? esc_attr( $choice['value'] ) : '' ) . '" class="widefat option-builder-ui-input" autocomplete="off" />
						</div>
					</div>
				</div>
				<div class="format-settings">
					<div class="format-setting-label">
						<h5>' . esc_html__( 'Image Source (Radio Image only)', 'option-builder' ) . '</h5>
					</div>
					<div class="format-setting type-text wide-desc">
						<div class="format-setting-inner">
							<input type="text" name="' . esc_attr( $name ) . '[choices][' . esc_attr( $key ) . '][src]" value="' . ( isset( $choice['src'] ) ? esc_attr( $choice['src'] ) : '' ) . '" class="widefat option-builder-ui-input" autocomplete="off" />
						</div>
					</div>
				</div>
			</div>
		</div>';

	}


	/**
	 * Helper function to display sections.
	 *
	 * This function is used in AJAX to add a new section
	 * and when section have already been added and saved.
	 *
	 * @param string $name The name/ID of the help page.
	 * @param int $key The array key for the current element.
	 * @param array $content An array of values for the current section.
	 *
	 * @return string
	 *
	 * @access public
	 * @since 1.0.0
	 */
	public static function contextual_help_view( $name, $key, $content = array() ) {

		/* translators: %s: Title emphasized */
		$str_title = esc_html__( '%s: Displayed as a contextual help menu item on the Theme Options page.', 'option-builder' );

		/* translators: %s: ID emphasized */
		$str_id = esc_html__( '%s: A unique lower case alphanumeric string, underscores allowed.', 'option-builder' );

		/* translators: %s: Content emphasized */
		$str_content = esc_html__( '%s: Enter the HTML content about this contextual help item displayed on the Theme Option page for end users to read.', 'option-builder' );

		return '
		<div class="option-builder-setting">
			<div class="open">' . ( isset( $content['title'] ) ? esc_attr( $content['title'] ) : 'Content ' . ( absint( $key ) + 1 ) ) . '</div>
			<div class="button-section">
				<a href="javascript:void(0);" class="option-builder-setting-edit option-builder-ui-button button left-item" title="' . esc_html__( 'Edit', 'option-builder' ) . '">
					<span class="icon opb-icon-pencil"></span>' . esc_html__( 'Edit', 'option-builder' ) . '
				</a>
				<a href="javascript:void(0);" class="option-builder-setting-remove option-builder-ui-button button button-secondary light right-item" title="' . esc_html__( 'Delete', 'option-builder' ) . '">
					<span class="icon opb-icon-trash-o"></span>' . esc_html__( 'Delete', 'option-builder' ) . '
				</a>
			</div>
			<div class="option-builder-setting-body">
				<div class="format-settings">
					<div class="format-setting type-text no-desc">
						<div class="description">' . sprintf( $str_title, '<strong>' . esc_html__( 'Title', 'option-builder' ) . '</strong>' ) . '</div>
						<div class="format-setting-inner">
							<input type="text" name="' . esc_attr( $name ) . '[' . esc_attr( $key ) . '][title]" value="' . ( isset( $content['title'] ) ? esc_attr( $content['title'] ) : '' ) . '" class="widefat option-builder-ui-input option-builder-setting-title" autocomplete="off" />
						</div>
					</div>
				</div>
				<div class="format-settings">
					<div class="format-setting type-text no-desc">
						<div class="description">' . sprintf( $str_id, '<strong>' . esc_html__( 'ID', 'option-builder' ) . '</strong>' ) . '</div>
						<div class="format-setting-inner">
							<input type="text" name="' . esc_attr( $name ) . '[' . esc_attr( $key ) . '][id]" value="' . ( isset( $content['id'] ) ? esc_attr( $content['id'] ) : '' ) . '" class="widefat option-builder-ui-input" autocomplete="off" />
						</div>
					</div>
				</div>
				<div class="format-settings">
					<div class="format-setting type-textarea no-desc">
						<div class="description">' . sprintf( $str_content, '<strong>' . esc_html__( 'Content', 'option-builder' ) . '</strong>' ) . '</div>
						<div class="format-setting-inner">
							<textarea class="textarea" rows="15" cols="40" name="' . esc_attr( $name ) . '[' . esc_attr( $key ) . '][content]">' . ( isset( $content['content'] ) ? esc_textarea( $content['content'] ) : '' ) . '</textarea>
						</div>
					</div>
				</div>
			</div>
		</div>';

	}


	/**
	 * Helper function to display sections.
	 *
	 * @param string $key Layout ID.
	 * @param string $data Layout encoded value.
	 * @param string $active_layout Active layout ID.
	 *
	 * @return string
	 *
	 * @access public
	 * @since 1.0.0
	 */
	public static function layout_view( $key, $data = '', $active_layout = '' ) {

		return '
		<div class="option-builder-setting">
			<div class="open">' . ( isset( $key ) ? esc_attr( $key ) : esc_html__( 'Layout', 'option-builder' ) ) . '</div>
			<div class="button-section">
				<a href="javascript:void(0);" class="option-builder-layout-activate option-builder-ui-button button left-item' . ( $active_layout === $key ? ' active' : '' ) . '" title="' . esc_html__( 'Activate', 'option-builder' ) . '">
					<span class="icon opb-icon-square-o"></span>' . esc_html__( 'Activate', 'option-builder' ) . '
				</a>
				<a href="javascript:void(0);" class="option-builder-setting-remove option-builder-ui-button button button-secondary light right-item" title="' . esc_html__( 'Delete', 'option-builder' ) . '">
					<span class="icon opb-icon-trash-o"></span>' . esc_html__( 'Delete', 'option-builder' ) . '
				</a>
			</div>
			<input type="hidden" name="' . esc_attr(  Utils::layouts_id() ) . '[' . esc_attr( $key ) . ']" value="' . esc_attr( $data ) . '" />
		</div>';
	}

	/**
	 * Helper function to display list items.
	 *
	 * This function is used in AJAX to add a new list items
	 * and when they have already been added and saved.
	 *
	 * @param string $name The form field name.
	 * @param int $key The array key for the current element.
	 * @param array $list_item An array of values for the current list item.
	 * @param int $post_id The post ID.
	 * @param string $get_option The option page ID.
	 * @param array $settings The settings.
	 * @param string $type The list type.
	 *
	 * @access public
	 * @since 1.0.0
	 */
	public static function list_item_view( $name, $key, $list_item = array(), $post_id = 0, $get_option = '', $settings = array(), $type = '' ) {

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

		// Load the old filterable slider settings.
		if ( 'slider' === $type ) {
			$settings = Utils::slider_settings( $name );
		}

		// If no settings array load the filterable list item settings.
		if ( empty( $settings ) ) {
			$settings = Utils::list_item_settings( $name );
		}

		// Merge the two settings array.
		$settings = array_merge( $required_setting, $settings );

		echo '
		<div class="option-builder-setting">
			<div class="open">' . ( isset( $list_item['title'] ) ? esc_attr( $list_item['title'] ) : '' ) . '</div>
			<div class="button-section">
				<a href="javascript:void(0);" class="option-builder-setting-edit option-builder-ui-button button left-item" title="' . esc_html__( 'Edit', 'option-builder' ) . '">
					<span class="icon opb-icon-pencil"></span>' . esc_html__( 'Edit', 'option-builder' ) . '
				</a>
				<a href="javascript:void(0);" class="option-builder-setting-remove option-builder-ui-button button button-secondary light right-item" title="' . esc_html__( 'Delete', 'option-builder' ) . '">
					<span class="icon opb-icon-trash-o"></span>' . esc_html__( 'Delete', 'option-builder' ) . '
				</a>
			</div>
			<div class="option-builder-setting-body">
		';

		$option_types = new OptionTypes();

		foreach ( $settings as $field ) {

			// Set field value.
			$field_value = isset( $list_item[ $field['id'] ] ) ? $list_item[ $field['id'] ] : '';

			// Set default to standard value.
			if ( isset( $field['std'] ) ) {
				$field_value = self::filter_std_value( $field_value, $field['std'] );
			}

			// filter the title label and description.
			if ( 'title' === $field['id'] ) {

				// filter the label.
				$field['label'] = apply_filters( 'opb_list_item_title_label', $field['label'], $name );

				// filter the description.
				$field['desc'] = apply_filters( 'opb_list_item_title_desc', $field['desc'], $name );
			}

			// Make life easier.
			$_field_name = $get_option ? $get_option . '[' . $name . ']' : $name;

			// Build the arguments array.
			$_args = array(
				'type'               => $field['type'],
				'field_id'           => $name . '_' . $field['id'] . '_' . $key,
				'field_name'         => $_field_name . '[' . $key . '][' . $field['id'] . ']',
				'field_value'        => $field_value,
				'field_desc'         => isset( $field['desc'] ) ? $field['desc'] : '',
				'field_std'          => isset( $field['std'] ) ? $field['std'] : '',
				'field_rows'         => isset( $field['rows'] ) ? $field['rows'] : 10,
				'field_post_type'    => isset( $field['post_type'] ) && ! empty( $field['post_type'] ) ? $field['post_type'] : 'post',
				'field_taxonomy'     => isset( $field['taxonomy'] ) && ! empty( $field['taxonomy'] ) ? $field['taxonomy'] : 'category',
				'field_min_max_step' => isset( $field['min_max_step'] ) && ! empty( $field['min_max_step'] ) ? $field['min_max_step'] : '0,100,1',
				'field_class'        => isset( $field['class'] ) ? $field['class'] : '',
				'field_condition'    => isset( $field['condition'] ) ? $field['condition'] : '',
				'field_operator'     => isset( $field['operator'] ) ? $field['operator'] : 'and',
				'field_choices'      => isset( $field['choices'] ) && ! empty( $field['choices'] ) ? $field['choices'] : array(),
				'field_settings'     => isset( $field['settings'] ) && ! empty( $field['settings'] ) ? $field['settings'] : array(),
				'post_id'            => $post_id,
				'get_option'         => $get_option,
			);

			$conditions = '';

			// Setup the conditions.
			if ( isset( $field['condition'] ) && ! empty( $field['condition'] ) ) {

				/* doing magic on the conditions so they work in a list item */
				$conditionals = explode( ',', $field['condition'] );
				foreach ( $conditionals as $condition ) {
					$parts = explode( ':', $condition );
					if ( isset( $parts[0] ) ) {
						$field['condition'] = str_replace( $condition, $name . '_' . $parts[0] . '_' . $key . ':' . $parts[1], $field['condition'] );
					}
				}

				$conditions = ' data-condition="' . esc_attr( $field['condition'] ) . '"';
				$conditions .= isset( $field['operator'] ) && in_array( $field['operator'], array(
					'and',
					'AND',
					'or',
					'OR'
				), true ) ? ' data-operator="' . esc_attr( $field['operator'] ) . '"' : '';
			}

			// Build the setting CSS class.
			if ( ! empty( $_args['field_class'] ) ) {
				$classes = explode( ' ', $_args['field_class'] );

				foreach ( $classes as $_key => $value ) {
					$classes[ $_key ] = $value . '-wrap';
				}

				$class = 'format-settings ' . implode( ' ', $classes );
			} else {
				$class = 'format-settings';
			}

			// Option label.
			echo '<div id="setting_' . esc_attr( $_args['field_id'] ) . '" class="' . esc_attr( $class ) . '"' . $conditions . '>'; // phpcs:ignore

			// Don't show title with textblocks.
			if ( 'textblock' !== $_args['type'] && ! empty( $field['label'] ) ) {
				echo '<div class="format-setting-label">';
				echo '<h3 class="label">' . esc_attr( $field['label'] ) . '</h3>';
				echo '</div>';
			}

			// Only allow simple textarea inside a list-item due to known DOM issues with wp_editor().
			if ( false === apply_filters( 'opb_override_forced_textarea_simple', false, $field['id'] ) && 'textarea' === $_args['type'] ) {
				$_args['type'] = 'textarea-simple';
			}

			// Option body, list-item is not allowed inside another list-item.
			if ( 'list-item' !== $_args['type'] && 'slider' !== $_args['type'] ) {
				$option_types->display_by_type( $_args ); // phpcs:ignore
			}

			echo '</div>';
		}

		echo '</div>';

		echo '</div>';
	}


	/**
	 * Helper function to display social links.
	 *
	 * This function is used in AJAX to add a new list items
	 * and when they have already been added and saved.
	 *
	 * @param string $name The form field name.
	 * @param int $key The array key for the current element.
	 * @param array $list_item An array of values for the current list item.
	 * @param int $post_id The post ID.
	 * @param string $get_option The option page ID.
	 * @param array $settings The settings.
	 * @param string $type
	 *
	 * @access public
	 * @since 1.0.0
	 */
	public static function social_links_view( $name, $key, $list_item = array(), $post_id = 0, $get_option = '', $settings = array(), $type = '' ) {

		// If no settings array load the filterable social links settings.
		if ( empty( $settings ) ) {
			$settings = Utils::social_links_settings( $name );
		}

		echo '
		<div class="option-builder-setting">
			<div class="open">' . ( isset( $list_item['name'] ) ? esc_attr( $list_item['name'] ) : '' ) . '</div>
			<div class="button-section">
				<a href="javascript:void(0);" class="option-builder-setting-edit option-builder-ui-button button left-item" title="' . esc_html__( 'Edit', 'option-builder' ) . '">
					<span class="icon opb-icon-pencil"></span>' . esc_html__( 'Edit', 'option-builder' ) . '
				</a>
				<a href="javascript:void(0);" class="option-builder-setting-remove option-builder-ui-button button button-secondary light right-item" title="' . esc_html__( 'Delete', 'option-builder' ) . '">
					<span class="icon opb-icon-trash-o"></span>' . esc_html__( 'Delete', 'option-builder' ) . '
				</a>
			</div>
			<div class="option-builder-setting-body">
		';

		$option_types = new OptionTypes();

		foreach ( $settings as $field ) {

			// Set field value.
			$field_value = isset( $list_item[ $field['id'] ] ) ? $list_item[ $field['id'] ] : '';

			// Set default to standard value.
			if ( isset( $field['std'] ) ) {
				$field_value = self::filter_std_value( $field_value, $field['std'] );
			}

			// Make life easier.
			$_field_name = $get_option ? $get_option . '[' . $name . ']' : $name;

			// Build the arguments array.
			$_args = array(
				'type'               => $field['type'],
				'field_id'           => $name . '_' . $field['id'] . '_' . $key,
				'field_name'         => $_field_name . '[' . $key . '][' . $field['id'] . ']',
				'field_value'        => $field_value,
				'field_desc'         => isset( $field['desc'] ) ? $field['desc'] : '',
				'field_std'          => isset( $field['std'] ) ? $field['std'] : '',
				'field_rows'         => isset( $field['rows'] ) ? $field['rows'] : 10,
				'field_post_type'    => isset( $field['post_type'] ) && ! empty( $field['post_type'] ) ? $field['post_type'] : 'post',
				'field_taxonomy'     => isset( $field['taxonomy'] ) && ! empty( $field['taxonomy'] ) ? $field['taxonomy'] : 'category',
				'field_min_max_step' => isset( $field['min_max_step'] ) && ! empty( $field['min_max_step'] ) ? $field['min_max_step'] : '0,100,1',
				'field_class'        => isset( $field['class'] ) ? $field['class'] : '',
				'field_condition'    => isset( $field['condition'] ) ? $field['condition'] : '',
				'field_operator'     => isset( $field['operator'] ) ? $field['operator'] : 'and',
				'field_choices'      => isset( $field['choices'] ) && ! empty( $field['choices'] ) ? $field['choices'] : array(),
				'field_settings'     => isset( $field['settings'] ) && ! empty( $field['settings'] ) ? $field['settings'] : array(),
				'post_id'            => $post_id,
				'get_option'         => $get_option,
			);

			$conditions = '';

			// Setup the conditions.
			if ( isset( $field['condition'] ) && ! empty( $field['condition'] ) ) {

				// Doing magic on the conditions so they work in a list item.
				$conditionals = explode( ',', $field['condition'] );
				foreach ( $conditionals as $condition ) {
					$parts = explode( ':', $condition );
					if ( isset( $parts[0] ) ) {
						$field['condition'] = str_replace( $condition, $name . '_' . $parts[0] . '_' . $key . ':' . $parts[1], $field['condition'] );
					}
				}

				$conditions = ' data-condition="' . esc_attr( $field['condition'] ) . '"';
				$conditions .= isset( $field['operator'] ) && in_array( $field['operator'], array(
					'and',
					'AND',
					'or',
					'OR'
				), true ) ? ' data-operator="' . esc_attr( $field['operator'] ) . '"' : '';
			}

			// Option label.
			echo '<div id="setting_' . esc_attr( $_args['field_id'] ) . '" class="format-settings"' . $conditions . '>'; // phpcs:ignore

			// Don't show title with textblocks.
			if ( 'textblock' !== $_args['type'] && ! empty( $field['label'] ) ) {
				echo '<div class="format-setting-label">';
				echo '<h3 class="label">' . esc_attr( $field['label'] ) . '</h3>';
				echo '</div>';
			}

			// Only allow simple textarea inside a list-item due to known DOM issues with wp_editor().
			if ( 'textarea' === $_args['type'] ) {
				$_args['type'] = 'textarea-simple';
			}

			// Option body, list-item is not allowed inside another list-item.
			if ( 'list-item' !== $_args['type'] && 'slider' !== $_args['type'] && 'social-links' !== $_args['type'] ) {
				$option_types->display_by_type( $_args ); // phpcs:ignore
			}

			echo '</div>';
		}

		echo '</div>';

		echo '</div>';
	}


	/**
	 * Helper function to display Theme Options layouts form.
	 *
	 * @access public
	 * @since 1.0.0
	 */
	public static function theme_options_layouts_form() {

		echo '<form method="post" id="option-builder-options-layouts-form">';

		// Form nonce.
		wp_nonce_field( 'option_builder_modify_layouts_form', 'option_builder_modify_layouts_nonce' );

		// Get the saved layouts.
		$layouts = get_option(  Utils::layouts_id() );

		// Set active layout.
		$active_layout = isset( $layouts['active_layout'] ) ? $layouts['active_layout'] : '';

		if ( is_array( $layouts ) && 1 < count( $layouts ) ) {

			$active_layout = $layouts['active_layout'];

			echo '<input type="hidden" id="the_current_layout" value="' . esc_attr( $active_layout ) . '" />';

			echo '<div class="option-builder-active-layout">';

			echo '<select name="' . esc_attr(  Utils::layouts_id() ) . '[active_layout]" class="option-builder-ui-select">';

			$hidden_safe = '';

			foreach ( $layouts as $key => $data ) {

				if ( 'active_layout' === $key ) {
					continue;
				}

				echo '<option ' . selected( $key, $active_layout, false ) . ' value="' . esc_attr( $key ) . '">' . esc_attr( $key ) . '</option>';
				$hidden_safe .= '<input type="hidden" name="' . esc_attr(  Utils::layouts_id() ) . '[' . esc_attr( $key ) . ']" value="' . esc_attr( isset( $data ) ? $data : '' ) . '" />';
			}

			echo '</select>';

			echo '</div>';

			echo $hidden_safe; // phpcs:ignore
		}

		/* new layout wrapper */
		echo '<div class="option-builder-save-layout' . ( ! empty( $active_layout ) ? ' active-layout' : '' ) . '">';

		/* add new layout */
		echo '<input type="text" name="' . esc_attr(  Utils::layouts_id() ) . '[_add_new_layout_]" value="" class="widefat option-builder-ui-input" autocomplete="off" />';

		echo '<button type="submit" class="option-builder-ui-button button button-primary save-layout" title="' . esc_html__( 'New Layout', 'option-builder' ) . '">' . esc_html__( 'New Layout', 'option-builder' ) . '</button>';

		echo '</div>';

		echo '</form>';
	}


	/**
	 * Helper function to filter standard option values.
	 *
	 * @param mixed $value Saved string or array value.
	 * @param mixed $std Standard string or array value.
	 *
	 * @return mixed String or array.
	 *
	 * @access public
	 * @since 1.0.0
	 */
	public static function filter_std_value( $value = '', $std = '' ) {

		if ( is_string( $std ) && ! empty( $std ) ) {

			// Search for an array.
			preg_match( '/a:\d+:{.*?}/', $std, $array_matches, PREG_OFFSET_CAPTURE, 0 );

			// Search for an object.
			preg_match( '/O:\d+:"[a-z0-9_]+":\d+:{.*?}/i', $std, $obj_matches, PREG_OFFSET_CAPTURE, 0 );

			// Prevent object injection.
			if ( $array_matches && ! $obj_matches ) {
				$std = maybe_unserialize( $std );
			} elseif ( $obj_matches ) {
				$std = '';
			}
		}

		if ( is_array( $value ) && is_array( $std ) ) {
			foreach ( $value as $k => $v ) {
				if ( '' === $value[ $k ] && isset( $std[ $k ] ) ) {
					$value[ $k ] = $std[ $k ];
				}
			}
		} elseif ( '' === $value && ! empty( $std ) ) {
			$value = $std;
		}

		return $value;
	}


	/**
	 * Helper function to set the Google fonts array.
	 *
	 * @param string $id The option ID.
	 * @param bool $value The option value.
	 *
	 * @access public
	 * @since 1.0.0
	 */
	public static function set_google_fonts( $id = '', $value = '' ) {

		$opb_set_google_fonts = get_theme_mod( 'opb_set_google_fonts', array() );

		if ( is_array( $value ) && ! empty( $value ) ) {
			$opb_set_google_fonts[ $id ] = $value;
		} elseif ( isset( $opb_set_google_fonts[ $id ] ) ) {
			unset( $opb_set_google_fonts[ $id ] );
		}

		set_theme_mod( 'opb_set_google_fonts', $opb_set_google_fonts );
	}


	/**
	 * Helper function to fetch the Google fonts array.
	 *
	 * @param bool $normalize Whether or not to return a normalized array. Default 'true'.
	 * @param bool $force_rebuild Whether or not to force the array to be rebuilt. Default 'false'.
	 *
	 * @return array
	 *
	 * @access public
	 * @since 1.0.0
	 */
	public static function fetch_google_fonts( $normalize = true, $force_rebuild = false ) {

		// Google Fonts cache key.
		$opb_google_fonts_cache_key = apply_filters( 'opb_google_fonts_cache_key', 'opb_google_fonts_cache' );

		// Get the fonts from cache.
		$opb_google_fonts = apply_filters( 'opb_google_fonts_cache', get_transient( $opb_google_fonts_cache_key ) );

		if ( $force_rebuild || ! is_array( $opb_google_fonts ) || empty( $opb_google_fonts ) ) {

			$opb_google_fonts = array();

			// API url and key.
			$opb_google_fonts_api_url = apply_filters( 'opb_google_fonts_api_url', 'https://www.googleapis.com/webfonts/v1/webfonts' );
			$opb_google_fonts_api_key = apply_filters( 'opb_google_fonts_api_key', OPB_GFONTS_API_KEY );

			if ( false === $opb_google_fonts_api_key ) {
				return array();
			}

			// API arguments.
			$opb_google_fonts_fields = apply_filters(
				'opb_google_fonts_fields',
				array(
					'family',
					'variants',
					'subsets',
				)
			);
			$opb_google_fonts_sort   = apply_filters( 'opb_google_fonts_sort', 'alpha' );

			// Initiate API request.
			$opb_google_fonts_query_args = array(
				'key'    => $opb_google_fonts_api_key,
				'fields' => 'items(' . implode( ',', $opb_google_fonts_fields ) . ')',
				'sort'   => $opb_google_fonts_sort,
			);

			// Build and make the request.
			$opb_google_fonts_query    = esc_url_raw( add_query_arg( $opb_google_fonts_query_args, $opb_google_fonts_api_url ) );
			$opb_google_fonts_response = wp_safe_remote_get(
				$opb_google_fonts_query,
				array(
					'sslverify' => false,
					'timeout'   => 15,
				)
			);

			// Continue if we got a valid response.
			if ( 200 === wp_remote_retrieve_response_code( $opb_google_fonts_response ) ) {

				$response_body = wp_remote_retrieve_body( $opb_google_fonts_response );

				if ( $response_body ) {

					// JSON decode the response body and cache the result.
					$opb_google_fonts_data = json_decode( trim( $response_body ), true );

					if ( is_array( $opb_google_fonts_data ) && isset( $opb_google_fonts_data['items'] ) ) {

						$opb_google_fonts = $opb_google_fonts_data['items'];

						// Normalize the array key.
						$opb_google_fonts_tmp = array();
						foreach ( $opb_google_fonts as $key => $value ) {
							if ( ! isset( $value['family'] ) ) {
								continue;
							}

							$id = preg_replace( '/[^a-z0-9_\-]/', '', strtolower( remove_accents( $value['family'] ) ) );

							if ( $id ) {
								$opb_google_fonts_tmp[ $id ] = $value;
							}
						}

						$opb_google_fonts = $opb_google_fonts_tmp;
						set_theme_mod( 'opb_google_fonts', $opb_google_fonts );
						set_transient( $opb_google_fonts_cache_key, $opb_google_fonts, WEEK_IN_SECONDS );
					}
				}
			}
		}

		return $normalize ? Utils::normalize_google_fonts( $opb_google_fonts ) : $opb_google_fonts;
	}


	/**
	 * Helper function to normalize the Google fonts array.
	 *
	 * @param array $google_fonts An array of fonts to normalize.
	 *
	 * @return array
	 *
	 * @access public
	 * @since 1.0.0
	 */
	public static function normalize_google_fonts( $google_fonts ) {

		$opb_normalized_google_fonts = array();

		if ( is_array( $google_fonts ) && ! empty( $google_fonts ) ) {

			foreach ( $google_fonts as $google_font ) {

				if ( isset( $google_font['family'] ) ) {

					$id = str_replace( ' ', '+', $google_font['family'] );

					$opb_normalized_google_fonts[ $id ] = array(
						'family' => $google_font['family'],
					);

					if ( isset( $google_font['variants'] ) ) {
						$opb_normalized_google_fonts[ $id ]['variants'] = $google_font['variants'];
					}

					if ( isset( $google_font['subsets'] ) ) {
						$opb_normalized_google_fonts[ $id ]['subsets'] = $google_font['subsets'];
					}
				}
			}
		}

		return $opb_normalized_google_fonts;
	}


	/**
	 * Returns the option type by ID.
	 *
	 * @param string $option_id The option ID.
	 * @param string $settings_id The settings array ID.
	 *
	 * @return string The option type.
	 *
	 * @access public
	 * @since 1.0.0
	 */
	public static function get_option_type_by_id( $option_id, $settings_id = '' ) {

		if ( empty( $settings_id ) ) {
			$settings_id = Utils::settings_id();
		}

		$settings = get_option( $settings_id, array() );

		if ( isset( $settings['settings'] ) ) {

			foreach ( $settings['settings'] as $value ) {

				if ( $option_id === $value['id'] && isset( $value['type'] ) ) {
					return $value['type'];
				}
			}
		}

		return false;
	}

	/**
	 * Theme Options ID
	 *
	 * @return string
	 *
	 * @access public
	 * @since 1.0.0
	 */
	public static function options_id() {
		return apply_filters( 'opb_options_id', 'option_builder' );
	}

	/**
	 * Theme Settings ID
	 *
	 * @return string
	 *
	 * @access public
	 * @since 1.0.0
	 */
	public static function settings_id() {
		return apply_filters( 'opb_settings_id', 'option_builder_settings' );
	}

	/**
	 * Theme Layouts ID
	 *
	 * @return string
	 *
	 * @access public
	 * @since 1.0.0
	 */
	public static function layouts_id() {
		return apply_filters( 'opb_layouts_id', 'option_builder_layouts' );
	}
}