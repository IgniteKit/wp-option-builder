<?php

namespace IgniteKit\WP\OptionBuilder;

/**
 * Class OPB_OptionTypes
 */
class OptionTypes {

	/**
	 * Builds the HTML for each of the available option types by calling those
	 * function with call_user_func and passing the arguments to the second param.
	 *
	 * All fields are required!
	 *
	 * @param array $args The array of arguments are as follows.
	 *
	 * @var string $type Type of option.
	 * @var string $field_id The field ID.
	 * @var string $field_name The field Name.
	 * @var mixed $field_value The field value is a string or an array of values.
	 * @var string $field_desc The field description.
	 * @var string $field_std The standard value.
	 * @var string $field_class Extra CSS classes.
	 * @var array $field_choices The array of option choices.
	 * @var array $field_settings The array of settings for a list item.
	 *
	 * @access public
	 * @since 1.0.0
	 */
	public function display_by_type( $args = array() ) {

		// Allow filters to be executed on the array.
		$args = apply_filters( 'opb_display_by_type', $args );

		if ( empty( $args['type'] ) ) {
			return;
		}

		// Build the function name.
		$method_name_by_type = str_replace( '-', '_', 'type_' . $args['type'] );

		if ( method_exists( $this, $method_name_by_type ) ) {
			$this->$method_name_by_type( $args );
		} else {
			echo '<p>' . esc_html__( 'Sorry, this option type does not exist', 'option-builder' ) . '</p>';
		}
	}

	/**
	 * Background option type.
	 *
	 * See @display_by_type to see the full list of available arguments.
	 *
	 * @param array $args An array of arguments.
	 *
	 * @access public
	 * @since 1.0.0
	 */
	public function type_background( $args = array() ) {

		// Turns arguments array into variables.
		extract( $args ); // phpcs:ignore

		// Verify a description.
		$has_desc = ! empty( $field_desc ) ? true : false;

		// If an attachment ID is stored here fetch its URL and replace the value.
		if ( isset( $field_value['background-image'] ) && wp_attachment_is_image( $field_value['background-image'] ) ) {

			$attachment_data = wp_get_attachment_image_src( $field_value['background-image'], 'original' );

			/* check for attachment data */
			if ( $attachment_data ) {

				$field_src = $attachment_data[0];

			}
		}

		// Format setting outer wrapper.
		echo '<div class="format-setting type-background ' . ( $has_desc ? 'has-desc' : 'no-desc' ) . '">';

		// Description.
		echo $has_desc ? '<div class="description">' . wp_kses_post( htmlspecialchars_decode( $field_desc ) ) . '</div>' : '';

		// Format setting inner wrapper.
		echo '<div class="format-setting-inner">';

		// Allow fields to be filtered.
		$opb_recognized_background_fields = apply_filters(
			'opb_recognized_background_fields',
			array(
				'background-color',
				'background-repeat',
				'background-attachment',
				'background-position',
				'background-size',
				'background-image',
			),
			$field_id
		);

		echo '<div class="opb-background-group">';

		// Build background color.
		if ( in_array( 'background-color', $opb_recognized_background_fields, true ) ) {

			echo '<div class="option-builder-ui-colorpicker-input-wrap">';

			echo '<script>jQuery(document).ready(function($) { OPB_UI.bind_colorpicker("' . esc_attr( $field_id ) . '-picker"); });</script>';

			$background_color = isset( $field_value['background-color'] ) ? $field_value['background-color'] : '';

			echo '<input type="text" name="' . esc_attr( $field_name ) . '[background-color]" id="' . esc_attr( $field_id ) . '-picker" value="' . esc_attr( $background_color ) . '" class="hide-color-picker ' . esc_attr( $field_class ) . '" />';

			echo '</div>';
		}

		// Build background repeat.
		if ( in_array( 'background-repeat', $opb_recognized_background_fields, true ) ) {

			$background_repeat = isset( $field_value['background-repeat'] ) ? esc_attr( $field_value['background-repeat'] ) : '';

			echo '<select name="' . esc_attr( $field_name ) . '[background-repeat]" id="' . esc_attr( $field_id ) . '-repeat" class="option-builder-ui-select ' . esc_attr( $field_class ) . '">';

			echo '<option value="">' . esc_html__( 'background-repeat', 'option-builder' ) . '</option>';
			foreach ( Utils::recognized_background_repeat( $field_id ) as $key => $value ) {

				echo '<option value="' . esc_attr( $key ) . '" ' . selected( $background_repeat, $key, false ) . '>' . esc_attr( $value ) . '</option>';
			}

			echo '</select>';
		}

		// Build background attachment.
		if ( in_array( 'background-attachment', $opb_recognized_background_fields, true ) ) {

			$background_attachment = isset( $field_value['background-attachment'] ) ? $field_value['background-attachment'] : '';

			echo '<select name="' . esc_attr( $field_name ) . '[background-attachment]" id="' . esc_attr( $field_id ) . '-attachment" class="option-builder-ui-select ' . esc_attr( $field_class ) . '">';

			echo '<option value="">' . esc_html__( 'background-attachment', 'option-builder' ) . '</option>';

			foreach ( Utils::recognized_background_attachment( $field_id ) as $key => $value ) {

				echo '<option value="' . esc_attr( $key ) . '" ' . selected( $background_attachment, $key, false ) . '>' . esc_attr( $value ) . '</option>';
			}

			echo '</select>';
		}

		// Build background position.
		if ( in_array( 'background-position', $opb_recognized_background_fields, true ) ) {

			$background_position = isset( $field_value['background-position'] ) ? $field_value['background-position'] : '';

			echo '<select name="' . esc_attr( $field_name ) . '[background-position]" id="' . esc_attr( $field_id ) . '-position" class="option-builder-ui-select ' . esc_attr( $field_class ) . '">';

			echo '<option value="">' . esc_html__( 'background-position', 'option-builder' ) . '</option>';

			foreach ( Utils::recognized_background_position( $field_id ) as $key => $value ) {

				echo '<option value="' . esc_attr( $key ) . '" ' . selected( $background_position, $key, false ) . '>' . esc_attr( $value ) . '</option>';
			}

			echo '</select>';
		}

		// Build background size .
		if ( in_array( 'background-size', $opb_recognized_background_fields, true ) ) {

			/**
			 * Use this filter to create a select instead of an text input.
			 * Be sure to return the array in the correct format. Add an empty
			 * value to the first choice so the user can leave it blank.
			 *
			 *  Example: array(
			 *    array(
			 *      'label' => 'background-size',
			 *      'value' => ''
			 *    ),
			 *    array(
			 *      'label' => 'cover',
			 *      'value' => 'cover'
			 *    ),
			 *    array(
			 *      'label' => 'contain',
			 *      'value' => 'contain'
			 *    )
			 *  )
			 */
			$choices = apply_filters( 'opb_type_background_size_choices', '', $field_id );

			if ( is_array( $choices ) && ! empty( $choices ) ) {

				// Build select.
				echo '<select name="' . esc_attr( $field_name ) . '[background-size]" id="' . esc_attr( $field_id ) . '-size" class="option-builder-ui-select ' . esc_attr( $field_class ) . '">';

				foreach ( (array) $choices as $choice ) {
					if ( isset( $choice['value'] ) && isset( $choice['label'] ) ) {
						echo '<option value="' . esc_attr( $choice['value'] ) . '" ' . selected( ( isset( $field_value['background-size'] ) ? $field_value['background-size'] : '' ), $choice['value'], false ) . '>' . esc_attr( $choice['label'] ) . '</option>';
					}
				}

				echo '</select>';
			} else {

				echo '<input type="text" name="' . esc_attr( $field_name ) . '[background-size]" id="' . esc_attr( $field_id ) . '-size" value="' . esc_attr( isset( $field_value['background-size'] ) ? $field_value['background-size'] : '' ) . '" class="widefat opb-background-size-input option-builder-ui-input ' . esc_attr( $field_class ) . '" placeholder="' . esc_html__( 'background-size', 'option-builder' ) . '" />';
			}
		}

		echo '</div>';

		// Build background image.
		if ( in_array( 'background-image', $opb_recognized_background_fields, true ) ) {

			echo '<div class="option-builder-ui-upload-parent">';

			// Input.
			echo '<input type="text" name="' . esc_attr( $field_name ) . '[background-image]" id="' . esc_attr( $field_id ) . '" value="' . esc_attr( isset( $field_value['background-image'] ) ? $field_value['background-image'] : '' ) . '" class="widefat option-builder-ui-upload-input ' . esc_attr( $field_class ) . '" placeholder="' . esc_html__( 'background-image', 'option-builder' ) . '" />';

			// Add media button.
			echo '<a href="javascript:void(0);" class="opb_upload_media option-builder-ui-button button button-primary light" rel="' . esc_attr( $post_id ) . '" title="' . esc_html__( 'Add Media', 'option-builder' ) . '"><span class="icon opb-icon-plus-circle"></span>' . esc_html__( 'Add Media', 'option-builder' ) . '</a>';

			echo '</div>';

			// Media.
			if ( isset( $field_value['background-image'] ) && '' !== $field_value['background-image'] ) {

				/* replace image src */
				if ( isset( $field_src ) ) {
					$field_value['background-image'] = $field_src;
				}

				echo '<div class="option-builder-ui-media-wrap" id="' . esc_attr( $field_id ) . '_media">';

				if ( preg_match( '/\.(?:jpe?g|png|gif|ico)$/i', $field_value['background-image'] ) ) {
					echo '<div class="option-builder-ui-image-wrap"><img src="' . esc_url_raw( $field_value['background-image'] ) . '" alt="" /></div>';
				}

				echo '<a href="javascript:(void);" class="option-builder-ui-remove-media option-builder-ui-button button button-secondary light" title="' . esc_html__( 'Remove Media', 'option-builder' ) . '"><span class="icon opb-icon-minus-circle"></span>' . esc_html__( 'Remove Media', 'option-builder' ) . '</a>';

				echo '</div>';
			}
		}

		echo '</div>';

		echo '</div>';
	}


	/**
	 * Border Option Type
	 *
	 * See @display_by_type to see the full list of available arguments.
	 *
	 * @param array $args The options arguments.
	 *
	 * @access public
	 * @since 1.0.0
	 */
	public function type_border( $args = array() ) {

		// Turns arguments array into variables.
		extract( $args ); // phpcs:ignore

		// Verify a description.
		$has_desc = ! empty( $field_desc ) ? true : false;

		// Format setting outer wrapper.
		echo '<div class="format-setting type-border ' . ( $has_desc ? 'has-desc' : 'no-desc' ) . '">';

		// Description.
		echo $has_desc ? '<div class="description">' . wp_kses_post( htmlspecialchars_decode( $field_desc ) ) . '</div>' : '';

		// Format setting inner wrapper.
		echo '<div class="format-setting-inner">';

		// Allow fields to be filtered.
		$opb_recognized_border_fields = apply_filters(
			'opb_recognized_border_fields',
			array(
				'width',
				'unit',
				'style',
				'color',
			),
			$field_id
		);

		// Build border width.
		if ( in_array( 'width', $opb_recognized_border_fields, true ) ) {

			$width = isset( $field_value['width'] ) ? $field_value['width'] : '';

			echo '<div class="opb-option-group opb-option-group--one-sixth"><input type="text" name="' . esc_attr( $field_name ) . '[width]" id="' . esc_attr( $field_id ) . '-width" value="' . esc_attr( $width ) . '" class="widefat option-builder-ui-input ' . esc_attr( $field_class ) . '" placeholder="' . esc_html__( 'width', 'option-builder' ) . '" /></div>';
		}

		// Build unit dropdown.
		if ( in_array( 'unit', $opb_recognized_border_fields, true ) ) {

			echo '<div class="opb-option-group opb-option-group--one-fourth">';

			echo '<select name="' . esc_attr( $field_name ) . '[unit]" id="' . esc_attr( $field_id ) . '-unit" class="option-builder-ui-select ' . esc_attr( $field_class ) . '">';

			echo '<option value="">' . esc_html__( 'unit', 'option-builder' ) . '</option>';

			foreach ( Utils::recognized_border_unit_types( $field_id ) as $unit ) {
				echo '<option value="' . esc_attr( $unit ) . '" ' . ( isset( $field_value['unit'] ) ? selected( $field_value['unit'], $unit, false ) : '' ) . '>' . esc_attr( $unit ) . '</option>';
			}

			echo '</select>';

			echo '</div>';
		}

		// Build style dropdown.
		if ( in_array( 'style', $opb_recognized_border_fields, true ) ) {

			echo '<div class="opb-option-group opb-option-group--one-fourth">';

			echo '<select name="' . esc_attr( $field_name ) . '[style]" id="' . esc_attr( $field_id ) . '-style" class="option-builder-ui-select ' . esc_attr( $field_class ) . '">';

			echo '<option value="">' . esc_html__( 'style', 'option-builder' ) . '</option>';

			foreach ( Utils::recognized_border_style_types( $field_id ) as $key => $style ) {
				echo '<option value="' . esc_attr( $key ) . '" ' . ( isset( $field_value['style'] ) ? selected( $field_value['style'], $key, false ) : '' ) . '>' . esc_attr( $style ) . '</option>';
			}

			echo '</select>';

			echo '</div>';
		}

		// Build color.
		if ( in_array( 'color', $opb_recognized_border_fields, true ) ) {

			echo '<div class="option-builder-ui-colorpicker-input-wrap">';

			echo '<script>jQuery(document).ready(function($) { OPB_UI.bind_colorpicker("' . esc_attr( $field_id ) . '-picker"); });</script>';

			$color = isset( $field_value['color'] ) ? $field_value['color'] : '';

			echo '<input type="text" name="' . esc_attr( $field_name ) . '[color]" id="' . esc_attr( $field_id ) . '-picker" value="' . esc_attr( $color ) . '" class="hide-color-picker ' . esc_attr( $field_class ) . '" />';

			echo '</div>';
		}

		echo '</div>';

		echo '</div>';
	}


