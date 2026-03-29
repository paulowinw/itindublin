<?php
/**
 * The Forminator_Integration_Form_Settings class.
 *
 * @package Forminator
 */

/**
 * Class Forminator_Integration_Form_Settings
 * Any change(s) to this file is subject to:
 * - Properly Written DocBlock! (what is this, why is that, how to be like those, etc, as long as you want!)
 * - Properly Written Changelog!
 *
 * @since 1.1
 */
abstract class Forminator_Integration_Form_Settings extends Forminator_Integration_Settings {

	/**
	 * Current Form Settings
	 *
	 * @since 1.1
	 * @var array
	 */
	protected $form_settings = array();

	/**
	 * Current Form Fields
	 *
	 * @var array
	 */
	protected $form_fields = array();

	/**
	 * Current Module instance
	 *
	 * @var Forminator_Base_Form_Model|null
	 */
	protected $module = null;

	/**
	 * An addon can be force disconnected from form, if its not met requirement, or data changed externally
	 * example :
	 *  - Mail List deleted on mailchimp server app
	 *  - Fields removed
	 *
	 * @since 1.1
	 * @var bool
	 */
	protected $is_force_form_disconnected = false;

	/**
	 * Reason of Force disonnected
	 *
	 * @since 1.1
	 * @var string
	 */
	protected $force_form_disconnected_reason = '';

	/**
	 * Module slug
	 *
	 * @var string
	 */
	protected static $module_slug = 'form';

	/**
	 * Forminator_Integration_Form_Settings constructor.
	 *
	 * @since 1.1
	 *
	 * @param Forminator_Integration $addon Class Forminator_Integration.
	 * @param int                    $form_id Form Id.
	 *
	 * @throws Forminator_Integration_Exception When there is an addon error.
	 */
	public function __construct( Forminator_Integration $addon, $form_id ) {
		$this->addon     = $addon;
		$this->module_id = $form_id;
		$custom_form     = Forminator_Base_Form_Model::get_model( $this->module_id );
		if ( ! $custom_form ) {
			/* translators: Form ID */
			throw new Forminator_Integration_Exception( sprintf( esc_html__( 'Form with id %d could not be found', 'forminator' ), esc_html( $this->module_id ) ) );
		}
		$this->form_fields   = forminator_addon_format_form_fields( $custom_form );
		$this->form_settings = forminator_addon_format_form_settings( $custom_form );
		$this->module        = $custom_form;
	}

	/**
	 * Override this function if addon need to do something with addon form setting values
	 *
	 * @example transform, load from other storage ?
	 * called when rendering tab on form settings
	 *
	 * @since   1.1
	 *
	 * @param array $values Settings.
	 *
	 * @return mixed
	 */
	public function before_get_form_settings_values( $values ) {
		return $values;
	}

	/**
	 * Override this function if addon need to do something with addon form setting values
	 *
	 * @example transform, load from other storage ?
	 * called when rendering tab on form settings
	 * @since   1.1
	 *
	 * @param array $values Settings.
	 *
	 * @return mixed
	 */
	public function before_save_form_settings_values( $values ) {
		return $values;
	}

	/**
	 * Get status of force disconnected from WP post_meta
	 *
	 * @since 1.1
	 * @return bool
	 */
	final public function is_force_form_disconnected() {
		$disconnected = get_post_meta( $this->module_id, 'forminator_addon_' . $this->addon->get_slug() . '_form_disconnect', true );

		if ( ! empty( $disconnected ) && isset( $disconnected['disconnect'] ) && $disconnected['disconnect'] ) {
			$this->is_force_form_disconnected     = true;
			$this->force_form_disconnected_reason = $disconnected['disconnnect_reason'];
		}

		return $this->is_force_form_disconnected;
	}

	/**
	 * Get disconnected reason
	 *
	 * @since 1.1
	 * @return string
	 */
	final public function force_form_disconnected_reason() {
		return $this->force_form_disconnected_reason;
	}

	/**
	 * Force form to be disconnected with addon
	 *
	 * @since 1.1
	 *
	 * @param string $reason Reason for disconnect.
	 */
	final public function force_form_disconnect( $reason ) {
		$this->is_force_form_disconnected     = true;
		$this->force_form_disconnected_reason = $reason;

		$this->addon_settings = array();

		$this->save_module_settings_values();
	}

	/**
	 * Save disconnect reason to WP post_meta
	 *
	 * @since 1.1
	 */
	final public function save_force_form_disconnect_reason() {
		if ( $this->is_force_form_disconnected ) {
			update_post_meta(
				$this->module_id,
				'forminator_addon_' . $this->addon->get_slug() . '_form_disconnect',
				array(
					'disconnect'         => true,
					'disconnnect_reason' => $this->force_form_disconnected_reason,
				)
			);
		}
	}

	/**
	 * Remove disconnect reason form WP post_meta
	 *
	 * @since 1.1
	 */
	final public function remove_saved_force_form_disconnect_reason() {
		delete_post_meta( $this->module_id, 'forminator_addon_' . $this->addon->get_slug() . '_form_disconnect' );
	}

	/**
	 * Get current form settings
	 *
	 * @since 1.1
	 * @return array
	 */
	final public function get_form_settings() {
		return $this->form_settings;
	}

	/**
	 * Get current form fields
	 *
	 * @since 1.1
	 * @return array
	 */
	final public function get_form_fields() {
		return $this->form_fields;
	}

