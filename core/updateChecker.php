<?php
namespace BasePlugin\core;

use stdClass;

if( ! class_exists('BasePlugin\core\updateChecker') ) {

	class updateChecker {

        public $plugin;
		public $plugin_slug;
		public $version;
		public $cache_key;
		public $cache_allowed;

        /**
         * @var int $plugin_id
         */
		private $plugin_id;

		public function __construct() {
			$this->cache_key = 'ez_plugins_' . wp_unique_id();
			$this->cache_allowed = true;
		}

        public function config( array $config = [] ): updateChecker
        {
            foreach ( $config as $key => $val ) {
                if( property_exists( $this, $key ) ) {
                    $this->{$key} = $val;
                }
            }

            return $this;
        }

		public function request(){

			$remote = get_transient( $this->cache_key );

			if( false === $remote || ! $this->cache_allowed ) {

				$remote = wp_remote_get(
					"https://melvinlomibao.com/wp-admin/admin-ajax.php?action=get_latest_plugin_info&id={$this->plugin_id}",
					[
                        'timeout' => 10,
                        'headers' => [
                            'Accept' => 'application/json'
                        ]
                    ]
				);

				if(
					is_wp_error( $remote )
					|| 200 !== wp_remote_retrieve_response_code( $remote )
					|| empty( wp_remote_retrieve_body( $remote ) )
				) {
					return false;
				}

				set_transient( $this->cache_key, $remote, DAY_IN_SECONDS );
			}

            return json_decode( wp_remote_retrieve_body( $remote ) );
		}

		function info( $res, $action, $args ) {
			if( 'plugin_information' !== $action ) {
				return $res;
			}

			if( $this->plugin_slug !== $args->slug ) {
				return $res;
			}

			$remote = $this->request();

			if( ! $remote ) {
				return $res;
			}

			$res = new stdClass();

			$res->name = $remote->name;
			$res->slug = $remote->slug;
			$res->version = (float) $remote->version;
			$res->tested = (float) $remote->wp_version_tested;
			$res->requires = (float) $remote->wp_version_requires;
			$res->author = $remote->author;
			$res->author_profile = $remote->author_profile;
			$res->download_link = $remote->plugin_file;
			$res->trunk = $remote->plugin_file;
			$res->requires_php = $remote->php_requires;
			$res->last_updated = $remote->last_updated;

			$res->sections = array(
				'description' => $remote->sections->description,
				'installation' => $remote->sections->installation,
				'changelog' => $remote->sections->changelog
			);

			if( ! empty( $remote->banners ) ) {
				$res->banners = array(
					'low' => $remote->banners->low,
					'high' => $remote->banners->high
				);
			}

			return $res;

		}

		public function update( $transient ) {

			if ( empty($transient->checked ) ) {
				return $transient;
			}

			$remote = $this->request();

			if(
				$remote
				&& version_compare( $this->version, floatval( $remote->version ), '<' )
				&& version_compare( floatval( $remote->wp_version_requires ), get_bloginfo( 'version' ), '<=' )
				&& version_compare( floatval( $remote->php_requires ), PHP_VERSION, '<' )
			) {
                    $res = new stdClass();
                    $res->slug = $this->plugin_slug;
                    $res->plugin = $this->plugin;
                    $res->new_version = $remote->version;
                    $res->tested = (float) $remote->wp_version_tested;
                    $res->package = $remote->plugin_file;
                    $transient->response[ $res->plugin ] = $res;
            }

			return $transient;

		}

		public function purge( $upgrader, $options ){

			if (
				$this->cache_allowed
				&& 'update' === $options['action']
				&& 'plugin' === $options[ 'type' ]
			) {
				// just clean the cache when new plugin version is installed
				delete_transient( $this->cache_key );
			}

		}

        public function run()
        {
            add_filter( 'plugins_api', array( $this, 'info' ), 20, 3 );
            add_filter( 'site_transient_update_plugins', array( $this, 'update' ) );
            add_action( 'upgrader_process_complete', array( $this, 'purge' ), 10, 2 );
        }
	}
}