	/**
	 * Box Shadow Option Type
	 *
	 * See @display_by_type to see the full list of available arguments.
	 *
	 * @param array $args The options arguments.
	 *
	 * @access public
	 * @since 1.0.0
	 */
	public function type_box_shadow( $args = array() ) {

		// Turns arguments array into variables.
		extract( $args ); // phpcs:ignore

		// Verify a description.
		$has_desc = ! empty( $field_desc ) ? true : false;

		// Format setting outer wrapper.
		echo '<div class="format-setting type-box-shadow ' . ( $has_desc ? 'has-desc' : 'no-desc' ) . '">';

		// Description.
		echo $has_desc ? '<div class="description">' . wp_kses_post( htmlspecialchars_decode( $field_desc ) ) . '</div>' : '';

		// Format setting inner wrapper.
		echo '<div class="format-setting-inner">';

		// Allow fields to be filtered.
		$opb_recognized_box_shadow_fields = apply_filters(
			'opb_recognized_box_shadow_fields',
			array(
				'inset',
				'offset-x',
				'offset-y',
				'blur-radius',
				'spread-radius',
				'color',
			),
			$field_id
		);

		// Build inset.
		if ( in_array( 'inset', $opb_recognized_box_shadow_fields, true ) ) {

			echo '<div class="opb-option-group opb-option-group--checkbox"><p>';
			echo '<input type="checkbox" name="' . esc_attr( $field_name ) . '[inset]" id="' . esc_attr( $field_id ) . '-inset" value="inset" ' . ( isset( $field_value['inset'] ) ? checked( $field_value['inset'], 'inset', false ) : '' ) . ' class="option-builder-ui-checkbox ' . esc_attr( $field_class ) . '" />';
			echo '<label for="' . esc_attr( $field_id ) . '-inset">inset</label>';
			echo '</p></div>';
		}

		// Build horizontal offset.
		if ( in_array( 'offset-x', $opb_recognized_box_shadow_fields, true ) ) {

			$offset_x = isset( $field_value['offset-x'] ) ? esc_attr( $field_value['offset-x'] ) : '';

			echo '<div class="opb-option-group opb-option-group--one-fifth"><span class="opb-icon-arrows-h opb-option-group--icon"></span><input type="text" name="' . esc_attr( $field_name ) . '[offset-x]" id="' . esc_attr( $field_id ) . '-offset-x" value="' . esc_attr( $offset_x ) . '" class="widefat option-builder-ui-input ' . esc_attr( $field_class ) . '" placeholder="' . esc_html__( 'offset-x', 'option-builder' ) . '" /></div>';
		}

		// Build vertical offset.
		if ( in_array( 'offset-y', $opb_recognized_box_shadow_fields, true ) ) {

			$offset_y = isset( $field_value['offset-y'] ) ? esc_attr( $field_value['offset-y'] ) : '';

			echo '<div class="opb-option-group opb-option-group--one-fifth"><span class="opb-icon-arrows-v opb-option-group--icon"></span><input type="text" name="' . esc_attr( $field_name ) . '[offset-y]" id="' . esc_attr( $field_id ) . '-offset-y" value="' . esc_attr( $offset_y ) . '" class="widefat option-builder-ui-input ' . esc_attr( $field_class ) . '" placeholder="' . esc_html__( 'offset-y', 'option-builder' ) . '" /></div>';
		}

		// Build blur-radius radius.
		if ( in_array( 'blur-radius', $opb_recognized_box_shadow_fields, true ) ) {

			$blur_radius = isset( $field_value['blur-radius'] ) ? esc_attr( $field_value['blur-radius'] ) : '';

			echo '<div class="opb-option-group opb-option-group--one-fifth"><span class="opb-icon-circle opb-option-group--icon"></span><input type="text" name="' . esc_attr( $field_name ) . '[blur-radius]" id="' . esc_attr( $field_id ) . '-blur-radius" value="' . esc_attr( $blur_radius ) . '" class="widefat option-builder-ui-input ' . esc_attr( $field_class ) . '" placeholder="' . esc_html__( 'blur-radius', 'option-builder' ) . '" /></div>';
		}

		// Build spread-radius radius.
		if ( in_array( 'spread-radius', $opb_recognized_box_shadow_fields, true ) ) {

			$spread_radius = isset( $field_value['spread-radius'] ) ? esc_attr( $field_value['spread-radius'] ) : '';

			echo '<div class="opb-option-group opb-option-group--one-fifth"><span class="opb-icon-arrows-alt opb-option-group--icon"></span><input type="text" name="' . esc_attr( $field_name ) . '[spread-radius]" id="' . esc_attr( $field_id ) . '-spread-radius" value="' . esc_attr( $spread_radius ) . '" class="widefat option-builder-ui-input ' . esc_attr( $field_class ) . '" placeholder="' . esc_html__( 'spread-radius', 'option-builder' ) . '" /></div>';
		}

		// Build color.
		if ( in_array( 'color', $opb_recognized_box_shadow_fields, true ) ) {

			echo '<div class="option-builder-ui-colorpicker-input-wrap">';

			echo '<script>jQuery(document).ready(function($) { OPB_UI.bind_colorpicker("' . esc_attr( $field_id ) . '-picker"); });</script>';

			$color = isset( $field_value['color'] ) ? $field_value['color'] : '';

			echo '<input type="text" name="' . esc_attr( $field_name ) . '[color]" id="' . esc_attr( $field_id ) . '-picker" value="' . esc_attr( $color ) . '" class="hide-color-picker ' . esc_attr( $field_class ) . '" />';

			echo '</div>';
		}

		echo '</div>';

		echo '</div>';
	}


	/**
	 * Category Checkbox option type.
	 *
	 * See @display_by_type to see the full list of available arguments.
	 *
	 * @param array $args An array of arguments.
	 *
	 * @access public
	 * @since 1.0.0
	 */
	public function type_category_checkbox( $args = array() ) {

		// Turns arguments array into variables.
		extract( $args );// phpcs:ignore

		// Verify a description.
		$has_desc = ! empty( $field_desc ) ? true : false;

		// Format setting outer wrapper.
		echo '<div class="format-setting type-category-checkbox type-checkbox ' . ( $has_desc ? 'has-desc' : 'no-desc' ) . '">';

		// Description.
		echo $has_desc ? '<div class="description">' . wp_kses_post( htmlspecialchars_decode( $field_desc ) ) . '</div>' : '';

		// Format setting inner wrapper.
		echo '<div class="format-setting-inner">';

		// Get category array.
		$categories = get_categories( apply_filters( 'opb_type_category_checkbox_query', array( 'hide_empty' => false ), $field_id ) );

		// Build categories.
		if ( ! empty( $categories ) ) {
			foreach ( $categories as $category ) {
				echo '<p>';
				echo '<input type="checkbox" name="' . esc_attr( $field_name ) . '[' . esc_attr( $category->term_id ) . ']" id="' . esc_attr( $field_id ) . '-' . esc_attr( $category->term_id ) . '" value="' . esc_attr( $category->term_id ) . '" ' . ( isset( $field_value[ $category->term_id ] ) ? checked( $field_value[ $category->term_id ], $category->term_id, false ) : '' ) . ' class="option-builder-ui-checkbox ' . esc_attr( $field_class ) . '" />';
				echo '<label for="' . esc_attr( $field_id ) . '-' . esc_attr( $category->term_id ) . '">' . esc_attr( $category->name ) . '</label>';
				echo '</p>';
			}
		} else {
			echo '<p>' . esc_html__( 'No Categories Found', 'option-builder' ) . '</p>';
		}

		echo '</div>';

		echo '</div>';
	}


	/**
	 * Category Select option type.
	 *
	 * See @display_by_type to see the full list of available arguments.
	 *
	 * @param array $args An array of arguments.
	 *
	 * @access public
	 * @since 1.0.0
	 */
	public function type_category_select( $args = array() ) {

		// Turns arguments array into variables.
		extract( $args ); // phpcs:ignore

		// Verify a description.
		$has_desc = ! empty( $field_desc ) ? true : false;

		// Format setting outer wrapper.
		echo '<div class="format-setting type-category-select ' . ( $has_desc ? 'has-desc' : 'no-desc' ) . '">';

		// Description.
		echo $has_desc ? '<div class="description">' . wp_kses_post( htmlspecialchars_decode( $field_desc ) ) . '</div>' : '';

		// Format setting inner wrapper.
		echo '<div class="format-setting-inner">';

		// Build category.
		echo '<select name="' . esc_attr( $field_name ) . '" id="' . esc_attr( $field_id ) . '" class="option-builder-ui-select ' . esc_attr( $field_class ) . '">';

		// Get category array.
		$categories = get_categories( apply_filters( 'opb_type_category_select_query', array( 'hide_empty' => false ), $field_id ) );

		// Has cats.
		if ( ! empty( $categories ) ) {
			echo '<option value="">-- ' . esc_html__( 'Choose One', 'option-builder' ) . ' --</option>';
			foreach ( $categories as $category ) {
				echo '<option value="' . esc_attr( $category->term_id ) . '" ' . selected( $field_value, $category->term_id, false ) . '>' . esc_attr( $category->name ) . '</option>';
			}
		} else {
			echo '<option value="">' . esc_html__( 'No Categories Found', 'option-builder' ) . '</option>';
		}

		echo '</select>';

		echo '</div>';

		echo '</div>';
	}


	/**
	 * Checkbox option type.
	 *
	 * See @display_by_type to see the full list of available arguments.
	 *
	 * @param array $args An array of arguments.
	 *
	 * @access public
	 * @since 1.0.0
	 */
	public function type_checkbox( $args = array() ) {

		// Turns arguments array into variables.
		extract( $args ); // phpcs:ignore

		// Verify a description.
		$has_desc = ! empty( $field_desc ) ? true : false;

		// Format setting outer wrapper.
		echo '<div class="format-setting type-checkbox ' . ( $has_desc ? 'has-desc' : 'no-desc' ) . '">';

		// Description.
		echo $has_desc ? '<div class="description">' . wp_kses_post( htmlspecialchars_decode( $field_desc ) ) . '</div>' : '';

		// Format setting inner wrapper.
		echo '<div class="format-setting-inner">';

		// Build checkbox.
		foreach ( (array) $field_choices as $key => $choice ) {
			if ( isset( $choice['value'] ) && isset( $choice['label'] ) ) {
				echo '<p>';
				echo '<input type="checkbox" name="' . esc_attr( $field_name ) . '[' . esc_attr( $key ) . ']" id="' . esc_attr( $field_id ) . '-' . esc_attr( $key ) . '" value="' . esc_attr( $choice['value'] ) . '" ' . ( isset( $field_value[ $key ] ) ? checked( $field_value[ $key ], $choice['value'], false ) : '' ) . ' class="option-builder-ui-checkbox ' . esc_attr( $field_class ) . '" />';
				echo '<label for="' . esc_attr( $field_id ) . '-' . esc_attr( $key ) . '">' . esc_attr( $choice['label'] ) . '</label>';
				echo '</p>';
			}
		}

		echo '</div>';

		echo '</div>';
	}

	/**
	 * Colorpicker option type.
	 *
	 * See @display_by_type to see the full list of available arguments.
	 *
	 * @param array $args An array of arguments.
	 *
	 * @access  public
	 * @since 1.0.0
	 * @updated 2.2.0
	 */
	public function type_colorpicker( $args = array() ) {

		// Turns arguments array into variables.
		extract( $args ); // phpcs:ignore

		// Verify a description.
		$has_desc = ! empty( $field_desc ) ? true : false;

		// Format setting outer wrapper.
		echo '<div class="format-setting type-colorpicker ' . ( $has_desc ? 'has-desc' : 'no-desc' ) . '">';

		// Description.
		echo $has_desc ? '<div class="description">' . wp_kses_post( htmlspecialchars_decode( $field_desc ) ) . '</div>' : '';

		// Format setting inner wrapper.
		echo '<div class="format-setting-inner">';

		// Build colorpicker.
		echo '<div class="option-builder-ui-colorpicker-input-wrap">';

		// Colorpicker JS.
		echo '<script>jQuery(document).ready(function($) { OPB_UI.bind_colorpicker("' . esc_attr( $field_id ) . '"); });</script>';

		// Input.
		echo '<input type="text" name="' . esc_attr( $field_name ) . '" id="' . esc_attr( $field_id ) . '" value="' . esc_attr( $field_value ) . '" class="hide-color-picker ' . esc_attr( $field_class ) . '"' . ( ! empty( $field_std ) ? ' data-default-color="' . esc_attr( $field_std ) . '"' : '' ) . ' />';

		echo '</div>';

		echo '</div>';

		echo '</div>';
	}


	/**
	 * Colorpicker Opacity option type.
	 *
	 * See @display_by_type to see the full list of available arguments.
	 *
	 * @param array $args An array of arguments.
	 *
	 * @access public
	 * @since 1.0.0
	 */
	public function type_colorpicker_opacity( $args = array() ) {

		$args['field_class'] = isset( $args['field_class'] ) ? $args['field_class'] . ' opb-colorpicker-opacity' : 'opb-colorpicker-opacity';
		$this->type_colorpicker( $args );
	}


	/**
	 * CSS option type.
	 *
	 * See @display_by_type to see the full list of available arguments.
	 *
	 * @param array $args An array of arguments.
	 *
	 * @access public
	 * @since 1.0.0
	 */
	public function type_css( $args = array() ) {

		// Turns arguments array into variables.
		extract( $args ); // phpcs:ignore

		// Verify a description.
		$has_desc = ! empty( $field_desc ) ? true : false;

		// Format setting outer wrapper.
		echo '<div class="format-setting type-css simple ' . ( $has_desc ? 'has-desc' : 'no-desc' ) . '">';

		// Description.
		echo $has_desc ? '<div class="description">' . wp_kses_post( htmlspecialchars_decode( $field_desc ) ) . '</div>' : '';

		// Format setting inner wrapper.
		echo '<div class="format-setting-inner">';

		// Build textarea for CSS.
		echo '<textarea class="hidden" id="textarea_' . esc_attr( $field_id ) . '" name="' . esc_attr( $field_name ) . '">' . esc_textarea( $field_value ) . '</textarea>';

		// Build pre to convert it into ace editor later.
		echo '<pre class="opb-css-editor ' . esc_attr( $field_class ) . '" id="' . esc_attr( $field_id ) . '">' . esc_textarea( $field_value ) . '</pre>';

		echo '</div>';

		echo '</div>';
	}


	/**
	 * Custom Post Type Checkbox option type.
	 *
	 * See @display_by_type to see the full list of available arguments.
	 *
	 * @param array $args An array of arguments.
	 *
	 * @access public
	 * @since 1.0.0
	 */
	public function type_custom_post_type_checkbox( $args = array() ) {

		// Turns arguments array into variables.
		extract( $args ); // phpcs:ignore

		// Verify a description.
		$has_desc = ! empty( $field_desc ) ? true : false;

		// Format setting outer wrapper.
		echo '<div class="format-setting type-custom-post-type-checkbox type-checkbox ' . ( $has_desc ? 'has-desc' : 'no-desc' ) . '">';

		// Description.
		echo $has_desc ? '<div class="description">' . wp_kses_post( htmlspecialchars_decode( $field_desc ) ) . '</div>' : '';

		// Format setting inner wrapper.
		echo '<div class="format-setting-inner">';

		// Setup the post types.
		$post_type = isset( $field_post_type ) ? explode( ',', $field_post_type ) : array( 'post' );

		// Query posts array.
		$my_posts = get_posts(
			apply_filters(
				'opb_type_custom_post_type_checkbox_query',
				array(
					'post_type'      => $post_type,
					'posts_per_page' => - 1,
					'orderby'        => 'title',
					'order'          => 'ASC',
					'post_status'    => 'any',
				),
				$field_id
			)
		);

		// Has posts.
		if ( is_array( $my_posts ) && ! empty( $my_posts ) ) {
			foreach ( $my_posts as $my_post ) {
				$post_title = ! empty( $my_post->post_title ) ? $my_post->post_title : 'Untitled';
				echo '<p>';
				echo '<input type="checkbox" name="' . esc_attr( $field_name ) . '[' . esc_attr( $my_post->ID ) . ']" id="' . esc_attr( $field_id ) . '-' . esc_attr( $my_post->ID ) . '" value="' . esc_attr( $my_post->ID ) . '" ' . ( isset( $field_value[ $my_post->ID ] ) ? checked( $field_value[ $my_post->ID ], $my_post->ID, false ) : '' ) . ' class="option-builder-ui-checkbox ' . esc_attr( $field_class ) . '" />';
				echo '<label for="' . esc_attr( $field_id ) . '-' . esc_attr( $my_post->ID ) . '">' . esc_html( $post_title ) . '</label>';
				echo '</p>';
			}
		} else {
			echo '<p>' . esc_html__( 'No Posts Found', 'option-builder' ) . '</p>';
		}

		echo '</div>';

		echo '</div>';
	}


	/**
	 * Custom Post Type Select option type.
	 *
	 * See @display_by_type to see the full list of available arguments.
	 *
	 * @param array $args An array of arguments.
	 *
	 * @access public
	 * @since 1.0.0
	 */
	public function type_custom_post_type_select( $args = array() ) {

		// Turns arguments array into variables.
		extract( $args ); // phpcs:ignore

		// Verify a description.
		$has_desc = ! empty( $field_desc ) ? true : false;

		// Format setting outer wrapper.
		echo '<div class="format-setting type-custom-post-type-select ' . ( $has_desc ? 'has-desc' : 'no-desc' ) . '">';

		// Description.
		echo $has_desc ? '<div class="description">' . wp_kses_post( htmlspecialchars_decode( $field_desc ) ) . '</div>' : '';

		// Format setting inner wrapper.
		echo '<div class="format-setting-inner">';

		// Build category.
		echo '<select name="' . esc_attr( $field_name ) . '" id="' . esc_attr( $field_id ) . '" class="option-builder-ui-select ' . esc_attr( $field_class ) . '">';

		// Setup the post types.
		$post_type = isset( $field_post_type ) ? explode( ',', $field_post_type ) : array( 'post' );

		// Query posts array.
		$my_posts = get_posts(
			apply_filters(
				'opb_type_custom_post_type_select_query',
				array(
					'post_type'      => $post_type,
					'posts_per_page' => - 1,
					'orderby'        => 'title',
					'order'          => 'ASC',
					'post_status'    => 'any',
				),
				$field_id
			)
		);

		// Has posts.
		if ( is_array( $my_posts ) && ! empty( $my_posts ) ) {
			echo '<option value="">-- ' . esc_html__( 'Choose One', 'option-builder' ) . ' --</option>';
			foreach ( $my_posts as $my_post ) {
				$post_title = ! empty( $my_post->post_title ) ? $my_post->post_title : 'Untitled';
				echo '<option value="' . esc_attr( $my_post->ID ) . '" ' . selected( $field_value, $my_post->ID, false ) . '>' . esc_html( $post_title ) . '</option>';
			}
		} else {
			echo '<option value="">' . esc_html__( 'No Posts Found', 'option-builder' ) . '</option>';
		}

		echo '</select>';

		echo '</div>';

		echo '</div>';
	}


