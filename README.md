# WP Option Builder

Lightweight and simple way to create Plugin and Theme options and also Metaboxes for WordPress. Inspired by OptionTree

## Description ##

WP Option Builder attempts to bridge the gap between WordPress developers, designers and end-users by creating fully
responsive option panels and meta boxes with an ease unlike any other plugin. OptionBuilder has many advanced features
with well placed hooks and filters to adjust every aspect of the user experience.

#### Contributing

To contribute or report bugs, please go to the [WP Option Builder](https://github.com/ignitekit/wp-option-builder)
repository.

#### Option Types

This is a complete list of all the available option types that come shipped with WP Option Builder.

* Background
* Border
* Box Shadow
* Category Checkbox
* Category Select
* Checkbox
* Colorpicker
* Colorpicker Opacity
* CSS
* Custom Post Type Checkbox
* Custom Post Type Select
* Date Picker
* Date Time Picker
* Dimension
* Gallery
* Google Fonts
* JavaScript
* Link Color
* List Item
* Measurement
* Numeric Slider
* On/Off
* Page Checkbox
* Page Select
* Post Checkbox
* Post Select
* Radio
* Radio Image
* Select
* Sidebar Select
* Slider
* Social Links
* Spacing
* Tab
* Tag Checkbox
* Tag Select
* Taxonomy Checkbox
* Taxonomy Select
* Text
* Textarea
* Textarea Simple
* Textblock
* Textblock Titled
* Typography
* Upload

## Installation ##

```
composer require ignitekit/wp-option-builder
```

or without Composer as follows:

```php
require_once '/path/to/wp-option-builder/autoload.php';
```

## How to use ##

Assuming that you already loaded the library you need instance of the Framework class:

```php
use IgniteKit\WP\OptionBuilder\Framework;
$framework = new Framework();
```

Then you can register option pages and metaboxes as follows:

### Option pages

```php

$settings = array(
	'id'    => 'custom_options',
	'pages' => array(
		array(
			'id'              => 'test_page',
			'parent_slug'     => 'themes.php',
			'page_title'      => __( 'Theme Options', 'your-text-domain' ),
			'menu_title'      => __( 'Theme Options', 'your-text-domain' ),
			'capability'      => 'edit_theme_options',
			'menu_slug'       => 'demo-theme-options',
			'icon_url'        => null,
			'position'        => null,
			'updated_message' => __( 'Options updated!', 'your-text-domain' ),
			'reset_message'   => __( 'Options reset!', 'your-text-domain' ),
			'button_text'     => __( 'Save changes', 'your-text-domain' ),
			'show_buttons'    => true,
			'screen_icon'     => 'options-general',
			'contextual_help' => array(
				'content' => array( array(
						'id'      => 'option_types_help',
						'title'   => __( 'Option Types', 'theme-text-domain' ),
						'content' => '<p>' . __( 'Help content goes here!', 'theme-text-domain' ) . '</p>',
					),
				),
				'sidebar' => '<p>' . __( 'Sidebar content goes here!', 'theme-text-domain' ) . '</p>',
			),
			'sections' => array( array(
				'id'    => 'option_types',
				'title' => __( 'Option Types', 'theme-text-domain' ),
			    ),
			),
			'settings'        => array(
				array(
					'id'           => 'demo_background',
					'label'        => __( 'Background', 'theme-text-domain' ),
					'desc'         => __( 'Some description goes here...', 'theme-text-domain' ),
					'std'          => '',
					'type'         => 'background',
					'section'      => 'option_types',
					'rows'         => '',
					'post_type'    => '',
					'taxonomy'     => '',
					'min_max_step' => '',
					'class'        => '',
					'condition'    => '',
					'operator'     => 'and',
				),
				array(
					'id'           => 'demo_border',
					'label'        => __( 'Border', 'theme-text-domain' ),
					'desc'         => __( 'Some description goes here...', 'theme-text-domain' ),
					'std'          => '',
					'type'         => 'border',
					'section'      => 'option_types',
					'rows'         => '',
					'post_type'    => '',
					'taxonomy'     => '',
					'min_max_step' => '',
					'class'        => '',
					'condition'    => '',
					'operator'     => 'and',
				),
				array(
					'id'           => 'demo_box_shadow',
					'label'        => __( 'Box Shadow', 'theme-text-domain' ),
					'desc'         => __( 'Some description goes here...', 'theme-text-domain' ),
					'std'          => '',
					'type'         => 'box-shadow',
					'section'      => 'option_types',
					'rows'         => '',
					'post_type'    => '',
					'taxonomy'     => '',
					'min_max_step' => '',
					'class'        => '',
					'condition'    => '',
					'operator'     => 'and',
				),
			)
		)
	)
);

$framework->register_settings( array( $settings ) ); // Note: $settings one group option pages, you can add multiple groups of pages.
```

### Metaboxes


```php
$framework->register_metabox( array(
	'id'       => 'demo_meta_box',
	'title'    => __( 'Demo Meta Box', 'theme-text-domain' ),
	'desc'     => '',
	'pages'    => array( 'post' ),
	'context'  => 'normal',
	'priority' => 'high',
	'fields'   => array(
		array(
			'label' => __( 'Conditions', 'theme-text-domain' ),
			'id'    => 'demo_conditions',
			'type'  => 'tab',
		),
		array(
			'label' => __( 'Show Gallery', 'theme-text-domain' ),
			'id'    => 'demo_show_gallery',
			'type'  => 'on-off',
			'desc'  => sprintf( __( 'Shows the Gallery when set to %s.', 'theme-text-domain' ), '<code>on</code>' ),
			'std'   => 'off',
		),
		array(
			'label'     => '',
			'id'        => 'demo_textblock',
			'type'      => 'textblock',
			'desc'      => __( 'Congratulations, you created a gallery!', 'theme-text-domain' ),
			'operator'  => 'and',
			'condition' => 'demo_show_gallery:is(on),demo_gallery:not()',
		),
		array(
			'label'     => __( 'Gallery', 'theme-text-domain' ),
			'id'        => 'demo_gallery',
			'type'      => 'gallery',
			'desc'      => sprintf( __( 'This is a Gallery option type. It displays when %s.', 'theme-text-domain' ), '<code>demo_show_gallery:is(on)</code>' ),
			'condition' => 'demo_show_gallery:is(on)',
		),
		array(
			'label' => __( 'More Options', 'theme-text-domain' ),
			'id'    => 'demo_more_options',
			'type'  => 'tab',
		),
		array(
			'label' => __( 'Text', 'theme-text-domain' ),
			'id'    => 'demo_text',
			'type'  => 'text',
			'desc'  => __( 'This is a demo Text field.', 'theme-text-domain' ),
		),
		array(
			'label' => __( 'Textarea', 'theme-text-domain' ),
			'id'    => 'demo_textarea',
			'type'  => 'textarea',
			'desc'  => __( 'This is a demo Textarea field.', 'theme-text-domain' ),
		),
	),
) );
```

### More details ###

* [Example array with all available option types and the accepted parameters](https://github.com/IgniteKit/wp-option-builder/wiki/Available-Option-Types)
* [Creating metaboxes](https://github.com/IgniteKit/wp-option-builder/wiki/Creating-Metaboxes)
* [Creating option pages](https://github.com/IgniteKit/wp-option-builder/wiki/Creating-Option-Pages)
* [More details about the option types](https://github.com/IgniteKit/wp-option-builder/wiki/Details-about-the-option-types)
* [Admin UI Option Builder](https://github.com/IgniteKit/wp-option-builder/wiki/UI-Option-Builder)


## License

```
Copyright (C) 2021 Darko Gjorgjijoski (https://darkog.com)

This file is part of WP Option Builder

WP Option Builder is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
(at your option) any later version.

WP Option Builder is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with WP Option Builder. If not, see <https://www.gnu.org/licenses/>.
```