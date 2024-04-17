<?php
namespace app\domain\maven;

class ArchivedArtifact
{
    private string $version;

    private string $groupId;

    public function __construct(string $version, string $groupId)
    {
        $this->version = $version;
        $this->groupId = $groupId;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function getGroupId(): string
    {
        return $this->groupId;
    }
}