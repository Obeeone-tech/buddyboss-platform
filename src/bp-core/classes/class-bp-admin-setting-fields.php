<?php
/**
 * BuddyBoss Admin Setting Fields Class.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'BP_Admin_Setting_Fields' ) ) :

	/**
	 * Load BuddyBoss plugin admin area.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	class BP_Admin_Setting_Fields {

		/**
		 * Contain field attributes to render in the admin setting page.
		 *
		 * @var array Field attributes.
		 */
		private $field = array();

		/**
		 * Contain field types to validate field type when render the field.
		 *
		 * @var array Array of field types to support.
		 */
		private $supported_fields = array(
			'text',
			'textarea',
			'password',
			'radio',
			'checkbox',
			'multi-checkbox',
			'select',
		);

		/**
		 * Initialize the field variable in this method.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param array $args Pass the field attributes to render field.
		 */
		public function __construct( array $args = array() ) {

			if ( empty( $args['id'] ) ) {
				return false;
			}

			$args = apply_filters( 'bb_admin_setting_field_' . sanitize_title( $args['id'] ), $args );

			$this->field = bp_parse_args(
				$args,
				array(
					'type'        => '',
					'name'        => '',
					'id'          => '',
					'class'       => '',
					'label'       => '',
					'placeholder' => '',
					'value'       => '',
					'maxlength'   => '',
					'minlength'   => '',
					'multiple'    => false,
					'disabled'    => false,
					'options'     => array(),
					'description' => '',
				),
				'bb_admin_setting_fields'
			);

			$this->render_field();
		}

		/**
		 * Render the field according the passing argument in the __construct method.
		 *
		 * @since BuddyBoss [BBVERSION]
		 */
		public function render_field() {

			if ( $this->is_field_supported() ) {

				switch ( $this->field['type'] ) {
					case 'text':
						$this->render_text_field();
						break;

					case 'password':
						$this->render_password_field();
						break;

					case 'textarea':
						$this->render_textarea_field();
						break;

					case 'radio':
						$this->render_radio_field();
						break;

					case 'checkbox':
						$this->render_checkbox_field();
						break;

					case 'multi-checkbox':
						$this->render_multi_checkbox_field();
						break;

					case 'select':
						$this->render_select_field();
						break;

					default:
						$this->field_not_supported();
						break;
				}
			} else {
				$this->field_not_supported();
			}
		}

		/**
		 * Check the field type is supported or not?
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @return bool True if field is supported otherwise false.
		 */
		private function is_field_supported(): bool
		{
			if ( ! empty( $this->field['type'] ) && in_array( $this->field['type'], $this->supported_fields, true ) ) {
				return true;
			}

			return false;
		}

		/**
		 * Render text if field is not supported.
		 *
		 * @since BuddyBoss [BBVERSION]
		 */
		private function field_not_supported() {
			?>
			<p><?php esc_html_e( 'This field type does not support.', 'buddyboss' ); ?></p>
			<?php
		}

		/**
		 * Render the field description.
		 *
		 * @since BuddyBoss [BBVERSION]
		 */
		private function field_description() {

			if ( ! empty( $this->field['description'] ) ) {
				?>
				<p class="description" id="bb_admin_field_<?php echo esc_attr( $this->field['id'] ); ?>_desc"><?php echo wp_kses_post( $this->field['description'] ); ?></p>
				<?php
			}
		}

		/**
		 * Render text field.
		 *
		 * @since BuddyBoss [BBVERSION]
		 */
		private function render_text_field() {

			$placeholder = '';
			if ( ! empty( $this->field['placeholder'] ) ) {
				$placeholder = 'placeholder="' . esc_attr( $this->field['placeholder'] ) . '"';
			}

			$min_length = '';
			if ( ! empty( $this->field['minlength'] ) ) {
				$min_length = 'minlength="' . intval( $this->field['minlength'] ) . '"';
			}

			$max_length = '';
			if ( ! empty( $this->field['maxlength'] ) ) {
				$max_length = 'maxlength="' . intval( $this->field['maxlength'] ) . '"';
			}
			?>

			<input type="text" name="<?php echo esc_attr( $this->field['name'] ); ?>" id="<?php echo esc_attr( $this->field['id'] ); ?>" value="<?php echo esc_attr( $this->field['value'] ); ?>" class="regular-text <?php echo esc_attr( $this->field['class'] ); ?>" <?php disabled( $this->field['disabled'] ); ?> <?php echo esc_attr( $placeholder ); ?> <?php echo esc_attr( $min_length ); ?> <?php echo esc_attr( $max_length ); ?>>

			<?php
			$this->field_description();
		}

		/**
		 * Render password field.
		 *
		 * @since BuddyBoss [BBVERSION]
		 */
		private function render_password_field() {

			$placeholder = '';
			if ( ! empty( $this->field['placeholder'] ) ) {
				$placeholder = 'placeholder="' . esc_attr( $this->field['placeholder'] ) . '"';
			}

			$min_length = '';
			if ( ! empty( $this->field['minlength'] ) ) {
				$min_length = 'minlength="' . intval( $this->field['minlength'] ) . '"';
			}

			$max_length = '';
			if ( ! empty( $this->field['maxlength'] ) ) {
				$max_length = 'maxlength="' . intval( $this->field['maxlength'] ) . '"';
			}
			?>
			<input type="password" name="<?php echo esc_attr( $this->field['name'] ); ?>" id="<?php echo esc_attr( $this->field['id'] ); ?>" value="<?php echo esc_attr( $this->field['value'] ); ?>" class="regular-text <?php echo esc_attr( $this->field['class'] ); ?>" <?php disabled( $this->field['disabled'] ); ?> <?php echo esc_attr( $placeholder ); ?> <?php echo esc_attr( $min_length ); ?> <?php echo esc_attr( $max_length ); ?>>

			<?php
			$this->field_description();
		}

		/**
		 * Render textarea field.
		 *
		 * @since BuddyBoss [BBVERSION]
		 */
		private function render_textarea_field() {

			$placeholder = '';
			if ( ! empty( $this->field['placeholder'] ) ) {
				$placeholder = 'placeholder="' . esc_attr( $this->field['placeholder'] ) . '"';
			}

			$min_length = '';
			if ( ! empty( $this->field['minlength'] ) ) {
				$min_length = 'minlength="' . intval( $this->field['minlength'] ) . '"';
			}

			$max_length = '';
			if ( ! empty( $this->field['maxlength'] ) ) {
				$max_length = 'maxlength="' . intval( $this->field['maxlength'] ) . '"';
			}
			?>
			<textarea name="<?php echo esc_attr( $this->field['name'] ); ?>" id="<?php echo esc_attr( $this->field['id'] ); ?>" class="large-text <?php echo esc_attr( $this->field['class'] ); ?>" <?php disabled( $this->field['disabled'] ); ?> <?php echo esc_attr( $placeholder ); ?> <?php echo esc_attr( $min_length ); ?> <?php echo esc_attr( $max_length ); ?> rows="3" cols="50"><?php echo esc_textarea( $this->field['value'] ); ?></textarea>

			<?php
			$this->field_description();
		}

		/**
		 * Render radio field.
		 *
		 * @since BuddyBoss [BBVERSION]
		 */
		private function render_radio_field() {

			$options = ( ! empty( $this->field['options'] ) ? $this->field['options'] : array() );

			if ( ! empty( $options ) ) {
				foreach ( $options as $key => $label ) {
					?>

					<input type="radio" name="<?php echo esc_attr( $this->field['name'] ); ?>" class="regular-text <?php echo esc_attr( $this->field['class'] ); ?>" id="<?php echo esc_attr( $this->field['id'] . $key ); ?>" value="<?php echo esc_attr( $key ); ?>" <?php checked( $key, $this->field['value'] ); ?> <?php disabled( $this->field['disabled'] ); ?>>

					<?php if ( ! empty( $label ) ) { ?>
						<label for="<?php echo esc_attr( $this->field['id'] . $key ); ?>">
							<?php echo wp_kses_post( $label ); ?>
						</label>
						<?php
					}
				}
			}

			$this->field_description();
		}

		/**
		 * Render checkbox field.
		 *
		 * @since BuddyBoss [BBVERSION]
		 */
		private function render_checkbox_field() {
			?>
			<input type="checkbox" name="<?php echo esc_attr( $this->field['name'] ); ?>" id="<?php echo esc_attr( $this->field['id'] ); ?>" value="1" class="regular-text <?php echo esc_attr( $this->field['class'] ); ?>" <?php disabled( $this->field['disabled'] ); ?> <?php checked( true, $this->field['value'] ); ?>>

			<?php if ( ! empty( $this->field['label'] ) ) { ?>
				<label for="<?php echo esc_attr( $this->field['id'] ); ?>">
					<?php echo wp_kses_post( $this->field['label'] ); ?>
				</label>
			<?php } ?>

			<?php
			$this->field_description();
		}

		/**
		 * Render multi checkbox field.
		 *
		 * @since BuddyBoss [BBVERSION]
		 */
		private function render_multi_checkbox_field() {

			$options = ( ! empty( $this->field['options'] ) ? $this->field['options'] : array() );

			if ( ! empty( $options ) ) {
				foreach ( $options as $option_value => $option_label ) {

					$checked = '';
					if ( in_array( $option_value, $this->field['value'], false ) || isset( $this->field['value'][ $option_value ] ) ) {
						$checked = "checked='checked'";
					}
					?>

					<input type="checkbox" name="<?php echo esc_attr( $this->field['name'] ); ?>[<?php echo esc_attr( $option_value ); ?>]" class="regular-text <?php echo esc_attr( $this->field['class'] ); ?>" id="<?php echo esc_attr( $this->field['id'] . $option_value ); ?>" value="<?php echo esc_attr( $option_value ); ?>" <?php echo esc_attr( $checked ); ?> <?php disabled( $this->field['disabled'] ); ?>>

					<?php if ( ! empty( $option_label ) ) { ?>
						<label for="<?php echo esc_attr( $this->field['id'] . $option_value ); ?>">
							<?php echo wp_kses_post( $option_label ); ?>
						</label>
						<?php
					}
				}
			}

			$this->field_description();
		}

		/**
		 * Render select field.
		 *
		 * @since BuddyBoss [BBVERSION]
		 */
		private function render_select_field() {

			$multiple = '';
			if ( $this->field['multiple'] ) {
				$multiple = 'multiple';
			}

			if ( ! empty( $this->field['label'] ) ) {
				echo '<label for="' . esc_attr( $this->field['id'] ) . '">' . wp_kses_post( $this->field['label'] ) . '</label>';
			}

			$options = ( ! empty( $this->field['options'] ) ? $this->field['options'] : array() );
			?>
			<select name="<?php echo esc_attr( $this->field['name'] ); ?>" id="<?php echo esc_attr( $this->field['id'] ); ?>" class="<?php echo esc_attr( $this->field['class'] ); ?>" <?php disabled( $this->field['disabled'] ); ?> <?php echo esc_attr( $multiple ); ?>>
				<?php
				if ( ! empty( $options ) ) {
					foreach ( $options as $option_value => $option_label ) {
						echo '<option ' . selected( $option_value, $this->field['value'], false ) . ' value="' . esc_attr( $option_value ) . '">' . esc_html( $option_label ) . '</option>';
					}
				}
				?>
			</select>

			<?php
			$this->field_description();

		}

	}

endif;