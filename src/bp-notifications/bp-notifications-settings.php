<?php
/**
 * Notifications Settings
 *
 * @package BuddyBoss\Notifications
 * @since BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get the Notification settings sections.
 *
 * @return array
 * @since BuddyBoss [BBVERSION]
 */
function bb_notification_get_settings_sections() {

	$settings = array(
		'bp_notifications'                   => array(
			'page'              => 'notifications',
			'title'             => __( 'On-screen Notifications', 'buddyboss' ),
			'tutorial_callback' => 'bp_admin_on_screen_notification_setting_tutorial',
			'notice'            => ( ! bb_enabled_legacy_email_preference() ) ? __( 'Members can manage which on-screen notifications they receive in their notification preferences by enabling or disabling the "Web" options.', 'buddyboss' ) : '',
		),
		'bp_notification_settings_automatic' => array(
			'page'              => 'notifications',
			'title'             => __( 'Notification Types', 'buddyboss' ),
			'tutorial_callback' => 'bb_automatic_notifications_tutorial',
		),
	);

	if ( bb_enabled_legacy_email_preference() ) {
		unset( $settings['bp_notification_settings_automatic'] );
	}

	return (array) apply_filters( 'bb_notification_get_settings_sections', $settings );
}

/**
 * Link to Automatic Notification tutorial
 *
 * @since BuddyBoss [BBVERSION]
 */
function bb_automatic_notifications_tutorial() {
	?>

	<p>
		<a class="button" href="
		<?php
		echo esc_url(
			bp_get_admin_url(
				add_query_arg(
					array(
						'page'    => 'bp-help',
						'article' => 62829,
					),
					'admin.php'
				)
			)
		);
		?>
		"><?php esc_html_e( 'View Tutorial', 'buddyboss' ); ?></a>
	</p>

	<?php
}

/**
 * Get settings fields by section.
 *
 * @param string $section_id
 *
 * @return mixed False if section is invalid, array of fields otherwise.
 * @since BuddyBoss [BBVERSION]
 */
function bb_notification_get_settings_fields_for_section( $section_id = '' ) {

	// Bail if section is empty.
	if ( empty( $section_id ) ) {
		return false;
	}

	$fields = bb_notification_get_settings_fields();
	$retval = isset( $fields[ $section_id ] ) ? $fields[ $section_id ] : false;

	return (array) apply_filters( 'bb_notification_get_settings_fields_for_section', $retval, $section_id );
}

/**
 * Get all the settings fields.
 *
 * @return array
 * @since BuddyBoss [BBVERSION]
 */
function bb_notification_get_settings_fields() {

	$fields = array();

	$fields['bp_notifications'] = array(
		'_bp_on_screen_notifications_enable'        => array(
			'title'             => __( 'On-screen notifications', 'buddyboss' ),
			'callback'          => 'bb_admin_setting_callback_on_screen_notifications_enable',
			'sanitize_callback' => 'intval',
			'args'              => array(),
		),
		'_bp_on_screen_notification_position'       => array(
			'title'             => __( 'Position on Screen', 'buddyboss' ),
			'callback'          => 'bb_admin_setting_callback_on_screen_notifications_position',
			'sanitize_callback' => 'intval',
			'args'              => array(),
		),
		'_bp_on_screen_notification_mobile_support' => array(
			'title'             => __( 'Mobile Support', 'buddyboss' ),
			'callback'          => 'bb_admin_setting_callback_on_screen_notifications_mobile_support',
			'sanitize_callback' => 'intval',
			'args'              => array(),
		),
		'_bp_on_screen_notification_visibility'     => array(
			'title'             => __( 'Automatically Hide', 'buddyboss' ),
			'callback'          => 'bb_admin_setting_callback_on_screen_notifications_visibility',
			'sanitize_callback' => 'intval',
			'args'              => array(),
		),
		'_bp_on_screen_notification_browser_tab'    => array(
			'title'             => __( 'Show in Browser Tab', 'buddyboss' ),
			'callback'          => 'bb_admin_setting_callback_on_screen_notifications_browser_tab',
			'sanitize_callback' => 'intval',
			'args'              => array(),
		),
	);

	$fields['bp_notification_settings_automatic'] = array();

	$fields['bp_notification_settings_automatic']['infos'] = array(
		'title'             => __( 'Notes', 'buddyboss' ),
		'callback'          => 'bb_admin_setting_callback_on_automatic_notification_information',
		'sanitize_callback' => 'string',
		'args'              => array( 'class' => 'notes-hidden-header' ),
	);

	$fields['bp_notification_settings_automatic']['fields'] = array(
		'title'             => __( 'Notification Fields', 'buddyboss' ),
		'callback'          => 'bb_admin_setting_callback_on_automatic_notification_fields',
		'sanitize_callback' => 'string',
		'args'              => array( 'class' => 'notes-hidden-header child-no-padding' ),
	);

	return (array) apply_filters( 'bb_notification_get_settings_fields', $fields );
}

/**
 * Added instructions for the notification type.
 *
 * @since BuddyBoss [BBVERSION]
 */