	/**
	 * Date Picker option type.
	 *
	 * See @display_by_type to see the full list of available arguments.
	 *
	 * @param array $args An array of arguments.
	 *
	 * @access public
	 * @since 1.0.0
	 */
	public function type_date_picker( $args = array() ) {

		// Turns arguments array into variables.
		extract( $args ); // phpcs:ignore

		// Verify a description.
		$has_desc = ! empty( $field_desc ) ? true : false;

		// Filter date format.
		$date_format = apply_filters( 'opb_type_date_picker_date_format', 'yy-mm-dd', $field_id );

		/**
		 * Filter the addition of the readonly attribute.
		 *
		 * @param bool $is_readonly Whether to add the 'readonly' attribute. Default 'false'.
		 * @param string $field_id The field ID.
		 *
		 * @since 1.0.0
		 *
		 */
		$is_readonly = apply_filters( 'opb_type_date_picker_readonly', false, $field_id );

		// Format setting outer wrapper.
		echo '<div class="format-setting type-date-picker ' . ( $has_desc ? 'has-desc' : 'no-desc' ) . '">';

		// Date picker JS.
		echo '<script>jQuery(document).ready(function($) { OPB_UI.bind_date_picker("' . esc_attr( $field_id ) . '", "' . esc_attr( $date_format ) . '"); });</script>';

		// Description.
		echo $has_desc ? '<div class="description">' . wp_kses_post( htmlspecialchars_decode( $field_desc ) ) . '</div>' : '';

		// Format setting inner wrapper.
		echo '<div class="format-setting-inner">';

		// Build date picker.
		echo '<input type="text" name="' . esc_attr( $field_name ) . '" id="' . esc_attr( $field_id ) . '" value="' . esc_attr( $field_value ) . '" class="widefat option-builder-ui-input ' . esc_attr( $field_class ) . '"' . ( true === $is_readonly ? ' readonly' : '' ) . ' />';

		echo '</div>';

		echo '</div>';
	}


	/**
	 * Date Time Picker option type.
	 *
	 * See @display_by_type to see the full list of available arguments.
	 *
	 * @param array $args An array of arguments.
	 *
	 * @access public
	 * @since 1.0.0
	 */
	public function type_date_time_picker( $args = array() ) {

		// Turns arguments array into variables.
		extract( $args ); // phpcs:ignore

		// Verify a description.
		$has_desc = ! empty( $field_desc ) ? true : false;

		// Filter date format.
		$date_format = apply_filters( 'opb_type_date_time_picker_date_format', 'yy-mm-dd', $field_id );

		/**
		 * Filter the addition of the readonly attribute.
		 *
		 * @param bool $is_readonly Whether to add the 'readonly' attribute. Default 'false'.
		 * @param string $field_id The field ID.
		 *
		 * @since 1.0.0
		 *
		 */
		$is_readonly = apply_filters( 'opb_type_date_time_picker_readonly', false, $field_id );

		// Format setting outer wrapper.
		echo '<div class="format-setting type-date-time-picker ' . ( $has_desc ? 'has-desc' : 'no-desc' ) . '">';

		// Date time picker JS.
		echo '<script>jQuery(document).ready(function($) { OPB_UI.bind_date_time_picker("' . esc_attr( $field_id ) . '", "' . esc_attr( $date_format ) . '"); });</script>';

		// Description.
		echo $has_desc ? '<div class="description">' . wp_kses_post( htmlspecialchars_decode( $field_desc ) ) . '</div>' : '';

		// Format setting inner wrapper.
		echo '<div class="format-setting-inner">';

		// Build date time picker.
		echo '<input type="text" name="' . esc_attr( $field_name ) . '" id="' . esc_attr( $field_id ) . '" value="' . esc_attr( $field_value ) . '" class="widefat option-builder-ui-input ' . esc_attr( $field_class ) . '"' . ( true === $is_readonly ? ' readonly' : '' ) . ' />';

		echo '</div>';

		echo '</div>';
	}


	/**
	 * Dimension Option Type
	 *
	 * See @display_by_type to see the full list of available arguments.
	 *
	 * @param array $args The options arguments.
	 *
	 * @access public
	 * @since 1.0.0
	 */
	public function type_dimension( $args = array() ) {

		// Turns arguments array into variables.
		extract( $args ); // phpcs:ignore

		// Verify a description.
		$has_desc = ! empty( $field_desc ) ? true : false;

		// Format setting outer wrapper.
		echo '<div class="format-setting type-dimension ' . ( $has_desc ? 'has-desc' : 'no-desc' ) . '">';

		// Description.
		echo $has_desc ? '<div class="description">' . wp_kses_post( htmlspecialchars_decode( $field_desc ) ) . '</div>' : '';

		// Format setting inner wrapper.
		echo '<div class="format-setting-inner">';

		// Allow fields to be filtered.
		$opb_recognized_dimension_fields = apply_filters(
			'opb_recognized_dimension_fields',
			array(
				'width',
				'height',
				'unit',
			),
			$field_id
		);

		// Build width dimension.
		if ( in_array( 'width', $opb_recognized_dimension_fields, true ) ) {

			$width = isset( $field_value['width'] ) ? esc_attr( $field_value['width'] ) : '';
			echo '<div class="opb-option-group opb-option-group--one-third"><span class="opb-icon-arrows-h opb-option-group--icon"></span><input type="text" name="' . esc_attr( $field_name ) . '[width]" id="' . esc_attr( $field_id ) . '-width" value="' . esc_attr( $width ) . '" class="widefat option-builder-ui-input ' . esc_attr( $field_class ) . '" placeholder="' . esc_html__( 'width', 'option-builder' ) . '" /></div>';
		}

		// Build height dimension.
		if ( in_array( 'height', $opb_recognized_dimension_fields, true ) ) {

			$height = isset( $field_value['height'] ) ? esc_attr( $field_value['height'] ) : '';
			echo '<div class="opb-option-group opb-option-group--one-third"><span class="opb-icon-arrows-v opb-option-group--icon"></span><input type="text" name="' . esc_attr( $field_name ) . '[height]" id="' . esc_attr( $field_id ) . '-height" value="' . esc_attr( $height ) . '" class="widefat option-builder-ui-input ' . esc_attr( $field_class ) . '" placeholder="' . esc_html__( 'height', 'option-builder' ) . '" /></div>';
		}

		// Build unit dropdown.
		if ( in_array( 'unit', $opb_recognized_dimension_fields, true ) ) {

			echo '<div class="opb-option-group opb-option-group--one-third opb-option-group--is-last">';

			echo '<select name="' . esc_attr( $field_name ) . '[unit]" id="' . esc_attr( $field_id ) . '-unit" class="option-builder-ui-select ' . esc_attr( $field_class ) . '">';

			echo '<option value="">' . esc_html__( 'unit', 'option-builder' ) . '</option>';

			foreach ( Utils::recognized_dimension_unit_types( $field_id ) as $unit ) {
				echo '<option value="' . esc_attr( $unit ) . '" ' . ( isset( $field_value['unit'] ) ? selected( $field_value['unit'], $unit, false ) : '' ) . '>' . esc_attr( $unit ) . '</option>';
			}

			echo '</select>';

			echo '</div>';
		}

		echo '</div>';

		echo '</div>';
	}

	/**
	 * Gallery option type.
	 *
	 * See @display_by_type to see the full list of available arguments.
	 *
	 * @param array $args The options arguments.
	 *
	 * @access public
	 * @since 1.0.0
	 */
	public function type_gallery( $args = array() ) {

		// Turns arguments array into variables.
		extract( $args ); // phpcs:ignore

		// Verify a description.
		$has_desc = ! empty( $field_desc ) ? true : false;

		// Format setting outer wrapper.
		echo '<div class="format-setting type-gallery ' . ( $has_desc ? 'has-desc' : 'no-desc' ) . '">';

		// Description.
		echo $has_desc ? '<div class="description">' . wp_kses_post( htmlspecialchars_decode( $field_desc ) ) . '</div>' : '';

		// Format setting inner wrapper.
		echo '<div class="format-setting-inner">';

		$field_value = trim( $field_value );

		// Saved values.
		echo '<input type="hidden" name="' . esc_attr( $field_name ) . '" id="' . esc_attr( $field_id ) . '" value="' . esc_attr( $field_value ) . '" class="opb-gallery-value ' . esc_attr( $field_class ) . '" />';

		// Search the string for the IDs.
		preg_match( '/ids=\'(.*?)\'/', $field_value, $matches );

		// Turn the field value into an array of IDs.
		if ( isset( $matches[1] ) ) {

			// Found the IDs in the shortcode.
			$ids = explode( ',', $matches[1] );
		} else {

			// The string is only IDs.
			$ids = ! empty( $field_value ) && '' !== $field_value ? explode( ',', $field_value ) : array();
		}

		// Has attachment IDs.
		if ( ! empty( $ids ) ) {

			echo '<ul class="opb-gallery-list">';

			foreach ( $ids as $id ) {

				if ( '' === $id ) {
					continue;
				}

				$thumbnail = wp_get_attachment_image_src( $id, 'thumbnail' );

				echo '<li><img  src="' . esc_url_raw( $thumbnail[0] ) . '" width="75" height="75" /></li>';
			}

			echo '</ul>';

			echo '
			<div class="opb-gallery-buttons">
				<a href="#" class="option-builder-ui-button button button-secondary hug-left opb-gallery-delete">' . esc_html__( 'Delete Gallery', 'option-builder' ) . '</a>
				<a href="#" class="option-builder-ui-button button button-primary right hug-right opb-gallery-edit">' . esc_html__( 'Edit Gallery', 'option-builder' ) . '</a>
			</div>';

		} else {

			echo '
			<div class="opb-gallery-buttons">
				<a href="#" class="option-builder-ui-button button button-primary right hug-right opb-gallery-edit">' . esc_html__( 'Create Gallery', 'option-builder' ) . '</a>
			</div>';

		}

		echo '</div>';

		echo '</div>';
	}


	/**
	 * Google Fonts option type.
	 *
	 * See @display_by_type to see the full list of available arguments.
	 *
	 * @param array $args An array of arguments.
	 *
	 * @access public
	 * @since 1.0.0
	 */
	public function type_google_fonts( $args = array() ) {

		// Turns arguments array into variables.
		extract( $args ); // phpcs:ignore

		// Verify a description.
		$has_desc = ! empty( $field_desc ) ? true : false;

		// Format setting outer wrapper.
		echo '<div class="format-setting type-google-font ' . ( $has_desc ? 'has-desc' : 'no-desc' ) . '">';

		// Description.
		echo $has_desc ? '<div class="description">' . wp_kses_post( htmlspecialchars_decode( $field_desc ) ) . '</div>' : '';

		// Format setting inner wrapper.
		echo '<div class="format-setting-inner">';

		// Allow fields to be filtered.
		$opb_recognized_google_fonts_fields = apply_filters(
			'opb_recognized_google_font_fields',
			array(
				'variants',
				'subsets',
			),
			$field_id
		);

		// Set a default to show at least one item.
		if ( ! is_array( $field_value ) || empty( $field_value ) ) {
			$field_value = array(
				array(
					'family'   => '',
					'variants' => array(),
					'subsets'  => array(),
				),
			);
		}

		foreach ( $field_value as $key => $value ) {

			echo '<div class="type-google-font-group">';

			// Build font family.
			$family = isset( $value['family'] ) ? $value['family'] : '';
			echo '<div class="option-builder-google-font-family">';
			echo '<a href="javascript:void(0);" class="js-remove-google-font option-builder-ui-button button button-secondary light" title="' . esc_html__( 'Remove Google Font', 'option-builder' ) . '"><span class="icon opb-icon-minus-circle"/>' . esc_html__( 'Remove Google Font', 'option-builder' ) . '</a>';
			echo '<select name="' . esc_attr( $field_name ) . '[' . esc_attr( $key ) . '][family]" id="' . esc_attr( $field_id ) . '-' . esc_attr( $key ) . '" class="option-builder-ui-select ' . esc_attr( $field_class ) . '">';
			echo '<option value="">' . esc_html__( '-- Choose One --', 'option-builder' ) . '</option>';
			foreach ( Utils::recognized_google_font_families( $field_id ) as $family_key => $family_value ) {
				echo '<option value="' . esc_attr( $family_key ) . '" ' . selected( $family, $family_key, false ) . '>' . esc_html( $family_value ) . '</option>';
			}
			echo '</select>';
			echo '</div>';

			// Build font variants.
			if ( in_array( 'variants', $opb_recognized_google_fonts_fields, true ) ) {
				$variants = isset( $value['variants'] ) ? $value['variants'] : array();
				echo '<div class="option-builder-google-font-variants" data-field-id-prefix="' . esc_attr( $field_id ) . '-' . esc_attr( $key ) . '-" data-field-name="' . esc_attr( $field_name ) . '[' . esc_attr( $key ) . '][variants]" data-field-class="option-builder-ui-checkbox ' . esc_attr( $field_class ) . '">';
				foreach ( Utils::recognized_google_font_variants( $field_id, $family ) as $variant_key => $variant ) {
					echo '<p class="checkbox-wrap">';
					echo '<input type="checkbox" name="' . esc_attr( $field_name ) . '[' . esc_attr( $key ) . '][variants][]" id="' . esc_attr( $field_id ) . '-' . esc_attr( $key ) . '-' . esc_attr( $variant ) . '" value="' . esc_attr( $variant ) . '" ' . checked( in_array( $variant, $variants, true ), true, false ) . ' class="option-builder-ui-checkbox ' . esc_attr( $field_class ) . '" />';
					echo '<label for="' . esc_attr( $field_id ) . '-' . esc_attr( $key ) . '-' . esc_attr( $variant ) . '">' . esc_html( $variant ) . '</label>';
					echo '</p>';
				}
				echo '</div>';
			}

			// Build font subsets.
			if ( in_array( 'subsets', $opb_recognized_google_fonts_fields, true ) ) {
				$subsets = isset( $value['subsets'] ) ? $value['subsets'] : array();
				echo '<div class="option-builder-google-font-subsets" data-field-id-prefix="' . esc_attr( $field_id ) . '-' . esc_attr( $key ) . '-" data-field-name="' . esc_attr( $field_name ) . '[' . esc_attr( $key ) . '][subsets]" data-field-class="option-builder-ui-checkbox ' . esc_attr( $field_class ) . '">';
				foreach ( Utils::recognized_google_font_subsets( $field_id, $family ) as $subset_key => $subset ) {
					echo '<p class="checkbox-wrap">';
					echo '<input type="checkbox" name="' . esc_attr( $field_name ) . '[' . esc_attr( $key ) . '][subsets][]" id="' . esc_attr( $field_id ) . '-' . esc_attr( $key ) . '-' . esc_attr( $subset ) . '" value="' . esc_attr( $subset ) . '" ' . checked( in_array( $subset, $subsets, true ), true, false ) . ' class="option-builder-ui-checkbox ' . esc_attr( $field_class ) . '" />';
					echo '<label for="' . esc_attr( $field_id ) . '-' . esc_attr( $key ) . '-' . esc_attr( $subset ) . '">' . esc_html( $subset ) . '</label>';
					echo '</p>';
				}
				echo '</div>';
			}

			echo '</div>';
		}

		echo '<div class="type-google-font-group-clone">';

		/* build font family */
		echo '<div class="option-builder-google-font-family">';
		echo '<a href="javascript:void(0);" class="js-remove-google-font option-builder-ui-button button button-secondary light" title="' . esc_html__( 'Remove Google Font', 'option-builder' ) . '"><span class="icon opb-icon-minus-circle"/>' . esc_html__( 'Remove Google Font', 'option-builder' ) . '</a>';
		echo '<select name="' . esc_attr( $field_name ) . '[%key%][family]" id="' . esc_attr( $field_id ) . '-%key%" class="option-builder-ui-select ' . esc_attr( $field_class ) . '">';
		echo '<option value="">' . esc_html__( '-- Choose One --', 'option-builder' ) . '</option>';

		foreach ( Utils::recognized_google_font_families( $field_id ) as $family_key => $family_value ) {
			echo '<option value="' . esc_attr( $family_key ) . '">' . esc_html( $family_value ) . '</option>';
		}

		echo '</select>';
		echo '</div>';

		// Build font variants.
		if ( in_array( 'variants', $opb_recognized_google_fonts_fields, true ) ) {
			echo '<div class="option-builder-google-font-variants" data-field-id-prefix="' . esc_attr( $field_id ) . '-%key%-" data-field-name="' . esc_attr( $field_name ) . '[%key%][variants]" data-field-class="option-builder-ui-checkbox ' . esc_attr( $field_class ) . '">';
			echo '</div>';
		}

		// Build font subsets.
		if ( in_array( 'subsets', $opb_recognized_google_fonts_fields, true ) ) {
			echo '<div class="option-builder-google-font-subsets" data-field-id-prefix="' . esc_attr( $field_id ) . '-%key%-" data-field-name="' . esc_attr( $field_name ) . '[%key%][subsets]" data-field-class="option-builder-ui-checkbox ' . esc_attr( $field_class ) . '">';
			echo '</div>';
		}

		echo '</div>';

		echo '<a href="javascript:void(0);" class="js-add-google-font option-builder-ui-button button button-primary right hug-right" title="' . esc_html__( 'Add Google Font', 'option-builder' ) . '">' . esc_html__( 'Add Google Font', 'option-builder' ) . '</a>';

		echo '</div>';

		echo '</div>';
	}

