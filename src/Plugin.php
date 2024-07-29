<?php

namespace Vidalia\Composer\MoodleOrg;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Package\BasePackage;
use Composer\Plugin\PluginEvents;
use Composer\Plugin\PluginInterface;
use Composer\Plugin\PostFileDownloadEvent;
use Composer\Plugin\PreFileDownloadEvent;
use Composer\Util\Platform;
use UnexpectedValueException;

/**
 * @package Vidalia\Composer\MoodleOrg
 */
class Plugin implements PluginInterface, EventSubscriberInterface
{
    /**
     * @param Composer $composer
     * @param IOInterface $io
     * @return void
     */
    public function activate(Composer $composer, IOInterface $io): void
    {
        $composer->getRepositoryManager()->addRepository(new Repository($composer->getConfig(), $io));
    }

    /**
     * @param Composer $composer
     * @param IOInterface $io
     * @return void
     */
    public function deactivate(Composer $composer, IOInterface $io)
    {
        // no-op
    }

    /**
     * @param Composer $composer
     * @param IOInterface $io
     * @return void
     */
    public function uninstall(Composer $composer, IOInterface $io)
    {
        // no-op
    }

    /**
     * Gets the subscribed events for this plugin
     *
     * @return array[]
     */
    public static function getSubscribedEvents(): array
    {
        return [
            PluginEvents::POST_FILE_DOWNLOAD => [
                ['onPostFileDownload', 0]
            ],
            PluginEvents::PRE_FILE_DOWNLOAD => [
                ['onPreFileDownload', 0]
            ]
        ];
    }

    /**
     * @var int How many files have been downloaded since the last wait
     */
    private int $throttleUsage = 0;

    /**
     * Rate limit connections to downloads.moodle.org, so we don't get
     * throttled by the CDN.
     *
     * @param PreFileDownloadEvent $event
     * @return void
     */
    public function onPreFileDownload(PreFileDownloadEvent $event): void
    {
        $context = $event->getContext();

        // Don't do any rate limiting for non-moodle.org packages.
        if (!($context instanceof CompleteMoodlePackage)) {
            return;
        }

        // Do some very simple rate limiting. After every <n> files we download,
        // wait for <s> seconds.
        if (++$this->throttleUsage > (Platform::getEnv("COMPOSER_MOODLEORG_THROTTLE_COUNT") ?: 10)) {
            sleep(Platform::getEnv("COMPOSER_MOODLEORG_THROTTLE_SLEEP") ?: 5);
            $this->throttleUsage = 0;
        }
    }

    /**
     * After any file is downloaded, check and see if it's a Moodle package.
     * If it is, then we can check the MD5 sum for the downloaded file.
     *
     * @param PostFileDownloadEvent $event
     * @return void
     */
    public function onPostFileDownload(PostFileDownloadEvent $event): void
    {
        $context = $event->getContext();

        // Context might be download metadata instead.
        if (!($context instanceof BasePackage)) {
            return;
        }

        // Does the package have a md5 hash?
        if (!array_key_exists('moodleorg-distmd5', $context->getExtra())) {
            return;
        }

        $fileChecksum = hash_file('md5', $event->getFileName());

        if ($fileChecksum !== $context->getExtra()['moodleorg-distmd5']) {
            throw new UnexpectedValueException(
                "Checksum verification of the file failed (downloaded from {$context->getSourceUrl()})"
            );
        }
    }
}
