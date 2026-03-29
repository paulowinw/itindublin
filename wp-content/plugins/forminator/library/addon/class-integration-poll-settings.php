<?php
/**
 * The Forminator_Integration_Poll_Settings class.
 *
 * @package Forminator
 */

/**
 * Class Forminator_Integration_Poll_Settings
 * Any change(s) to this file is subject to:
 * - Properly Written DocBlock! (what is this, why is that, how to be like those, etc, as long as you want!)
 * - Properly Written Changelog!
 *
 * @since 1.6.1
 */
abstract class Forminator_Integration_Poll_Settings extends Forminator_Integration_Settings {

	/**
	 * Current Poll fields (answers)
	 *
	 * @since 1.6.1
	 * @var array
	 */
	protected $poll_fields = array();

	/**
	 * Current Poll Settings
	 *
	 * @since 1.6.1
	 * @var array
	 */
	protected $poll_settings = array();

	/**
	 * An addon can be force disconnected from poll, if its not met requirement, or data changed externally
	 * example :
	 *  - Mail List deleted on mailchimp server app
	 *  - Fields removed
	 *
	 * @since 1.6.1
	 * @var bool
	 */
	protected $is_force_poll_disconnected = false;

	/**
	 * Reason of Force disconnected
	 *
	 * @since 1.6.1
	 * @var string
	 */
	protected $force_poll_disconnected_reason = '';


	/**
	 * Poll Model
	 *
	 * @since 1.6.1
	 * @var Forminator_Poll_Model|null
	 */
	protected $poll = null;

	/**
	 * Module slug
	 *
	 * @var string
	 */
	protected static $module_slug = 'poll';

	/**
	 * Forminator_Integration_Poll_Settings constructor.
	 *
	 * @since 1.6.1
	 *
	 * @param Forminator_Integration $addon Class Forminator_Integration.
	 * @param int                    $poll_id Poll Id.
	 *
	 * @throws Forminator_Integration_Exception When there is an Integration error.
	 */
	public function __construct( Forminator_Integration $addon, $poll_id ) {
		$this->addon     = $addon;
		$this->module_id = $poll_id;
		$this->poll      = Forminator_Base_Form_Model::get_model( $this->module_id );
		if ( ! $this->poll ) {
			/* translators: %d: Poll ID */
			throw new Forminator_Integration_Exception( sprintf( esc_html__( 'Poll with id %d could not be found', 'forminator' ), esc_html( $this->module_id ) ) );
		}
		$this->poll_fields   = forminator_addon_format_poll_fields( $this->poll );
		$this->poll_settings = forminator_addon_format_poll_settings( $this->poll );
	}

	/**
	 * Override this function if addon need to do something with addon poll setting values
	 *
	 * @example transform, load from other storage ?
	 * called when rendering tab on poll settings
	 *
	 * @since   1.6.1
	 *
	 * @param array $values Settings.
	 *
	 * @return mixed
	 */
	public function before_get_poll_settings_values( $values ) {
		return $values;
	}

	/**
	 * Override this function if addon need to do something with addon poll setting values
	 *
	 * @example transform, load from other storage ?
	 * called when rendering tab on poll settings
	 * @since   1.6.1
	 *
	 * @param array $values Settings.
	 *
	 * @return mixed
	 */
	public function before_save_poll_settings_values( $values ) {
		return $values;
	}

	/**
	 * Get status of force disconnected from WP post_meta
	 *
	 * @since 1.6.1
	 * @return bool
	 */
	final public function is_force_poll_disconnected() {
		$disconnected = get_post_meta( $this->module_id, 'forminator_addon_' . $this->addon->get_slug() . '_poll_disconnect', true );

		if ( ! empty( $disconnected ) && isset( $disconnected['disconnect'] ) && $disconnected['disconnect'] ) {
			$this->is_force_poll_disconnected     = true;
			$this->force_poll_disconnected_reason = $disconnected['disconnect_reason'];
		}

		return $this->is_force_poll_disconnected;
	}

	/**
	 * Get disconnected reason
	 *
	 * @since 1.6.1
	 * @return string
	 */
	final public function force_poll_disconnected_reason() {
		return $this->force_poll_disconnected_reason;
	}

	/**
	 * Force poll to be disconnected with addon
	 *
	 * @since 1.6.1
	 *
	 * @param string $reason Reason for disconnect.
	 */
	final public function force_poll_disconnect( $reason ) {
		$this->is_force_poll_disconnected     = true;
		$this->force_poll_disconnected_reason = $reason;

		$this->addon_settings = array();

		$this->save_module_settings_values();
	}