	/**
	 * Override this function to set wizardable settings
	 * Default its and empty array which is indicating that Integration doesnt have settings
	 *
	 * Its multi array, with numerical key, start with `0`
	 * Every step on wizard, will consist at least
	 * - `callback` : when application requesting wizard, Forminator will do `call_user_func` on this value, with these arguments
	 *      - `$submitted_data` : array of submitted data POST-ed by user
	 *      - `$form_id` : current form_id when called on `Form Settings` or 0 when called on Global Settings
	 * - `is_completed` : when application requesting wizard, will check if `Previous Step` `is_completed` by doing `call_user_func` on its value
	 *      this function should return `true` or `false`
	 *
	 * @since 1.1
	 * @return array
	 */
	public function module_settings_wizards() {
		// What this function return should looks like.
		$steps = array(
			// First Step / step `0`.
			array(
				/**
				 * Value of `callback` will be passed as first argument of `call_user_func`
				 * it does not have to be passed `$this` as reference such as `array( $this, 'sample_setting_first_step' )`,
				 * But its encouraged to passed `$this` because you will be benefited with $this class instance, in case you need to call private function or variable inside it
				 * you can make the value to be `some_function_name` as long `some_function_name` as long it will globally callable which will be checked with `is_callable`
				 * and should be able to accept 2 arguments $submitted_data, $form_id
				 *
				 * This callback should return an array @see Forminator_Integration::sample_setting_first_step()
				 *
				 * @see Forminator_Integration::sample_setting_first_step()
				 */
				'callback'     => array( $this, 'sample_setting_first_step' ),
				/**
				 * Before Forminator call the `calback`, Forminator will attempt to run `is_completed` from the previous step
				 * In this case, `is_completed` will be called when Forminator trying to display Settings Wizard for Second Step / step `1`
				 * Like `callback` its value will be passed as first argument of `call_user_func`
				 * and no arguments passed to this function when its called
				 *
				 * @see Forminator_Integration::sample_setting_first_step_is_completed()
				 */
				'is_completed' => array( $this, 'sample_setting_first_step_is_completed' ),
			),
		);

		return array();
	}

	/**
	 * Get form settings data to export
	 *
	 * Default is from post_meta, override when needed
	 *
	 * @since 1.4
	 *
	 * @return array
	 */
	public function to_exportable_data() {
		$addon_slug    = $this->addon->get_slug();
		$form_settings = $this->get_settings_values();
		if ( empty( $form_settings ) ) {
			$exportable_data = array();
		} else {
			$exportable_data = array(
				'form_settings' => $form_settings,
				'version'       => $this->addon->get_version(),
			);
		}

		$form_id = $this->module_id;

		/**
		 * Filter Form settings that will be exported when requested
		 *
		 * @since 1.4
		 *
		 * @param array $exportable_data
		 * @param int   $form_id
		 */
		$exportable_data = apply_filters( "forminator_addon_{$addon_slug}_form_settings_to_exportable_data", $exportable_data, $form_id );

		return $exportable_data;
	}

	/**
	 * Executed when form settings imported
	 *
	 * Default is save imported data to post_meta, override when needed
	 *
	 * @since 1.4
	 *
	 * @param mixed $import_data Import data.
	 * @throws Forminator_Integration_Exception When there is an integration error.
	 */
	public function import_data( $import_data ) {
		$addon_slug = $this->addon->get_slug();
		$form_id    = $this->module_id;

		$import_data = apply_filters( "forminator_addon_{$addon_slug}_form_settings_import_data", $import_data, $form_id );

		/**
		 * Executed when importing form settings of this addon
		 *
		 * @since 1.4
		 *
		 * @param array                                        $exportable_data
		 * @param int                                          $form_id
		 * @param Forminator_Integration_Form_Settings|null $form_settings_instance
		 */
		do_action( "forminator_addon_{$addon_slug}_on_import_form_settings_data", $form_id, $import_data );

		try {
			// pre-basic-validation.
			if ( empty( $import_data ) ) {
				throw new Forminator_Integration_Exception( 'import_data_empty' );
			}

			if ( ! isset( $import_data['form_settings'] ) ) {
				throw new Forminator_Integration_Exception( 'import_data_no_form_settings' );
			}

			if ( empty( $import_data['form_settings'] ) ) {
				throw new Forminator_Integration_Exception( 'import_data_form_settings_empty' );
			}

			if ( ! isset( $import_data['version'] ) ) {
				throw new Forminator_Integration_Exception( 'import_data_no_version' );
			}
			$this->save_module_settings_values( $import_data['form_settings'] );

		} catch ( Forminator_Integration_Exception $e ) {
			forminator_addon_maybe_log( $e->getMessage() );
			// do nothing.
		}
	}

	/**
	 * Mailchimp Address type fields array
	 *
	 * @since 1.0 Mailchimp Integration
	 * @return array
	 */
	public function mail_address_fields() {

		$address = array(
			'addr1'   => 'Address 1',
			'addr2'   => 'Address 2',
			'city'    => 'City',
			'state'   => 'State',
			'zip'     => 'Zip',
			'country' => 'Country',
		);

		return $address;
	}

	/**
	 * Get current Module instance
	 *
	 * @return Forminator_Base_Form_Model|null
	 */
	public function get_module() {
		return $this->module;
	}
}
