<?php
/**
 * Forminator Admin Module Edit Page
 *
 * @package Forminator
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Class Forminator_Admin_Module_Edit_Page
 *
 * @since 1.14.10
 */
abstract class Forminator_Admin_Module_Edit_Page extends Forminator_Admin_Page {

	/**
	 * Page number
	 *
	 * @var int
	 */
	protected static $page_number = 1;

	/**
	 * Initialize
	 *
	 * @since 1.0
	 */
	public function init() {
		$pagenum           = absint( Forminator_Core::sanitize_text_field( 'paged' ) );
		self::$page_number = max( 1, $pagenum );
		$this->processRequest();
	}

	/**
	 * Trigger before render
	 */
	public function before_render() {
		wp_enqueue_script( 'forminator-chart', forminator_plugin_url() . 'assets/js/library/Chart.bundle.min.js', array( 'jquery' ), '2.9.4', false );
	}

	/**
	 * Count modules
	 *
	 * @param string $status Modules status.
	 * @since 1.0
	 * @return int
	 */
	public function countModules( $status = '' ) {
		$class_name = 'Forminator_' . forminator_get_prefix( static::$module_slug, '', true ) . '_Model';
		return $class_name::model()->count_all( $status );
	}

	/**
	 * Get modules
	 *
	 * @since 1.0
	 * @return array
	 */
	public function getModules() {
		$modules = array();
		$limit   = null;
		$const   = 'FORMINATOR_' . strtoupper( static::$module_slug ) . '_LIST_LIMIT';
		if ( defined( $const ) && constant( $const ) ) {
			$limit = constant( $const );
		}

		$data      = self::get_models( $limit );
		$form_view = Forminator_Form_Views_Model::get_instance();

		// Fallback.
		if ( ! isset( $data['models'] ) || empty( $data['models'] ) ) {
			return $modules;
		}

		foreach ( $data['models'] as $model ) {
			$form_name = $model->name;
			if ( isset( $model->settings['formName'] ) && ! empty( $model->settings['formName'] ) ) {
				$form_name = $model->settings['formName'];
			}

			$modules[] = static::module_array(
				$model->id,
				$form_name,
				$form_view->count_views( $model->id ),
				gmdate( get_option( 'date_format' ), strtotime( $model->raw->post_date ) ),
				$model->status,
				$model
			);
		}

		return $modules;
	}

	/**
	 * Get modules for search
	 *
	 * @param null|string $search_keyword Search Keyword.
	 * @param null|int    $page_number Optional page number. Added in 1.52.
	 *
	 * @since 1.14.12
	 * @return array
	 */
	public static function get_searched_modules( $search_keyword = null, $page_number = 1 ) {
		$modules           = array();
		self::$page_number = max( 1, $page_number );
		$data              = self::get_models( null, $search_keyword );

		// Fallback.
		if ( ! isset( $data['models'] ) || empty( $data['models'] ) ) {
			return $modules;
		}

		// Search.
		if ( ! is_null( $search_keyword ) ) {

			$form_view   = Forminator_Form_Views_Model::get_instance();
			$module_slug = self::get_slug_ajax( true );

			$class_name = 'Forminator_' . $module_slug . '_Page';

			foreach ( $data['models'] as $model ) {

						$modules[] = $class_name::module_array(
							$model->id,
							$model->settings['formName'],
							$form_view->count_views( $model->id ),
							gmdate( get_option( 'date_format' ), strtotime( $model->raw->post_date ) ),
							$model->status,
							$model
						);

			}
		}

		return array(
			'modules'       => $modules,
			'total_modules' => $data['foundPosts'],
		);
	}

	/**
	 * Get slug for ajax search
	 *
	 * @param string|bool $slug Module slug.
	 *
	 * @since 1.14.12
	 * @return array
	 */
	public static function get_slug_ajax( $slug = false ) {
		$page = Forminator_Core::sanitize_text_field( 'page' );
		if ( ! $page ) {
			return '';
		}

		switch ( $page ) {
			case 'forminator-poll':
				$module_slug = $slug ? 'Poll' : 'poll';
				break;
			case 'forminator-quiz':
				$module_slug = $slug ? 'Quiz' : 'quiz';
				break;
			default:
				$module_slug = $slug ? 'CForm' : 'form';
				break;
		}

		return $module_slug;
	}

