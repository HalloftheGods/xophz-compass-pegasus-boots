<?php

/**
 * Fired during plugin activation.
 *
 * @package    Xophz_Compass_Pegasus_Boots
 * @subpackage Xophz_Compass_Pegasus_Boots/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Xophz_Compass_Pegasus_Boots_Activator {

	/**
	 * Run activation logic.
	 */
	public static function activate(): void {
		// Register rewrite rules for llms.txt
		add_rewrite_rule( '^llms\.txt$', 'index.php?pegasus_boots_llms=1', 'top' );
		add_rewrite_rule( '^llms-full\.txt$', 'index.php?pegasus_boots_llms_full=1', 'top' );

		// Flush rewrites
		flush_rewrite_rules();

		// Set default settings if not exists
		if ( false === get_option( 'xophz_pegasus_boots_settings' ) ) {
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
			update_option( 'xophz_pegasus_boots_settings', $defaults, false );
		}
	}
}
