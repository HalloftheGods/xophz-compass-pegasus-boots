<?php
/**
 * Pegasus Boots LLMS.txt Protocol Engine.
 * Serves dynamic /llms.txt and /llms-full.txt manifests for AI search engines and LLM crawlers.
 *
 * @package    Xophz_Compass_Pegasus_Boots
 * @subpackage Xophz_Compass_Pegasus_Boots/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Xophz_Compass_Pegasus_Boots_LLMS {

	/**
	 * Settings option name.
	 */
	const OPTION_KEY = 'xophz_pegasus_boots_settings';

	/**
	 * Register hooks.
	 */
	public function init(): void {
		add_action( 'init', array( $this, 'add_rewrite_rules' ) );
		add_filter( 'query_vars', array( $this, 'add_query_vars' ) );
		add_action( 'template_redirect', array( $this, 'handle_request' ) );
	}

	/**
	 * Add rewrite rules for /llms.txt and /llms-full.txt.
	 */
	public function add_rewrite_rules(): void {
		add_rewrite_rule( '^llms\.txt$', 'index.php?pegasus_boots_llms=1', 'top' );
		add_rewrite_rule( '^llms-full\.txt$', 'index.php?pegasus_boots_llms_full=1', 'top' );
	}

	/**
	 * Register query variables with WordPress.
	 *
	 * @param array<string> $vars Existing query vars.
	 * @return array<string>
	 */
	public function add_query_vars( array $vars ): array {
		$vars[] = 'pegasus_boots_llms';
		$vars[] = 'pegasus_boots_llms_full';
		return $vars;
	}

	/**
	 * Intercept template redirection to serve llms manifests.
	 */
	public function handle_request(): void {
		$is_llms      = get_query_var( 'pegasus_boots_llms' );
		$is_llms_full = get_query_var( 'pegasus_boots_llms_full' );

		if ( empty( $is_llms ) && empty( $is_llms_full ) ) {
			return;
		}

		$settings = get_option( self::OPTION_KEY, array() );
		$is_enabled = isset( $settings['llms_enabled'] ) ? (bool) $settings['llms_enabled'] : true;

		if ( ! $is_enabled ) {
			status_header( 404 );
			nocache_headers();
			echo "# LLMS Manifest Disabled\n";
			exit;
		}

		// Set content type and cache headers
		header( 'Content-Type: text/plain; charset=utf-8' );
		header( 'X-Robots-Tag: noindex' );
		header( 'Cache-Control: public, max-age=3600' );

		if ( ! empty( $is_llms_full ) ) {
			echo $this->generate_full_manifest( $settings );
		} else {
			echo $this->generate_standard_manifest( $settings );
		}
		exit;
	}

	/**
	 * Generate standard /llms.txt manifest.
	 *
	 * @param array<string, mixed> $settings
	 * @return string
	 */
	public function generate_standard_manifest( array $settings = array() ): string {
		$site_name = get_bloginfo( 'name' );
		$site_desc = get_bloginfo( 'description' );
		$site_url  = home_url( '/' );

		$output  = "# " . esc_html( $site_name ) . "\n\n";
		if ( ! empty( $site_desc ) ) {
			$output .= "> " . esc_html( $site_desc ) . "\n\n";
		}

		$notes = isset( $settings['llms_notes'] ) ? trim( (string) $settings['llms_notes'] ) : '';
		if ( ! empty( $notes ) ) {
			$output .= "## Information for LLMs\n\n";
			$output .= esc_html( $notes ) . "\n\n";
		}

		// Core Pages
		$output .= "## Core Navigation & Documents\n\n";
		$pages = get_pages( array(
			'number'      => 15,
			'sort_column' => 'menu_order,post_title',
			'post_status' => 'publish',
		) );

		foreach ( $pages as $page ) {
			$title = esc_html( $page->post_title );
			$link  = esc_url( get_permalink( $page ) );
			$desc  = ! empty( $page->post_excerpt ) ? wp_strip_all_tags( $page->post_excerpt ) : '';
			if ( empty( $desc ) ) {
				$desc = wp_trim_words( wp_strip_all_tags( $page->post_content ), 20, '...' );
			}
			$output .= "- [{$title}]({$link}): {$desc}\n";
		}
		$output .= "\n";

		// Public Post Types
		$post_types = isset( $settings['llms_post_types'] ) && is_array( $settings['llms_post_types'] )
			? $settings['llms_post_types']
			: array( 'post' );

		if ( in_array( 'post', $post_types, true ) ) {
			$output .= "## Articles & Insights\n\n";
			$posts = get_posts( array(
				'numberposts' => 20,
				'post_status' => 'publish',
			) );

			foreach ( $posts as $post ) {
				$title = esc_html( $post->post_title );
				$link  = esc_url( get_permalink( $post ) );
				$desc  = wp_trim_words( wp_strip_all_tags( $post->post_content ), 20, '...' );
				$output .= "- [{$title}]({$link}): {$desc}\n";
			}
			$output .= "\n";
		}

		// Optional Full link
		$output .= "## Full Content Manifest\n\n";
		$output .= "- [Full Deep Manifest](" . esc_url( home_url( '/llms-full.txt' ) ) . "): Comprehensive plain text markdown feed.\n";

		return $output;
	}

	/**
	 * Generate deep /llms-full.txt manifest.
	 *
	 * @param array<string, mixed> $settings
	 * @return string
	 */
	public function generate_full_manifest( array $settings = array() ): string {
		$site_name = get_bloginfo( 'name' );
		$output = "# " . esc_html( $site_name ) . " (Full Deep Manifest)\n\n";

		$posts = get_posts( array(
			'numberposts' => 30,
			'post_status' => 'publish',
		) );

		foreach ( $posts as $post ) {
			$output .= "---\n";
			$output .= "# " . esc_html( $post->post_title ) . "\n";
			$output .= "URL: " . esc_url( get_permalink( $post ) ) . "\n\n";
			if ( class_exists( 'Xophz_Compass_Markdown' ) ) {
				$output .= Xophz_Compass_Markdown::from_post( $post );
			} else {
				$output .= wp_strip_all_tags( $post->post_content );
			}
			$output .= "\n\n";
		}

		return $output;
	}
}