	/**
	 * Has error on payment field
	 *
	 * @param mixed $module Module.
	 * @param bool  $is_stripe_connected Is stripe connected.
	 * @return bool
	 */
	public static function has_payment_field_error( $module, $is_stripe_connected ) {
		if ( ! empty( $module['model'] ) && method_exists( $module['model'], 'has_stripe_field' ) ) {
			$stripe_field = $module['model']->has_stripe_field();
			if ( ! empty( $stripe_field ) ) {
				if ( false === $is_stripe_connected ) {
					return true;
				}
				$stripe_field->mode;
				$plan_id_key = 'live' === $stripe_field->mode ? 'live_plan_id' : 'test_plan_id';
				foreach ( $stripe_field->payments as $plan ) {
					if ( 'subscription' === $plan['payment_method'] && empty( $plan[ $plan_id_key ] ) ) {
						return true;
					}
				}
			}
		}
		if ( ! empty( $module['model'] ) && method_exists( $module['model'], 'has_paypal_field' ) ) {
			$paypal_field = $module['model']->has_paypal_field();
			if ( ! empty( $paypal_field ) ) {
				if ( false === forminator_has_paypal_settings() ) {
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * Check if has error on registration form
	 *
	 * @param mixed $module Module.
	 * @return bool
	 */
	public static function has_error_on_registration_form( $module ) {
		if ( ! empty( $module['model']->settings['form-type'] ) && 'registration' === $module['model']->settings['form-type'] ) {
			$settings = $module['model']->settings;
			if ( 'manual' === forminator_get_property( $settings, 'activation-method' ) ) {
				return false;
			}
			if ( ! forminator_is_user_registration_enabled() ) {
				return true;
			}
			$option_create_site = forminator_get_property( $settings, 'site-registration' );
			if ( forminator_is_main_site() && 'enable' === $option_create_site && ! forminator_is_site_registration_enabled() ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Check if module is editable by the current user.
	 *
	 * @since 1.52.0
	 *
	 * @param array $module Module data array (may contain 'model' object or flat 'settings' array).
	 * @return bool
	 */
	public static function is_module_editable( $module ) {
		if ( ! empty( $module['model'] ) && is_object( $module['model'] ) ) {
			$settings = $module['model']->settings;
		} elseif ( ! empty( $module['settings'] ) && is_array( $module['settings'] ) ) {
			$settings = $module['settings'];
		} else {
			return true;
		}

		return true === forminator_validate_registration_form_settings( $settings );
	}

	/**
	 * Show the modules
	 *
	 * @param array  $modules Modules.
	 * @param string $module_slug Module slug.
	 * @param string $preview_dialog Preview dialog.
	 * @param string $preview_title Preview title.
	 * @param string $export_dialog Export dialog.
	 * @param string $post_type Post type.
	 * @param string $soon Soon.
	 * @param string $sql_month_start_date Start date.
	 * @param string $wizard_page Wizard page.
	 * @param string $search_keyword Search Keyword.
	 *
	 * @since 1.14.12
	 * @return mixed
	 */
	public static function show_modules( $modules, $module_slug, $preview_dialog, $preview_title, $export_dialog, $post_type, $soon, $sql_month_start_date, $wizard_page, $search_keyword = null ) {

		if ( empty( $modules ) ) {
			$is_search = true;
			require_once forminator_plugin_dir() . 'admin/views/common/list/empty_content.php';
		}

		$is_stripe_connected = false;
		$page                = $module_slug;
		if ( 'form' === $page ) {
			$page                = 'cform';
			$is_stripe_connected = forminator_has_stripe_connected();
		}

		foreach ( $modules as $module ) {
			$module_entries_from_last_month = 0 !== $module['entries'] ? count( Forminator_Form_Entry_Model::get_newer_entry_ids_of_form_id( $module['id'], $sql_month_start_date ) ) : 0;
			$opened_class                   = '';
			$opened_chart                   = '';
			$has_leads                      = isset( $module['has_leads'] ) ? $module['has_leads'] : false;
			$leads_id                       = isset( $module['leads_id'] ) ? $module['leads_id'] : 0;

			if ( ! is_null( $wizard_page ) && ! isset( $module['type'] ) ) {
				$edit_url = admin_url( 'admin.php?page=' . $wizard_page . '&id=' . $module['id'] );
			} else {
				// For quizzes.
				$edit_url = admin_url( 'admin.php?page=forminator-' . ( 'nowrong' === $module['type'] ? $module['type'] : 'knowledge' ) . '-wizard&id=' . $module['id'] );
			}

			$view_stats = filter_input( INPUT_GET, 'view-stats', FILTER_VALIDATE_INT );
			if ( $view_stats && intval( $module['id'] ) === $view_stats ) {
				$opened_class = ' sui-accordion-item--open forminator-scroll-to';
				$opened_chart = ' sui-chartjs-loaded';
			}
			$display_warning_icon         = self::has_payment_field_error( $module, $is_stripe_connected );
			$display_registration_warning = self::has_error_on_registration_form( $module );
			$is_module_editable           = self::is_module_editable( $module );
			if ( $display_warning_icon || $display_registration_warning ) {
				$opened_class .= ' forminator-notice-yellow';
			}
			?>

			<div class="sui-accordion-item<?php echo esc_attr( $opened_class ); ?>" data-module-id="<?php echo esc_attr( $module['id'] ); ?>">

				<div class="sui-accordion-item-header">

					<div class="sui-accordion-item-title sui-trim-title">

						<label for="wpf-module-<?php echo esc_attr( $module['id'] ); ?>" class="sui-checkbox sui-accordion-item-action">
							<input type="checkbox" id="wpf-module-<?php echo esc_attr( $module['id'] ); ?>" value="<?php echo esc_html( $module['id'] ); ?>">
							<span aria-hidden="true"></span>
							<span class="sui-screen-reader-text"><?php esc_html_e( 'Select this module', 'forminator' ); ?></span>
						</label>

						<?php if ( true === $display_warning_icon ) { ?>
							<span class="sui-notice-icon sui-icon-info forminator-accordion-notice-icon" aria-hidden="true"></span>
						<?php } ?>

						<span class="sui-trim-text"><?php echo esc_html( htmlspecialchars( forminator_get_form_name( $module['id'] ) ) ); ?></span>

						<?php
						if ( 'publish' === $module['status'] ) {
							echo '<span class="sui-tag sui-tag-blue">' . esc_html__( 'Published', 'forminator' ) . '</span>';
						}
						?>

						<?php
						if ( 'draft' === $module['status'] ) {
							echo '<span class="sui-tag">' . esc_html__( 'Draft', 'forminator' ) . '</span>';
						}
						?>

					</div>

					<div class="sui-accordion-item-date"><strong><?php esc_html_e( 'Last Submission', 'forminator' ); ?></strong> <?php echo esc_html( $module['last_entry_time'] ); ?></div>

					<div class="sui-accordion-col-auto">

						<?php if ( $is_module_editable ) : ?>
						<a href="<?php echo esc_url( $edit_url ); ?>"
							class="forminator-edit-<?php echo esc_attr( $module_slug ); ?> sui-button sui-button-ghost sui-accordion-item-action sui-desktop-visible">
							<i class="sui-icon-pencil" aria-hidden="true"></i> <?php esc_html_e( 'Edit', 'forminator' ); ?>
						</a>

						<a href="<?php echo esc_url( $edit_url ); ?>"
							class="forminator-mobile-edit-<?php echo esc_attr( $module_slug ); ?> sui-button-icon sui-accordion-item-action sui-mobile-visible">
							<i class="sui-icon-pencil" aria-hidden="true"></i>
							<span class="sui-screen-reader-text"><?php esc_html_e( 'Edit', 'forminator' ); ?></span>
						</a>
						<?php endif; ?>

						<div class="sui-dropdown sui-accordion-item-action<?php echo $soon ? ' fui-dropdown-soon' : ''; ?>">

							<button class="forminator-action-button-<?php echo esc_attr( $module_slug ); ?> sui-button-icon sui-dropdown-anchor">
								<i class="sui-icon-widget-settings-config" aria-hidden="true"></i>
								<span class="sui-screen-reader-text"><?php esc_html_e( 'Open list settings', 'forminator' ); ?></span>
							</button>

							<ul class="module-actions">

								<li><a href="#"
									class="forminator-action-preview wpmudev-open-modal"
									data-modal="<?php echo esc_attr( $preview_dialog ); ?>"
									data-modal-title="<?php /* translators: 1. Review title, 2. Form name. */ printf( esc_attr__( '%1$s - %2$s', 'forminator' ), esc_html( $preview_title ), esc_attr( htmlspecialchars( forminator_get_form_name( $module['id'] ) ) ) ); ?>"
									data-form-id="<?php echo esc_attr( $module['id'] ); ?>"
									data-has-leads="<?php echo esc_attr( $has_leads ); ?>"
									data-leads-id="<?php echo esc_attr( $leads_id ); ?>"
									data-nonce-preview="<?php echo esc_attr( wp_create_nonce( 'forminator_load_module' ) ); ?>"
									data-nonce="<?php echo esc_attr( wp_create_nonce( 'forminator_popup_' . $preview_dialog ) ); ?>">
									<i class="sui-icon-eye" aria-hidden="true"></i> <?php esc_html_e( 'Preview', 'forminator' ); ?>
								</a></li>

								<li>
									<button class="forminator-action-copy-shortcode copy-clipboard" data-shortcode='[forminator_<?php echo esc_attr( $module_slug ); ?> id="<?php echo esc_attr( $module['id'] ); ?>"]'><i class="sui-icon-code" aria-hidden="true"></i> <?php esc_html_e( 'Copy Shortcode', 'forminator' ); ?></button>
								</li>

								<li>
									<form method="post">
										<input type="hidden" name="forminator_action" value="update-status">
										<input type="hidden" name="id" value="<?php echo esc_attr( $module['id'] ); ?>"/>
										<input type="hidden" name="msearch" value="" />

										<?php if ( 'publish' === $module['status'] ) : ?>
											<input type="hidden" name="status" value="draft"/>
										<?php elseif ( 'draft' === $module['status'] ) : ?>
											<input type="hidden" name="status" value="publish"/>
										<?php endif; ?>

										<?php
											$update_status_nonce = esc_attr( 'forminator-nonce-update-status-' . $module['id'] );
											wp_nonce_field( $update_status_nonce, $update_status_nonce, false );
										?>
										<input type="hidden" name="_wp_http_referer" value="<?php echo esc_url( admin_url( 'admin.php?page=forminator-' . $page ) ); ?>">
										<button class="forminator-action-<?php echo 'publish' === $module['status'] ? 'unpublish' : 'publish'; ?>" type="submit">

											<?php if ( 'publish' === $module['status'] ) : ?>
												<i class="sui-icon-unpublish" aria-hidden="true"></i> <?php esc_html_e( 'Unpublish', 'forminator' ); ?>
											<?php elseif ( 'draft' === $module['status'] ) : ?>
												<i class="sui-icon-upload-cloud" aria-hidden="true"></i> <?php esc_html_e( 'Publish', 'forminator' ); ?>
											<?php endif; ?>

										</button>
									</form>
								</li>

								<li><a class="forminator-action-view-submissions" href="<?php echo esc_url( admin_url( 'admin.php?page=forminator-entries&form_type=' . $post_type . '&form_id=' . $module['id'] ) ); ?>">
									<i class="sui-icon-community-people" aria-hidden="true"></i> <?php esc_html_e( 'View Submissions', 'forminator' ); ?>
								</a></li>

								<li <?php echo ( $has_leads ) ? 'aria-hidden="true"' : ''; ?>><form method="post">
									<input type="hidden" name="forminator_action" value="clone">
									<input type="hidden" name="id" value="<?php echo esc_attr( $module['id'] ); ?>"/>
									<input type="hidden" name="msearch" value="" />
									<?php
										$clone_nonce = esc_attr( 'forminator-nonce-clone-' . $module['id'] );
										wp_nonce_field( $clone_nonce, 'forminatorNonce' );
									?>
									<?php if ( $has_leads ) : ?>
										<button type="submit" disabled="disabled" class="forminator-action-duplicate fui-button-with-tag sui-tooltip sui-tooltip-left sui-constrained" data-tooltip="<?php esc_html_e( 'Duplicate isn\'t supported at the moment for the quizzes with lead capturing enabled.', 'forminator' ); ?>">
											<span class="sui-icon-page-multiple" aria-hidden="true"></span>
											<span class="fui-button-label"><?php esc_html_e( 'Duplicate', 'forminator' ); ?></span>
											<span class="sui-tag sui-tag-blue sui-tag-sm"><?php echo esc_html__( 'Coming soon', 'forminator' ); ?></span>
										</button>
									<?php else : ?>
										<button class="forminator-action-duplicate" type="submit">
											<i class="sui-icon-page-multiple" aria-hidden="true"></i> <?php esc_html_e( 'Duplicate', 'forminator' ); ?>
										</button>
									<?php endif; ?>
								</form></li>

								<li>
									<button
										class="forminator-action-reset-tracking-data wpmudev-open-modal"
										data-modal="delete-module"
										data-modal-title="<?php esc_attr_e( 'Reset Tracking Data', 'forminator' ); ?>"
										data-modal-content="<?php /* translators: %s is the Module slug. */ printf( esc_attr__( 'This action will reset the views and conversions data for this %s. Are you sure you want to proceed?', 'forminator' ), esc_html__( $module_slug, 'forminator' ) ); /* phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText -- I18n is required. */ ?>"
										data-button-text="<?php esc_attr_e( 'Reset', 'forminator' ); ?>"
										data-form-id="<?php echo esc_attr( $module['id'] ); ?>"
										data-action="reset-views"
										data-nonce="<?php echo esc_attr( wp_create_nonce( 'forminator-nonce-reset-views-' . $module['id'] ) ); ?>"
									>
										<i class="sui-icon-update" aria-hidden="true"></i> <?php esc_html_e( 'Reset Tracking Data', 'forminator' ); ?>
									</button>
								</li>

								<?php if ( 'form' === $module_slug ) { ?>
								<li>
									<button
										class="forminator-action-apply-preset wpmudev-open-modal"
										data-modal="apply_preset"
										data-form-id="<?php echo esc_attr( $module['id'] ); ?>"
									>
										<i class="sui-icon-brush" aria-hidden="true"></i> <?php esc_html_e( 'Apply Preset', 'forminator' ); ?>
									</button>
								</li>
								<?php } ?>

								<?php if ( Forminator::is_import_export_feature_enabled() ) : ?>
									<?php if ( $has_leads ) : ?>
										<li aria-hidden="true"><a href="#" class="forminator-action-export fui-button-with-tag sui-tooltip sui-tooltip-left"
											data-tooltip="<?php esc_html_e( 'Export isn\'t supported at the moment for the quizzes with lead capturing enabled.', 'forminator' ); ?>">
											<span class="sui-icon-cloud-migration" aria-hidden="true"></span>
											<span class="fui-button-label"><?php esc_html_e( 'Export', 'forminator' ); ?></span>
											<span class="sui-tag sui-tag-blue sui-tag-sm"><?php echo esc_html__( 'Coming soon', 'forminator' ); ?></span>
										</a></li>
									<?php else : ?>
										<li><a href="#"
											class="forminator-action-export wpmudev-open-modal"
											data-modal="<?php echo esc_attr( $export_dialog ); ?>"
											data-modal-title=""
											data-form-id="<?php echo esc_attr( $module['id'] ); ?>"
											data-nonce="<?php echo esc_attr( wp_create_nonce( 'forminator_popup_export_' . $module_slug ) ); ?>">
											<i class="sui-icon-cloud-migration" aria-hidden="true"></i> <?php esc_html_e( 'Export', 'forminator' ); ?>
										</a></li>
									<?php endif; ?>

								<?php endif; ?>

								<li>
									<button
										class="forminator-action-delete sui-option-red wpmudev-open-modal"
										data-modal="delete-module"
										data-modal-title="<?php /* translators: %s: Module slug. */ printf( esc_attr__( 'Delete %s', 'forminator' ), esc_html( forminator_get_prefix( $module_slug, '', true ) ) ); ?>"
										data-modal-content="<?php /* translators: %s: Module slug. */ printf( esc_attr__( 'Are you sure you wish to permanently delete this %s?', 'forminator' ), esc_html( $module_slug ) ); ?>"
										data-form-id="<?php echo esc_attr( $module['id'] ); ?>"
										data-nonce="<?php echo esc_attr( wp_create_nonce( 'forminator_' . $module_slug . '_request' ) ); ?>"
									>
										<i class="sui-icon-trash" aria-hidden="true"></i> <?php esc_html_e( 'Delete', 'forminator' ); ?>
									</button>
								</li>

							</ul>

						</div>

						<button class="sui-button-icon sui-accordion-open-indicator" aria-label="<?php esc_html_e( 'Open item', 'forminator' ); ?>"><i class="sui-icon-chevron-down" aria-hidden="true"></i></button>

					</div>

				</div>

				<div class="sui-accordion-item-body">

					<ul class="sui-accordion-item-data">

						<li data-col="large">
							<strong><?php esc_html_e( 'Last Submission', 'forminator' ); ?></strong>
							<span class="forminator-summary-last-submission"><?php echo esc_html( $module['last_entry_time'] ); ?></span>
						</li>
						<?php if ( forminator_global_tracking() ) { ?>
							<li data-col="small">
								<strong><?php esc_html_e( 'Views', 'forminator' ); ?></strong>
								<span class="forminator-summary-views"><?php echo esc_html( $module['views'] ); ?></span>
							</li>
						<?php } ?>
						<li>
							<?php if ( $has_leads ) : ?>
								<strong class="forminator-leads-leads" style="display:none;"><?php esc_html_e( 'Leads Collected', 'forminator' ); ?></strong>
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=forminator-quiz-view&form_id=' . $module['id'] ) ); ?>" class="forminator-leads-leads" style="display:none;"><?php echo esc_html( $module['leads'] ); ?></a>
							<?php endif; ?>
							<strong class="forminator-leads-submissions"><?php esc_html_e( 'Submissions', 'forminator' ); ?></strong>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=forminator-entries&form_type=' . $post_type . '&entry_status=completed&form_id=' . $module['id'] ) ); ?>" class="forminator-leads-submissions"><?php echo esc_html( $module['entries'] ); ?></a>
						</li>
						<?php if ( forminator_global_tracking() ) { ?>
							<li>
								<strong><?php esc_html_e( 'Conversion Rate', 'forminator' ); ?></strong>
								<span class="forminator-submission-rate"><?php echo esc_html( self::getRate( $module ) ); ?>%</span>
								<?php if ( $has_leads ) : ?>
									<span class="forminator-leads-rate" style="display:none;"><?php echo esc_html( Forminator_Quiz_Page::getLeadsRate( $module ) ); ?>%</span>
								<?php endif; ?>
							</li>
						<?php } ?>
						<?php if ( 'form' === $module_slug && ! forminator_form_abandonment_disabled() ) { ?>
							<li>
								<strong><?php esc_html_e( 'Abandoned', 'forminator' ); ?></strong>
								<?php Forminator_Abandonment::get_abandoned_entries( $module ); ?>
							</li>
						<?php } ?>
						<?php if ( $has_leads ) : ?>
							<li class="fui-conversion-select" data-col="selector">
								<label class="fui-selector-label"><?php esc_html_e( 'View data for', 'forminator' ); ?></label>
								<select class="sui-select sui-select-sm fui-selector-button fui-select-listing-data">
									<option value="submissions"><?php esc_html_e( 'Submissions', 'forminator' ); ?></option>
									<option value="leads"><?php esc_html_e( 'Leads Form', 'forminator' ); ?></option>
								</select>
							</li>
						<?php endif; ?>
						<?php if ( forminator_global_tracking() ) { ?>
							<li>
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=forminator-reports&form_type=' . $post_type . '&form_id=' . $module['id'] ) ); ?>"
										class="sui-button sui-button-ghost forminator-view-full-report">
									<i class="sui-icon-page" aria-hidden="true"></i>
									<?php esc_html_e( 'view full report', 'forminator' ); ?>
								</a>
							</li>
						<?php } ?>
					</ul>

					<div class="sui-chartjs <?php echo esc_attr( $opened_chart ); ?> forminator-stats-chart" data-chart-id="<?php echo esc_attr( $module['id'] ); ?>">

						<?php
						unset( $message );
						if ( 0 === $module['entries'] ) {
							/* translators: %s: Module slug */
							$message = sprintf( esc_html__( 'Your %s doesn\'t have any submission yet. Try again in a moment.', 'forminator' ), esc_html__( $module_slug, 'forminator' ) ); /* phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText -- I18n is required. */
						} elseif ( 'draft' === $module['status'] ) {
							/* translators: %s: Module slug */
							$message = sprintf( esc_html__( 'This %s is in draft state, so we\'ve paused collecting data until you publish it live.', 'forminator' ), esc_html__( $module_slug, 'forminator' ) ); /* phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText -- I18n is required. */
						} elseif ( 0 === $module_entries_from_last_month ) {
							/* translators: %s: Module slug */
							$message = sprintf( esc_html__( 'Your %s didn\'t collect submissions the past 30 days.', 'forminator' ), esc_html__( $module_slug, 'forminator' ) ); /* phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText -- I18n is required. */
						}
						?>
						<?php if ( ! empty( $message ) ) { ?>

							<div class="sui-chartjs-message sui-chartjs-message--empty">
								<p><i class="sui-icon-info" aria-hidden="true"></i> <?php echo esc_html( $message ); ?></p>
							</div>

						<?php } ?>

						<div class="sui-chartjs-canvas">

							<?php if ( ( 0 !== $module['entries'] ) || ( 0 !== $module_entries_from_last_month ) ) { ?>
								<canvas id="forminator-module-<?php echo esc_attr( $module['id'] ); ?>-stats"></canvas>
							<?php } ?>

						</div>

					</div>

					<?php if ( $has_leads ) { ?>

						<div class="sui-chartjs <?php echo esc_attr( $opened_chart ); ?> forminator-leads-chart" style="display: none;" data-chart-id="<?php echo esc_attr( $leads_id ); ?>">

							<?php if ( ! empty( $message ) ) { ?>

								<div class="sui-chartjs-message sui-chartjs-message--empty">
									<p><i class="sui-icon-info" aria-hidden="true"></i> <?php echo esc_html( $message ); ?></p>
								</div>

							<?php } ?>

							<div class="sui-chartjs-canvas">

								<?php if ( ( 0 !== $module['entries'] ) || ( 0 !== $module_entries_from_last_month ) ) { ?>
									<canvas id="forminator-module-<?php echo esc_attr( $leads_id ); ?>-stats"></canvas>
								<?php } ?>

							</div>

						</div>

					<?php } ?>

				</div>

			</div>

			<?php
		}
	}

	/**
	 * Calculate rate
	 *
	 * @since 1.0
	 *
	 * @param array $module Module.
	 *
	 * @return float|int
	 */
	public static function getRate( $module ) {
		if ( $module['views'] > 0 ) {
			$rate = round( ( $module['entries'] * 100 ) / $module['views'], 1 );
		} else {
			$rate = 0;
		}

		return $rate;
	}

	/**
	 * Pagination
	 *
	 * @param bool $is_search Is search.
	 * @param int  $count Count.
	 *
	 * @since 1.0
	 */
	public function pagination( $is_search, $count ) {
		echo '<span class="sui-pagination-results">'
			/* translators: %s: Pagination Count */
			. esc_html( sprintf( _n( '%s result', '%s results', $count, 'forminator' ), $count ) )
			. '</span>';

		if ( $is_search ) {
			return;
		}
		forminator_list_pagination( $count );
	}

	/**
	 * Get models
	 *
	 * @since 1.0
	 * @since 1.6 add $limit
	 * @param int         $limit Limit.
	 * @param null|string $search_keyword  Optional Search Keyword.Added in 1.52.
	 *
	 * @return array
	 */
	public static function get_models( $limit = null, $search_keyword = null ) {

		if ( ! isset( static::$module_slug ) ) {
			$module_slug = self::get_slug_for_search();
		} else {
			$module_slug = static::$module_slug;
		}

		$class_name = 'Forminator_' . forminator_get_prefix( $module_slug, '', true ) . '_Model';
		$data       = $class_name::model()->get_all_paged( self::$page_number, $limit, '', null, $search_keyword );

		return $data;
	}

	/**
	 * Get slug when in search
	 *
	 * @since 1.14.12
	 *
	 * @return string
	 */
	public static function get_slug_for_search() {
		$page = Forminator_Core::sanitize_text_field( 'page' );

		switch ( $page ) {
			case 'forminator-poll':
				$module_slug = 'poll';
				break;
			case 'forminator-quiz':
				$module_slug = 'quiz';
				break;
			default:
				$module_slug = 'form';
				break;
		}

		return $module_slug;
	}

	/**
	 * Clone Module
	 *
	 * @since 1.6
	 *
	 * @param int $id Module Id.
	 */
	public function clone_module( $id ) {
		// check if this id is valid and the record is exists.
		$model = Forminator_Base_Form_Model::get_model( $id );

		if ( is_object( $model ) ) {
			// create one.
			// reset id.
			$model->id = null;

			// update title.
			if ( isset( $model->settings['formName'] ) ) {
				$model->settings['formName'] = /* translators: %s: Form name */ sprintf( esc_html__( 'Copy of %s', 'forminator' ), esc_html( $model->settings['formName'] ) );
			}

			// save it to create new record.
			$new_id = $model->save( true );
			if ( is_wp_error( $new_id ) ) {
				return $new_id;
			}

			/**
			 * Action called after module cloned
			 *
			 * @since 1.11
			 *
			 * @param int    $new_id - module id.
			 * @param object $model - module model.
			 *
			 * @since 1.39
			 * @param int    $id - Old module id.
			 */
			do_action( 'forminator_' . static::$module_slug . '_action_clone', $new_id, $model, $id );

			$function = 'forminator_clone_' . static::$module_slug . '_submissions_retention';
			if ( function_exists( $function ) ) {
				$function( $id, $new_id );
			}

			// Purge count forms cache.
			$cache_prefix = 'forminator_' . static::$module_slug . '_total_entries';
			wp_cache_delete( $cache_prefix, $cache_prefix );
			wp_cache_delete( $cache_prefix . '_publish', $cache_prefix . '_publish' );
			wp_cache_delete( $cache_prefix . '_draft', $cache_prefix . '_draft' );
			// Call do action after create duplicate module.
			Forminator_Base_Form_Model::module_update_do_action( static::$module_slug, $new_id, $model );

		}
	}

	/**
	 * Delete module
	 *
	 * @since 1.6
	 *
	 * @param int $id Module Id.
	 */
	public static function delete_module( $id ) {
		// check if this id is valid and the record is exists.
		$model = Forminator_Base_Form_Model::get_model( $id );
		if ( is_object( $model ) ) {
			$validate = forminator_validate_registration_form_settings( $model->settings );
			if ( is_wp_error( $validate ) ) {
				return $validate;
			}
			// For Quizzes with Leads.
			if ( isset( $model->settings['hasLeads'] ) && isset( $model->settings['leadsId'] ) && $model->settings['hasLeads'] ) {
				$leads_id    = $model->settings['leadsId'];
				$leads_model = Forminator_Base_Form_Model::get_model( $leads_id );

				if ( is_object( $leads_model ) ) {
					wp_delete_post( $leads_id );
					self::delete_css( $leads_id );
				}
			}

			Forminator_Form_Entry_Model::delete_by_form( $id );
			$form_view = Forminator_Form_Views_Model::get_instance();
			$form_view->delete_by_form( $id );

			$post_status = get_post_status( $id );
			if ( 'pdf_form' === $post_status ) {
				$model::$module_slug = 'pdf';
			}

			$function = 'forminator_update_' . $model::$module_slug . '_submissions_retention';
			if ( function_exists( $function ) ) {
				$function( $id, null, null );
			}
			wp_delete_post( $id );
			self::delete_css( $id );

			// Purge count forms cache.
			$cache_prefix = 'forminator_' . $model::$module_slug . '_total_entries';
			wp_cache_delete( $cache_prefix, $cache_prefix );
			wp_cache_delete( $cache_prefix . '_publish', $cache_prefix . '_publish' );
			wp_cache_delete( $cache_prefix . '_draft', $cache_prefix . '_draft' );

			/**
			 * Action called after module deleted
			 *
			 * @since 1.11
			 *
			 * @param int    $id - module id.
			 */
			do_action( 'forminator_' . $model::$module_slug . '_action_delete', $id );
		}
	}

	/**
	 * Delete module CSS file
	 *
	 * @param int $id Module ID.
	 */
	private static function delete_css( $id ) {
		$css_file = Forminator_Assets_Enqueue::get_css_upload( $id, 'dir' );
		if ( file_exists( $css_file ) ) {
			// delete CSS file.
			wp_delete_file( $css_file );
		}
		$css_dir    = dirname( $css_file );
		$index_file = $css_dir . DIRECTORY_SEPARATOR . 'index.php';
		if ( file_exists( $index_file ) ) {
			// Delete index.php file inside `css` folder.
			wp_delete_file( $index_file );
		}

		// Ensure the WP_Filesystem is initialized.
		if ( ! function_exists( 'wp_filesystem' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		WP_Filesystem();

		// Global $wp_filesystem should be available now.
		global $wp_filesystem;

		// remove `css` folder.
		if ( $wp_filesystem->is_dir( $css_dir ) ) {
			$wp_filesystem->rmdir( $css_dir );
		}

		$module_dir = dirname( $css_dir );
		$files      = array_diff( scandir( $module_dir ), array( '.', '..', 'index.php' ) );

		// Completely remove module folder if there are no other files.
		if ( ! $files ) {
			$index_file = $module_dir . DIRECTORY_SEPARATOR . 'index.php';
			if ( file_exists( $index_file ) ) {
				// Delete index.php file inside module folder.
				wp_delete_file( $index_file );
			}
			// remove module folder.
			if ( $wp_filesystem->is_dir( $module_dir ) ) {
				$wp_filesystem->rmdir( $module_dir );
			}
		}
	}

	/**
	 * Delete module entries
	 *
	 * @since 1.6
	 *
	 * @param int $id Module Id.
	 */
	public function delete_module_entries( $id ) {
		// check if this id is valid and the record is exists.
		$model = Forminator_Base_Form_Model::get_model( $id );
		if ( is_object( $model ) ) {
			Forminator_Form_Entry_Model::delete_by_form( $id );
		}
	}

	/**
	 * Export module
	 *
	 * @since 1.6
	 *
	 * @param int $id Module Id.
	 */
	public function export_module( $id ) {

		$exportable = array();
		$model_name = '';
		$model      = Forminator_Base_Form_Model::get_model( $id );
		if ( $model instanceof Forminator_Base_Form_Model ) {
			$model_name = $model->name;
			$exportable = $model->to_exportable_data();
		}
		$encoded = wp_json_encode( $exportable );
		$fp      = fopen( 'php://memory', 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		fwrite( $fp, $encoded ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite
		fseek( $fp, 0 );

		$filename = esc_html__( 'forminator', 'forminator' ) . '-' . sanitize_title( $model_name ) . '-' . static::$module_slug . '-export.txt';

		header( 'Content-Description: File Transfer' );
		header( 'Content-Type: text/plain' );
		header( 'Content-Disposition: attachment; filename="' . basename( $filename ) . '"' );
		header( 'Cache-Control: must-revalidate' );
		header( 'Content-Length: ' . strlen( $encoded ) );

		// make php send the generated csv lines to the browser.
		ob_clean();
		fpassthru( $fp );
	}

	/**
	 * Override scripts to be loaded
	 *
	 * @since 1.11
	 *
	 * @param string $hook Hook name.
	 */
	public function enqueue_scripts( $hook ) {
		parent::enqueue_scripts( $hook );

		forminator_print_front_styles();
		forminator_print_front_scripts();
	}

	/**
	 * Process request
	 *
	 * @since 1.0
	 */
	public function processRequest() {
		$action = Forminator_Core::sanitize_text_field( 'forminator_action' );
		if ( ! $action ) {
			return;
		}

		if ( 'pdf-preview' === $action ) {
			$this->process_pdf_actions( $action );
			return;
		}

		$page = Forminator_Core::sanitize_text_field( 'page' );
		// Check if the page is not the relevant module type page and not forminator dashboard page.
		if ( 'forminator-' . forminator_get_prefix( static::$module_slug, 'c' ) !== $page && 'forminator' !== $page ) {
			return;
		}
		$form_type = Forminator_Core::sanitize_text_field( 'form_type' );
		// In forminator dashboard, check if form type is not the relevant module type.
		if ( 'forminator' === $page && $form_type && forminator_get_prefix( static::$module_slug, 'custom-' ) !== $form_type ) {
			return;
		}

		$id = filter_input( INPUT_POST, 'id', FILTER_VALIDATE_INT );
		// Set nonce names first for verification.
		switch ( $action ) {
			case 'clone':
				$nonce_name   = 'forminatorNonce';
				$nonce_action = 'forminator-nonce-clone-' . $id;
				break;

			case 'reset-views':
				$nonce_name   = 'forminatorNonce';
				$nonce_action = 'forminator-nonce-reset-views-' . $id;
				break;

			case 'update-status':
				$nonce_name   = 'forminator-nonce-update-status-' . $id;
				$nonce_action = $nonce_name;
				break;

			default:
				$nonce_name   = 'forminatorNonce';
				$nonce_action = 'forminator_' . static::$module_slug . '_request';
				break;
		}

		// Verify nonce.
		$nonce = Forminator_Core::sanitize_text_field( $nonce_name );
		if ( ! $nonce || ! wp_verify_nonce( $nonce, $nonce_action ) ) {
			return;
		}

		$plural_slug      = forminator_get_prefix( static::$module_slug, '', false, true );
		$is_redirect      = true;
		$ids              = Forminator_Core::sanitize_text_field( 'ids' );
		$module_ids       = ! empty( $ids ) ? explode( ',', $ids ) : array();
		$has_access_error = 0;
		$has_form_error   = 0;
		switch ( $action ) {
			case 'delete':
				if ( ! empty( $id ) ) {
					$result = static::delete_module( $id );
					if ( is_wp_error( $result ) ) {
						$error_message = $result->get_error_message();
					} else {
						$notice = static::$module_slug . '_deleted';
					}
				}
				break;

			case 'clone':
				if ( ! empty( $id ) ) {
					$result = $this->clone_module( $id );
					if ( is_wp_error( $result ) ) {
						$error_message = $result->get_error_message();
					} else {
						$notice = static::$module_slug . '_duplicated';
					}
				}
				break;

			case 'reset-views':
				if ( ! empty( $id ) ) {
					self::reset_module_views( $id );
					$notice = static::$module_slug . '_reset';
				}
				break;

			case 'delete-votes':
			case 'delete-entries':
				if ( ! empty( $id ) ) {
					$this->delete_module_entries( $id );
				}
				break;

			case 'export':
				if ( ! empty( $id ) ) {
					$this->export_module( $id );
				}
				$is_redirect = false;
				break;

			case 'delete-' . $plural_slug:
				foreach ( $module_ids as $id ) {
					$result = static::delete_module( $id );
					if ( is_wp_error( $result ) && ( in_array( $result->get_error_code(), array( 'invalid_access', 'invalid_user_role' ), true ) ) ) {
						++$has_access_error;
					}
				}
				if ( $has_access_error ) {
					$text_message = __( 'The selected form(s) have been deleted. Note: This action cannot be performed on forms without sufficient permissions.', 'forminator' );
				}
				break;

			case 'delete-votes-polls':
			case 'delete-entries-' . $plural_slug:
				foreach ( $module_ids as $id ) {
					$this->delete_module_entries( $id );
				}
				break;

			case 'clone-' . $plural_slug:
				foreach ( $module_ids as $id ) {
					$result = $this->clone_module( $id );
					if ( is_wp_error( $result ) && ( in_array( $result->get_error_code(), array( 'invalid_access', 'invalid_user_role' ), true ) ) ) {
						++$has_access_error;
					}
				}
				if ( $has_access_error ) {
					$text_message = esc_html__( 'The selected form(s) have been duplicated. Note: This action cannot be performed on forms without sufficient permissions.', 'forminator' );
				}
				break;

			case 'reset-views-' . $plural_slug:
				foreach ( $module_ids as $id ) {
					self::reset_module_views( $id );
				}
				break;

			case 'update-status':
				$status = Forminator_Core::sanitize_text_field( 'status' );

				if ( ! empty( $id ) && ! empty( $status ) ) {
					// only publish and draft status avail.
					if ( in_array( $status, array( 'publish', 'draft' ), true ) ) {
						$model = Forminator_Base_Form_Model::get_model( $id );
						if ( $model instanceof Forminator_Base_Form_Model ) {
							$is_publishable = $model->is_publishable();
							if ( 'publish' === $status && is_wp_error( $is_publishable ) ) {
								$error_message = $is_publishable->get_error_message();
								break;
							}
							$model->status = $status;
							$result        = $model->save();
							if ( is_wp_error( $result ) ) {
								$error_message = $result->get_error_message();
							} else {
								// Call module update do action on status update.
								Forminator_Base_Form_Model::module_update_do_action( static::$module_slug, $id, $model );
							}
						}
					}
				}
				break;
			case 'update-statuses':
				$status = Forminator_Core::sanitize_text_field( 'status' );

				// only publish and draft status avail.
				if ( ! empty( $status ) && in_array( $status, array( 'publish', 'draft' ), true ) ) {
					foreach ( $module_ids as $id ) {
						$model = Forminator_Base_Form_Model::get_model( $id );
						if ( $model instanceof Forminator_Base_Form_Model ) {
							$model->status = $status;
							$result        = $model->save();
							if ( is_wp_error( $result ) && ( in_array( $result->get_error_code(), array( 'invalid_access', 'invalid_user_role' ), true ) ) ) {
								++$has_access_error;
							} else {
								// Call module update do action on status update.
								Forminator_Base_Form_Model::module_update_do_action( static::$module_slug, $id, $model );
							}
						}
					}
					if ( $has_access_error ) {
						$text_message = esc_html__( 'The selected form(s) have been updated. Note: This action cannot be performed on forms without sufficient permissions.', 'forminator' );
					}
				}
				break;

			case 'draft-' . $plural_slug:
				foreach ( $module_ids as $form_id ) {
					$result = $this->update_module_status( $form_id, 'draft' );
					if ( is_wp_error( $result ) && ( in_array( $result->get_error_code(), array( 'invalid_access', 'invalid_user_role' ), true ) ) ) {
						++$has_access_error;
					}
				}
				if ( $has_access_error ) {
					$text_message = esc_html__( 'The selected form(s) have been updated. Note: This action cannot be performed on forms without sufficient permissions.', 'forminator' );
				}
				break;

			case 'publish-' . $plural_slug:
				foreach ( $module_ids as $form_id ) {
					$result = $this->update_module_status( $form_id, 'publish' );
					if ( is_wp_error( $result ) ) {
						if ( in_array( $result->get_error_code(), array( 'invalid_access', 'invalid_user_role' ), true ) ) {
							++$has_access_error;
						} elseif ( 'form_error' === $result->get_error_code() ) {
							++$has_form_error;
						}
					}
				}
				if ( $has_access_error ) {
					$text_message = esc_html__( 'The selected form(s) have been updated. Note: This action cannot be performed on forms without sufficient permissions.', 'forminator' );
				} elseif ( $has_form_error ) {
					$text_message = esc_html__( 'The selected form(s) have been updated. Note: Some forms could not be published due to validation errors.', 'forminator' );
				}
				break;

			default:
				break;
		}

		if ( $is_redirect ) {
			$to_referer = true;

			if ( isset( $_POST['forminatorRedirect'] ) && 'false' === $_POST['forminatorRedirect'] ) {
				$to_referer = false;
			}

			$args   = array(
				'page' => $this->get_admin_page(),
			);
			$search = Forminator_Core::sanitize_text_field( 'msearch' );
			if ( $search ) {
				$args['module-search'] = $search;
				$to_referer            = false;
			}
			if ( ! empty( $notice ) ) {
				$args['forminator_notice'] = $notice;
				$to_referer                = false;
			}

			if ( ! empty( $error_message ) ) {
				$args['forminator_error_notice'] = $error_message;
				$to_referer                      = false;
			}

			if ( ! empty( $text_message ) ) {
				$args['forminator_text_notice'] = $text_message;
				$to_referer                     = false;
			}

			$fallback_redirect = add_query_arg(
				$args,
				admin_url( 'admin.php' )
			);

			$this->maybe_redirect_to_referer( $fallback_redirect, $to_referer );
		}

		exit;
	}

	/**
	 * Process PDF actions
	 *
	 * @param string $action PDF action.
	 *
	 * @since 1.25
	 */
	public function process_pdf_actions( $action ) {
		$page      = Forminator_Core::sanitize_text_field( 'page' );
		$form_type = Forminator_Core::sanitize_text_field( 'form_type' );
		if ( 'forminator-cform-wizard' !== $page && 'pdf-form' !== $form_type ) {
			return;
		}

		$nonce = Forminator_Core::sanitize_text_field( 'preview_nonce' );
		if ( ! $nonce || ! wp_verify_nonce( $nonce, 'forminator_load_module' ) ) {
			return;
		}

		if ( ! current_user_can( forminator_get_permission( 'forminator-cform' ) ) ) {
			return;
		}

		$pdf_id = Forminator_Core::sanitize_text_field( 'pdf_id' );
		if ( empty( $pdf_id ) ) {
			$pdf_id = Forminator_Core::sanitize_text_field( 'id' );
		}

		if ( ! is_numeric( $pdf_id ) ) {
			return;
		}

		if ( class_exists( 'Forminator_PDF_Form_Actions' ) ) {
			$pdf_action = new Forminator_PDF_Form_Actions();
			$pdf        = Forminator_API::get_module( $pdf_id );

			if ( 'pdf-preview' === $action ) {
				$pdf_action->process_pdf_download(
					$pdf,
					'preview',
					esc_html( $pdf->name . '_' . wp_date( 'M-j-y' ) . '.pdf' ),
					'I'
				);
				exit; // Do not remove.
			}
		}
	}

	/**
	 * Update Module Status
	 *
	 * @since 1.6
	 *
	 * @param int    $id Module Id.
	 * @param string $status Module status.
	 */
	public function update_module_status( $id, $status ) {
		// only publish and draft status avail.
		if ( in_array( $status, array( 'publish', 'draft' ), true ) ) {
			$model = Forminator_Base_Form_Model::get_model( $id );
			if ( $model instanceof Forminator_Base_Form_Model ) {
				$is_publishable = $model->is_publishable();
				if ( 'publish' === $status && is_wp_error( $is_publishable ) ) {
					return $is_publishable;
				}
				$model->status = $status;
				$result        = $model->save();
				if ( is_wp_error( $result ) ) {
					return $result;
				} else {
					// Call module update do action on status update.
					Forminator_Base_Form_Model::module_update_do_action( static::$module_slug, $id, $model );
				}
			}
		}
	}

	/**
	 * Reset views data
	 *
	 * @since 1.6
	 *
	 * @param int $id Module ID.
	 */
	public static function reset_module_views( $id ) {
		$form_types = forminator_form_types();
		$module     = get_post( $id );
		if ( ! empty( $module->post_type ) && in_array( $module->post_type, $form_types, true ) ) {
			$form_view = Forminator_Form_Views_Model::get_instance();
			$form_view->delete_by_form( $id );
		}
	}
}
