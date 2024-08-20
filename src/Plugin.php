<?php

namespace Vidalia\Composer\MoodleOrg;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Package\BasePackage;
use Composer\Plugin\PluginEvents;
use Composer\Plugin\PluginInterface;
use Composer\Plugin\PostFileDownloadEvent;
use Composer\Util\Platform;
use UnexpectedValueException;

/**
 * @package Vidalia\Composer\MoodleOrg
 */
class Plugin implements PluginInterface, EventSubscriberInterface
{
    private IOInterface $io;

    private int $throttleUsage = 0;

    /**
     * @param Composer $composer
     * @param IOInterface $io
     * @return void
     */
    public function activate(Composer $composer, IOInterface $io): void
    {
        $this->io = $io;

        if (!(Platform::getEnv("COMPOSER_MOODLEORG_NO_THROTTLE") ?: false)) {
            Platform::putEnv("COMPOSER_MAX_PARALLEL_HTTP", 1);
        }

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
                [ 'verifyDownloadedPackage', 0 ],
                [ 'rateLimit', 1 ],
            ],
        ];
    }

    /**
     * Verifies the md5 hash of a file downloaded from Moodle.org
     *
     * @param PostFileDownloadEvent $event
     * @return void
     */
    public function verifyDownloadedPackage(PostFileDownloadEvent $event): void
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

    /**
     * Do some dirt cheap and simple rate limiting. After every <n> files we download, wait for <s> seconds
     *
     * @param PostFileDownloadEvent $event
     * @return void
     */
    public function rateLimit(PostFileDownloadEvent $event): void
    {
        if (Platform::getEnv("COMPOSER_MOODLEORG_NO_THROTTLE")) {
            return;
        }

        // Did we get this from moodle.org?
        if (parse_url($event->getUrl(), PHP_URL_HOST) !== 'moodle.org') {
            return;
        }

        if (++$this->throttleUsage >= (Platform::getEnv("COMPOSER_MOODLEORG_THROTTLE_COUNT") ?: 12)) {
            $this->io->debug("$this->throttleUsage exceeded count, sleeping...");
            sleep(Platform::getEnv("COMPOSER_MOODLEORG_THROTTLE_SLEEP") ?: 12);
            $this->throttleUsage = 0;
            $this->io->info("Rate limit exceeded, backing off...");
        }
    }
}