	/**
	 * JavaScript option type.
	 *
	 * See @display_by_type to see the full list of available arguments.
	 *
	 * @param array $args An array of arguments.
	 *
	 * @access public
	 * @since 1.0.0
	 */
	public function type_javascript( $args = array() ) {

		// Turns arguments array into variables.
		extract( $args ); // phpcs:ignore

		// Verify a description.
		$has_desc = ! empty( $field_desc ) ? true : false;

		// Format setting outer wrapper.
		echo '<div class="format-setting type-javascript simple ' . ( $has_desc ? 'has-desc' : 'no-desc' ) . '">';

		// Description.
		echo $has_desc ? '<div class="description">' . wp_kses_post( htmlspecialchars_decode( $field_desc ) ) . '</div>' : '';

		// Format setting inner wrapper.
		echo '<div class="format-setting-inner">';

		// Build textarea for CSS.
		echo '<textarea class="hidden" id="textarea_' . esc_attr( $field_id ) . '" name="' . esc_attr( $field_name ) . '">' . esc_textarea( $field_value ) . '</textarea>';

		// Build pre to convert it into ace editor later.
		echo '<pre class="opb-javascript-editor ' . esc_attr( $field_class ) . '" id="' . esc_attr( $field_id ) . '">' . esc_textarea( $field_value ) . '</pre>';

		echo '</div>';

		echo '</div>';
	}


	/**
	 * Link Color option type.
	 *
	 * See @display_by_type to see the full list of available arguments.
	 *
	 * @param array $args The options arguments.
	 *
	 * @access public
	 * @since 1.0.0
	 */
	public function type_link_color( $args = array() ) {

		// Turns arguments array into variables.
		extract( $args ); // phpcs:ignore

		// Verify a description.
		$has_desc = ! empty( $field_desc ) ? true : false;

		// Format setting outer wrapper.
		echo '<div class="format-setting type-link-color ' . ( $has_desc ? 'has-desc' : 'no-desc' ) . '">';

		// Description.
		echo $has_desc ? '<div class="description">' . wp_kses_post( htmlspecialchars_decode( $field_desc ) ) . '</div>' : '';

		// Format setting inner wrapper.
		echo '<div class="format-setting-inner">';

		// Allow fields to be filtered.
		$opb_recognized_link_color_fields = apply_filters(
			'opb_recognized_link_color_fields',
			array(
				'link'    => _x( 'Standard', 'color picker', 'option-builder' ),
				'hover'   => _x( 'Hover', 'color picker', 'option-builder' ),
				'active'  => _x( 'Active', 'color picker', 'option-builder' ),
				'visited' => _x( 'Visited', 'color picker', 'option-builder' ),
				'focus'   => _x( 'Focus', 'color picker', 'option-builder' ),
			),
			$field_id
		);

		// Build link color fields.
		foreach ( $opb_recognized_link_color_fields as $type => $label ) {

			if ( array_key_exists( $type, $opb_recognized_link_color_fields ) ) {

				echo '<div class="option-builder-ui-colorpicker-input-wrap">';

				echo '<label for="' . esc_attr( $field_id ) . '-picker-' . esc_attr( $type ) . '" class="option-builder-ui-colorpicker-label">' . esc_attr( $label ) . '</label>';

				// Colorpicker JS.
				echo '<script>jQuery(document).ready(function($) { OPB_UI.bind_colorpicker("' . esc_attr( $field_id ) . '-picker-' . esc_attr( $type ) . '"); });</script>';

				// Set color.
				$color = isset( $field_value[ $type ] ) ? esc_attr( $field_value[ $type ] ) : '';

				// Set default color.
				$std = isset( $field_std[ $type ] ) ? 'data-default-color="' . $field_std[ $type ] . '"' : '';

				// Input.
				echo '<input type="text" name="' . esc_attr( $field_name ) . '[' . esc_attr( $type ) . ']" id="' . esc_attr( $field_id ) . '-picker-' . esc_attr( $type ) . '" value="' . esc_attr( $color ) . '" class="hide-color-picker ' . esc_attr( $field_class ) . '" ' . esc_attr( $std ) . ' />';

				echo '</div>';

			}
		}

		echo '</div>';

		echo '</div>';
	}

	/**
	 * List Item option type.
	 *
	 * See @display_by_type to see the full list of available arguments.
	 *
	 * @param array $args An array of arguments.
	 *
	 * @access public
	 * @since 1.0.0
	 */
	public function type_list_item( $args = array() ) {

		// Turns arguments array into variables.
		extract( $args ); // phpcs:ignore

		// Verify a description.
		$has_desc = ! empty( $field_desc ) ? true : false;

		// Default.
		$sortable = true;

		// Check if the list can be sorted.
		if ( ! empty( $field_class ) ) {
			$classes = explode( ' ', $field_class );
			if ( in_array( 'nopb-sortable', $classes, true ) ) {
				$sortable = false;
				str_replace( 'nopb-sortable', '', $field_class );
			}
		}

		// Format setting outer wrapper.
		echo '<div class="format-setting type-list-item ' . ( $has_desc ? 'has-desc' : 'no-desc' ) . '">';

		// Description.
		echo $has_desc ? '<div class="description">' . wp_kses_post( htmlspecialchars_decode( $field_desc ) ) . '</div>' : '';

		// Format setting inner wrapper.
		echo '<div class="format-setting-inner">';

		// Pass the settings array arround.
		echo '<input type="hidden" name="' . esc_attr( $field_id ) . '_settings_array" id="' . esc_attr( $field_id ) . '_settings_array" value="' . esc_attr( Utils::encode( $field_settings ) ) . '" />';

		/**
		 * Settings pages have array wrappers like 'option_builder'.
		 * So we need that value to create a proper array to save to.
		 * This is only for NON metabox settings.
		 */
		if ( ! isset( $get_option ) ) {
			$get_option = '';
		}

		// Build list items.
		echo '<ul class="option-builder-setting-wrap' . ( $sortable ? ' option-builder-sortable' : '' ) . '" data-name="' . esc_attr( $field_id ) . '" data-id="' . esc_attr( $post_id ) . '" data-get-option="' . esc_attr( $get_option ) . '" data-type="' . esc_attr( $type ) . '">';

		if ( is_array( $field_value ) && ! empty( $field_value ) ) {

			foreach ( $field_value as $key => $list_item ) {

				echo '<li class="ui-state-default list-list-item">';
				Utils::list_item_view( $field_id, $key, $list_item, $post_id, $get_option, $field_settings, $type );
				echo '</li>';
			}
		}

		echo '</ul>';

		// Button.
		echo '<a href="javascript:void(0);" class="option-builder-list-item-add option-builder-ui-button button button-primary right hug-right" title="' . esc_html__( 'Add New', 'option-builder' ) . '">' . esc_html__( 'Add New', 'option-builder' ) . '</a>';

		// Description.
		$list_desc = $sortable ? __( 'You can re-order with drag & drop, the order will update after saving.', 'option-builder' ) : '';
		echo '<div class="list-item-description">' . esc_html( apply_filters( 'opb_list_item_description', $list_desc, $field_id ) ) . '</div>';

		echo '</div>';

		echo '</div>';
	}

	/**
	 * Measurement option type.
	 *
	 * See @display_by_type to see the full list of available arguments.
	 *
	 * @param array $args An array of arguments.
	 *
	 * @access public
	 * @since 1.0.0
	 */
	public function type_measurement( $args = array() ) {

		// Turns arguments array into variables.
		extract( $args ); // phpcs:ignore

		// Verify a description.
		$has_desc = ! empty( $field_desc ) ? true : false;

		// Format setting outer wrapper.
		echo '<div class="format-setting type-measurement ' . ( $has_desc ? 'has-desc' : 'no-desc' ) . '">';

		// Description.
		echo $has_desc ? '<div class="description">' . wp_kses_post( htmlspecialchars_decode( $field_desc ) ) . '</div>' : '';

		// Format setting inner wrapper.
		echo '<div class="format-setting-inner">';

		echo '<div class="option-builder-ui-measurement-input-wrap">';

		echo '<input type="text" name="' . esc_attr( $field_name ) . '[0]" id="' . esc_attr( $field_id ) . '-0" value="' . esc_attr( ( isset( $field_value[0] ) ? $field_value[0] : '' ) ) . '" class="widefat option-builder-ui-input ' . esc_attr( $field_class ) . '" />';

		echo '</div>';

		// Build measurement.
		echo '<select name="' . esc_attr( $field_name ) . '[1]" id="' . esc_attr( $field_id ) . '-1" class="option-builder-ui-select ' . esc_attr( $field_class ) . '">';

		echo '<option value="">' . esc_html__( 'unit', 'option-builder' ) . '</option>';

		foreach ( Utils::measurement_unit_types( $field_id ) as $unit ) {
			echo '<option value="' . esc_attr( $unit ) . '" ' . ( isset( $field_value[1] ) ? selected( $field_value[1], $unit, false ) : '' ) . '>' . esc_attr( $unit ) . '</option>';
		}

		echo '</select>';

		echo '</div>';

		echo '</div>';
	}


	/**
	 * Numeric Slider option type.
	 *
	 * See @display_by_type to see the full list of available arguments.
	 *
	 * @param array $args An array of arguments.
	 *
	 * @access public
	 * @since 1.0.0
	 */
	public function type_numeric_slider( $args = array() ) {

		// Turns arguments array into variables.
		extract( $args ); // phpcs:ignore

		// Verify a description.
		$has_desc = ! empty( $field_desc ) ? true : false;

		$_options = explode( ',', $field_min_max_step );
		$min      = isset( $_options[0] ) ? $_options[0] : 0;
		$max      = isset( $_options[1] ) ? $_options[1] : 100;
		$step     = isset( $_options[2] ) ? $_options[2] : 1;

		// Format setting outer wrapper.
		echo '<div class="format-setting type-numeric-slider ' . ( $has_desc ? 'has-desc' : 'no-desc' ) . '">';

		// Description.
		echo $has_desc ? '<div class="description">' . wp_kses_post( htmlspecialchars_decode( $field_desc ) ) . '</div>' : '';

		// Format setting inner wrapper.
		echo '<div class="format-setting-inner">';

		echo '<div class="opb-numeric-slider-wrap">';

		echo '<input type="hidden" name="' . esc_attr( $field_name ) . '" id="' . esc_attr( $field_id ) . '" class="opb-numeric-slider-hidden-input" value="' . esc_attr( $field_value ) . '" data-min="' . esc_attr( $min ) . '" data-max="' . esc_attr( $max ) . '" data-step="' . esc_attr( $step ) . '">';

		echo '<input type="text" class="opb-numeric-slider-helper-input widefat option-builder-ui-input ' . esc_attr( $field_class ) . '" value="' . esc_attr( $field_value ) . '" readonly>';

		echo '<div id="opb_numeric_slider_' . esc_attr( $field_id ) . '" class="opb-numeric-slider"></div>';

		echo '</div>';

		echo '</div>';

		echo '</div>';
	}


	/**
	 * On/Off option type
	 *
	 * See @display_by_type to see the full list of available arguments.
	 *
	 * @param array $args The options arguments.
	 *
	 * @access public
	 * @since 1.0.0
	 */
	public function type_on_off( $args = array() ) {

		// Turns arguments array into variables.
		extract( $args ); // phpcs:ignore

		// Verify a description.
		$has_desc = ! empty( $field_desc ) ? true : false;

		// Format setting outer wrapper.
		echo '<div class="format-setting type-radio ' . ( $has_desc ? 'has-desc' : 'no-desc' ) . '">';

		// Description.
		echo $has_desc ? '<div class="description">' . wp_kses_post( htmlspecialchars_decode( $field_desc ) ) . '</div>' : '';

		// Format setting inner wrapper.
		echo '<div class="format-setting-inner">';

		// Force only two choices, and allowing filtering on the choices value & label.
		$field_choices = array(
			array(
				/**
				 * Filter the value of the On button.
				 *
				 * @param string $value The On button value. Default 'on'.
				 * @param string $field_id The field ID.
				 * @param string $filter_id For filtering both on/off value with one function.
				 *
				 * @since 1.0.0
				 *
				 */
				'value' => apply_filters( 'opb_on_off_switch_on_value', 'on', $field_id, 'on' ),
				/**
				 * Filter the label of the On button.
				 *
				 * @param string $label The On button label. Default 'On'.
				 * @param string $field_id The field ID.
				 * @param string $filter_id For filtering both on/off label with one function.
				 *
				 * @since 1.0.0
				 *
				 */
				'label' => apply_filters( 'opb_on_off_switch_on_label', esc_html__( 'On', 'option-builder' ), $field_id, 'on' ),
			),
			array(
				/**
				 * Filter the value of the Off button.
				 *
				 * @param string $value The Off button value. Default 'off'.
				 * @param string $field_id The field ID.
				 * @param string $filter_id For filtering both on/off value with one function.
				 *
				 * @since 1.0.0
				 *
				 */
				'value' => apply_filters( 'opb_on_off_switch_off_value', 'off', $field_id, 'off' ),
				/**
				 * Filter the label of the Off button.
				 *
				 * @param string $label The Off button label. Default 'Off'.
				 * @param string $field_id The field ID.
				 * @param string $filter_id For filtering both on/off label with one function.
				 *
				 * @since 1.0.0
				 *
				 */
				'label' => apply_filters( 'opb_on_off_switch_off_label', esc_html__( 'Off', 'option-builder' ), $field_id, 'off' ),
			),
		);

		/**
		 * Filter the width of the On/Off switch.
		 *
		 * @param string $switch_width The switch width. Default '100px'.
		 * @param string $field_id The field ID.
		 *
		 * @since 1.0.0
		 *
		 */
		$switch_width = apply_filters( 'opb_on_off_switch_width', '100px', $field_id );

		echo '<div class="on-off-switch"' . ( '100px' !== $switch_width ? sprintf( ' style="width:%s"', esc_attr( $switch_width ) ) : '' ) . '>'; // phpcs:ignore

		// Build radio.
		foreach ( (array) $field_choices as $key => $choice ) {
			echo '
            <input type="radio" name="' . esc_attr( $field_name ) . '" id="' . esc_attr( $field_id ) . '-' . esc_attr( $key ) . '" value="' . esc_attr( $choice['value'] ) . '" ' . checked( $field_value, $choice['value'], false ) . ' class="radio option-builder-ui-radio ' . esc_attr( $field_class ) . '" />
            <label for="' . esc_attr( $field_id ) . '-' . esc_attr( $key ) . '" onclick="">' . esc_attr( $choice['label'] ) . '</label>';
		}

		echo '<span class="slide-button"></span>';

		echo '</div>';

		echo '</div>';

		echo '</div>';

	}

