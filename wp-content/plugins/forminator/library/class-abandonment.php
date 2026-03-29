<?php
/**
 * Form Abandonment Feature
 *
 * @package Forminator
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Class Forminator_Abandonment
 *
 * Handles form abandonment tracking and analytics
 *
 * @since 1.47.0
 */
class Forminator_Abandonment {

	/**
	 * Get abandoned entries
	 *
	 * @param mixed $module Module or form id.
	 * @param int   $abandoned_entries Abandoned entries.
	 *
	 * @return void
	 */
	public static function get_abandoned_entries( $module, $abandoned_entries = null ) {
		// Check if abandonment feature is disabled globally.
		if ( forminator_form_abandonment_disabled() ) {
			return;
		}

		$status = self::get_abandoned_status( $module );
		if ( 'active' === $status ) {
			if ( is_numeric( $module ) ) {
				$module_id = $module;
				$amount    = $abandoned_entries ?? 0;
			} else {
				$module_id = $module['id'];
				$amount    = $module['abandoned'] ?? 0;
			}
			echo '<a href="'
				. esc_url( admin_url( 'admin.php?page=forminator-entries&form_type=forminator_forms&entry_status=abandoned&form_id=' . $module_id ) )
				. '" class="forminator-abandoned-submissions">'
				. esc_html( $amount ) . '</a>';
		} else {
			echo self::get_abandoned_cta_link( $status ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}

	/**
	 * Get abandoned status
	 *
	 * @param mixed $module Module or form id.
	 *
	 * @return string Status.
	 */
	public static function get_abandoned_status( $module ) {
		// Check if abandonment feature is disabled globally.
		if ( forminator_form_abandonment_disabled() ) {
			return 'disabled';
		}

		static $status = null;
		if ( ! is_null( $status ) ) {
			return $status;
		}

		if ( is_numeric( $module ) ) {
			$model = Forminator_Form_Model::model()->load( $module );
		} else {
			$model = $module['model'];
		}

		if ( self::is_feature_enabled( $model ) ) {
			return 'active';
		}

		if ( self::is_plugin_active() ) {
			return 'inactive';
		} elseif ( Forminator_Hub_Connector::hub_connector_connected() || FORMINATOR_PRO ) {
			$status = 'not_installed';
		} else {
			$status = 'not_connected';
		}

		return $status;
	}

	/**
	 * Get abandoned CTA link
	 *
	 * @param string $status Status.
	 * @param string $referral Page referral.
	 *
	 * @return string CTA link.
	 */
	public static function get_abandoned_cta_link( $status, $referral = '' ) {
		switch ( $status ) {
			case 'inactive':
				$cta = '<b>' . esc_html__( 'Inactive', 'forminator' ) . '</b>';
				break;
			case 'not_installed':
				$cta = '<a class="forminator-icons-inherit"
					href="' . esc_url( admin_url( 'admin.php?page=forminator-addons&forminator_open_addon=' . Forminator_Admin_Addons_Page::EXTENSION_PACK_PID ) ) . '"'
					. ' target="_blank"'
					. '>'
					. '<i class="sui-icon-unlock sui-sm" aria-hidden="true"></i>&nbsp;'
					. esc_html__( 'Get Add-on', 'forminator' ) . '</a>';
				break;
			case 'not_connected':
				if ( empty( $referral ) ) {
					$referral = self::get_referral();
				}
				$referral = $referral ? '&page_referral=' . $referral : '';

				$url = admin_url( 'admin.php?page=forminator-addons&forminator_open_addon=' . Forminator_Admin_Addons_Page::EXTENSION_PACK_PID . $referral );
				$cta = '<a class="forminator-icons-inherit forminator-link-bright-blue sui-tooltip sui-tooltip-constrained" style="--tooltip-width: 180px;"'
					. ' href="' . esc_url( $url ) . '"'
					. ' target="_blank"'
					. ' data-tooltip="' . esc_html__( 'Unlock abandoned form insights to refine your strategy and recover missed leads. Connect your site to install and activate the Forminator Extension Pack Add-on.', 'forminator' ) . '"'
					. '>'
					. '<i class="sui-icon-unlock sui-sm" aria-hidden="true"></i>&nbsp;'
					. esc_html__( 'Unlock with Free Plan', 'forminator' ) . '</a>';
				break;
			default:
				$cta = '';
				break;
		}

		return $cta;
	}

	/**
	 * Get referral
	 *
	 * @return string Referral.
	 */
	public static function get_referral() {
		$page = filter_input( INPUT_GET, 'page' );
		switch ( $page ) {
			case 'forminator-cform-wizard':
				return 'form_builder';
			case 'forminator-cform':
				return 'forms_page';
			case 'forminator-reports':
				return 'report_page';
			default:
				return '';
		}
	}

	/**
	 * Check if plugin is active
	 *
	 * @return bool
	 */
	public static function is_plugin_active() {
		return class_exists( 'Forminator_Extension_Pack' );
	}

	/**
	 * Check if the feature is enabled
	 *
	 * @param array $module Module.
	 * @return bool
	 */
	public static function is_feature_enabled( $module ) {
		// Check if abandonment feature is disabled globally.
		if ( forminator_form_abandonment_disabled() ) {
			return false;
		}

		return self::is_plugin_active() && ! empty( $module->settings['abandonment'] );
	}
}
