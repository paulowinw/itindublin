<?php
/**
 * Callable function for Autofill related
 *
 * @package Forminator
 */

/**
 * Build autofill providers from $slug_attributes array
 *
 * @since 1.0.5
 *
 * @param array $slug_attributes Slug attributes.
 *
 * @return array
 */
function forminator_build_autofill_providers( $slug_attributes ) {
	return Forminator_Autofill_Loader::get_instance()->get_grouped_autofill_providers( $slug_attributes );
}

/**
 * Init autofill provider by its slug
 *
 * @since 1.0.5
 *
 * @param string $provider_slug provider slug.
 *
 * @return Forminator_Autofill_Provider_Abstract|null
 */
function forminator_autofill_init_provider( $provider_slug ) {
	return Forminator_Autofill_Loader::get_instance()->init_provider( $provider_slug );
}
