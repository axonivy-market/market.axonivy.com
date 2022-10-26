<?php

namespace app\domain\market;

use app\domain\Version;

class VersionResolver
{
  public static function get(MavenProductInfo $info, string $requestedVersion) : ?string
  {
    // redirect to latest version at all
    if ($requestedVersion == 'dev' || $requestedVersion == 'nightly' || $requestedVersion == 'sprint') {
     $v = $info->getNewestVersion();
     if ($v == null) {
       return null;
     }
     return $v;
   }

   // redirect latest
   if ($requestedVersion == 'latest') {
     $v = reset($info->getVersionsReleased());
     if ($v == null) {
       return null;
     }
     return $v;
   }

   // redirect to real version if major e.g (9), minor (9.1) version is given 
   $v = self::findNewestVersion($info, $requestedVersion);
   if ($v == null) {
     return null;
   }
   return $v;
 }

 public static function findNewestVersion(MavenProductInfo $info, string $version): ?string
 {
    $v = new Version($version);
    if ($v->isMinor() || $v->isMajor()) {
      $versions = $info->getVersionsReleased();
      foreach ($versions as $ver) {
        if (str_starts_with($ver, $version)) {
          return $ver;
        }
      }
      return null;
    }
    return $version;
  }
}