	/**
	 * Page Checkbox option type.
	 *
	 * See @display_by_type to see the full list of available arguments.
	 *
	 * @param array $args An array of arguments.
	 *
	 * @access public
	 * @since 1.0.0
	 */
	public function type_page_checkbox( $args = array() ) {

		// Turns arguments array into variables.
		extract( $args ); // phpcs:ignore

		// Verify a description.
		$has_desc = ! empty( $field_desc ) ? true : false;

		// Format setting outer wrapper.
		echo '<div class="format-setting type-page-checkbox type-checkbox ' . ( $has_desc ? 'has-desc' : 'no-desc' ) . '">';

		// Description.
		echo $has_desc ? '<div class="description">' . wp_kses_post( htmlspecialchars_decode( $field_desc ) ) . '</div>' : '';

		// Format setting inner wrapper.
		echo '<div class="format-setting-inner">';

		// Query pages array.
		$my_posts = get_posts(
			apply_filters(
				'opb_type_page_checkbox_query',
				array(
					'post_type'      => array( 'page' ),
					'posts_per_page' => - 1,
					'orderby'        => 'title',
					'order'          => 'ASC',
					'post_status'    => 'any',
				),
				$field_id
			)
		);

		// Has pages.
		if ( is_array( $my_posts ) && ! empty( $my_posts ) ) {
			foreach ( $my_posts as $my_post ) {
				$post_title = ! empty( $my_post->post_title ) ? $my_post->post_title : 'Untitled';
				echo '<p>';
				echo '<input type="checkbox" name="' . esc_attr( $field_name ) . '[' . esc_attr( $my_post->ID ) . ']" id="' . esc_attr( $field_id ) . '-' . esc_attr( $my_post->ID ) . '" value="' . esc_attr( $my_post->ID ) . '" ' . ( isset( $field_value[ $my_post->ID ] ) ? checked( $field_value[ $my_post->ID ], $my_post->ID, false ) : '' ) . ' class="option-builder-ui-checkbox ' . esc_attr( $field_class ) . '" />';
				echo '<label for="' . esc_attr( $field_id ) . '-' . esc_attr( $my_post->ID ) . '">' . esc_html( $post_title ) . '</label>';
				echo '</p>';
			}
		} else {
			echo '<p>' . esc_html__( 'No Pages Found', 'option-builder' ) . '</p>';
		}

		echo '</div>';

		echo '</div>';
	}

	/**
	 * Page Select option type.
	 *
	 * See @display_by_type to see the full list of available arguments.
	 *
	 * @param array $args An array of arguments.
	 *
	 * @access public
	 * @since 1.0.0
	 */
	public function type_page_select( $args = array() ) {

		// Turns arguments array into variables.
		extract( $args ); // phpcs:ignore

		// Verify a description.
		$has_desc = ! empty( $field_desc ) ? true : false;

		// Format setting outer wrapper.
		echo '<div class="format-setting type-page-select ' . ( $has_desc ? 'has-desc' : 'no-desc' ) . '">';

		// Description.
		echo $has_desc ? '<div class="description">' . wp_kses_post( htmlspecialchars_decode( $field_desc ) ) . '</div>' : '';

		// Format setting inner wrapper.
		echo '<div class="format-setting-inner">';

		// Build page select.
		echo '<select name="' . esc_attr( $field_name ) . '" id="' . esc_attr( $field_id ) . '" class="option-builder-ui-select ' . esc_attr( $field_class ) . '">';

		// Query pages array.
		$my_posts = get_posts(
			apply_filters(
				'opb_type_page_select_query',
				array(
					'post_type'      => array( 'page' ),
					'posts_per_page' => - 1,
					'orderby'        => 'title',
					'order'          => 'ASC',
					'post_status'    => 'any',
				),
				$field_id
			)
		);

		// Has pages.
		if ( is_array( $my_posts ) && ! empty( $my_posts ) ) {
			echo '<option value="">-- ' . esc_html__( 'Choose One', 'option-builder' ) . ' --</option>';
			foreach ( $my_posts as $my_post ) {
				$post_title = ! empty( $my_post->post_title ) ? $my_post->post_title : 'Untitled';
				echo '<option value="' . esc_attr( $my_post->ID ) . '" ' . selected( $field_value, $my_post->ID, false ) . '>' . esc_html( $post_title ) . '</option>';
			}
		} else {
			echo '<option value="">' . esc_html__( 'No Pages Found', 'option-builder' ) . '</option>';
		}

		echo '</select>';

		echo '</div>';

		echo '</div>';
	}

	/**
	 * Post Checkbox option type.
	 *
	 * See @display_by_type to see the full list of available arguments.
	 *
	 * @param array $args An array of arguments.
	 *
	 * @access public
	 * @since 1.0.0
	 */
	public function type_post_checkbox( $args = array() ) {

		// Turns arguments array into variables.
		extract( $args ); // phpcs:ignore

		// Verify a description.
		$has_desc = ! empty( $field_desc ) ? true : false;

		// Format setting outer wrapper.
		echo '<div class="format-setting type-post-checkbox type-checkbox ' . ( $has_desc ? 'has-desc' : 'no-desc' ) . '">';

		// Description.
		echo $has_desc ? '<div class="description">' . wp_kses_post( htmlspecialchars_decode( $field_desc ) ) . '</div>' : '';

		// Format setting inner wrapper.
		echo '<div class="format-setting-inner">';

		// Query posts array.
		$my_posts = get_posts(
			apply_filters(
				'opb_type_post_checkbox_query',
				array(
					'post_type'      => array( 'post' ),
					'posts_per_page' => - 1,
					'orderby'        => 'title',
					'order'          => 'ASC',
					'post_status'    => 'any',
				),
				$field_id
			)
		);

		// Has posts.
		if ( is_array( $my_posts ) && ! empty( $my_posts ) ) {
			foreach ( $my_posts as $my_post ) {
				$post_title = ! empty( $my_post->post_title ) ? $my_post->post_title : 'Untitled';
				echo '<p>';
				echo '<input type="checkbox" name="' . esc_attr( $field_name ) . '[' . esc_attr( $my_post->ID ) . ']" id="' . esc_attr( $field_id ) . '-' . esc_attr( $my_post->ID ) . '" value="' . esc_attr( $my_post->ID ) . '" ' . ( isset( $field_value[ $my_post->ID ] ) ? checked( $field_value[ $my_post->ID ], $my_post->ID, false ) : '' ) . ' class="option-builder-ui-checkbox ' . esc_attr( $field_class ) . '" />';
				echo '<label for="' . esc_attr( $field_id ) . '-' . esc_attr( $my_post->ID ) . '">' . esc_html( $post_title ) . '</label>';
				echo '</p>';
			}
		} else {
			echo '<p>' . esc_html__( 'No Posts Found', 'option-builder' ) . '</p>';
		}

		echo '</div>';

		echo '</div>';
	}


	/**
	 * Post Select option type.
	 *
	 * See @display_by_type to see the full list of available arguments.
	 *
	 * @param array $args An array of arguments.
	 *
	 * @access public
	 * @since 1.0.0
	 */
	public function type_post_select( $args = array() ) {

		// Turns arguments array into variables.
		extract( $args ); // phpcs:ignore

		// Verify a description.
		$has_desc = ! empty( $field_desc ) ? true : false;

		// Format setting outer wrapper.
		echo '<div class="format-setting type-post-select ' . ( $has_desc ? 'has-desc' : 'no-desc' ) . '">';

		/* description */
		echo $has_desc ? '<div class="description">' . wp_kses_post( htmlspecialchars_decode( $field_desc ) ) . '</div>' : '';

		// Format setting inner wrapper.
		echo '<div class="format-setting-inner">';

		// Build page select.
		echo '<select name="' . esc_attr( $field_name ) . '" id="' . esc_attr( $field_id ) . '" class="option-builder-ui-select ' . esc_attr( $field_class ) . '">';

		// Query posts array.
		$my_posts = get_posts(
			apply_filters(
				'opb_type_post_select_query',
				array(
					'post_type'      => array( 'post' ),
					'posts_per_page' => - 1,
					'orderby'        => 'title',
					'order'          => 'ASC',
					'post_status'    => 'any',
				),
				$field_id
			)
		);

		// Has posts.
		if ( is_array( $my_posts ) && ! empty( $my_posts ) ) {
			echo '<option value="">-- ' . esc_html__( 'Choose One', 'option-builder' ) . ' --</option>';
			foreach ( $my_posts as $my_post ) {
				$post_title = ! empty( $my_post->post_title ) ? $my_post->post_title : 'Untitled';
				echo '<option value="' . esc_attr( $my_post->ID ) . '" ' . selected( $field_value, $my_post->ID, false ) . '>' . esc_html( $post_title ) . '</option>';
			}
		} else {
			echo '<option value="">' . esc_html__( 'No Posts Found', 'option-builder' ) . '</option>';
		}

		echo '</select>';

		echo '</div>';

		echo '</div>';
	}

	/**
	 * Radio option type.
	 *
	 * See @display_by_type to see the full list of available arguments.
	 *
	 * @param array $args An array of arguments.
	 *
	 * @access public
	 * @since 1.0.0
	 */
	public function type_radio( $args = array() ) {

		// Turns arguments array into variables.
		extract( $args ); // phpcs:ignore

		// Verify a description.
		$has_desc = ! empty( $field_desc ) ? true : false;

		// Format setting outer wrapper.
		echo '<div class="format-setting type-radio ' . ( $has_desc ? 'has-desc' : 'no-desc' ) . '">';

		// Description.
		echo $has_desc ? '<div class="description">' . wp_kses_post( htmlspecialchars_decode( $field_desc ) ) . '</div>' : '';

		// Format setting inner wrapper.
		echo '<div class="format-setting-inner">';

		// Build radio.
		foreach ( (array) $field_choices as $key => $choice ) {
			echo '<p><input type="radio" name="' . esc_attr( $field_name ) . '" id="' . esc_attr( $field_id ) . '-' . esc_attr( $key ) . '" value="' . esc_attr( $choice['value'] ) . '" ' . checked( $field_value, $choice['value'], false ) . ' class="radio option-builder-ui-radio ' . esc_attr( $field_class ) . '" /><label for="' . esc_attr( $field_id ) . '-' . esc_attr( $key ) . '">' . esc_attr( $choice['label'] ) . '</label></p>';
		}

		echo '</div>';

		echo '</div>';
	}

	/**
	 * Radio Images option type.
	 *
	 * See @display_by_type to see the full list of available arguments.
	 *
	 * @param array $args An array of arguments.
	 *
	 * @access public
	 * @since 1.0.0
	 */
	public function type_radio_image( $args = array() ) {

		// Turns arguments array into variables.
		extract( $args ); // phpcs:ignore

		// Verify a description.
		$has_desc = ! empty( $field_desc ) ? true : false;

		// Format setting outer wrapper.
		echo '<div class="format-setting type-radio-image ' . ( $has_desc ? 'has-desc' : 'no-desc' ) . '">';

		// Description.
		echo $has_desc ? '<div class="description">' . wp_kses_post( htmlspecialchars_decode( $field_desc ) ) . '</div>' : '';

		// Format setting inner wrapper.
		echo '<div class="format-setting-inner">';

		/**
		 * Load the default filterable images if nothing
		 * has been set in the choices array.
		 */
		if ( empty( $field_choices ) ) {
			$field_choices = Utils::radio_images( $field_id );
		}

		// Build radio image.
		foreach ( (array) $field_choices as $key => $choice ) {

			// Make radio image source filterable.
			$src = apply_filters( 'opb_type_radio_image_src', $choice['src'], $field_id );

			/**
			 * Filter the image attributes.
			 *
			 * @param string $attributes The image attributes.
			 * @param string $field_id The field ID.
			 * @param array $choice The choice.
			 *
			 * @since 1.0.0
			 *
			 */
			$attributes = apply_filters( 'opb_type_radio_image_attributes', '', $field_id, $choice );

			echo '<div class="option-builder-ui-radio-images">';
			echo '<p style="display:none"><input type="radio" name="' . esc_attr( $field_name ) . '" id="' . esc_attr( $field_id ) . '-' . esc_attr( $key ) . '" value="' . esc_attr( $choice['value'] ) . '" ' . checked( $field_value, $choice['value'], false ) . ' class="option-builder-ui-radio option-builder-ui-images" /><label for="' . esc_attr( $field_id ) . '-' . esc_attr( $key ) . '">' . esc_attr( $choice['label'] ) . '</label></p>';
			echo '<img ' . sanitize_text_field( $attributes ) . ' src="' . esc_url( $src ) . '" alt="' . esc_attr( $choice['label'] ) . '" title="' . esc_attr( $choice['label'] ) . '" class="option-builder-ui-radio-image ' . esc_attr( $field_class ) . ( $field_value === $choice['value'] ? ' option-builder-ui-radio-image-selected' : '' ) . '" />'; // phpcs:ignore
			echo '</div>';
		}

		echo '</div>';

		echo '</div>';
	}


	/**
	 * Select option type.
	 *
	 * See @display_by_type to see the full list of available arguments.
	 *
	 * @param array $args An array of arguments.
	 *
	 * @access public
	 * @since 1.0.0
	 */
	public function type_select( $args = array() ) {

		// Turns arguments array into variables.
		extract( $args ); // phpcs:ignore

		// Verify a description.
		$has_desc = ! empty( $field_desc ) ? true : false;

		// Format setting outer wrapper.
		echo '<div class="format-setting type-select ' . ( $has_desc ? 'has-desc' : 'no-desc' ) . '">';

		// Description.
		echo $has_desc ? '<div class="description">' . wp_kses_post( htmlspecialchars_decode( $field_desc ) ) . '</div>' : '';

		// Filter choices array.
		$field_choices = apply_filters( 'opb_type_select_choices', $field_choices, $field_id );

		// Format setting inner wrapper.
		echo '<div class="format-setting-inner">';

		// Build select.
		echo '<select name="' . esc_attr( $field_name ) . '" id="' . esc_attr( $field_id ) . '" class="option-builder-ui-select ' . esc_attr( $field_class ) . '">';
		foreach ( (array) $field_choices as $choice ) {
			if ( isset( $choice['value'] ) && isset( $choice['label'] ) ) {
				echo '<option value="' . esc_attr( $choice['value'] ) . '"' . selected( $field_value, $choice['value'], false ) . '>' . esc_attr( $choice['label'] ) . '</option>';
			}
		}

		echo '</select>';

		echo '</div>';

		echo '</div>';
	}


	/**
	 * Sidebar Select option type.
	 *
	 * This option type makes it possible for users to select a WordPress registered sidebar
	 * to use on a specific area. By using the two provided filters, 'opb_recognized_sidebars',
	 * and 'opb_recognized_sidebars_{$field_id}' we can be selective about which sidebars are
	 * available on a specific content area.
	 *
	 * For example, if we create a WordPress theme that provides the ability to change the
	 * Blog Sidebar and we don't want to have the footer sidebars available on this area,
	 * we can unset those sidebars either manually or by using a regular expression if we
	 * have a common name like footer-sidebar-$i.
	 *
	 * @param array $args An array of arguments.
	 *
	 * @access public
	 * @since 1.0.0
	 */
	public function type_sidebar_select( $args = array() ) {

		// Turns arguments array into variables.
		extract( $args ); // phpcs:ignore

		// Verify a description.
		$has_desc = ! empty( $field_desc ) ? true : false;

		// Format setting outer wrapper.
		echo '<div class="format-setting type-sidebar-select ' . ( $has_desc ? 'has-desc' : 'no-desc' ) . '">';

		// Description.
		echo $has_desc ? '<div class="description">' . wp_kses_post( htmlspecialchars_decode( $field_desc ) ) . '</div>' : '';

		// Format setting inner wrapper.
		echo '<div class="format-setting-inner">';

		// Build page select.
		echo '<select name="' . esc_attr( $field_name ) . '" id="' . esc_attr( $field_id ) . '" class="option-builder-ui-select ' . esc_attr( $field_class ) . '">';

		// Get the registered sidebars.
		global $wp_registered_sidebars;

		$sidebars = array();
		foreach ( $wp_registered_sidebars as $id => $sidebar ) {
			$sidebars[ $id ] = $sidebar['name'];
		}

		// Filters to restrict which sidebars are allowed to be selected, for example we can restrict footer sidebars to be selectable on a blog page.
		$sidebars = apply_filters( 'opb_recognized_sidebars', $sidebars );
		$sidebars = apply_filters( 'opb_recognized_sidebars_' . $field_id, $sidebars );

		// Has sidebars.
		if ( count( $sidebars ) ) {
			echo '<option value="">-- ' . esc_html__( 'Choose Sidebar', 'option-builder' ) . ' --</option>';
			foreach ( $sidebars as $id => $sidebar ) {
				echo '<option value="' . esc_attr( $id ) . '" ' . selected( $field_value, $id, false ) . '>' . esc_attr( $sidebar ) . '</option>';
			}
		} else {
			echo '<option value="">' . esc_html__( 'No Sidebars', 'option-builder' ) . '</option>';
		}

		echo '</select>';

		echo '</div>';

		echo '</div>';
	}

