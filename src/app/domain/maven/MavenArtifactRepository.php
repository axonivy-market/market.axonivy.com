<?php

namespace app\domain\maven;

use app\domain\market\Market;

class MavenArtifactRepository
{
  public static function getMavenArtifact($key, $type): ?MavenArtifact
  {
    $artifacts = self::getAll();
    foreach ($artifacts as $artifact) {
      if ($artifact->getKey() == $key && $artifact->getType() == $type) {
        return $artifact;
      }
    }
    return null;
  }

  private static function getAll(): array
  {
    $all = [];
    foreach (Market::all() as $product) {
      $info = $product->getMavenProductInfo();
      if ($info != null) {
        $all = array_merge($all, $info->getMavenArtifacts());
      }
    }
    return $all;
  }
}
