<?php

namespace app\domain\maven;

use app\domain\market\Product;
use app\domain\Version;

class MavenArtifact
{

  private $name;

  private string $repoUrl;

  private $groupId;

  private $artifactId;

  private $type;

  private $versionCache = null;

  private $makesSenseAsMavenDependency;

  private $isDocumentation;

  private $deprecatedGroupId;

  private $deprecatedGroupIdLatestVersion;

  function __construct($name, string $repoUrl, $groupId, $artifactId, $type, bool $makesSenseAsMavenDependency, bool $isDocumentation, $deprecatedGroupId, $deprecatedGroupIdLatestVersion)
  {
    $this->name = $name;
    $this->repoUrl = $repoUrl;
    $this->groupId = $groupId;
    $this->artifactId = $artifactId;
    $this->type = $type;
    $this->makesSenseAsMavenDependency = $makesSenseAsMavenDependency;
    $this->isDocumentation = $isDocumentation;
    $this->deprecatedGroupId = $deprecatedGroupId;
    $this->deprecatedGroupIdLatestVersion = $deprecatedGroupIdLatestVersion;
  }

  public static function create(): MavenArtifactBuilder
  {
    return new MavenArtifactBuilder();
  }


  public function getRepoUrl(): string
  {
    return $this->repoUrl;
  }

  public function getGroupId(): string
  {
    return $this->groupId;
  }

  public function getArtifactId(): string
  {
    return $this->artifactId;
  }

  public function isProduct(): bool
  {
    return str_ends_with($this->getArtifactId(), '-product');
  }

  public function getType(): string
  {
    return $this->type;
  }

  public function getName(): string
  {
    return $this->name;
  }

  public function getMakesSenseAsMavenDependency(): bool
  {
    return $this->makesSenseAsMavenDependency;
  }

  public function isDocumentation(): bool
  {
    return $this->isDocumentation;
  }

  public function getDeprecateGroupId(): string
  {
    return $this->deprecatedGroupId;
  }

  public function getDeprecatedGroupIdLatestVersion(): string
  {
    return $this->deprecatedGroupIdLatestVersion;
  }

  public function getDocUrl(Product $product, string $version)
  {
    return '/market-cache/' . $product->getKey() . '/' . $this->artifactId . '/' . $version;
  }

  public function getUrl($version)
  {
    $concretVersion = $this->getConcreteVersion($version);
    $baseUrl = $this->getBaseUrl();
    return $baseUrl . '/' . $version . '/' . $this->artifactId . '-' . $concretVersion . '.' . $this->type;
  }

  private function getTargetGroupId($version)
  {
    if (version_compare($this->deprecatedGroupIdLatestVersion, $version) > 0) {
      return $this->deprecatedGroupId;
    }
    return $this->groupId;
  }

  public function getConcreteVersion($version)
  {
    if (str_contains($version, 'SNAPSHOT')) {
      $baseUrl = $this->getBaseUrl();
      $xml = HttpRequester::request("$baseUrl/$version/maven-metadata.xml");
      if (empty($xml)) {
        return "";
      }
      return self::parseVersionIdentifierFromXml($xml);
    }
    return $version;
  }

  public static function parseVersionIdentifierFromXml(string $xml): string
  {
    $element = new \SimpleXMLElement($xml);
    $result = $element->xpath('/metadata/versioning/snapshotVersions/snapshotVersion');
    return $result[0]->value;
  }

  private function getBaseUrl()
  {
    return $this->getBaseUrlFromGroupId($this->groupId);
  }

  private function getTargetBaseUrlFromVersion($version)
  {
    $targetGroupId = $this->getTargetGroupId($version);
    $groupId = str_replace('.', '/', $targetGroupId);
    return $this->repoUrl . "$groupId/" . $this->artifactId;
  }

  private function getBaseUrlFromGroupId($targetGroupId)
  {
    $groupId = str_replace('.', '/', $targetGroupId);
    return $this->repoUrl . "$groupId/" . $this->artifactId;
  }

  public function getVersions(): array
  {
    if ($this->versionCache == null) {
      $baseUrl = $this->getBaseUrl();

      $xml = HttpRequester::request("$baseUrl/maven-metadata.xml");

      if (empty($xml)) {
        $this->versionCache = [];
        return $this->versionCache;
      }

      $v = self::parseVersions($xml);

      usort($v, 'version_compare');
      $v = array_reverse($v);
      $v = self::filterSnapshotsWhichAreRealesed($v);
      $this->versionCache = $v;
    }
    return $this->versionCache;
  }

  public static function filterSnapshotsBetweenReleasedVersions(array $versions): array
  {
    return array_values(array_filter($versions, fn($version) => self::filterBetweenSnapshots($versions, $version)));
  }

  private static function filterBetweenSnapshots(array $versions, string $version): bool
  {
    if (str_ends_with($version, '-SNAPSHOT')) {
      $bugFixVersion = str_replace('-SNAPSHOT', '', $version);
      $minorVersion = (new Version($bugFixVersion))->getMinorNumber();
      foreach ($versions as $v) {
        if ((new Version($v))->getMinorNumber() == $minorVersion) {
          if (version_compare($bugFixVersion, $v) == -1) {
            return false;
          }
        }
      }
    }
    return true;
  }

  public static function filterSnapshotsWhichAreRealesed(array $versions): array
  {
    return array_values(array_filter($versions, fn($version) => self::filterReleasedSnapshots($versions, $version)));
  }

  private static function filterReleasedSnapshots(array $versions, string $v): bool
  {
    if (str_ends_with($v, '-SNAPSHOT')) {
      $relasedVersion = str_replace('-SNAPSHOT', '', $v);
      if (in_array($relasedVersion, $versions)) {
        return false;
      }
    }
    if (str_contains($v, '-m')) {
      $relasedVersion = substr($v, 0, strpos($v, "-m"));
      if (in_array($relasedVersion, $versions)) {
        return false;
      }
    }
    return true;
  }

  public static function parseVersions($xml)
  {
    $element = new \SimpleXMLElement($xml);
    $result = $element->xpath('/metadata/versioning/versions');
    $versions = get_object_vars($result[0]->version);
    return array_values($versions);
  }
}
