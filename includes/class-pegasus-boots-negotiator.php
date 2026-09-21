<?php
/**
 * Pegasus Boots Markdown Content Negotiator.
 * Dynamically streams clean Markdown directly to AI engines and markdown-enabled user agents.
 *
 * @package    Xophz_Compass_Pegasus_Boots
 * @subpackage Xophz_Compass_Pegasus_Boots/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Xophz_Compass_Pegasus_Boots_Negotiator {

	/**
	 * Register hooks.
	 */
	public function init(): void {
		add_action( 'template_redirect', array( $this, 'negotiate_content' ), 5 );
	}

	/**
	 * Evaluate incoming request for Markdown content delivery.
	 */
	public function negotiate_content(): void {
		if ( is_admin() || wp_doing_ajax() || wp_doing_cron() ) {
			return;
		}

		if ( ! is_singular() ) {
			return;
		}

		$settings = get_option( 'xophz_pegasus_boots_settings', array() );
		$is_negotiation_enabled = isset( $settings['markdown_negotiation_enabled'] ) ? (bool) $settings['markdown_negotiation_enabled'] : true;
		if ( ! $is_negotiation_enabled ) {
			return;
		}

		$post = get_queried_object();
		if ( ! ( $post instanceof WP_Post ) ) {
			return;
		}

		$auto_for_ai = isset( $settings['markdown_auto_for_ai'] ) ? (bool) $settings['markdown_auto_for_ai'] : true;
		$is_ai_bot   = class_exists( 'Xophz_Compass_AI_Detector' ) && Xophz_Compass_AI_Detector::is_ai_bot();
		$wants_md    = class_exists( 'Xophz_Compass_AI_Detector' ) && Xophz_Compass_AI_Detector::wants_markdown();

		$should_serve_markdown = $wants_md || ( $auto_for_ai && $is_ai_bot );
		if ( ! $should_serve_markdown ) {
			return;
		}

		// Stream Markdown
		header( 'Content-Type: text/markdown; charset=utf-8' );
		header( 'X-Markdown-Stream: compass-pegasus-boots' );
		header( 'Vary: Accept, User-Agent' );

		if ( class_exists( 'Xophz_Compass_Markdown' ) ) {
			echo Xophz_Compass_Markdown::from_post( $post );
		} else {
			echo "# " . esc_html( $post->post_title ) . "\n\n";
			echo wp_strip_all_tags( $post->post_content );
		}

		exit;
	}
}
