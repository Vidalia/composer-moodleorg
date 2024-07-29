<?php

declare(strict_types=1);

namespace Vidalia\Composer\MoodleOrg;

use Composer\Cache;
use Composer\Config;
use Composer\IO\IOInterface;
use Composer\Package\BasePackage;
use Composer\Package\CompletePackage;
use Composer\Package\Link;
use Composer\Package\Package;
use Composer\Package\PackageInterface;
use Composer\Pcre\Preg;
use Composer\Repository\ArrayRepository;
use Composer\Repository\RepositoryInterface;
use Composer\Semver\Constraint\Constraint;
use Composer\Util\Platform;
use Composer\Util\Url;
use ErrorException;
use Exception;
use Generator;
use LogicException;
use Vidalia\Composer\MoodleOrg\Moodle\MoodlePlugin;
use Vidalia\Composer\MoodleOrg\Moodle\MoodlePluginMaturity;
use Vidalia\Composer\MoodleOrg\Moodle\MoodlePluginVersion;
use Vidalia\Composer\MoodleOrg\Moodle\PlugListResponse;

/**
 * Provides packages from downloads.moodle.org
 *
 * @package Vidalia\Composer\MoodleOrg
 */
class Repository extends ArrayRepository implements RepositoryInterface
{
    private const API_URL = "https://download.moodle.org/api";
    private const API_VERSION = "1.3";

    private IOInterface $io;
    private Cache $cache;

    /**
     * @var string The package namespace for moodle.org packages
     */
    private string $packageNamespace = 'moodledotorg';

    /**
     * Constructor
     *
     * @param Config $config
     * @param IOInterface $io
     */
    public function __construct(Config $config, IOInterface $io)
    {
        parent::__construct();

        $this->io = $io;

        $this->cache = new Cache(
            $io,
            $config->get('cache-repo-dir')
                . '/' . Preg::replace('{[^a-z0-9.]}i', '-', Url::sanitize(self::API_URL)),
            'a-z0-9.$~_'
        );

        $isReadOnly = boolval($config->get('cache-read-only'));
        $this->cache->setReadOnly($isReadOnly);

        if (($packageNamespace = Platform::getEnv('COMPOSER_MOODLEORG_NAMESPACE')) !== false) {
            $this->packageNamespace = $packageNamespace;
        }
    }

    /**
     * Initialises the repository as an array of packages
     *
     * @return void
     */
    protected function initialize(): void
    {
        parent::initialize();

        $plugins = $this->queryAllPlugins();

        foreach ($plugins as $plugin) {
            if (empty($plugin->getComponent())) {
                continue;
            }

            try {
                foreach ($this->convertPluginToPackages($plugin) as $package) {
                    $this->addPackage($package);
                }
            } catch (Exception $e) {
                $this->io->warning($e->getMessage());
            }
        }
    }

    /**
     * Repository name
     *
     * @return string
     */
    public function getRepoName(): string
    {
        return "MoodleOrg repository (https://downloads.moodle.org)";
    }

    /**
     * Fetch a list of all plugins from download.moodle.org
     *
     * @return MoodlePlugin[]
     */
    private function queryAllPlugins(): array
    {

        $cacheKey = "pluglist.json";
        $cacheAge = $this->cache->getAge($cacheKey);

        if (false !== $cacheAge && $cacheAge < 600 && false !== ($cachedData = $this->cache->read($cacheKey))) {
            return PlugListResponse::jsonDeserialize(json_decode($cachedData))->getPlugins();
        }

        $url = implode('/', [ self::API_URL, self::API_VERSION, 'pluglist.php' ]);

        if (false === ($response = file_get_contents($url))) {
            throw new LogicException("Failed to query $url");
        }

        $this->io->debug("HTTP GET $url");

        if (!$this->cache->isReadOnly()) {
            try {
                $this->cache->write($cacheKey, $response);
            } catch (ErrorException $e) {
                $this->io->warning("Failed to cache $cacheKey: {$e->getMessage()}");
            }
        }

        return PlugListResponse::jsonDeserialize(json_decode($response))->getPlugins();
    }

    /**
     * Given a plugin from an API response, return a package for each version
     *
     * @param MoodlePlugin $plugin
     * @return Generator<Package>
     * @throws Exception
     */
    private function convertPluginToPackages(MoodlePlugin $plugin): Generator
    {

        $packageName = "$this->packageNamespace/{$plugin->getComponent()}";

        [ $pluginType, $pluginName ] = explode('_', $plugin->getComponent(), 2);

        foreach ($plugin->getVersions() as $version) {
            $calVer = self::convertMoodleVersion($version);
            $package = new CompletePackage($packageName, $calVer, $calVer);

            $package->setDescription($plugin->getName());
            $package->setType("moodle-$pluginType");
            $package->setDistType("zip");
            $package->setDistUrl($version->getDownloadUrl());
            $package->setReleaseDate($version->getTimeCreated());

            if (!empty($version->doc)) {
                $package->setHomepage($version->doc);
            } elseif (!empty($version->bugs)) {
                $package->setHomepage($version->bugs);
            }

            $package->setRequires([
                "composer/installers" => new Link(
                    $packageName,
                    "composer/installers",
                    new Constraint(">=", "2.0"),
                    Link::TYPE_REQUIRE,
                    "~2.0"
                )
            ]);

            $package->setExtra([
                'installer-name' => $pluginName,
                'moodleorg-distmd5' => $version->getDownloadMd5()
            ]);

            if (
                !empty($version->getVcsSystem())
                && !empty($version->getVcsRepositoryUrl())
                && (!empty($version->getVcsTag()) || !empty($version->getVcsBranch()))
            ) {
                $package->setSourceUrl($version->getVcsRepositoryUrl());
                $package->setSourceType($version->getVcsSystem());
                $package->setSourceReference($version->getVcsTag() ?? $version->getVcsBranch());
            }

            yield $package;
        }
    }

    /**
     * Converts a Moodle version string (e.g. 2024071800) to a SemVer string (2024.7.1800)
     *
     * @param MoodlePluginVersion $version The plugin version object
     * @return string
     */
    private static function convertMoodleVersion(MoodlePluginVersion $version): string
    {
        $moodleVersion = (string) $version->getVersion();

        $year = intval(substr($moodleVersion, 0, 4));
        $month = intval(substr($moodleVersion, 4, 2));
        $day = intval(substr($moodleVersion, 6, 2));
        $minor = intval(substr($moodleVersion, 8, 2));

        $calVer = "$year.$month.$day.$minor";

        $calVer .= match ($version->getMaturity()) {
            MoodlePluginMaturity::ALPHA => "-alpha",
            MoodlePluginMaturity::BETA => "-beta",
            MoodlePluginMaturity::RC => "-rc",
            default => ""
        };

        return $calVer;
    }
}
