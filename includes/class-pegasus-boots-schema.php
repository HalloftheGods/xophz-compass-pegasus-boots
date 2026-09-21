<?php
/**
 * Pegasus Boots Knowledge Graph & Schema.org Injector.
 * Generates rich JSON-LD graph schema for generative search engine indexing.
 *
 * @package    Xophz_Compass_Pegasus_Boots
 * @subpackage Xophz_Compass_Pegasus_Boots/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Xophz_Compass_Pegasus_Boots_Schema {

	/**
	 * Register hooks.
	 */
	public function init(): void {
		add_action( 'wp_head', array( $this, 'inject_schema_graph' ), 2 );
	}

	/**
	 * Render JSON-LD script block.
	 */
	public function inject_schema_graph(): void {
		if ( is_admin() || wp_doing_ajax() ) {
			return;
		}

		$settings = get_option( 'xophz_pegasus_boots_settings', array() );
		$is_enabled = isset( $settings['schema_entities_enabled'] ) ? (bool) $settings['schema_entities_enabled'] : true;
		if ( ! $is_enabled ) {
			return;
		}

		$graph = array();

		// 1. Organization / WebSite Identity
		$site_url  = home_url( '/' );
		$site_name = get_bloginfo( 'name' );
		$same_as   = isset( $settings['same_as_links'] ) && is_array( $settings['same_as_links'] )
			? array_filter( array_map( 'esc_url_raw', $settings['same_as_links'] ) )
			: array();

		$org_id = $site_url . '#organization';
		$org_schema = array(
			'@type' => 'Organization',
			'@id'   => $org_id,
			'name'  => $site_name,
			'url'   => $site_url,
		);

		if ( ! empty( $same_as ) ) {
			$org_schema['sameAs'] = array_values( $same_as );
		}

		$graph[] = $org_schema;

		// 2. Singular Document / Article Schema
		if ( is_singular() ) {
			$post = get_queried_object();
			if ( $post instanceof WP_Post ) {
				$permalink   = get_permalink( $post );
				$author_name = get_the_author_meta( 'display_name', $post->post_author );
				$author_url  = get_author_posts_url( $post->post_author );

				$article_schema = array(
					'@type'            => 'Article',
					'@id'              => $permalink . '#article',
					'isPartOf'         => array( '@id' => $site_url . '#website' ),
					'headline'         => wp_strip_all_tags( $post->post_title ),
					'datePublished'    => get_the_date( 'c', $post ),
					'dateModified'     => get_the_modified_date( 'c', $post ),
					'mainEntityOfPage' => $permalink,
					'publisher'        => array( '@id' => $org_id ),
					'author'           => array(
						'@type' => 'Person',
						'name'  => $author_name,
						'url'   => $author_url,
					),
				);

				if ( ! empty( $post->post_excerpt ) ) {
					$article_schema['description'] = wp_strip_all_tags( $post->post_excerpt );
				}

				// Direct answer speakable specification
				$article_schema['speakable'] = array(
					'@type'        => 'SpeakableSpecification',
					'cssSelector'  => array( 'h1', '.direct-answer', '.entry-summary', 'article p:first-of-type' ),
				);

				$graph[] = $article_schema;
			}
		}

		if ( empty( $graph ) ) {
			return;
		}

		$payload = array(
			'@context' => 'https://schema.org',
			'@graph'   => $graph,
		);

		echo "\n<!-- Pegasus Boots GEO Knowledge Graph -->\n";
		echo '<script type="application/ld+json">' . wp_json_encode( $payload, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT ) . "</script>\n";
		echo "<!-- /Pegasus Boots GEO Knowledge Graph -->\n\n";
	}
}
