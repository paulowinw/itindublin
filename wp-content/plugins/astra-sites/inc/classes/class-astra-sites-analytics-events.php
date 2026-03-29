<?php
/**
 * Analytics Events helper for event tracking.
 *
 * Supports both one-time (unique) and repeatable events.
 * Tracks events temporarily, sends them via BSF Analytics,
 * then cleans up. For unique events, a minimal deduplicate flag remains.
 *
 * @package Starter Templates
 * @since 4.4.51
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Astra_Sites_Analytics_Events Class.
 *
 * @since 4.4.51
 */
class Astra_Sites_Analytics_Events {

	/**
	 * Option name for pending events queue.
	 *
	 * @var string
	 */
	const OPTION_PENDING = 'astra_sites_usage_events_pending';

	/**
	 * Option name for pushed (sent) events deduplicate list.
	 *
	 * @var string
	 */
	const OPTION_PUSHED = 'astra_sites_usage_events_pushed';

	/**
	 * Track an event. By default, unique events are tracked only once.
	 * Pass $unique = false to allow the same event name multiple times.
	 *
	 * @param string               $event_name  Event identifier.
	 * @param string               $event_value Primary value (version, form ID, mode, etc.).
	 * @param array<string, mixed> $properties  Additional context as key-value pairs.
	 * @param bool                 $unique      Whether to deduplicate by event_name. Default true.
	 * @since 4.4.51
	 * @return void
	 */
	public static function track( $event_name, $event_value = '', $properties = array(), $unique = true ) {
		if ( $unique && self::is_tracked( $event_name ) ) {
			return;
		}

		$pending = get_option( self::OPTION_PENDING, array() );
		$pending = is_array( $pending ) ? $pending : array();

		// Add to pending queue. Store 'unique' flag internally for flush_pending().
		$pending[] = array(
			'event_name'  => sanitize_text_field( $event_name ),
			'event_value' => sanitize_text_field( (string) $event_value ),
			'properties'  => ! empty( $properties ) ? $properties : new \stdClass(),
			'date'        => current_time( 'mysql' ),
		);
		update_option( self::OPTION_PENDING, $pending );
	}

	/**
	 * Flush pending events: returns them for the payload, then cleans up.
	 *
	 * After this call:
	 * - astra_sites_usage_events_pending is EMPTY (full event data deleted).
	 * - astra_sites_usage_events_pushed has unique event_name strings added (deduplicated).
	 *
	 * @since 4.4.51
	 * @return array Pending events to include in payload. Empty if none.
	 */
	public static function flush_pending() {
		$pending = get_option( self::OPTION_PENDING, array() );
		if ( empty( $pending ) || ! is_array( $pending ) ) {
			return array();
		}

		// Add all event names to the pushed flag (ensures every event is sent at least once).
		$unique_event_names = array();
		foreach ( $pending as $event ) {
			if ( ! in_array( $event['event_name'], $unique_event_names, true ) ) {
				$unique_event_names[] = $event['event_name'];
			}
		}

		if ( ! empty( $unique_event_names ) ) {
			$pushed = get_option( self::OPTION_PUSHED, array() );
			$pushed = is_array( $pushed ) ? $pushed : array();
			$pushed = array_unique( array_merge( $pushed, $unique_event_names ) );
			update_option( self::OPTION_PUSHED, $pushed );
		}

		// DELETE all temporary event data.
		update_option( self::OPTION_PENDING, array() );

		return $pending;
	}

	/**
	 * Check if an event has already been tracked (sent or pending).
	 *
	 * @param string $event_name Event identifier.
	 * @since 4.4.51
	 * @return bool
	 */
	public static function is_tracked( $event_name ) {
		// Check pushed flag — already sent in a previous cycle.
		$pushed = get_option( self::OPTION_PUSHED, array() );
		$pushed = is_array( $pushed ) ? $pushed : array();
		if ( in_array( $event_name, $pushed, true ) ) {
			return true;
		}

		// Check if already queued in current cycle.
		$pending = get_option( self::OPTION_PENDING, array() );
		$pending = is_array( $pending ) ? $pending : array();
		return in_array( $event_name, wp_list_pluck( $pending, 'event_name' ), true );
	}
}
