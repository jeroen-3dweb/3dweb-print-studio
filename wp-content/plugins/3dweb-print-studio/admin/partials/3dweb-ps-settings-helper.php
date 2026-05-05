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
			'readonly'     => true,
			'autocomplete' => true,
			'placeholder'  => true,
		),
		'select' => array(
			'name'     => true,
			'class'    => true,
			'disabled' => true,
		),
		'textarea' => array(
			'name'        => true,
			'class'       => true,
			'disabled'    => true,
			'readonly'    => true,
			'rows'        => true,
			'placeholder' => true,
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
 * @param bool   $readonly    Whether the field is read-only.
 * @return string
 */
function dweb_ps_setting_create_row( $label, $description, $name, $value, $type = 'number', $disabled = false, $readonly = false ) {
	$checked = '';
	if ( 'checkbox' === $type ) {
		$checked = $value ? 'checked' : '';
		$value   = 1;
	}

	$disabled_attr = $disabled ? 'disabled' : '';
	$readonly_attr = $readonly ? 'readonly' : '';
	$field_markup  = sprintf(
		'<input class="regular-text ltr dweb_ps__settings__input" type="%s" name="%s" %s %s %s value="%s"/>',
		esc_attr( $type ),
		esc_attr( $name ),
		$checked,
		$disabled_attr,
		$readonly_attr,
		esc_attr( $value )
	);

	if ( 'textarea' === $type ) {
		$field_markup = sprintf(
			'<textarea class="regular-text ltr dweb_ps__settings__input dweb_ps__settings__input--textarea" name="%s" rows="4" %s %s>%s</textarea>',
			esc_attr( $name ),
			$disabled_attr,
			$readonly_attr,
			esc_textarea( $value )
		);
	}

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
		$field_markup
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
 * @param bool   $disabled    Whether the select is disabled.
 * @return string
 */
function dweb_ps_setting_create_select( $label, $description, $name, $value, $options, $disabled = false ) {
	$disabled_attr = $disabled ? ' disabled' : '';
	$select        = '<select name="' . esc_attr( $name ) . '"' . $disabled_attr . '>';
	foreach ( $options as $option ) {
		$selected = $option['value'] === $value ? 'selected' : '';
		$select  .= '<option value="' . esc_attr( $option['value'] ) . '" ' . $selected . '>' . esc_html( $option['label'] ) . '</option>';
	}
	$select .= '</select>';

	if ( $disabled ) {
		$select .= '<input type="hidden" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '"/>';
	}

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
