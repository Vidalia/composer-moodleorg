<?php

declare(strict_types=1);

namespace Vidalia\Composer\MoodleOrg\Moodle;

use InvalidArgumentException;

/**
 * Enumeration of Moodle plugin MATURITY_* types
 */
enum MoodlePluginMaturity
{
    case ALPHA;
    case BETA;
    case RC;
    case STABLE;

    /**
     * Deserializes a JSON value to a plugin maturity value
     *
     * @param int|string $source
     * @return MoodlePluginMaturity
     */
    public static function jsonDeserialize(int|string $source): MoodlePluginMaturity
    {
        return match (strval($source)) {
            "0", "50" => self::ALPHA,
            "100" => self::BETA,
            "150" => self::RC,
            "200" => self::STABLE,
            default => throw new InvalidArgumentException("Unknown plugin maturity \"$source\"")
        };
    }
}