	/**
	 * List Item option type.
	 *
	 * See @display_by_type to see the full list of available arguments.
	 *
	 * @param array $args An array of arguments.
	 *
	 * @access public
	 * @since 1.0.0
	 */
	public function type_slider( $args = array() ) {

		// Turns arguments array into variables.
		extract( $args ); // phpcs:ignore

		// Verify a description.
		$has_desc = ! empty( $field_desc ) ? true : false;

		// Format setting outer wrapper.
		echo '<div class="format-setting type-slider ' . ( $has_desc ? 'has-desc' : 'no-desc' ) . '">';

		// Description.
		echo $has_desc ? '<div class="description">' . wp_kses_post( htmlspecialchars_decode( $field_desc ) ) . '</div>' : '';

		// Format setting inner wrapper.
		echo '<div class="format-setting-inner">';

		// Pass the settings array around.
		echo '<input type="hidden" name="' . esc_attr( $field_id ) . '_settings_array" id="' . esc_attr( $field_id ) . '_settings_array" value="' . esc_attr( Utils::encode( $field_settings ) ) . '" />';

		/**
		 * Settings pages have array wrappers like 'option_builder'.
		 * So we need that value to create a proper array to save to.
		 * This is only for NON metabox settings.
		 */
		if ( ! isset( $get_option ) ) {
			$get_option = '';
		}

		// Build list items.
		echo '<ul class="option-builder-setting-wrap option-builder-sortable" data-name="' . esc_attr( $field_id ) . '" data-id="' . esc_attr( $post_id ) . '" data-get-option="' . esc_attr( $get_option ) . '" data-type="' . esc_attr( $type ) . '">';

		if ( is_array( $field_value ) && ! empty( $field_value ) ) {

			foreach ( $field_value as $key => $list_item ) {

				echo '<li class="ui-state-default list-list-item">';
				Utils::list_item_view( $field_id, $key, $list_item, $post_id, $get_option, $field_settings, $type );
				echo '</li>';
			}
		}

		echo '</ul>';

		// Button.
		echo '<a href="javascript:void(0);" class="option-builder-list-item-add option-builder-ui-button button button-primary right hug-right" title="' . esc_html__( 'Add New', 'option-builder' ) . '">' . esc_html__( 'Add New', 'option-builder' ) . '</a>'; // phpcs:ignore

		// Description.
		echo '<div class="list-item-description">' . esc_html__( 'You can re-order with drag & drop, the order will update after saving.', 'option-builder' ) . '</div>';

		echo '</div>';

		echo '</div>';
	}


	/**
	 * Social Links option type.
	 *
	 * See @display_by_type to see the full list of available arguments.
	 *
	 * @param array $args An array of arguments.
	 *
	 * @access public
	 * @since 1.0.0
	 */
	public function type_social_links( $args = array() ) {

		// Turns arguments array into variables.
		extract( $args ); // phpcs:ignore

		// Load the default social links.
		if ( empty( $field_value ) && apply_filters( 'opb_type_social_links_load_defaults', true, $field_id ) ) {

			$field_value = apply_filters(
				'opb_type_social_links_defaults',
				array(
					array(
						'name'  => __( 'Facebook', 'option-builder' ),
						'title' => '',
						'href'  => '',
					),
					array(
						'name'  => __( 'Twitter', 'option-builder' ),
						'title' => '',
						'href'  => '',
					),
					array(
						'name'  => __( 'Google+', 'option-builder' ),
						'title' => '',
						'href'  => '',
					),
					array(
						'name'  => __( 'LinkedIn', 'option-builder' ),
						'title' => '',
						'href'  => '',
					),
					array(
						'name'  => __( 'Pinterest', 'option-builder' ),
						'title' => '',
						'href'  => '',
					),
					array(
						'name'  => __( 'Youtube', 'option-builder' ),
						'title' => '',
						'href'  => '',
					),
					array(
						'name'  => __( 'Dribbble', 'option-builder' ),
						'title' => '',
						'href'  => '',
					),
					array(
						'name'  => __( 'Github', 'option-builder' ),
						'title' => '',
						'href'  => '',
					),
					array(
						'name'  => __( 'Forrst', 'option-builder' ),
						'title' => '',
						'href'  => '',
					),
					array(
						'name'  => __( 'Digg', 'option-builder' ),
						'title' => '',
						'href'  => '',
					),
					array(
						'name'  => __( 'Delicious', 'option-builder' ),
						'title' => '',
						'href'  => '',
					),
					array(
						'name'  => __( 'Tumblr', 'option-builder' ),
						'title' => '',
						'href'  => '',
					),
					array(
						'name'  => __( 'Skype', 'option-builder' ),
						'title' => '',
						'href'  => '',
					),
					array(
						'name'  => __( 'SoundCloud', 'option-builder' ),
						'title' => '',
						'href'  => '',
					),
					array(
						'name'  => __( 'Vimeo', 'option-builder' ),
						'title' => '',
						'href'  => '',
					),
					array(
						'name'  => __( 'Flickr', 'option-builder' ),
						'title' => '',
						'href'  => '',
					),
					array(
						'name'  => __( 'VK.com', 'option-builder' ),
						'title' => '',
						'href'  => '',
					),
				),
				$field_id
			);

		}

		// Verify a description.
		$has_desc = ! empty( $field_desc ) ? true : false;

		// Format setting outer wrapper.
		echo '<div class="format-setting type-social-list-item ' . ( $has_desc ? 'has-desc' : 'no-desc' ) . '">';

		// Description.
		echo $has_desc ? '<div class="description">' . wp_kses_post( htmlspecialchars_decode( $field_desc ) ) . '</div>' : '';

		// Format setting inner wrapper.
		echo '<div class="format-setting-inner">';

		// Pass the settings array around.
		echo '<input type="hidden" name="' . esc_attr( $field_id ) . '_settings_array" id="' . esc_attr( $field_id ) . '_settings_array" value="' . esc_attr( Utils::encode( $field_settings ) ) . '" />';

		/**
		 * Settings pages have array wrappers like 'option_builder'.
		 * So we need that value to create a proper array to save to.
		 * This is only for NON metabox settings.
		 */
		if ( ! isset( $get_option ) ) {
			$get_option = '';
		}

		// Build list items.
		echo '<ul class="option-builder-setting-wrap option-builder-sortable" data-name="' . esc_attr( $field_id ) . '" data-id="' . esc_attr( $post_id ) . '" data-get-option="' . esc_attr( $get_option ) . '" data-type="' . esc_attr( $type ) . '">';

		if ( is_array( $field_value ) && ! empty( $field_value ) ) {

			foreach ( $field_value as $key => $link ) {

				echo '<li class="ui-state-default list-list-item">';
				Utils::social_links_view( $field_id, $key, $link, $post_id, $get_option, $field_settings );
				echo '</li>';
			}
		}

		echo '</ul>';

		// Button.
		echo '<a href="javascript:void(0);" class="option-builder-social-links-add option-builder-ui-button button button-primary right hug-right" title="' . esc_html__( 'Add New', 'option-builder' ) . '">' . esc_html__( 'Add New', 'option-builder' ) . '</a>'; // phpcs:ignore

		// Description.
		echo '<div class="list-item-description">' . esc_html( apply_filters( 'opb_social_links_description', __( 'You can re-order with drag & drop, the order will update after saving.', 'option-builder' ), $field_id ) ) . '</div>';

		echo '</div>';

		echo '</div>';
	}

	/**
	 * Spacing Option Type.
	 *
	 * See @display_by_type to see the full list of available arguments.
	 *
	 * @param array $args An array of arguments.
	 *
	 * @access public
	 * @since 1.0.0
	 */
	public function type_spacing( $args = array() ) {

		// Turns arguments array into variables.
		extract( $args ); // phpcs:ignore

		// Verify a description.
		$has_desc = ! empty( $field_desc ) ? true : false;

		// Format setting outer wrapper.
		echo '<div class="format-setting type-spacing ' . ( $has_desc ? 'has-desc' : 'no-desc' ) . '">';

		// Description.
		echo $has_desc ? '<div class="description">' . wp_kses_post( htmlspecialchars_decode( $field_desc ) ) . '</div>' : '';

		// Format setting inner wrapper.
		echo '<div class="format-setting-inner">';

		// Allow fields to be filtered.
		$opb_recognized_spacing_fields = apply_filters(
			'opb_recognized_spacing_fields',
			array(
				'top',
				'right',
				'bottom',
				'left',
				'unit',
			),
			$field_id
		);

		// Build top spacing.
		if ( in_array( 'top', $opb_recognized_spacing_fields, true ) ) {

			$top = isset( $field_value['top'] ) ? $field_value['top'] : '';

			echo '<div class="opb-option-group"><span class="opb-icon-arrow-up opb-option-group--icon"></span><input type="text" name="' . esc_attr( $field_name ) . '[top]" id="' . esc_attr( $field_id ) . '-top" value="' . esc_attr( $top ) . '" class="widefat option-builder-ui-input ' . esc_attr( $field_class ) . '" placeholder="' . esc_html__( 'top', 'option-builder' ) . '" /></div>';
		}

		// Build right spacing.
		if ( in_array( 'right', $opb_recognized_spacing_fields, true ) ) {

			$right = isset( $field_value['right'] ) ? $field_value['right'] : '';

			echo '<div class="opb-option-group"><span class="opb-icon-arrow-right opb-option-group--icon"></span></span><input type="text" name="' . esc_attr( $field_name ) . '[right]" id="' . esc_attr( $field_id ) . '-right" value="' . esc_attr( $right ) . '" class="widefat option-builder-ui-input ' . esc_attr( $field_class ) . '" placeholder="' . esc_html__( 'right', 'option-builder' ) . '" /></div>';
		}

		// Build bottom spacing.
		if ( in_array( 'bottom', $opb_recognized_spacing_fields, true ) ) {

			$bottom = isset( $field_value['bottom'] ) ? $field_value['bottom'] : '';

			echo '<div class="opb-option-group"><span class="opb-icon-arrow-down opb-option-group--icon"></span><input type="text" name="' . esc_attr( $field_name ) . '[bottom]" id="' . esc_attr( $field_id ) . '-bottom" value="' . esc_attr( $bottom ) . '" class="widefat option-builder-ui-input ' . esc_attr( $field_class ) . '" placeholder="' . esc_html__( 'bottom', 'option-builder' ) . '" /></div>';
		}

		// Build left spacing.
		if ( in_array( 'left', $opb_recognized_spacing_fields, true ) ) {

			$left = isset( $field_value['left'] ) ? $field_value['left'] : '';

			echo '<div class="opb-option-group"><span class="opb-icon-arrow-left opb-option-group--icon"></span><input type="text" name="' . esc_attr( $field_name ) . '[left]" id="' . esc_attr( $field_id ) . '-left" value="' . esc_attr( $left ) . '" class="widefat option-builder-ui-input ' . esc_attr( $field_class ) . '" placeholder="' . esc_html__( 'left', 'option-builder' ) . '" /></div>';
		}

		// Build unit dropdown.
		if ( in_array( 'unit', $opb_recognized_spacing_fields, true ) ) {

			echo '<div class="opb-option-group opb-option-group--is-last">';

			echo '<select name="' . esc_attr( $field_name ) . '[unit]" id="' . esc_attr( $field_id ) . '-unit" class="option-builder-ui-select ' . esc_attr( $field_class ) . '">';

			echo '<option value="">' . esc_html__( 'unit', 'option-builder' ) . '</option>';

			foreach ( Utils::recognized_spacing_unit_types( $field_id ) as $unit ) {
				echo '<option value="' . esc_attr( $unit ) . '"' . ( isset( $field_value['unit'] ) ? selected( $field_value['unit'], $unit, false ) : '' ) . '>' . esc_attr( $unit ) . '</option>';
			}

			echo '</select>';

			echo '</div>';
		}

		echo '</div>';

		echo '</div>';
	}

	/**
	 * Tab option type.
	 *
	 * See @display_by_type to see the full list of available arguments.
	 *
	 * @access public
	 * @since 1.0.0
	 */
	public function type_tab() {
		echo '<div class="format-setting type-tab"><br /></div>';
	}


	/**
	 * Tag Checkbox option type.
	 *
	 * See @display_by_type to see the full list of available arguments.
	 *
	 * @param array $args An array of arguments.
	 *
	 * @access public
	 * @since 1.0.0
	 */
	public function type_tag_checkbox( $args = array() ) {

		// Turns arguments array into variables.
		extract( $args ); // phpcs:ignore

		// Verify a description.
		$has_desc = ! empty( $field_desc ) ? true : false;

		// Format setting outer wrapper.
		echo '<div class="format-setting type-tag-checkbox type-checkbox ' . ( $has_desc ? 'has-desc' : 'no-desc' ) . '">';

		// Description.
		echo $has_desc ? '<div class="description">' . wp_kses_post( htmlspecialchars_decode( $field_desc ) ) . '</div>' : '';

		// Format setting inner wrapper.
		echo '<div class="format-setting-inner">';

		// Get tags.
		$tags = get_tags( array( 'hide_empty' => false ) );

		// Has tags.
		if ( $tags ) {
			foreach ( $tags as $tag ) {
				echo '<p>';
				echo '<input type="checkbox" name="' . esc_attr( $field_name ) . '[' . esc_attr( $tag->term_id ) . ']" id="' . esc_attr( $field_id ) . '-' . esc_attr( $tag->term_id ) . '" value="' . esc_attr( $tag->term_id ) . '" ' . ( isset( $field_value[ $tag->term_id ] ) ? checked( $field_value[ $tag->term_id ], $tag->term_id, false ) : '' ) . ' class="option-builder-ui-checkbox ' . esc_attr( $field_class ) . '" />';
				echo '<label for="' . esc_attr( $field_id ) . '-' . esc_attr( $tag->term_id ) . '">' . esc_attr( $tag->name ) . '</label>';
				echo '</p>';
			}
		} else {
			echo '<p>' . esc_html__( 'No Tags Found', 'option-builder' ) . '</p>';
		}

		echo '</div>';

		echo '</div>';
	}


	/**
	 * Tag Select option type.
	 *
	 * See @display_by_type to see the full list of available arguments.
	 *
	 * @param array $args An array of arguments.
	 *
	 * @access public
	 * @since 1.0.0
	 */
	public function type_tag_select( $args = array() ) {

		// Turns arguments array into variables.
		extract( $args ); // phpcs:ignore

		// Verify a description.
		$has_desc = ! empty( $field_desc ) ? true : false;

		// Format setting outer wrapper.
		echo '<div class="format-setting type-tag-select ' . ( $has_desc ? 'has-desc' : 'no-desc' ) . '">';

		// Description.
		echo $has_desc ? '<div class="description">' . wp_kses_post( htmlspecialchars_decode( $field_desc ) ) . '</div>' : '';

		// Format setting inner wrapper.
		echo '<div class="format-setting-inner">';

		// Build tag select.
		echo '<select name="' . esc_attr( $field_name ) . '" id="' . esc_attr( $field_id ) . '" class="option-builder-ui-select ' . esc_attr( $field_class ) . '">';

		// Get tags.
		$tags = get_tags( array( 'hide_empty' => false ) );

		// Has tags.
		if ( $tags ) {
			echo '<option value="">-- ' . esc_html__( 'Choose One', 'option-builder' ) . ' --</option>';
			foreach ( $tags as $tag ) {
				echo '<option value="' . esc_attr( $tag->term_id ) . '"' . selected( $field_value, $tag->term_id, false ) . '>' . esc_attr( $tag->name ) . '</option>';
			}
		} else {
			echo '<option value="">' . esc_html__( 'No Tags Found', 'option-builder' ) . '</option>';
		}

		echo '</select>';

		echo '</div>';

		echo '</div>';
	}


