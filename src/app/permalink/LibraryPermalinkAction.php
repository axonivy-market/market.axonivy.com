<?php

namespace app\permalink;

use app\domain\market\Market;
use Slim\Exception\HttpNotFoundException;
use app\domain\util\Redirect;
use app\domain\maven\MavenArtifactRepository;
use app\domain\maven\MavenArtifact;

class LibraryPermalinkAction
{
  public function __invoke($request, $response, $args)
  {
    $key = $args['key'];
    if (empty($key)) {
      throw new HttpNotFoundException($request);
    }
    
    $product = Market::getProductByKey($key);
    if ($product == null) {
      throw new HttpNotFoundException($request);
    }

    $version = $args['version'];
    if (empty($version)) {
      throw new HttpNotFoundException($request);
    }

    $info = $product->getMavenProductInfo();
    if ($info == null) {
      throw new HttpNotFoundException($request);
    }
    
    $name = $args['name'] ?? ''; // e.g demo-app.zip
    $type = pathinfo($name, PATHINFO_EXTENSION); // e.g. zip 
    $filename = pathinfo($name, PATHINFO_FILENAME); // e.g. demo-app

    $foundArtifact = null;
    foreach($info->getMavenArtifacts() as $artifact) {
      if ($artifact->getKey() == $filename && $artifact->getType() == $type) {
        $foundArtifact = $artifact;
      }
    }

    if ($foundArtifact == null) {
      throw new HttpNotFoundException($request);
    }

    $url = self::getUrl($foundArtifact, $version, $request);
    return Redirect::to($response, $url);
  }

  private static function getUrl(MavenArtifact $mavenArtifact, string $version, $request)
  {
    if ($version == 'dev') {
      return $mavenArtifact->getDevUrl();
    } else {
      foreach ($mavenArtifact->getVersions() as $v) {
        if (str_starts_with($v, $version)) {
          return $mavenArtifact->getUrl($v);
        }
      }
    }
    throw new HttpNotFoundException($request, "Version $version not found");
  }
}
