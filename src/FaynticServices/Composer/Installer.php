<?php

namespace FaynticServices\Composer;

use Composer\Script\Event;

class Installer
{
    /** @var string */
    private static $rootDirectory;

    /** @var string */
    private static $binDirectory;

    /** @var string */
    private static $cacheDirectory;

    /** @var string */
    private static $generatedDirectory;

    /** @var string */
    private static $srcDirectory;

    /** @var string */
    private static $sqlDirectory;

    /** @var array */
    private static $configuration = array();

    /**
     * @param Event $event
     */
    public static function dispatchStartup(Event $event)
    {
        self::$rootDirectory = realpath('.');

        self::setMaintenance(true);

        $composer = $event->getComposer();

        /** @var \Composer\Config $config */
        $config = $composer->getConfig();

        self::$binDirectory = $config->get('bin-dir');
        self::$cacheDirectory = $config->get('cache-dir');
        self::$generatedDirectory = $config->get('generated-dir');
        self::$srcDirectory = $config->get('src-dir');
        self::$sqlDirectory = $config->get('sql-dir');

        if (!is_dir(self::$binDirectory)) {
            mkdir(self::$binDirectory);
        }

        if (!is_dir(self::$cacheDirectory)) {
            mkdir(self::$cacheDirectory);
        }

        if (!is_dir(self::$generatedDirectory)) {
            mkdir(self::$generatedDirectory);
        }

        if (!is_dir(self::$srcDirectory)) {
            mkdir(self::$srcDirectory);
        }

        if (!is_dir(self::$sqlDirectory)) {
            mkdir(self::$sqlDirectory);
        }

        $configurationFile = self::$rootDirectory . DIRECTORY_SEPARATOR . 'configuration.json';
        if (is_file($configurationFile)) {
            self::$configuration = json_decode(file_get_contents($configurationFile), true);
        }

        self::$configuration = array_merge_recursive(
            self::$configuration,
            [
                'directories' => [
                    'root' => realpath(self::$rootDirectory),
                    'bin' => realpath(self::$binDirectory),
                    'cache' => realpath(self::$cacheDirectory),
                    'generated' => realpath(self::$generatedDirectory),
                    'src' => realpath(self::$srcDirectory),
                    'sql' => realpath(self::$sqlDirectory)
                ],
                'application' => [
                    'environment' => $event->isDevMode() ? 'development' : 'production',
                    'include_suffix' => uniqid()
                ],
                'security' => [
                    'password' => [
                        'cost' => self::generateCostValue(1)
                    ]
                ]
            ]
        );
    }

    /**
     * @param Event $event
     */
    public static function dispatchShutdown(Event $event)
    {
        self::clearCache();
        self::buildPropel();

        file_put_contents(self::$generatedDirectory . DIRECTORY_SEPARATOR . 'configuration.json', json_encode(self::$configuration, JSON_PRETTY_PRINT));

        self::setMaintenance(false);
    }

    /**
     * @param bool $status
     */
    private static function setMaintenance($status)
    {
        if ($status) {
            touch(self::$rootDirectory . DIRECTORY_SEPARATOR . '.maintenance');
        } else {
            unlink(self::$rootDirectory . DIRECTORY_SEPARATOR . '.maintenance');
        }
    }

    private static function clearCache()
    {
        $recursiveDirectoryIterator = new \RecursiveDirectoryIterator(self::$cacheDirectory, \FilesystemIterator::SKIP_DOTS);
        /** @var \DirectoryIterator[] $fileEntries */
        $fileEntries = new \RecursiveIteratorIterator($recursiveDirectoryIterator, \RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($fileEntries as $fileEntry) {
            if ($fileEntry->isDir()) {
                rmdir($fileEntry->getPathname());
            } else {
                unlink($fileEntry->getPathname());
            }
        }
    }

    private static function buildPropel()
    {
        // todo remove on actual (production) release
        $recursiveDirectoryIterator = new \RecursiveDirectoryIterator(self::$sqlDirectory . DIRECTORY_SEPARATOR . 'migration', \FilesystemIterator::SKIP_DOTS);
        /** @var \DirectoryIterator[] $fileEntries */
        $fileEntries = new \RecursiveIteratorIterator($recursiveDirectoryIterator, \RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($fileEntries as $fileEntry) {
            if ($fileEntry->isFile()) {
                unlink($fileEntry->getPathname());
            }
        }

        if (isset(self::$configuration['database'])) {
            $propelXml = self::generatePropelSchema(self::$configuration['database']);
            file_put_contents(self::$rootDirectory . DIRECTORY_SEPARATOR . "buildtime-conf.xml", $propelXml);
            // todo remove on actual (production) release
            passthru("'" . self::$binDirectory . "/propel' migration:diff --output-dir='sql/migration'");
            // todo remove on actual (production) release
            passthru("'" . self::$binDirectory . "/propel' model:build --output-dir='" . self::$srcDirectory . "'");
            passthru("'" . self::$binDirectory . "/propel' migration:migrate --output-dir='sql/migration'");
        }
        passthru("'" . self::$binDirectory . "/propel' config:convert-xml --input-file='buildtime-conf.xml' --output-dir='" . self::$generatedDirectory . "' --output-file='database.php'");
        unlink(self::$rootDirectory . DIRECTORY_SEPARATOR . "buildtime-conf.xml");
    }

    /**
     * @param int $maximumDelay
     * @return int
     */
    private static function generateCostValue($maximumDelay)
    {
        for ($cost = 4; $cost <= 31; $cost++) {
            $start = microtime(true);
            password_hash('randomstring', PASSWORD_BCRYPT, ['cost' => $cost]);
            $end = microtime(true);

            if ($end - $start > $maximumDelay) {
                return $cost;
            }
        }
        return 10; // default cost value
    }

    private static function generatePropelSchema(array $database)
    {
        $xml = new \XMLWriter();
        $xml->openMemory();
        $xml->startDocument('1.0', 'UTF-8');
        $xml->setIndent(true);
        $xml->startElement('config');
        $xml->startElement('propel');
        $xml->startElement('datasources');
        foreach ($database as $datasource => $configuration) {
            if (isset($configuration['default']) && true === $configuration['default']) {
                $xml->writeAttribute('default', $datasource);
            }
            $xml->startElement('datasource');
            $xml->writeAttribute('id', $datasource);
            $xml->writeElement('adapter', isset($configuration['adapter']) ? $configuration['adapter'] : 'mysql');
            if (isset($configuration['connection'])) {
                $xml->startElement('connection');
                foreach ($configuration['connection'] as $connectionOption => $connectionValue) {
                    if (is_scalar($connectionValue)) {
                        $xml->writeElement($connectionOption, $connectionValue);
                    } else {
                        $xml->startElement($connectionOption);
                        $nodeElement = 'settings' === $connectionOption ? 'setting' : 'option';
                        foreach ($connectionValue as $optionName => $optionValue) {
                            $xml->startElement($nodeElement);
                            $xml->writeAttribute('id', $optionName);
                            if (is_bool($optionValue)) {
                                $xml->text($optionValue ? 'true' : 'false');
                            } else {
                                $xml->text($optionValue);
                            }
                            $xml->endElement();
                        }
                        $xml->endElement();
                    }
                }
                $xml->endElement();
            }
            $xml->endElement();
        }
        $xml->endElement();
        $xml->endElement();
        $xml->endElement();
        return $xml->outputMemory();
    }
}
