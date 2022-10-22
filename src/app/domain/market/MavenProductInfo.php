<?php

namespace app\domain\market;

use app\domain\maven\MavenArtifact;
use app\domain\Version;

class MavenProductInfo
{
  private array $mavenArtifacts;

  public function __construct(array $mavenArtifacts)
  {
    $this->mavenArtifacts = $mavenArtifacts;
  }

  public function getMavenArtifacts(): array
  {
    return $this->mavenArtifacts;
  }

  public function getDocArtifact(): ?MavenArtifact
  {
    foreach ($this->mavenArtifacts as $mavenArtifact) {
      if ($mavenArtifact->isDocumentation()) {
        return $mavenArtifact;
      }
    }
    return null;
  }
  
  public function getProductArtifact(): ?MavenArtifact
  {
    foreach ($this->getMavenArtifacts() as $mavenArtifact) {
      if ($mavenArtifact->isProduct()) {
        return $mavenArtifact;
      }
    }
    return null;
  }

  public function getVersions(): array
  {
    $artifact = $this->getProductArtifact();
    if ($artifact == null) {
      return [];
    }
    return $artifact->getVersions();
  }

  public function getNewestVersion(): ?string
  {
    $versions = $this->getVersions();
    return reset($versions);
  }

  public function getOldestVersion(): ?string
  {
    $versions = $this->getVersions();
    return end($versions);
  }

  public function hasVersion(string $v): bool
  {
    $versions = $this->getVersions();
    foreach ($versions as $version) {
      if ($version == $v) {
        return true;
      }
    }
    return false;
  }

  public function getVersionsReleased(): array
  {
    return $this->getVersionsToDisplay(false, null);
  }

  public function getVersionsToDisplay(bool $showDevVersions, ?String $designerVersion): array
  {
    $versions = $this->getVersions();
    if ($showDevVersions) {
      return $versions;
    }

    if (empty($designerVersion)) {
      $versions = array_filter($versions, fn(string $v) => !str_contains($v, '-SNAPSHOT') && !str_contains($v, "-m"));
      return array_values($versions);
    }
    
    if (Version::isValidVersionNumber($designerVersion)) {
      $designerVersion = (new Version($designerVersion))->getMinorVersion();
    }
    $versions = array_filter($versions, fn(string $v) => str_starts_with($v, $designerVersion) || (!str_contains($v, '-SNAPSHOT') && !str_contains($v, '-m')));
    return array_values($versions);
  }

  public function getBestMatchVersion(bool $showDevVersions, ?String $designerVersion): ?string
  {
    $versions = $this->getVersionsToDisplay($showDevVersions, $designerVersion);
    if (empty($designerVersion)) {
      return reset($versions);
    }
    if (Version::isValidVersionNumber($designerVersion)) {
      $designerVersion = (new Version($designerVersion))->getMinorVersion();
      foreach ($versions as $v) {
        if (str_starts_with($v, $designerVersion)) {
          return $v;
        }
      }
    }
    return reset($versions);
  }
}
