<?php

namespace app\domain\maven;

class ArchivedArtifact
{
    private string $version;

    private string $groupId;

    private string $artifactId;

    public function __construct(string $version, string $groupId, string $artifactId)
    {
        $this->version = $version;
        $this->groupId = $groupId;
        $this->artifactId = $artifactId;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function getGroupId(): string
    {
        return $this->groupId;
    }

    public function getArtifactId(): string
    {
        return $this->artifactId;
    }
}
