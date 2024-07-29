<?php

declare(strict_types=1);

namespace Vidalia\Composer\MoodleOrg\Moodle;

use DateTimeImmutable;
use DateTimeZone;
use Exception;
use LogicException;

final class PlugListResponse
{
    /**
     * @var DateTimeImmutable Response timestamp
     */
    private DateTimeImmutable $timestamp;

    /**
     * @var MoodlePlugin[] List of plugins
     */
    private array $plugins;

    public function getTimestamp(): DateTimeImmutable
    {
        return $this->timestamp;
    }

    public function getPlugins(): array
    {
        return $this->plugins;
    }

    public static function jsonDeserialize(object $source): PlugListResponse
    {
        $response = new PlugListResponse();

        try {
            $response->timestamp = new DateTimeImmutable("@$source->timestamp", new DateTimeZone('UTC'));
        } catch (Exception $e) {
            throw new LogicException(
                "Couldn't convert plugin ->timecreated \"$source->timecreated\" to a DateTime",
                0,
                $e
            );
        }

        $response->plugins = array_map(
            MoodlePlugin::jsonDeserialize(...),
            $source->plugins
        );

        return $response;
    }
}
