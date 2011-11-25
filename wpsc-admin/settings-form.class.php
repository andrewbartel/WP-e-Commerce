<?php

class WPSC_Settings_Form
{
	private $form_array = array();
	private $sections = array();
	private $validation_rules = array();

	public function __construct( $sections, $form_array ) {
		$this->form_array = $form_array;
		$this->sections = $sections;

		foreach ( $sections as $section_id => $section_array ) {
			add_settings_section( $section_id, $section_array['title'], array( $this, 'callback_section_description' ),  'wpsc-settings' );

			foreach ( $section_array['fields'] as $field_name ) {
				$field_array = $this->form_array[$field_name];
				if ( empty( $field_array['id'] ) )
					$field_array['id'] = str_replace( '_', '-', $field_name );

				$field_array['internal_name'] = $field_name;
				$field_array['name'] = 'wpsc_' . $field_name;

				if ( ! array_key_exists( 'label_for', $field_array ) )
					$field_array['label_for'] = $field_array['id'];

				if ( ! array_key_exists( 'value', $field_array ) )
					$field_array['value'] = wpsc_get_option( $field_name );

				if ( array_key_exists( 'validation', $field_array ) )
					add_filter( 'sanitize_option_' . $field_array['name'], array( $this, 'validate_field' ), 10, 2 );

				add_settings_field( $field_array['id'], $field_array['title'], array( $this, 'output_field' ), 'wpsc-settings', $section_id, $field_array );
				register_setting( 'wpsc-settings', $field_array['name'] );
			}
		}

		add_filter( 'wpsc_settings_validation_rule_required', array( $this, 'validate_field_required' ), 10, 4 );
	}

	public function validate_field_required( $valid, $value, $field_name, $field_title ) {
		if ( $value == '' ) {
			add_settings_error( $field_name, 'field-required', sprintf( __( 'The field %s cannot be blank.', 'wpsc' ), $field_title ) );
			$valid = false;
		}
		return $valid;
	}

	public function validate_field( $value, $field_name ) {
		$internal_name = substr( $field_name, 5 ); // remove the wpsc_ part, WP core passes the whole option name
		$rules = explode( '|', $this->form_array[$internal_name]['validation'] );
		$field_title = $this->form_array[$internal_name]['title'];
		$valid = true;
		foreach ( $rules as $rule ) {
			$valid = apply_filters( 'wpsc_settings_validation_rule_' . $rule, $valid, $value, $field_name, $field_title );
		}

		if ( ! $valid )
			$value = wpsc_get_option( $internal_name );

		return $value;
	}

	public function callback_section_description( $section ) {
		$section_id = $section['id'];
		$description = esc_html( $this->sections[$section_id]['description'] );
		$description = apply_filters( 'wpsc_' . $section_id . '_description', $description );
		echo '<p>' . $description . '</p>';
	}

	private function output_textfield( $field_array ) {
		extract( $field_array );
		$description_html = apply_filters( $name . '_setting_description', esc_html( $description ), $field_array );
		if ( ! isset( $class ) )
			$class = 'regular-text wpsc-textfield';
		?>
		<input
			class="<?php echo esc_attr( $class ); ?>"
			id   ="<?php echo esc_attr( $id    ); ?>"
			name ="<?php echo esc_attr( $name  ); ?>"
			type ="text"
			value="<?php echo esc_attr( $value ); ?>"
		/>
		<p class="howto"><?php echo $description_html; ?></p>
		<?php
	}

	private function output_radios( $field_array ) {
		extract( $field_array );
		$description_html = apply_filters( 'wpsc_settings_' . $name . '_description', esc_html( $description ), $field_array );
		if ( ! isset( $class ) )
			$class = 'wpsc-radio';

		foreach ( $options as $radio_value => $radio_label ) {
			$radio_id = $id . '-' . sanitize_title_with_dashes( $value );
			?>
			<label class="wpsc-radio-label">
				<input
					class="<?php echo esc_attr( $class    ); ?>"
					id   ="<?php echo esc_attr( $radio_id ); ?>"
					name ="<?php echo esc_attr( $name     ); ?>"
					<?php checked( $value, $radio_value ); ?>
					type ="radio"
					value="<?php echo esc_attr( $radio_value    ); ?>"
				/>
				<?php echo esc_html( $radio_label ); ?>
			</label>
			<?php
		}
		echo '<br />';
		echo '<p class="howto">' . $description_html . '</p>';
	}

	public function output_field( $field_array ) {
		$output_function = 'output_' . $field_array['type'];
		$this->$output_function( $field_array );
		?>
		<?php
	}

	public function display() {
		settings_fields( 'wpsc-settings' );
		do_settings_sections( 'wpsc-settings' );
	}
}