function bb_admin_setting_callback_on_automatic_notification_information() {
	echo '<p class="description notification-information">' .
		esc_html__( 'Select which types of notifications are sent to members when specific actions happen on your site. When a notification is disabled, it will not be generated for any member. Members can configure which notifications they receive via email, web or app in their Notification Preferences.', 'buddyboss' ) .
	'</p>';
}

/**
 * Callback fields for the notification fields options.
 *
 * @since BuddyBoss [BBVERSION]
 */
function bb_admin_setting_callback_on_automatic_notification_fields() {
	$all_notifications    = bb_register_notification_preferences();
	$enabled_notification = bp_get_option( 'bb_enabled_notification', array() );

	if ( ! empty( $all_notifications ) ) {
		echo '<table class="form-table"><tbody>';
		foreach ( $all_notifications as $field_group ) {
			?>
			<tr class="child-no-padding">
				<th><?php echo isset( $field_group['admin_label'] ) ? esc_html( $field_group['admin_label'] ) : ''; ?></th>
				<td class="no-padding">
					<?php
					if ( ! empty( $field_group['fields'] ) ) {
						echo '<div class="field-set">';
						foreach ( $field_group['fields'] as $field ) {
							$checked = isset( $field['default'] ) && 'yes' === $field['default'] ? true : false;
							?>
								<div class="field-block">
									<div class="field-render">
										<?php
										if ( array_key_exists( $field['key'], $enabled_notification ) ) {
											$checked = false;
											if (
												isset( $enabled_notification[ $field['key'] ]['main'] ) &&
												'yes' === $enabled_notification[ $field['key'] ]['main']
											) {
												$checked = true;
											}
										}

										bb_activate_notification( $field, $checked );
										?>
									</div>

									<?php
									$registered_emails = bb_register_notification_email_templates( $field['key'] );
									$total_email_count = 0;
									if ( ! empty( $registered_emails ) ) {
										foreach ( $registered_emails as $email_type ) {
											$total_email_count += get_terms(
												array(
													'taxonomy' => bp_get_email_tax_type(),
													'slug' => $email_type,
													'fields' => 'count',
												)
											);
										}
									}

									if ( ! empty( $registered_emails ) && count( $registered_emails ) > $total_email_count ) {
										?>
										<div class="no-email-info"><?php esc_html_e( 'Missing Email Template', 'buddyboss' ); ?></div>
										<?php
									}
									?>

									<a href="javascript:void(0);" class="notification-defaults"><?php esc_html_e( 'Manage Defaults', 'buddyboss' ); ?></a>
									<div class="manage-defaults manage-defaults-hide <?php echo esc_attr( $field['key'] ); ?> " data-id="<?php echo esc_attr( $field['key'] ); ?>">
										<?php
										$options = bb_notification_preferences_types( $field, 0 );

										foreach ( $options as $key => $v ) {
											$is_disabled = apply_filters( 'bb_is_' . $field['key'] . '_' . $key . '_preference_enabled', ! $checked );
											$is_render   = apply_filters( 'bb_is_' . $field['key'] . '_' . $key . '_preference_type_render', $v['is_render'], $field['key'], $key );
											if ( $is_render ) {
												?>
												<div class="field-wrap <?php echo esc_attr( $key ); ?>">
													<input type="hidden" name="bb_enabled_notification[<?php echo esc_attr( $field['key'] ); ?>][<?php echo esc_attr( $key ); ?>]" class="bs-styled-checkbox" value="no" <?php disabled( $is_disabled, true ); ?> />
													<input type="checkbox" id="<?php echo esc_attr( $field['key'] . '_' . $key ); ?>" name="bb_enabled_notification[<?php echo esc_attr( $field['key'] ); ?>][<?php echo esc_attr( $key ); ?>]" class="bs-styled-checkbox" value="yes" <?php checked( $v['is_checked'], 'yes' ); ?> <?php disabled( $is_disabled, true ); ?> />
													<label for="<?php echo esc_attr( $field['key'] . '_' . $key ); ?>"><?php echo esc_html( $v['label'] ); ?></label>
												</div>
												<?php
											} else {
												?>
												<div class="field-wrap <?php echo esc_attr( $key ); ?>"> -- </div>
												<?php
											}
										}
										?>
									</div>
								</div>
							<?php
						}
						echo '</div>';
					}
					?>
				</td>
			</tr>

			<?php
		}
		echo '</tbody></table>';
	}
}

/**
 * Callback fields for the notification fields options.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param array $field   Fieldset data.
 * @param bool  $checked Is checked or not.
 */
function bb_activate_notification( $field, $checked ) {
	$label = ( ! empty( $field['admin_label'] ) ? $field['admin_label'] : $field['label'] );
	?>

	<input name="bb_enabled_notification[<?php echo esc_attr( $field['key'] ); ?>][main]" type="hidden" value="no" />
	<input class="bb-notification-checkbox" id="bb_enabled_notification_<?php echo esc_attr( $field['key'] ); ?>" name="bb_enabled_notification[<?php echo esc_attr( $field['key'] ); ?>][main]" type="checkbox" value="yes" <?php checked( $checked, 1 ); ?> />
	<label class="notification-label" for="bb_enabled_notification_<?php echo esc_attr( $field['key'] ); ?>"><?php echo esc_html( $label ); ?></label>

	<?php
}