	/**
	 * Taxonomy Checkbox option type.
	 *
	 * See @display_by_type to see the full list of available arguments.
	 *
	 * @param array $args An array of arguments.
	 *
	 * @access public
	 * @since 1.0.0
	 */
	public function type_taxonomy_checkbox( $args = array() ) {

		// Turns arguments array into variables.
		extract( $args ); // phpcs:ignore

		// Verify a description.
		$has_desc = ! empty( $field_desc ) ? true : false;

		// Format setting outer wrapper.
		echo '<div class="format-setting type-taxonomy-checkbox type-checkbox ' . ( $has_desc ? 'has-desc' : 'no-desc' ) . '">';

		// Description.
		echo $has_desc ? '<div class="description">' . wp_kses_post( htmlspecialchars_decode( $field_desc ) ) . '</div>' : '';

		// Format setting inner wrapper.
		echo '<div class="format-setting-inner">';

		// Setup the taxonomy.
		$taxonomy = isset( $field_taxonomy ) ? explode( ',', $field_taxonomy ) : array( 'category' );

		// Get taxonomies.
		$taxonomies = get_categories(
			apply_filters(
				'opb_type_taxonomy_checkbox_query',
				array(
					'hide_empty' => false,
					'taxonomy'   => $taxonomy,
				),
				$field_id
			)
		);

		// Has tags.
		if ( $taxonomies ) {
			foreach ( $taxonomies as $taxonomy ) {
				echo '<p>';
				echo '<input type="checkbox" name="' . esc_attr( $field_name ) . '[' . esc_attr( $taxonomy->term_id ) . ']" id="' . esc_attr( $field_id ) . '-' . esc_attr( $taxonomy->term_id ) . '" value="' . esc_attr( $taxonomy->term_id ) . '" ' . ( isset( $field_value[ $taxonomy->term_id ] ) ? checked( $field_value[ $taxonomy->term_id ], $taxonomy->term_id, false ) : '' ) . ' class="option-builder-ui-checkbox ' . esc_attr( $field_class ) . '" />';
				echo '<label for="' . esc_attr( $field_id ) . '-' . esc_attr( $taxonomy->term_id ) . '">' . esc_attr( $taxonomy->name ) . '</label>';
				echo '</p>';
			}
		} else {
			echo '<p>' . esc_html__( 'No Taxonomies Found', 'option-builder' ) . '</p>';
		}

		echo '</div>';

		echo '</div>';
	}


	/**
	 * Taxonomy Select option type.
	 *
	 * See @display_by_type to see the full list of available arguments.
	 *
	 * @param array $args An array of arguments.
	 *
	 * @access public
	 * @since 1.0.0
	 */
	public function type_taxonomy_select( $args = array() ) {

		// Turns arguments array into variables.
		extract( $args ); // phpcs:ignore

		// Verify a description.
		$has_desc = ! empty( $field_desc ) ? true : false;

		// Format setting outer wrapper.
		echo '<div class="format-setting type-tag-select ' . ( $has_desc ? 'has-desc' : 'no-desc' ) . '">';

		// Description.
		echo $has_desc ? '<div class="description">' . wp_kses_post( htmlspecialchars_decode( $field_desc ) ) . '</div>' : '';

		// Format setting inner wrapper.
		echo '<div class="format-setting-inner">';

		// Build tag select.
		echo '<select name="' . esc_attr( $field_name ) . '" id="' . esc_attr( $field_id ) . '" class="option-builder-ui-select ' . esc_attr( $field_class ) . '">';

		// Setup the taxonomy.
		$taxonomy = isset( $field_taxonomy ) ? explode( ',', $field_taxonomy ) : array( 'category' );

		// Get taxonomies.
		$taxonomies = get_categories(
			apply_filters(
				'opb_type_taxonomy_select_query',
				array(
					'hide_empty' => false,
					'taxonomy'   => $taxonomy,
				),
				$field_id
			)
		);

		// Has tags.
		if ( $taxonomies ) {
			echo '<option value="">-- ' . esc_html__( 'Choose One', 'option-builder' ) . ' --</option>';
			foreach ( $taxonomies as $taxonomy ) {
				echo '<option value="' . esc_attr( $taxonomy->term_id ) . '"' . selected( $field_value, $taxonomy->term_id, false ) . '>' . esc_attr( $taxonomy->name ) . '</option>';
			}
		} else {
			echo '<option value="">' . esc_html__( 'No Taxonomies Found', 'option-builder' ) . '</option>';
		}

		echo '</select>';

		echo '</div>';

		echo '</div>';
	}


	/**
	 * Text option type.
	 *
	 * See @display_by_type to see the full list of available arguments.
	 *
	 * @param array $args An array of arguments.
	 *
	 * @access public
	 * @since 1.0.0
	 */
	public function type_text( $args = array() ) {

		// Turns arguments array into variables.
		extract( $args ); // phpcs:ignore

		// Verify a description.
		$has_desc = ! empty( $field_desc ) ? true : false;

		// Format setting outer wrapper.
		echo '<div class="format-setting type-text ' . ( $has_desc ? 'has-desc' : 'no-desc' ) . '">';

		// Description.
		echo $has_desc ? '<div class="description">' . wp_kses_post( htmlspecialchars_decode( $field_desc ) ) . '</div>' : '';

		// Format setting inner wrapper.
		echo '<div class="format-setting-inner">';

		// Build text input.
		echo '<input type="text" name="' . esc_attr( $field_name ) . '" id="' . esc_attr( $field_id ) . '" value="' . esc_attr( $field_value ) . '" class="widefat option-builder-ui-input ' . esc_attr( $field_class ) . '" />';

		echo '</div>';

		echo '</div>';
	}


	/**
	 * Textarea option type.
	 *
	 * See @display_by_type to see the full list of available arguments.
	 *
	 * @param array $args An array of arguments.
	 *
	 * @access public
	 * @since 1.0.0
	 */
	public function type_textarea( $args = array() ) {

		// Turns arguments array into variables.
		extract( $args ); // phpcs:ignore

		// Verify a description.
		$has_desc = ! empty( $field_desc ) ? true : false;

		// Format setting outer wrapper.
		echo '<div class="format-setting type-textarea ' . ( $has_desc ? 'has-desc' : 'no-desc' ) . ' fill-area">';

		// Description.
		echo $has_desc ? '<div class="description">' . wp_kses_post( htmlspecialchars_decode( $field_desc ) ) . '</div>' : '';

		// Format setting inner wrapper.
		echo '<div class="format-setting-inner">';

		// Build textarea.
		wp_editor(
			$field_value,
			esc_attr( $field_id ),
			array(
				'editor_class'  => esc_attr( $field_class ),
				'wpautop'       => apply_filters( 'opb_wpautop', false, $field_id ),
				'media_buttons' => apply_filters( 'opb_media_buttons', true, $field_id ),
				'textarea_name' => esc_attr( $field_name ),
				'textarea_rows' => esc_attr( $field_rows ),
				'tinymce'       => apply_filters( 'opb_tinymce', true, $field_id ),
				'quicktags'     => apply_filters( 'opb_quicktags', array( 'buttons' => 'strong,em,link,block,del,ins,img,ul,ol,li,code,spell,close' ), $field_id ),
			)
		);

		echo '</div>';

		echo '</div>';
	}


	/**
	 * Textarea Simple option type.
	 *
	 * See @display_by_type to see the full list of available arguments.
	 *
	 * @param array $args An array of arguments.
	 *
	 * @access public
	 * @since 1.0.0
	 */
	public function type_textarea_simple( $args = array() ) {

		// Turns arguments array into variables.
		extract( $args ); // phpcs:ignore

		// Verify a description.
		$has_desc = ! empty( $field_desc ) ? true : false;

		// Format setting outer wrapper.
		echo '<div class="format-setting type-textarea simple ' . ( $has_desc ? 'has-desc' : 'no-desc' ) . '">';

		// Description.
		echo $has_desc ? '<div class="description">' . wp_kses_post( htmlspecialchars_decode( $field_desc ) ) . '</div>' : '';

		// Format setting inner wrapper.
		echo '<div class="format-setting-inner">';

		// Filter to allow wpautop.
		$wpautop = apply_filters( 'opb_wpautop', false, $field_id );

		// Wpautop $field_value.
		if ( true === $wpautop ) {
			$field_value = wpautop( $field_value );
		}

		// Build textarea simple.
		echo '<textarea class="textarea ' . esc_attr( $field_class ) . '" rows="' . esc_attr( $field_rows ) . '" cols="40" name="' . esc_attr( $field_name ) . '" id="' . esc_attr( $field_id ) . '">' . esc_textarea( $field_value ) . '</textarea>';

		echo '</div>';

		echo '</div>';
	}


	/**
	 * Textblock option type.
	 *
	 * See @display_by_type to see the full list of available arguments.
	 *
	 * @param array $args An array of arguments.
	 *
	 * @access public
	 * @since 1.0.0
	 */
	public function type_textblock( $args = array() ) {

		// Turns arguments array into variables.
		extract( $args ); // phpcs:ignore

		// Format setting outer wrapper.
		echo '<div class="format-setting type-textblock wide-desc">';

		// Description.
		echo '<div class="description">' . wp_kses_post( htmlspecialchars_decode( $field_desc ) ) . '</div>';

		echo '</div>';
	}


	/**
	 * Textblock Titled option type.
	 *
	 * See @display_by_type to see the full list of available arguments.
	 *
	 * @param array $args An array of arguments.
	 *
	 * @access public
	 * @since 1.0.0
	 */
	public function type_textblock_titled( $args = array() ) {

		// Turns arguments array into variables.
		extract( $args ); // phpcs:ignore

		// Format setting outer wrapper.
		echo '<div class="format-setting type-textblock titled wide-desc">';

		// Description.
		echo '<div class="description">' . wp_kses_post( htmlspecialchars_decode( $field_desc ) ) . '</div>';

		echo '</div>';
	}


	/**
	 * Typography option type.
	 *
	 * See @display_by_type to see the full list of available arguments.
	 *
	 * @param array $args An array of arguments.
	 *
	 * @access public
	 * @since 1.0.0
	 */
	public function type_typography( $args = array() ) {

		// Turns arguments array into variables.
		extract( $args ); // phpcs:ignore

		// Verify a description.
		$has_desc = ! empty( $field_desc ) ? true : false;

		// Format setting outer wrapper.
		echo '<div class="format-setting type-typography ' . ( $has_desc ? 'has-desc' : 'no-desc' ) . '">';

		// Description.
		echo $has_desc ? '<div class="description">' . wp_kses_post( htmlspecialchars_decode( $field_desc ) ) . '</div>' : '';

		// Format setting inner wrapper.
		echo '<div class="format-setting-inner">';

		// Allow fields to be filtered.
		$opb_recognized_typography_fields = apply_filters(
			'opb_recognized_typography_fields',
			array(
				'font-color',
				'font-family',
				'font-size',
				'font-style',
				'font-variant',
				'font-weight',
				'letter-spacing',
				'line-height',
				'text-decoration',
				'text-transform',
			),
			$field_id
		);

		// Build font color.
		if ( in_array( 'font-color', $opb_recognized_typography_fields, true ) ) {

			// Build colorpicker.
			echo '<div class="option-builder-ui-colorpicker-input-wrap">';

			// Colorpicker JS.
			echo '<script>jQuery(document).ready(function($) { OPB_UI.bind_colorpicker("' . esc_attr( $field_id ) . '-picker"); });</script>';

			// Set background color.
			$background_color = isset( $field_value['font-color'] ) ? esc_attr( $field_value['font-color'] ) : '';

			/* input */
			echo '<input type="text" name="' . esc_attr( $field_name ) . '[font-color]" id="' . esc_attr( $field_id ) . '-picker" value="' . esc_attr( $background_color ) . '" class="hide-color-picker ' . esc_attr( $field_class ) . '" />';

			echo '</div>';
		}

		// Build font family.
		if ( in_array( 'font-family', $opb_recognized_typography_fields, true ) ) {
			$font_family = isset( $field_value['font-family'] ) ? $field_value['font-family'] : '';
			echo '<select name="' . esc_attr( $field_name ) . '[font-family]" id="' . esc_attr( $field_id ) . '-font-family" class="option-builder-ui-select ' . esc_attr( $field_class ) . '">';
			echo '<option value="">font-family</option>';
			foreach ( Utils::recognized_font_families( $field_id ) as $key => $value ) {
				echo '<option value="' . esc_attr( $key ) . '" ' . selected( $font_family, $key, false ) . '>' . esc_attr( $value ) . '</option>';
			}
			echo '</select>';
		}

		// Build font size.
		if ( in_array( 'font-size', $opb_recognized_typography_fields, true ) ) {
			$font_size = isset( $field_value['font-size'] ) ? esc_attr( $field_value['font-size'] ) : '';
			echo '<select name="' . esc_attr( $field_name ) . '[font-size]" id="' . esc_attr( $field_id ) . '-font-size" class="option-builder-ui-select ' . esc_attr( $field_class ) . '">';
			echo '<option value="">font-size</option>';
			foreach ( Utils::recognized_font_sizes( $field_id ) as $option ) {
				echo '<option value="' . esc_attr( $option ) . '" ' . selected( $font_size, $option, false ) . '>' . esc_attr( $option ) . '</option>';
			}
			echo '</select>';
		}

		// Build font style.
		if ( in_array( 'font-style', $opb_recognized_typography_fields, true ) ) {
			$font_style = isset( $field_value['font-style'] ) ? esc_attr( $field_value['font-style'] ) : '';
			echo '<select name="' . esc_attr( $field_name ) . '[font-style]" id="' . esc_attr( $field_id ) . '-font-style" class="option-builder-ui-select ' . esc_attr( $field_class ) . '">';
			echo '<option value="">font-style</option>';
			foreach ( Utils::recognized_font_styles( $field_id ) as $key => $value ) {
				echo '<option value="' . esc_attr( $key ) . '" ' . selected( $font_style, $key, false ) . '>' . esc_attr( $value ) . '</option>';
			}
			echo '</select>';
		}

		// Build font variant.
		if ( in_array( 'font-variant', $opb_recognized_typography_fields, true ) ) {
			$font_variant = isset( $field_value['font-variant'] ) ? esc_attr( $field_value['font-variant'] ) : '';
			echo '<select name="' . esc_attr( $field_name ) . '[font-variant]" id="' . esc_attr( $field_id ) . '-font-variant" class="option-builder-ui-select ' . esc_attr( $field_class ) . '">';
			echo '<option value="">font-variant</option>';
			foreach ( Utils::recognized_font_variants( $field_id ) as $key => $value ) {
				echo '<option value="' . esc_attr( $key ) . '" ' . selected( $font_variant, $key, false ) . '>' . esc_attr( $value ) . '</option>';
			}
			echo '</select>';
		}

		// Build font weight.
		if ( in_array( 'font-weight', $opb_recognized_typography_fields, true ) ) {
			$font_weight = isset( $field_value['font-weight'] ) ? esc_attr( $field_value['font-weight'] ) : '';
			echo '<select name="' . esc_attr( $field_name ) . '[font-weight]" id="' . esc_attr( $field_id ) . '-font-weight" class="option-builder-ui-select ' . esc_attr( $field_class ) . '">';
			echo '<option value="">font-weight</option>';
			foreach ( Utils::recognized_font_weights( $field_id ) as $key => $value ) {
				echo '<option value="' . esc_attr( $key ) . '" ' . selected( $font_weight, $key, false ) . '>' . esc_attr( $value ) . '</option>';
			}
			echo '</select>';
		}

		// Build letter spacing.
		if ( in_array( 'letter-spacing', $opb_recognized_typography_fields, true ) ) {
			$letter_spacing = isset( $field_value['letter-spacing'] ) ? esc_attr( $field_value['letter-spacing'] ) : '';
			echo '<select name="' . esc_attr( $field_name ) . '[letter-spacing]" id="' . esc_attr( $field_id ) . '-letter-spacing" class="option-builder-ui-select ' . esc_attr( $field_class ) . '">';
			echo '<option value="">letter-spacing</option>';
			foreach ( Utils::recognized_letter_spacing( $field_id ) as $option ) {
				echo '<option value="' . esc_attr( $option ) . '" ' . selected( $letter_spacing, $option, false ) . '>' . esc_attr( $option ) . '</option>';
			}
			echo '</select>';
		}

		// Build line height.
		if ( in_array( 'line-height', $opb_recognized_typography_fields, true ) ) {
			$line_height = isset( $field_value['line-height'] ) ? esc_attr( $field_value['line-height'] ) : '';
			echo '<select name="' . esc_attr( $field_name ) . '[line-height]" id="' . esc_attr( $field_id ) . '-line-height" class="option-builder-ui-select ' . esc_attr( $field_class ) . '">';
			echo '<option value="">line-height</option>';
			foreach ( Utils::recognized_line_heights( $field_id ) as $option ) {
				echo '<option value="' . esc_attr( $option ) . '" ' . selected( $line_height, $option, false ) . '>' . esc_attr( $option ) . '</option>';
			}
			echo '</select>';
		}

		// Build text decoration.
		if ( in_array( 'text-decoration', $opb_recognized_typography_fields, true ) ) {
			$text_decoration = isset( $field_value['text-decoration'] ) ? esc_attr( $field_value['text-decoration'] ) : '';
			echo '<select name="' . esc_attr( $field_name ) . '[text-decoration]" id="' . esc_attr( $field_id ) . '-text-decoration" class="option-builder-ui-select ' . esc_attr( $field_class ) . '">';
			echo '<option value="">text-decoration</option>';
			foreach ( Utils::recognized_text_decorations( $field_id ) as $key => $value ) {
				echo '<option value="' . esc_attr( $key ) . '" ' . selected( $text_decoration, $key, false ) . '>' . esc_attr( $value ) . '</option>';
			}
			echo '</select>';
		}

		// Build text transform.
		if ( in_array( 'text-transform', $opb_recognized_typography_fields, true ) ) {
			$text_transform = isset( $field_value['text-transform'] ) ? esc_attr( $field_value['text-transform'] ) : '';
			echo '<select name="' . esc_attr( $field_name ) . '[text-transform]" id="' . esc_attr( $field_id ) . '-text-transform" class="option-builder-ui-select ' . esc_attr( $field_class ) . '">';
			echo '<option value="">text-transform</option>';
			foreach ( Utils::recognized_text_transformations( $field_id ) as $key => $value ) {
				echo '<option value="' . esc_attr( $key ) . '" ' . selected( $text_transform, $key, false ) . '>' . esc_attr( $value ) . '</option>';
			}
			echo '</select>';
		}

		echo '</div>';

		echo '</div>';

	}


