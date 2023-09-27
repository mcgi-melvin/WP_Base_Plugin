<?php

namespace BasePlugin\core\functions;

if( ! class_exists( 'pluginUpdateChecker' ) ) {

	class pluginUpdateChecker{

		public $plugin_slug;
		public $version;
		public $cache_key;
		public $cache_allowed;
		private $plugin_id;

		public function __construct( $plugin_id = null ) {

			if( !$plugin_id ) return false;

			$this->plugin_id = $plugin_id;

			$this->plugin_slug = plugin_basename( __DIR__ );
			$this->version = '1.0';
			$this->cache_key = 'ez_plugins';
			$this->cache_allowed = false;

			add_filter( 'plugins_api', array( $this, 'info' ), 20, 3 );
			add_filter( 'site_transient_update_plugins', array( $this, 'update' ) );
			add_action( 'upgrader_process_complete', array( $this, 'purge' ), 10, 2 );

		}

		public function request(){

			$remote = get_transient( $this->cache_key );

			if( false === $remote || ! $this->cache_allowed ) {

				$remote = wp_remote_get(
					"https://melvinlomibao.com/wp-admin/admin-ajax.php?action=get_latest_plugin_info&{$this->plugin_id}",
					array(
						'timeout' => 10,
						'headers' => array(
							'Accept' => 'application/json'
						)
					)
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

			$remote = json_decode( wp_remote_retrieve_body( $remote ) );

			return $remote;

		}


		function info( $res, $action, $args ) {
			if( 'plugin_information' !== $action ) {
				return $res;
			}

			if( $this->plugin_slug !== $args->slug ) {
				return $res;
			}

			$remote = $this->request( $this->plugin_id );

			if( ! $remote ) {
				return $res;
			}

			$res = new stdClass();

			$res->name = $remote->name;
			$res->slug = $remote->slug;
			$res->version = $remote->version;
			$res->tested = $remote->wp_version_tested;
			$res->requires = $remote->wp_version_requires;
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

			$remote = $this->request( $this->plugin_id );

			if(
				$remote
				&& version_compare( $this->version, $remote->version, '<' )
				&& version_compare( $remote->wp_version_requires, get_bloginfo( 'version' ), '<=' )
				&& version_compare( $remote->php_requires, PHP_VERSION, '<' )
			) {
				$res = new stdClass();
				$res->slug = $this->plugin_slug;
				$res->plugin = plugin_basename( __FILE__ );
				$res->new_version = $remote->version;
				$res->tested = $remote->wp_version_tested;
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


	}
}