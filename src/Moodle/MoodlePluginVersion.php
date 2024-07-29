<?php

declare(strict_types=1);

namespace Vidalia\Composer\MoodleOrg\Moodle;

use DateTimeImmutable;
use DateTimeZone;
use Exception;
use LogicException;

/**
 * A single version of a {@see MoodlePlugin}
 */
final class MoodlePluginVersion
{
    private int $id;

    private int $version;

    private ?string $release;

    private MoodlePluginMaturity $maturity;

    private string $downloadUrl;

    private string $downloadMd5;

    private ?string $vcsSystem;

    private ?string $vcsSystemOther;

    private ?string $vcsRepositoryUrl;

    private ?string $vcsBranch;

    private ?string $vcsTag;

    private DateTimeImmutable $timeCreated;

    public function getId(): int
    {
        return $this->id;
    }

    public function getVersion(): int
    {
        return $this->version;
    }

    public function getRelease(): ?string
    {
        return $this->release;
    }

    public function getMaturity(): MoodlePluginMaturity
    {
        return $this->maturity;
    }

    public function getDownloadUrl(): string
    {
        return $this->downloadUrl;
    }

    public function getDownloadMd5(): string
    {
        return $this->downloadMd5;
    }

    public function getVcsSystem(): ?string
    {
        return $this->vcsSystem;
    }

    public function getVcsSystemOther(): ?string
    {
        return $this->vcsSystemOther;
    }

    public function getVcsRepositoryUrl(): ?string
    {
        return $this->vcsRepositoryUrl;
    }

    public function getVcsBranch(): ?string
    {
        return $this->vcsBranch;
    }

    public function getVcsTag(): ?string
    {
        return $this->vcsTag;
    }

    public function getTimeCreated(): DateTimeImmutable
    {
        return $this->timeCreated;
    }

    /**
     * Deserializes a JSON object
     *
     * @param object $source
     * @return MoodlePluginVersion
     */
    public static function jsonDeserialize(object $source): MoodlePluginVersion
    {
        $version = new MoodlePluginVersion();

        $version->id = intval($source->id);
        $version->version = intval($source->version);
        $version->release = $source->release;
        $version->maturity = MoodlePluginMaturity::jsonDeserialize($source->maturity ?? "200");
        $version->downloadUrl = $source->downloadurl;
        $version->downloadMd5 = $source->downloadmd5;
        $version->vcsSystem = $source->vcssystem;
        $version->vcsSystemOther = $source->vcssystemother;
        $version->vcsRepositoryUrl = $source->vcsrepositoryurl;
        $version->vcsTag = $source->vcstag;
        $version->vcsBranch = $source->vcsbranch;

        try {
            $version->timeCreated = new DateTimeImmutable("@$source->timecreated", new DateTimeZone('UTC'));
        } catch (Exception $e) {
            throw new LogicException(
                "Couldn't convert plugin ->timecreated \"$source->timecreated\" to a DateTime",
                0,
                $e
            );
        }

        return $version;
    }
}
