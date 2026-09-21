<?php
/**
 * Pegasus Boots AI Bot Telemetry & Crawl Intelligence.
 * Logs and aggregates visits from generative search crawlers and LLM retrieval agents.
 *
 * @package    Xophz_Compass_Pegasus_Boots
 * @subpackage Xophz_Compass_Pegasus_Boots/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Xophz_Compass_Pegasus_Boots_Telemetry {

	/**
	 * Option key for telemetry logs and stats.
	 */
	const STATS_KEY = 'xophz_pegasus_boots_telemetry';

	/**
	 * Register hooks.
	 */
	public function init(): void {
		add_action( 'template_redirect', array( $this, 'record_crawl' ), 1 );
	}

	/**
	 * Record incoming crawl event if from an AI bot.
	 */
	public function record_crawl(): void {
		if ( is_admin() || wp_doing_ajax() || wp_doing_cron() ) {
			return;
		}

		if ( ! class_exists( 'Xophz_Compass_AI_Detector' ) ) {
			return;
		}

		$bot_info = Xophz_Compass_AI_Detector::get_bot_info();
		if ( ! $bot_info ) {
			return;
		}

		$stats = get_option( self::STATS_KEY, array(
			'hits_24h'       => 0,
			'hits_7d'        => 0,
			'last_reset_24h' => time(),
			'last_reset_7d'  => time(),
			'breakdown'      => array(),
			'recent_events'  => array(),
		) );

		$now = time();

		// Handle rolling resets
		if ( $now - $stats['last_reset_24h'] > 86400 ) {
			$stats['hits_24h'] = 0;
			$stats['last_reset_24h'] = $now;
		}

		if ( $now - $stats['last_reset_7d'] > 604800 ) {
			$stats['hits_7d'] = 0;
			$stats['last_reset_7d'] = $now;
		}

		// Increment counts
		$stats['hits_24h']++;
		$stats['hits_7d']++;

		$bot_name = $bot_info['name'];
		if ( ! isset( $stats['breakdown'][ $bot_name ] ) ) {
			$stats['breakdown'][ $bot_name ] = 0;
		}
		$stats['breakdown'][ $bot_name ]++;

		// Append recent event (capped at 25)
		$current_path = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '/';
		$is_markdown  = Xophz_Compass_AI_Detector::wants_markdown();

		$event = array(
			'bot'       => $bot_name,
			'provider'  => $bot_info['provider'],
			'category'  => $bot_info['category'],
			'path'      => $current_path,
			'timestamp' => current_time( 'mysql' ),
			'format'    => $is_markdown ? 'markdown' : 'html',
		);

		if ( ! isset( $stats['recent_events'] ) || ! is_array( $stats['recent_events'] ) ) {
			$stats['recent_events'] = array();
		}

		array_unshift( $stats['recent_events'], $event );
		if ( count( $stats['recent_events'] ) > 25 ) {
			$stats['recent_events'] = array_slice( $stats['recent_events'], 0, 25 );
		}

		update_option( self::STATS_KEY, $stats, false );
	}

	/**
	 * Retrieve telemetry stats with sensible defaults.
	 *
	 * @return array<string, mixed>
	 */
	public static function get_stats(): array {
		return get_option( self::STATS_KEY, array(
			'hits_24h'       => 0,
			'hits_7d'        => 0,
			'last_reset_24h' => time(),
			'last_reset_7d'  => time(),
			'breakdown'      => array(),
			'recent_events'  => array(),
		) );
	}
}
