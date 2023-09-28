<?php

namespace BasePlugin;

use BasePlugin\core\updateChecker;

if( !class_exists('BasePlugin\EZ_Plugin') ) {
    abstract class EZ_Plugin
    {
        /**
         * @var updateChecker $updateChecker
         */
        private $updateChecker;

        abstract function id(): int;

        abstract function slug(): string;

        abstract function version(): float;

        abstract function baseFile(): string;

        abstract function enable_cache(): bool;

        public function __construct()
        {
            $this->updateChecker = new updateChecker();
        }

        public function init()
        {
            $this->updateChecker->config([
                'plugin' => $this->baseFile(),
                'plugin_slug' => $this->slug(),
                'plugin_id' => $this->id(),
                'version' => $this->version(),
                'cache_allowed' => $this->enable_cache()
            ])->run();
        }
    }
}