	/**
	 * Upload option type.
	 *
	 * See @display_by_type to see the full list of available arguments.
	 *
	 * @param array $args An array of arguments.
	 *
	 * @access public
	 * @since 1.0.0
	 */
	public function type_upload( $args = array() ) {

		// Turns arguments array into variables.
		extract( $args ); // phpcs:ignore

		// Verify a description.
		$has_desc = ! empty( $field_desc ) ? true : false;

		// If an attachment ID is stored here fetch its URL and replace the value.
		if ( $field_value && wp_attachment_is_image( $field_value ) ) {

			$attachment_data = wp_get_attachment_image_src( $field_value, 'original' );

			// Check for attachment data.
			if ( $attachment_data ) {

				$field_src = $attachment_data[0];
			}
		}

		// Format setting outer wrapper.
		echo '<div class="format-setting type-upload ' . ( $has_desc ? 'has-desc' : 'no-desc' ) . '">';

		// Description.
		echo $has_desc ? '<div class="description">' . wp_kses_post( htmlspecialchars_decode( $field_desc ) ) . '</div>' : ''; // phpcs:ignore

		// Format setting inner wrapper.
		echo '<div class="format-setting-inner">';

		// Build upload.
		echo '<div class="option-builder-ui-upload-parent">';

		echo '<input type="text" name="' . esc_attr( $field_name ) . '" id="' . esc_attr( $field_id ) . '" value="' . esc_attr( $field_value ) . '" class="widefat option-builder-ui-upload-input ' . esc_attr( $field_class ) . '" />';

		// Add media button.
		echo '<a href="javascript:void(0);" class="opb_upload_media option-builder-ui-button button button-primary light" rel="' . esc_attr( $post_id ) . '" title="' . esc_html__( 'Add Media', 'option-builder' ) . '"><span class="icon opb-icon-plus-circle"></span>' . esc_html__( 'Add Media', 'option-builder' ) . '</a>'; // phpcs:ignore

		echo '</div>';

		// Media.
		if ( $field_value ) {

			echo '<div class="option-builder-ui-media-wrap" id="' . esc_attr( $field_id ) . '_media">';

			// Replace image src.
			if ( isset( $field_src ) ) {
				$field_value = $field_src;
			}

			if ( preg_match( '/\.(?:jpe?g|png|gif|ico)$/i', $field_value ) ) {
				echo '<div class="option-builder-ui-image-wrap"><img src="' . esc_url( $field_value ) . '" alt="" /></div>';
			}

			echo '<a href="javascript:(void);" class="option-builder-ui-remove-media option-builder-ui-button button button-secondary light" title="' . esc_html__( 'Remove Media', 'option-builder' ) . '"><span class="icon opb-icon-minus-circle"></span>' . esc_html__( 'Remove Media', 'option-builder' ) . '</a>';

			echo '</div>';

		}

		echo '</div>';

		echo '</div>';
	}


	/**
	 * Modify Layouts option type.
	 *
	 * @access public
	 * @since 1.0.0
	 */
	public function type_modify_layouts() {

		echo '<form method="post" id="option-builder-settings-form">';

		// Form nonce.
		wp_nonce_field( 'option_builder_modify_layouts_form', 'option_builder_modify_layouts_nonce' );

		// Format setting outer wrapper.
		echo '<div class="format-setting type-textarea has-desc">';

		// Description.
		echo '<div class="description">';

		echo '<p>' . esc_html__( 'To add a new layout enter a unique lower case alphanumeric string (dashes allowed) in the text field and click "Save Layouts".', 'option-builder' ) . '</p>';
		echo '<p>' . esc_html__( 'As well, you can activate, remove, and drag & drop the order; all situations require you to click "Save Layouts" for the changes to be applied.', 'option-builder' ) . '</p>';
		echo '<p>' . esc_html__( 'When you create a new layout it will become active and any changes made to the Theme Options will be applied to it. If you switch back to a different layout immediately after creating a new layout that new layout will have a snapshot of the current Theme Options data attached to it.', 'option-builder' ) . '</p>';

		/* translators: %s: visual path to layouts overview */
		$string = esc_html__( 'Visit %s to see a more in-depth description of what layouts are and how to use them.', 'option-builder' );
		echo '<p>' . sprintf( $string, '<code>' . esc_html__( 'OptionBuilder->Documentation->Layouts Overview', 'option-builder' ) . '</code>' ) . '</p>'; // phpcs:ignore

		echo '</div>';

		echo '<div class="format-setting-inner">';

		// Get the saved layouts.
		$layouts = get_option( Utils::layouts_id() );

		// Set active layout.
		$active_layout = isset( $layouts['active_layout'] ) ? $layouts['active_layout'] : '';

		echo '<input type="hidden" name="' . esc_attr( Utils::layouts_id() ) . '[active_layout]" value="' . esc_attr( $active_layout ) . '" class="active-layout-input" />';

		// Add new layout.
		echo '<input type="text" name="' . esc_attr( Utils::layouts_id() ) . '[_add_new_layout_]" value="" class="widefat option-builder-ui-input" autocomplete="off" />';

		// Loop through each layout.
		echo '<ul class="option-builder-setting-wrap option-builder-sortable" id="option_builder_layouts">';

		if ( is_array( $layouts ) && ! empty( $layouts ) ) {

			foreach ( $layouts as $key => $data ) {

				// Skip active layout array.
				if ( 'active_layout' === $key ) {
					continue;
				}

				// Content.
				echo '<li class="ui-state-default list-layouts">' . Utils::layout_view( $key, $data, $active_layout ) . '</li>'; // phpcs:ignore
			}
		}

		echo '</ul>';

		echo '<button class="option-builder-ui-button button button-primary right hug-right">' . esc_html__( 'Save Layouts', 'option-builder' ) . '</button>';

		echo '</div>';

		echo '</div>';

		echo '</form>';
	}


	/**
	 * Export Settings File option type.
	 *
	 * @access public
	 * @since 1.0.0
	 */
	public function type_export_settings_file() {
		global $blog_id;

		echo '<form method="post" id="export-settings-file-form">';

		// Form nonce.
		wp_nonce_field( 'export_settings_file_form', 'export_settings_file_nonce' );

		// Format setting outer wrapper.
		echo '<div class="format-setting type-textarea simple has-desc">';

		// Description.
		echo '<div class="description">';

		/* translators: %1$s: file name, %2$s: link to I18n docs, %3$s: link to internal docs */
		$string = esc_html__( 'Export your Settings into a fully functional %1$s file. If you want to add your own custom %2$s text domain to the file, enter it into the text field before exporting. For more information on how to use this file read the documentation on %3$s. Remember, you should always check the file for errors before including it in your theme.', 'option-builder' );
		echo '<p>' . sprintf( $string, '<code>theme-options.php</code>', '<a href="http://codex.wordpress.org/I18n_for_WordPress_Developers" target="_blank">I18n</a>', '<a href="' . get_admin_url( $blog_id, 'admin.php?page=opb-documentation#section_theme_mode' ) . '">' . esc_html__( 'Theme Mode', 'option-builder' ) . '</a>' ) . '</p>'; // phpcs:ignore

		echo '</div>';

		echo '<div class="format-setting-inner">';

		echo '<input type="text" name="domain" value="" class="widefat option-builder-ui-input" placeholder="text-domain" autocomplete="off" />';

		echo '<button class="option-builder-ui-button button button-primary hug-left">' . esc_html__( 'Export Settings File', 'option-builder' ) . '</button>';

		echo '</div>';

		echo '</div>';

		echo '</form>';
	}


	/**
	 * Create option type.
	 *
	 * @access public
	 * @since 1.0.0
	 */
	public function type_theme_options_ui() {
		global $blog_id;

		echo '<form method="post" id="option-builder-settings-form">';

		// Form nonce.
		wp_nonce_field( 'option_builder_settings_form', 'option_builder_settings_nonce' );

		// Format setting outer wrapper.
		echo '<div class="format-setting type-textblock has-desc">';

		// Description.
		echo '<div class="description">';

		echo '<h4>' . esc_html__( 'Warning!', 'option-builder' ) . '</h4>';

		/* translators: %s: link to theme options */
		$string = esc_html__( 'Go to the %s page if you want to save data, this page is for adding settings.', 'option-builder' );
		echo '<p class="warning">' . sprintf( $string, '<a href="' . esc_url_raw( get_admin_url( $blog_id, apply_filters( 'opb_theme_options_parent_slug', 'themes.php' ) ) . '?page=' . apply_filters( 'opb_theme_options_menu_slug', 'opb-theme-options' ) ) . '"><code>' . esc_html__( 'Appearance->Theme Options', 'option-builder' ) . '</code></a>' ) . '</p>'; // phpcs:ignore

		/* translators: %s: link to documentation */
		$string = esc_html__( 'If you\'re unsure or not completely positive that you should be editing these settings, you should read the %s first.', 'option-builder' );
		echo '<p class="warning">' . sprintf( $string, '<a href="https://github.com/gdarko/option-builder/wiki"><code>' . esc_html__( 'OptionBuilder->Documentation', 'option-builder' ) . '</code></a>' ) . '</p>'; // phpcs:ignore

		echo '<h4>' . esc_html__( 'Things could break or be improperly displayed to the end-user if you do one of the following:', 'option-builder' ) . '</h4>';
		echo '<p class="warning">' . esc_html__( 'Give two sections the same ID, give two settings the same ID, give two contextual help content areas the same ID, don\'t create any settings, or have a section at the end of the settings list.', 'option-builder' ) . '</p>';
		echo '<p>' . esc_html__( 'You can create as many settings as your project requires and use them how you see fit. When you add a setting here, it will be available on the Theme Options page for use in your theme. To separate your settings into sections, click the "Add Section" button, fill in the input fields, and a new navigation menu item will be created.', 'option-builder' ) . '</p>';
		echo '<p>' . esc_html__( 'All of the settings can be sorted and rearranged to your liking with Drag & Drop. Don\'t worry about the order in which you create your settings, you can always reorder them.', 'option-builder' ) . '</p>';

		echo '</div>';

		// Get the saved settings.
		$settings = get_option( Utils::settings_id() );

		// Wrap settings array.
		echo '<div class="format-setting-inner">';

		// Set count to zero.
		$count = 0;

		// Loop through each section and its settings.
		echo '<ul class="option-builder-setting-wrap option-builder-sortable" id="option_builder_settings_list" data-name="' . esc_attr( Utils::settings_id() ) . '[settings]">';

		if ( isset( $settings['sections'] ) ) {

			foreach ( $settings['sections'] as $section ) {

				// Section.
				echo '<li class="' . ( $count == 0 ? 'ui-state-disabled' : 'ui-state-default' ) . ' list-section">' . Utils::sections_view( Utils::settings_id() . '[sections]', $count, $section ) . '</li>'; // phpcs:ignore

				// Increment item count.
				$count ++;

				// Settings in this section.
				if ( isset( $settings['settings'] ) ) {

					foreach ( $settings['settings'] as $setting ) {

						if ( isset( $setting['section'] ) && $setting['section'] === $section['id'] ) {

							echo '<li class="ui-state-default list-setting">' . Utils::settings_view( Utils::settings_id() . '[settings]', $count, $setting ) . '</li>'; // phpcs:ignore

							// Increment item count.
							$count ++;
						}
					}
				}
			}
		}

		echo '</ul>';

		// Buttons.
		echo '<a href="javascript:void(0);" class="option-builder-section-add option-builder-ui-button button hug-left">' . esc_html__( 'Add Section', 'option-builder' ) . '</a>';
		echo '<a href="javascript:void(0);" class="option-builder-setting-add option-builder-ui-button button">' . esc_html__( 'Add Setting', 'option-builder' ) . '</a>';
		echo '<button class="option-builder-ui-button button button-primary right hug-right">' . esc_html__( 'Save Changes', 'option-builder' ) . '</button>';

		// Sidebar textarea.
		echo '
		<div class="format-setting-label" id="contextual-help-label">
			<h3 class="label">' . esc_html__( 'Contextual Help', 'option-builder' ) . '</h3>
		</div>
		<div class="format-settings" id="contextual-help-setting">
			<div class="format-setting type-textarea no-desc">
				<div class="description"><strong>' . esc_html__( 'Contextual Help Sidebar', 'option-builder' ) . '</strong>: ' . esc_html__( 'If you decide to add contextual help to the Theme Option page, enter the optional "Sidebar" HTML here. This would be an extremely useful place to add links to your themes documentation or support forum. Only after you\'ve added some content below will this display to the user.', 'option-builder' ) . '</div>
				<div class="format-setting-inner">
					<textarea class="textarea" rows="10" cols="40" name="' . esc_attr( Utils::settings_id() ) . '[contextual_help][sidebar]">' . ( isset( $settings['contextual_help']['sidebar'] ) ? esc_html( $settings['contextual_help']['sidebar'] ) : '' ) . '</textarea>
				</div>
			</div>
		</div>';

		// Set count to zero.
		$count = 0;

		// Loop through each contextual_help content section.
		echo '<ul class="option-builder-setting-wrap option-builder-sortable" id="option_builder_settings_help" data-name="' . esc_attr( Utils::settings_id() ) . '[contextual_help][content]">';

		if ( isset( $settings['contextual_help']['content'] ) ) {

			foreach ( $settings['contextual_help']['content'] as $content ) {

				// Content.
				echo '<li class="ui-state-default list-contextual-help">' . Utils::contextual_help_view( Utils::settings_id() . '[contextual_help][content]', $count, $content ) . '</li>'; // phpcs:ignore

				// Increment content count.
				$count ++;
			}
		}

		echo '</ul>';

		echo '<a href="javascript:void(0);" class="option-builder-help-add option-builder-ui-button button hug-left">' . esc_html__( 'Add Contextual Help Content', 'option-builder' ) . '</a>';
		echo '<button class="option-builder-ui-button button button-primary right hug-right">' . esc_html__( 'Save Changes', 'option-builder' ) . '</button>';

		echo '</div>';

		echo '</div>';

		echo '</form>';
	}

}
