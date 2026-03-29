<?php
/**
 * Forminator Templates Page
 *
 * @package Forminator
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Class Forminator_Templates_Page
 *
 * @since 1.0
 */
class Forminator_Templates_Page extends Forminator_Admin_Page {

	/**
	 * Initialize templates page
	 *
	 * @return void
	 */
	public function init() {}

	/**
	 * Enqueue scripts
	 *
	 * @param string $hook Hook name.
	 * @return void
	 */
	public function enqueue_scripts( $hook ) {
		parent::enqueue_scripts( $hook );

		add_filter( 'forminator_data', array( $this, 'change_forminator_data' ) );
		$forminator_data = new Forminator_Admin_Data();
		$forminator_l10n = new Forminator_Admin_L10n();

		wp_localize_script( 'forminator-admin', 'forminatorData', $forminator_data->get_options_data() );
		wp_localize_script( 'forminator-admin', 'forminatorl10n', $forminator_l10n->get_l10n_strings() );
	}

	/**
	 * Add select forms for creating new Appearance Preset
	 *
	 * @param array $data Data.
	 *
	 * @return array
	 */
	public function change_forminator_data( $data ) {
		ob_start();
		Forminator_Entries_Page::render_form_switcher();
		$forms_select         = ob_get_clean();
		$data['forms_select'] = $forms_select;
		$data['presetNonce']  = wp_create_nonce( 'forminator_appearance_preset' );
		$preset_id            = Forminator_Core::sanitize_text_field( 'preset' );
		if ( empty( $preset_id ) ) {
			$preset_id = 'default';
		}

		return $data;
	}

	/**
	 * Before render
	 *
	 * @return void
	 */
	public function before_render() {
		// Add js data for Permissions.
		add_filter( 'forminator_data', array( $this, 'add_permissions_js_data' ) );
	}

	/**
	 * Add js data
	 *
	 * @param mixed $data Data to add.
	 * @return mixed
	 */
	public function add_permissions_js_data( $data ) {
		if ( ! current_user_can( forminator_get_admin_cap() ) ) {
			return $data;
		}

		$permissions = get_option( 'forminator_permissions', array() );

		$data['mainSettings']     = array(
			'permissions' => $permissions,
			'modal'       => array(),
		);
		$data['permission_nonce'] = wp_create_nonce( 'forminator_permission_nonce' );

		return $data;
	}
}
