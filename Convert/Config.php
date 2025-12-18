<?php

namespace Convert;

class Exception extends \Exception {}
class Config {
    private static $config = null;
    private static $configObj = null;
    private $filename = __DIR__ . '/../conf.yaml';
    private $loaded = false;

    public static function getConfig(?string $filename = null) {
        if (self::$configObj === null) {
            self::$configObj = new Config($filename);
        }
        if ($filename && $filename != self::$config->filename) {
            throw new Exception('Cannot change config file');
        }
        return self::$configObj;
    }
    private function __construct(?string $filename = null) {
        if ($filename !== null) {
            $this->filename = $filename;
        }
        $this->load();
    }
    public function getDefaultConfigFileName() {
        return $this->filename;
    }
    public function load(?string $filename = null) {
        if (!$this->loaded) {
            if ($filename === null) {
                $filename = $this->getDefaultConfigFileName();
            }
            self::$config = yaml_parse_file($filename);
            $this->loaded = true;
        }
    }

    public function get($section) {
        if (!$this->loaded) {
            $this->load();
        }
        if (strstr($section, '.')) {
            $sections = explode('.', $section);
        } else {
            $sections= [ $section ];
        }
        $configPart = self::$config;
        foreach ($sections as $section) {
            if (isset($configPart[$section])) {
                $configPart = $configPart[$section];
            } else {
                return null;
            }
        }
        return $configPart;
    }
}