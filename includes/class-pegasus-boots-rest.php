<?php
/**
 * Pegasus Boots REST API Controller.
 * Exposes endpoints for GEO analytics, llms.txt generation, and bot governance.
 *
 * @package    Xophz_Compass_Pegasus_Boots
 * @subpackage Xophz_Compass_Pegasus_Boots/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Xophz_Compass_Pegasus_Boots_REST extends Xophz_Compass_REST_Controller {

	/**
	 * Settings option name.
	 */
	const OPTION_KEY = 'xophz_pegasus_boots_settings';

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct( 'pegasus-boots', 'v1' );
	}

	/**
	 * Register REST routes.
	 */
	public function register_routes(): void {
		// Stats endpoint
		register_rest_route( $this->namespace, '/stats', array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => array( $this, 'get_stats' ),
			'permission_callback' => array( $this, 'permissions_authenticated' ),
		) );

		// Settings endpoints
		register_rest_route( $this->namespace, '/settings', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_settings' ),
				'permission_callback' => array( $this, 'permissions_authenticated' ),
			),
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'update_settings' ),
				'permission_callback' => array( $this, 'permissions_admin' ),
			),
		) );

		// Preview endpoints
		register_rest_route( $this->namespace, '/preview/llms', array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => array( $this, 'get_llms_preview' ),
			'permission_callback' => array( $this, 'permissions_authenticated' ),
		) );

		register_rest_route( $this->namespace, '/preview/robots', array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => array( $this, 'get_robots_preview' ),
			'permission_callback' => array( $this, 'permissions_authenticated' ),
		) );

		// Content audit endpoint
		register_rest_route( $this->namespace, '/audit', array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => array( $this, 'run_geo_audit' ),
			'permission_callback' => array( $this, 'permissions_authenticated' ),
		) );
	}

	/**
	 * Retrieve dashboard stats and telemetry.
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response
	 */
	public function get_stats( WP_REST_Request $request ): WP_REST_Response {
		$telemetry = class_exists( 'Xophz_Compass_Pegasus_Boots_Telemetry' )
			? Xophz_Compass_Pegasus_Boots_Telemetry::get_stats()
			: array();

		$settings = $this->get_sanitized_settings();

		// Calculate GEO readiness score
		$score = 0;
		if ( ! empty( $settings['llms_enabled'] ) ) {
			$score += 25;
		}
		if ( ! empty( $settings['markdown_negotiation_enabled'] ) ) {
			$score += 25;
		}
		if ( ! empty( $settings['schema_entities_enabled'] ) ) {
			$score += 25;
		}
		if ( ! empty( $settings['robots_governance_enabled'] ) ) {
			$score += 25;
		}

		$data = array(
			'hits_24h'        => isset( $telemetry['hits_24h'] ) ? (int) $telemetry['hits_24h'] : 0,
			'hits_7d'         => isset( $telemetry['hits_7d'] ) ? (int) $telemetry['hits_7d'] : 0,
			'breakdown'       => isset( $telemetry['breakdown'] ) ? $telemetry['breakdown'] : array(),
			'recent_events'   => isset( $telemetry['recent_events'] ) ? $telemetry['recent_events'] : array(),
			'readiness_score' => $score,
			'llms_url'        => home_url( '/llms.txt' ),
			'llms_full_url'   => home_url( '/llms-full.txt' ),
			'llms_status'     => ! empty( $settings['llms_enabled'] ) ? 'active' : 'disabled',
			'settings'        => $settings,
		);

		return $this->success( $data );
	}

	/**
	 * Get current settings.
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response
	 */
	public function get_settings( WP_REST_Request $request ): WP_REST_Response {
		return $this->success( $this->get_sanitized_settings() );
	}

	/**
	 * Update settings.
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response
	 */
	public function update_settings( WP_REST_Request $request ): WP_REST_Response {
		$raw = $request->get_json_params();
		if ( ! is_array( $raw ) ) {
			return $this->error( 'invalid_payload', 'Payload must be a valid JSON object.', 400 );
		}

		$existing = $this->get_sanitized_settings();

		if ( isset( $raw['llms_enabled'] ) ) {
			$existing['llms_enabled'] = (bool) $raw['llms_enabled'];
		}
		if ( isset( $raw['llms_notes'] ) ) {
			$existing['llms_notes'] = sanitize_textarea_field( (string) $raw['llms_notes'] );
		}
		if ( isset( $raw['llms_post_types'] ) && is_array( $raw['llms_post_types'] ) ) {
			$existing['llms_post_types'] = array_map( 'sanitize_key', $raw['llms_post_types'] );
		}
		if ( isset( $raw['markdown_negotiation_enabled'] ) ) {
			$existing['markdown_negotiation_enabled'] = (bool) $raw['markdown_negotiation_enabled'];
		}
		if ( isset( $raw['markdown_auto_for_ai'] ) ) {
			$existing['markdown_auto_for_ai'] = (bool) $raw['markdown_auto_for_ai'];
		}
		if ( isset( $raw['schema_entities_enabled'] ) ) {
			$existing['schema_entities_enabled'] = (bool) $raw['schema_entities_enabled'];
		}
		if ( isset( $raw['same_as_links'] ) && is_array( $raw['same_as_links'] ) ) {
			$existing['same_as_links'] = array_filter( array_map( 'esc_url_raw', $raw['same_as_links'] ) );
		}
		if ( isset( $raw['robots_governance_enabled'] ) ) {
			$existing['robots_governance_enabled'] = (bool) $raw['robots_governance_enabled'];
		}
		if ( isset( $raw['robots_ai_rules'] ) && is_array( $raw['robots_ai_rules'] ) ) {
			$clean_rules = array();
			foreach ( $raw['robots_ai_rules'] as $bot => $action ) {
				$clean_rules[ sanitize_key( $bot ) ] = 'disallow' === $action ? 'disallow' : 'allow';
			}
			$existing['robots_ai_rules'] = $clean_rules;
		}

		update_option( self::OPTION_KEY, $existing, false );

		return $this->success( $existing );
	}

	/**
	 * Get preview of generated llms.txt.
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response
	 */
	public function get_llms_preview( WP_REST_Request $request ): WP_REST_Response {
		$llms_engine = new Xophz_Compass_Pegasus_Boots_LLMS();
		$settings    = $this->get_sanitized_settings();

		$data = array(
			'standard' => $llms_engine->generate_standard_manifest( $settings ),
			'full'     => $llms_engine->generate_full_manifest( $settings ),
		);

		return $this->success( $data );
	}

	/**
	 * Get preview of robots.txt additions.
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response
	 */
	public function get_robots_preview( WP_REST_Request $request ): WP_REST_Response {
		$robots_engine = new Xophz_Compass_Pegasus_Boots_Robots();
		$settings      = $this->get_sanitized_settings();

		$data = array(
			'directives' => $robots_engine->generate_ai_directives( $settings ),
		);

		return $this->success( $data );
	}

	/**
	 * Perform lightweight content audit for GEO readiness.
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response
	 */
	public function run_geo_audit( WP_REST_Request $request ): WP_REST_Response {
		$posts = get_posts( array(
			'post_type'      => array( 'post', 'page' ),
			'post_status'    => 'publish',
			'posts_per_page' => 10,
		) );

		$results = array();

		foreach ( $posts as $post ) {
			$content = wp_strip_all_tags( $post->post_content );
			$words   = str_word_count( $content );
			$has_excerpt = ! empty( $post->post_excerpt );
			$has_answers = preg_match( '/\b(is|are|means|defined as|consists of)\b/i', substr( $content, 0, 300 ) );

			$post_score = 40;
			if ( $words > 300 ) {
				$post_score += 20;
			}
			if ( $has_excerpt ) {
				$post_score += 20;
			}
			if ( $has_answers ) {
				$post_score += 20;
			}

			$results[] = array(
				'id'          => $post->ID,
				'title'       => $post->post_title,
				'type'        => $post->post_type,
				'url'         => get_permalink( $post ),
				'word_count'  => $words,
				'has_excerpt' => $has_excerpt,
				'score'       => $post_score,
				'status'      => $post_score >= 80 ? 'optimized' : ( $post_score >= 60 ? 'good' : 'needs_work' ),
			);
		}

		return $this->success( array( 'audit_items' => $results ) );
	}

	/**
	 * Retrieve sanitized settings with complete defaults.
	 *
	 * @return array<string, mixed>
	 */
	protected function get_sanitized_settings(): array {
		$defaults = array(
			'llms_enabled'                  => true,
			'llms_notes'                    => 'Project Compass high-speed application suite documentation and service endpoints.',
			'llms_post_types'               => array( 'page', 'post' ),
			'markdown_negotiation_enabled'  => true,
			'markdown_auto_for_ai'          => true,
			'schema_entities_enabled'       => true,
			'same_as_links'                 => array(),
			'robots_governance_enabled'     => true,
			'robots_ai_rules'               => array(
				'perplexitybot'      => 'allow',
				'gptbot'             => 'allow',
				'chatgpt-user'       => 'allow',
				'claudebot'          => 'allow',
				'google-extended'    => 'allow',
				'applebot-extended'  => 'allow',
				'amazonbot'          => 'allow',
				'meta-externalagent' => 'allow',
				'bytespider'         => 'disallow',
				'ccbot'              => 'disallow',
				'diffbot'            => 'disallow',
				'cohere-ai'          => 'disallow',
			),
		);

		$stored = get_option( self::OPTION_KEY, array() );
		return wp_parse_args( is_array( $stored ) ? $stored : array(), $defaults );
	}
}
