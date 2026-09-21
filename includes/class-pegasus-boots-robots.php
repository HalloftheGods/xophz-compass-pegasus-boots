<?php
/**
 * Pegasus Boots AI Governance & Robots Engine.
 * Configures fine-grained access rules for AI retrieval bots and training scrapers.
 *
 * @package    Xophz_Compass_Pegasus_Boots
 * @subpackage Xophz_Compass_Pegasus_Boots/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Xophz_Compass_Pegasus_Boots_Robots {

	/**
	 * Register hooks.
	 */
	public function init(): void {
		add_filter( 'robots_txt', array( $this, 'filter_robots_txt' ), 20, 2 );
	}

	/**
	 * Append AI bot directives to robots.txt.
	 *
	 * @param string $output Current robots.txt content.
	 * @param bool   $public Whether site is public.
	 * @return string
	 */
	public function filter_robots_txt( string $output, bool $public ): string {
		if ( ! $public ) {
			return $output;
		}

		$settings = get_option( 'xophz_pegasus_boots_settings', array() );
		$is_governance_enabled = isset( $settings['robots_governance_enabled'] ) ? (bool) $settings['robots_governance_enabled'] : true;
		if ( ! $is_governance_enabled ) {
			return $output;
		}

		$ai_block = $this->generate_ai_directives( $settings );
		return trim( $output ) . "\n\n" . $ai_block . "\n";
	}

	/**
	 * Build AI directives string.
	 *
	 * @param array<string, mixed> $settings
	 * @return string
	 */
	public function generate_ai_directives( array $settings = array() ): string {
		$rules = isset( $settings['robots_ai_rules'] ) && is_array( $settings['robots_ai_rules'] )
			? $settings['robots_ai_rules']
			: $this->get_default_rules();

		$lines = array(
			'# START Pegasus Boots AI Governance',
			'Allow: /llms.txt',
			'Allow: /llms-full.txt',
			'',
		);

		foreach ( $rules as $bot_slug => $action ) {
			$ua_name = $this->resolve_user_agent_name( $bot_slug );
			if ( empty( $ua_name ) ) {
				continue;
			}

			$lines[] = 'User-agent: ' . $ua_name;
			if ( 'disallow' === $action ) {
				$lines[] = 'Disallow: /';
			} else {
				$lines[] = 'Allow: /';
			}
			$lines[] = '';
		}

		$lines[] = '# END Pegasus Boots AI Governance';

		return implode( "\n", $lines );
	}

	/**
	 * Default governance rules: Allow citation retrieval bots, disallow heavy training scrapers.
	 *
	 * @return array<string, string>
	 */
	public function get_default_rules(): array {
		return array(
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
		);
	}

	/**
	 * Map slug to official User-Agent string.
	 *
	 * @param string $slug
	 * @return string
	 */
	protected function resolve_user_agent_name( string $slug ): string {
		if ( class_exists( 'Xophz_Compass_AI_Detector' ) ) {
			$catalog = Xophz_Compass_AI_Detector::get_known_bots();
			if ( isset( $catalog[ $slug ]['name'] ) ) {
				return $catalog[ $slug ]['name'];
			}
		}

		return ucfirst( $slug );
	}
}
