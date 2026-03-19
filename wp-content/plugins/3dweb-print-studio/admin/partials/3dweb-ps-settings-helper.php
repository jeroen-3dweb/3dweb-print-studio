<?php
/**
 * Admin settings partial helpers.
 *
 * @package DWeb_PS
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Gets allowed HTML tags for settings helper output.
 *
 * @return array
 */
function dweb_ps_setting_allowed_html() {
	return array(
		'div'    => array(
			'class' => true,
			'style' => true,
		),
		'small'  => array(
			'class' => true,
		),
		'input'  => array(
			'class'        => true,
			'type'         => true,
			'name'         => true,
			'value'        => true,
			'checked'      => true,
			'disabled'     => true,
			'autocomplete' => true,
			'placeholder'  => true,
		),
		'select' => array(
			'name'     => true,
			'class'    => true,
			'disabled' => true,
		),
		'option' => array(
			'value'    => true,
			'selected' => true,
		),
	);
}

/**
 * Creates a settings row with an input field.
 *
 * @param string $label       Field label.
 * @param string $description Field description.
 * @param string $name        Field name.
 * @param mixed  $value       Field value.
 * @param string $type        Input type.
 * @param bool   $disabled    Whether the field is disabled.
 * @return string
 */
function dweb_ps_setting_create_row( $label, $description, $name, $value, $type = 'number', $disabled = false ) {
	$checked = '';
	if ( 'checkbox' === $type ) {
		$checked = $value ? 'checked' : '';
		$value   = 1;
	}

	$disabled_attr = $disabled ? 'disabled' : '';

	return sprintf(
		'
            <div class="dweb_ps__settings__row">
                <div class="dweb_ps__settings__meta">
                    <div class="dweb_ps__settings__label">
                        %s
                    </div>
                    <small class="dweb_ps__settings-holder__description">%s</small>
                </div>
                <div class="dweb_ps__settings-holder">
                        <input class="regular-text ltr dweb_ps__settings__input" type="%s"
                               name="%s"
                               %s
                               %s
                               value="%s"/>
                </div>
            </div>
      ',
		esc_html( $label ),
		esc_html( $description ),
		esc_attr( $type ),
		esc_attr( $name ),
		$checked,
		$disabled_attr,
		esc_attr( $value )
	);
}

/**
 * Creates a settings row with a select box.
 *
 * @param string $label       Field label.
 * @param string $description Field description.
 * @param string $name        Field name.
 * @param string $value       Selected value.
 * @param array  $options     Select options.
 * @return string
 */
function dweb_ps_setting_create_select( $label, $description, $name, $value, $options ) {
	$select = '<select name="' . esc_attr( $name ) . '">';
	foreach ( $options as $option ) {
		$selected = $option['value'] === $value ? 'selected' : '';
		$select  .= '<option value="' . esc_attr( $option['value'] ) . '" ' . $selected . '>' . esc_html( $option['label'] ) . '</option>';
	}
	$select .= '</select>';

	return sprintf(
		'
            <div class="dweb_ps__settings__row">
                    <div class="dweb_ps__settings__meta">
                        <div class="dweb_ps__settings__label">
                        %s
                        </div>
                        <small class="dweb_ps__settings-holder__description">%s</small>
                    </div>
                    <div class="dweb_ps__settings-holder">
                        %s
                    </div>
                </div>
      ',
		esc_html( $label ),
		esc_html( $description ),
		$select
	);
}
