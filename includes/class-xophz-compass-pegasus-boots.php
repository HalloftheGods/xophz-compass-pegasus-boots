<?php

/**
 * The core plugin class for Pegasus Boots.
 *
 * @link       https://youmeos.com
 * @since      1.0.0
 * @package    Xophz_Compass_Pegasus_Boots
 * @subpackage Xophz_Compass_Pegasus_Boots/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Xophz_Compass_Plugin_Base' ) ) {
	$core_plugin_base = dirname( dirname( __DIR__ ) ) . '/xophz-compass/includes/core/class-compass-plugin-base.php';
	if ( file_exists( $core_plugin_base ) ) {
		require_once $core_plugin_base;
	}
}

class Xophz_Compass_Pegasus_Boots extends Xophz_Compass_Plugin_Base {

	/**
	 * Unique identifier of this plugin.
	 *
	 * @var string
	 */
	protected $plugin_name;

	/**
	 * Subsystems.
	 */
	protected ?Xophz_Compass_Pegasus_Boots_LLMS $llms = null;
	protected ?Xophz_Compass_Pegasus_Boots_Negotiator $negotiator = null;
	protected ?Xophz_Compass_Pegasus_Boots_Schema $schema = null;
	protected ?Xophz_Compass_Pegasus_Boots_Telemetry $telemetry = null;
	protected ?Xophz_Compass_Pegasus_Boots_Robots $robots = null;

	/**
	 * Constructor.
	 *
	 * @param string|null $param1
	 * @param string|null $version
	 * @param string      $param3
	 */
	public function __construct( ?string $param1 = null, ?string $version = null, string $param3 = '' ) {
		if ( null === $param1 ) {
			$file = dirname( __DIR__ ) . '/xophz-compass-pegasus-boots.php';
			$ver  = defined( 'XOPHZ_COMPASS_PEGASUS_BOOTS_VERSION' ) ? XOPHZ_COMPASS_PEGASUS_BOOTS_VERSION : '26.9.11';
			parent::__construct( $file, $ver, 'xophz-compass-pegasus-boots' );
		} else {
			parent::__construct( $param1, $version ?? '26.9.11', $param3 );
		}

		$this->plugin_name = $this->text_domain;
	}

	/**
	 * Load subsystem classes.
	 */
	private function load_dependencies(): void {
		$inc = plugin_dir_path( __FILE__ );

		require_once $inc . 'class-pegasus-boots-llms.php';
		require_once $inc . 'class-pegasus-boots-negotiator.php';
		require_once $inc . 'class-pegasus-boots-schema.php';
		require_once $inc . 'class-pegasus-boots-telemetry.php';
		require_once $inc . 'class-pegasus-boots-robots.php';
		require_once $inc . 'class-pegasus-boots-rest.php';

		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-xophz-compass-pegasus-boots-admin.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-xophz-compass-pegasus-boots-public.php';
	}

	/**
	 * Child initialization hook called from Xophz_Compass_Plugin_Base.
	 */
	public function init(): void {
		$this->plugin_name = $this->plugin_name ?? $this->text_domain;
		$this->load_dependencies();
		$this->define_admin_hooks();
		$this->define_public_hooks();

		$this->llms = new Xophz_Compass_Pegasus_Boots_LLMS();
		$this->llms->init();

		$this->negotiator = new Xophz_Compass_Pegasus_Boots_Negotiator();
		$this->negotiator->init();

		$this->schema = new Xophz_Compass_Pegasus_Boots_Schema();
		$this->schema->init();

		$this->telemetry = new Xophz_Compass_Pegasus_Boots_Telemetry();
		$this->telemetry->init();

		$this->robots = new Xophz_Compass_Pegasus_Boots_Robots();
		$this->robots->init();
	}

	/**
	 * Register REST API routes.
	 */
	public function register_rest_routes(): void {
		$rest = new Xophz_Compass_Pegasus_Boots_REST();
		$rest->register_routes();
	}

	/**
	 * YouMeOS Spark Definition for Compass registry.
	 *
	 * @return array<string, mixed>
	 */
	public function get_spark_definition(): ?array {
		return array(
			'id'          => 'pegasus-boots',
			'name'        => 'PegaSEOus Boots',
			'tagline'     => 'Generative Engine Optimization (GEO) & Search Acceleration',
			'description' => 'Autonomous llms.txt generation, Markdown content negotiation, Schema.org Knowledge Graphs, and AI crawler governance.',
			'icon'        => 'fal fa-boot',
			'route'       => '/pegasus-boots',
			'category'    => 'growth',
			'version'     => $this->version,
		);
	}

	/**
	 * Admin hooks.
	 */
	private function define_admin_hooks(): void {
		$plugin_admin = new Xophz_Compass_Pegasus_Boots_Admin( $this->get_xophz_compass_pegasus_boots(), $this->get_version() );
		$this->add_action( 'admin_menu', $plugin_admin, 'addToMenu' );
	}

	/**
	 * Public hooks.
	 */
	private function define_public_hooks(): void {
		$plugin_public = new Xophz_Compass_Pegasus_Boots_Public( $this->get_xophz_compass_pegasus_boots(), $this->get_version() );
	}

	/**
	 * Name accessor.
	 *
	 * @return string
	 */
	public function get_xophz_compass_pegasus_boots(): string {
		return $this->plugin_name;
	}

	/**
	 * Version accessor.
	 *
	 * @return string
	 */
	public function get_version(): string {
		return $this->version;
	}
}
