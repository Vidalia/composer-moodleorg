<?php

declare(strict_types=1);

namespace Vidalia\Composer\MoodleOrg\Moodle;

use DateTimeImmutable;
use DateTimeZone;
use Exception;
use LogicException;

/**
 * A single plugin on Moodle.org
 */
final class MoodlePlugin
{
    private int $id;

    private string $name;

    private string $component;

    private string $sourceUrl;

    private string $documentationUrl;

    private string $bugsUrl;

    private string $discussionUrl;

    /**
     * @var DateTimeImmutable
     */
    private DateTimeImmutable $timeLastReleased;

    /**
     * @var MoodlePluginVersion[] Plugin versions
     */
    private array $versions;

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getComponent(): string
    {
        return $this->component;
    }

    public function getSourceUrl(): string
    {
        return $this->sourceUrl;
    }

    public function getDocumentationUrl(): string
    {
        return $this->documentationUrl;
    }

    public function getBugsUrl(): string
    {
        return $this->bugsUrl;
    }

    public function getDiscussionUrl(): string
    {
        return $this->discussionUrl;
    }

    public function getTimeLastReleased(): DateTimeImmutable
    {
        return $this->timeLastReleased;
    }

    public function getVersions(): array
    {
        return $this->versions;
    }

    /**
     * Deserializes a JSON object into a plugin
     *
     * @param object $source
     * @return MoodlePlugin
     */
    public static function jsonDeserialize(object $source): MoodlePlugin
    {
        $plugin = new MoodlePlugin();

        $plugin->id = intval($source->id);
        $plugin->name = strval($source->name);
        $plugin->component = strval($source->component);
        $plugin->sourceUrl = strval($source->source);
        $plugin->documentationUrl = strval($source->doc);
        $plugin->bugsUrl = strval($source->bugs);
        $plugin->discussionUrl = strval($source->discussion);
        try {
            $plugin->timeLastReleased = new DateTimeImmutable("@$source->timelastreleased", new DateTimeZone('UTC'));
        } catch (Exception $e) {
            throw new LogicException(
                "Couldn't convert plugin ->timecreated \"$source->timecreated\" to a DateTime",
                0,
                $e
            );
        }

        $plugin->versions = array_map(
            MoodlePluginVersion::jsonDeserialize(...),
            $source->versions
        );

        return $plugin;
    }
}