	/**
	 * Save disconnect reason to WP post_meta
	 *
	 * @since 1.6.1
	 */
	final public function save_force_poll_disconnect_reason() {
		if ( $this->is_force_poll_disconnected ) {
			update_post_meta(
				$this->module_id,
				'forminator_addon_' . $this->addon->get_slug() . '_poll_disconnect',
				array(
					'disconnect'        => true,
					'disconnect_reason' => $this->force_poll_disconnected_reason,
				)
			);
		}
	}

	/**
	 * Remove disconnect reason poll WP post_meta
	 *
	 * @since 1.6.1
	 */
	final public function remove_saved_force_poll_disconnect_reason() {
		delete_post_meta( $this->module_id, 'forminator_addon_' . $this->addon->get_slug() . '_poll_disconnect' );
	}

	/**
	 * Get current poll settings
	 *
	 * @since 1.6.1
	 * @return array
	 */
	final public function get_poll_settings() {
		return $this->poll_settings;
	}

	/**
	 * Get current poll fields
	 *
	 * @since 1.6.1
	 * @return array
	 */
	final public function get_poll_fields() {
		return $this->poll_fields;
	}

	/**
	 * Override this function to set wizardable settings
	 * Default its and empty array which is indicating that Integration doesnt have settings
	 *
	 * Its multi array, with numerical key, start with `0`
	 * Every step on wizard, will consist at least
	 * - `callback` : when application requesting wizard, Forminator will do `call_user_func` on this value, with these arguments
	 *      - `$submitted_data` : array of submitted data POST-ed by user
	 *      - `$poll_id` : current poll_id when called on `Poll Settings` or 0 when called on Global Settings
	 * - `is_completed` : when application requesting wizard, will check if `Previous Step` `is_completed` by doing `call_user_func` on its value
	 *      this function should return `true` or `false`
	 *
	 * @since 1.6.1
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
				 * and should be able to accept 2 arguments $submitted_data, $poll_id
				 *
				 * This callback should return an array @see Forminator_Integration::sample_setting_first_step()
				 *
				 * @see Forminator_Integration::sample_setting_first_step()
				 */
				'callback'     => array( $this, 'sample_setting_first_step' ),
				/**
				 * Before Forminator call the `callback`, Forminator will attempt to run `is_completed` from the previous step
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
	 * Get poll settings data to export
	 *
	 * Default is from post_meta, override when needed
	 *
	 * @since 1.6.1
	 *
	 * @return array
	 */
	public function to_exportable_data() {
		$addon_slug    = $this->addon->get_slug();
		$poll_settings = $this->get_settings_values();
		if ( empty( $poll_settings ) ) {
			$exportable_data = array();
		} else {
			$exportable_data = array(
				'poll_settings' => $poll_settings,
				'version'       => $this->addon->get_version(),
			);
		}

		$poll_id = $this->module_id;

		/**
		 * Filter poll settings that will be exported when requested
		 *
		 * @since 1.6.1
		 *
		 * @param array $exportable_data
		 * @param int   $poll_id
		 */
		$exportable_data = apply_filters( "forminator_addon_{$addon_slug}_poll_settings_to_exportable_data", $exportable_data, $poll_id );

		return $exportable_data;
	}

	/**
	 * Executed when poll settings imported
	 *
	 * Default is save imported data to post_meta, override when needed
	 *
	 * @since 1.6.1
	 *
	 * @param mixed $import_data Import data.
	 * @throws Forminator_Integration_Exception When there is an Integration error.
	 */
	public function import_data( $import_data ) {
		$addon_slug = $this->addon->get_slug();
		$poll_id    = $this->module_id;

		$import_data = apply_filters( "forminator_addon_{$addon_slug}_poll_settings_import_data", $import_data, $poll_id );

		/**
		 * Executed when importing poll settings of this addon
		 *
		 * @since 1.6.1
		 *
		 * @param array $exportable_data
		 * @param int   $poll_id
		 */
		do_action( "forminator_addon_{$addon_slug}_on_import_poll_settings_data", $poll_id, $import_data );

		try {
			// pre-basic-validation.
			if ( empty( $import_data ) ) {
				throw new Forminator_Integration_Exception( 'import_data_empty' );
			}

			if ( ! isset( $import_data['poll_settings'] ) ) {
				throw new Forminator_Integration_Exception( 'import_data_no_poll_settings' );
			}

			if ( empty( $import_data['poll_settings'] ) ) {
				throw new Forminator_Integration_Exception( 'import_data_poll_settings_empty' );
			}

			if ( ! isset( $import_data['version'] ) ) {
				throw new Forminator_Integration_Exception( 'import_data_no_version' );
			}
			$this->save_module_settings_values( $import_data['poll_settings'] );

		} catch ( Forminator_Integration_Exception $e ) {
			forminator_addon_maybe_log( $e->getMessage() );
			// do nothing.
		}
	}
}
