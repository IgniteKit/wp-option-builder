<?php

namespace IgniteKit\WP\OptionBuilder;

/**
 * OptionBuilder Meta Box class.
 *
 * This class loads all the methods and helpers specific to build a meta box.
 */
class Metabox {

	/**
	 * Stores the meta box config array.
	 *
	 * @var string
	 */
	private $meta_box;

	/**
	 * Class constructor.
	 *
	 * This method adds other methods of the class to specific hooks within WordPress.
	 *
	 * @param array $meta_box Meta box config array.
	 *
	 * @since 1.0.0
	 *
	 * @uses add_action()
	 *
	 * @access public
	 */
	public function __construct( $meta_box ) {
		if ( ! is_admin() ) {
			return;
		}

		global $opb_meta_boxes;

		if ( ! isset( $opb_meta_boxes ) ) {
			$opb_meta_boxes = array();
		}

		$opb_meta_boxes[] = $meta_box;

		$this->meta_box = $meta_box;

		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );

		add_action( 'save_post', array( $this, 'save_meta_box' ), 1, 2 );
	}

	/**
	 * Adds meta box to any post type
	 *
	 * @uses add_meta_box()
	 *
	 * @access public
	 * @since 1.0.0
	 */
	public function add_meta_boxes() {
		global $wp_version;

		$is_wp_5 = version_compare( $wp_version, '5.0', '>=' );

		foreach ( (array) $this->meta_box['pages'] as $page ) {
			add_meta_box( $this->meta_box['id'], $this->meta_box['title'], array(
				$this,
				'build_meta_box'
			), $page, $this->meta_box['context'], $this->meta_box['priority'], $this->meta_box['fields'] );

			if ( $is_wp_5 ) {
				add_filter(
					'postbox_classes_' . $page . '_' . $this->meta_box['id'],
					function ( $classes ) {
						array_push( $classes, 'opb-meta-box' );

						return $classes;
					}
				);
			}
		}
	}


	/**
	 * Returns the field value
	 *
	 * @param $post
	 * @param $field
	 *
	 * @return string
	 *
	 * @since 1.0.4
	 */
	public function get_field_value( $post, $field ) {

		$save_mode = $this->get_field_save_mode();

		if ( 'default' === $save_mode ) {
			return get_post_meta( $post->ID, $field['id'], true );
		} else {
			$data = get_post_meta( $post->ID, $this->meta_box['id'], true );

			return isset( $data[ $field['id'] ] ) ? $data[ $field['id'] ] : '';
		}

	}

	/**
	 * Returns the save mode
	 * @return mixed|string
	 * @since 1.0.4
	 */
	public function get_field_save_mode() {
		return isset($this->meta_box['save_mode']) ? $this->meta_box['save_mode'] : 'default';
	}

	/**
	 * Meta box view.
	 *
	 * @access public
	 *
	 * @param object $post The WP_Post object.
	 * @param array $fields The meta box fields.
	 *
	 * @since 1.0.0
	 *
	 */
	public function build_meta_box( $post, $fields ) {
		unset( $fields );

		$option_types = new OptionTypes();

		echo '<div class="opb-metabox-wrapper">';

		// Use nonce for verification.
		echo '<input type="hidden" name="' . esc_attr( $this->meta_box['id'] ) . '_nonce" value="' . esc_attr( wp_create_nonce( $this->meta_box['id'] ) ) . '" />';

		// Meta box description.
		echo isset( $this->meta_box['desc'] ) && ! empty( $this->meta_box['desc'] ) ? '<div class="description" style="padding-top:10px;">' . htmlspecialchars_decode( $this->meta_box['desc'] ) . '</div>' : ''; // phpcs:ignore

		// Loop through meta box fields.
		foreach ( $this->meta_box['fields'] as $field ) {

			// Get current post meta data.
			$field_value = $this->get_field_value($post, $field);

			// Set standard value.
			if ( isset( $field['std'] ) ) {
				$field_value = Utils::filter_std_value( $field_value, $field['std'] );
			}

			// Build the arguments array.
			$_args = array(
				'type'               => $field['type'],
				'field_id'           => $field['id'],
				'field_name'         => $field['id'],
				'field_value'        => $field_value,
				'field_desc'         => isset( $field['desc'] ) ? $field['desc'] : '',
                'field_type'         => isset( $field['input_type'] ) ? $field['input_type'] : '',
                'field_std'          => isset( $field['std'] ) ? $field['std'] : '',
				'field_rows'         => isset( $field['rows'] ) && ! empty( $field['rows'] ) ? $field['rows'] : 10,
				'field_post_type'    => isset( $field['post_type'] ) && ! empty( $field['post_type'] ) ? $field['post_type'] : 'post',
				'field_taxonomy'     => isset( $field['taxonomy'] ) && ! empty( $field['taxonomy'] ) ? $field['taxonomy'] : 'category',
				'field_min_max_step' => isset( $field['min_max_step'] ) && ! empty( $field['min_max_step'] ) ? $field['min_max_step'] : '0,100,1',
				'field_class'        => isset( $field['class'] ) ? $field['class'] : '',
				'field_condition'    => isset( $field['condition'] ) ? $field['condition'] : '',
				'field_operator'     => isset( $field['operator'] ) ? $field['operator'] : 'and',
				'field_choices'      => isset( $field['choices'] ) ? $field['choices'] : array(),
				'field_settings'     => isset( $field['settings'] ) && ! empty( $field['settings'] ) ? $field['settings'] : array(),
				'field_ajax'         => isset( $field['ajax'] ) ? $field['ajax'] : [],
				'field_group'        => isset( $field['group'] ) ? $field['group'] : [],
				'field_markup'       => isset( $field['markup'] ) && !empty ( $field['markup'] ) ? $field['markup'] : '',
				'field_section'      => isset( $field['section'] ) && !empty ( $field['section'] ) ? $field['section'] : '',
				'post_id'            => $post->ID,
				'meta'               => true,
			);

			$conditions = '';

			// Setup the conditions.
			if ( isset( $field['condition'] ) && ! empty( $field['condition'] ) ) {
				$conditions = ' data-condition="' . esc_attr( $field['condition'] ) . '"';
				$conditions .= isset( $field['operator'] ) && in_array( $field['operator'], array(
					'and',
					'AND',
					'or',
					'OR'
				), true ) ? ' data-operator="' . esc_attr( $field['operator'] ) . '"' : '';
			}

			// Only allow simple textarea due to DOM issues with wp_editor().
			if ( false === apply_filters( 'opb_override_forced_textarea_simple', false, $field['id'] ) && 'textarea' === $_args['type'] ) {
				$_args['type'] = 'textarea-simple';
			}

			// Build the setting CSS class.
			if ( ! empty( $_args['field_class'] ) ) {

				$classes = explode( ' ', $_args['field_class'] );

				foreach ( $classes as $key => $value ) {

					$classes[ $key ] = $value . '-wrap';

				}

				$class = 'format-settings ' . implode( ' ', $classes );
			} else {

				$class = 'format-settings';
			}

			// Option label.
			echo '<div id="setting_' . esc_attr( $field['id'] ) . '" class="' . esc_attr( $class ) . '"' . $conditions . '>'; // phpcs:ignore

			echo '<div class="format-setting-wrap">';

			// Don't show title with textblocks.
			if ( 'textblock' !== $_args['type'] && ! empty( $field['label'] ) ) {
				echo '<div class="format-setting-label">';
				echo '<label for="' . esc_attr( $field['id'] ) . '" class="label">' . esc_html( $field['label'] ) . '</label>';
				echo '</div>';
			}

			// Get the option HTML.
			$option_types->display_by_type( $_args ); // phpcs:ignore

			echo '</div>';

			echo '</div>';

		}

		echo '<div class="clear"></div>';

		echo '</div>';
	}

	/**
	 * Saves the meta box values
	 *
	 * @access public
	 *
	 * @param int $post_id The post ID.
	 * @param object $post_object The WP_Post object.
	 *
	 * @return int|void
	 * @since 1.0.0
	 *
	 */
	public function save_meta_box( $post_id, $post_object ) {
		global $pagenow;

		// Verify nonce.
		if ( ! isset( $_POST[ $this->meta_box['id'] . '_nonce' ] ) || ! wp_verify_nonce( $_POST[ $this->meta_box['id'] . '_nonce' ], $this->meta_box['id'] ) ) { // phpcs:ignore
			return $post_id;
		}

		// Store the post global for use later.
		$post_global = $_POST;

		// Don't save if $_POST is empty.
		if ( empty( $post_global ) || ( isset( $post_global['vc_inline'] ) && true === $post_global['vc_inline'] ) ) {
			return $post_id;
		}

		// Don't save during quick edit.
		if ( 'admin-ajax.php' === $pagenow ) {
			return $post_id;
		}

		// Don't save during autosave.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return $post_id;
		}

		// Don't save if viewing a revision.
		if ( 'revision' === $post_object->post_type || 'revision.php' === $pagenow ) {
			return $post_id;
		}

		// Check permissions.
		if ( isset( $post_global['post_type'] ) && 'page' === $post_global['post_type'] ) {
			if ( ! current_user_can( 'edit_page', $post_id ) ) {
				return $post_id;
			}
		} else {
			if ( ! current_user_can( 'edit_post', $post_id ) ) {
				return $post_id;
			}
		}

		$save_as    = $this->get_field_save_mode();
		$field_data = 'default' === $save_as ? array() : get_post_meta( $post_id, $this->meta_box['id'], true );
		if ( empty( $field_data ) ) {
			$field_data = array();
		}

		foreach ( $this->meta_box['fields'] as $field ) {

			if ( 'default' === $save_as ) {
				$old = get_post_meta( $post_id, $field['id'], true );
			} else {
				$old = isset( $field_data[ $field['id'] ] ) ? $field_data[ $field['id'] ] : '';
			}
			$new = '';

			// There is data to validate.
			if ( isset( $post_global[ $field['id'] ] ) ) {

				// Slider and list item.
				if ( in_array( $field['type'], array( 'list-item', 'slider' ), true ) ) {

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
					$settings = isset( $post_global[ $field['id'] . '_settings_array' ] ) ? Utils::decode( $post_global[ $field['id'] . '_settings_array' ] ) : array();

					// Settings are empty for some odd reason get the defaults.
					if ( empty( $settings ) ) {
						$settings = ( 'slider' === $field['type'] ) ? Utils::slider_settings( $field['id'] ) : Utils::list_item_settings( $field['id'] );
					}

					// Merge the two settings array.
					$settings = array_merge( $required_setting, $settings );

					foreach ( $post_global[ $field['id'] ] as $k => $setting_array ) {

						foreach ( $settings as $sub_setting ) {

							// Verify sub setting has a type & value.
							if ( isset( $sub_setting['type'] ) && isset( $post_global[ $field['id'] ][ $k ][ $sub_setting['id'] ] ) ) {

								$post_global[ $field['id'] ][ $k ][ $sub_setting['id'] ] = Utils::validate_setting( $post_global[ $field['id'] ][ $k ][ $sub_setting['id'] ], $sub_setting['type'], $sub_setting['id'] );
							}
						}
					}

					// Set up new data with validated data.
					$new = $post_global[ $field['id'] ];

				} elseif ( 'social-links' === $field['type'] ) {

					// Convert the settings to an array.
					$settings = isset( $post_global[ $field['id'] . '_settings_array' ] ) ? Utils::decode( $post_global[ $field['id'] . '_settings_array' ] ) : array();

					// Settings are empty get the defaults.
					if ( empty( $settings ) ) {
						$settings = Utils::social_links_settings( $field['id'] );
					}

					foreach ( $post_global[ $field['id'] ] as $k => $setting_array ) {

						foreach ( $settings as $sub_setting ) {

							// Verify sub setting has a type & value.
							if ( isset( $sub_setting['type'] ) && isset( $post_global[ $field['id'] ][ $k ][ $sub_setting['id'] ] ) ) {
								$post_global[ $field['id'] ][ $k ][ $sub_setting['id'] ] = Utils::validate_setting( $post_global[ $field['id'] ][ $k ][ $sub_setting['id'] ], $sub_setting['type'], $sub_setting['id'] );
							}
						}
					}

					// Set up new data with validated data.
					$new = $post_global[ $field['id'] ];
				} else {

					// Run through validation.
					$new = Utils::validate_setting( $post_global[ $field['id'] ], $field['type'], $field['id'] );
				}

				// Insert CSS.
				if ( 'css' === $field['type'] ) {

					if ( '' !== $new ) {

						// insert CSS into dynamic.css.
						Utils::insert_css_with_markers( $field['id'], $new, true );
					} else {

						// Remove old CSS from dynamic.css.
						Utils::remove_old_css( $field['id'] );
					}
				}
			}

			if ( isset( $new ) && $new !== $old ) {
				if ( 'default' === $save_as ) {
					update_post_meta( $post_id, $field['id'], $new );
				} else {
					$field_data[ $field['id'] ] = $new;
				}
			} elseif ( '' === $new && $old ) {
				delete_post_meta( $post_id, $field['id'], $old );
			}
		}

		if ( 'default' !== $save_as ) {
			update_post_meta( $post_id, $this->meta_box['id'], $field_data );
		}
	}

}
