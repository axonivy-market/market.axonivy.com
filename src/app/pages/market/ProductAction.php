<?php

namespace app\pages\market;

use Slim\Exception\HttpNotFoundException;
use Slim\Views\Twig;
use app\domain\util\CookieUtil;
use app\domain\market\Market;
use app\domain\market\Product;
use Slim\Psr7\Request;
use app\domain\market\MavenProductInfo;
use app\domain\market\ProductDescription;
use app\domain\market\ProductMavenArtifactDownloader;
use app\domain\maven\MavenArtifact;
use app\domain\market\OpenAPIProvider;
use app\domain\util\Redirect;

class ProductAction
{

  private Twig $view;

  public function __construct(Twig $view)
  {
    $this->view = $view;
  }

  public function __invoke($request, $response, $args)
  {
    $key = $args['key'] ?? '';
    $product = Market::getProductByKey($key);
    if ($product == null) {
      throw new HttpNotFoundException($request);
    }
    
    $version = $args['version'] ?? '';
    $topic = $args['topic'] ?? '';
    $path = $args['path'] ?? '';

    $mavenProductInfo = $product->getMavenProductInfo();

    // redirect to full version for shortcut versions (e.g. 9, 9.0, dev, nightly, sprint, latest)
    if (!empty($version) && $mavenProductInfo != null) {
      $v = self::versionToShow($mavenProductInfo, $version);
      if ($v == null) {
        throw new HttpNotFoundException($request, "$key for $version does not exist");
      }
      if ($v != $version) {
        $url = "/$key/$v";
        if (!empty($topic)) {
          $url .= "/$topic";
        }
        if (!empty($path)) {
          $url .= "/$path";
        }
        return Redirect::to($response, $url);
      }
    }

    // redirect to doc
    if ($topic == 'doc' && $mavenProductInfo != null) {
      $docArtifact = $mavenProductInfo->getFirstDocArtifact();
      if ($docArtifact == null) {
        throw new HttpNotFoundException($request, 'no doc artifact');
      }
      $exists = (new ProductMavenArtifactDownloader())->downloadArtifact($product, $docArtifact, $version);
      If (!$exists) {        
        throw new HttpNotFoundException($request, "doc artifact does not exist for version $version");
      }
      $docUrl = $docArtifact->getDocUrl($product, $version);
      if (!empty($path)) {
        $path = "/$path";
      }
      return Redirect::to($response, $docUrl . $path);
    }

    $installNow = isset($request->getQueryParams()['installNow']);
    $initVersion = $args['version'] ?? '';
    $mavenArtifactsAsDependency = [];
    $mavenArtifacts = [];
    $docUrl = '';
    $versionsToDisplay = null;

    $showDevVersionCookie = $request->getCookieParams()['showDevVersions'] ?? 'false';
    $showDevVersions = filter_var($showDevVersionCookie, FILTER_VALIDATE_BOOLEAN) ?? false;
    if (isset($request->getQueryParams()['showDevVersions'])) {
      if ($request->getQueryParams()['showDevVersions'] == "true") {
        $showDevVersions = true;
        setcookie('showDevVersions', "true", time()+60*60*24*30*12, "/");
      } else {
        $showDevVersions = false;
        setcookie('showDevVersions', "false", -1, "/");
      }
    }

    $showDevVersionsLink = "/$key?showDevVersions=true#download";
    if ($showDevVersions) {
      $showDevVersionsLink = "/$key?showDevVersions=false#download";
    }

    if ($mavenProductInfo == null && !empty($version)) {
      throw new HttpNotFoundException($request);
    }

    if ($mavenProductInfo != null) {
      $requestVersion = self::readIvyVersionCookie($request);
      if (empty($version)) {
        $version = self::findBestMatchingVersionFromCookie($request, $mavenProductInfo);
        if (empty($version)) {
          $version = $mavenProductInfo->getLatestVersionToDisplay($showDevVersions, $requestVersion);
        }
      } else if (!$mavenProductInfo->hasVersion($version)) {
        throw new HttpNotFoundException($request);
      }
      if (empty($version)) {
        $version = '';
      }
      if (!empty($version)) {
        $mavenArtifacts = $mavenProductInfo->getMavenArtifactsForVersion($version);
        foreach ($mavenArtifacts as $artifact) {
          if ($artifact->getMakesSenseAsMavenDependency()) {
            $mavenArtifactsAsDependency[] = $artifact;
          }
        }

        $mavenArtifacts = array_filter($mavenArtifacts, fn(MavenArtifact $a) => !$a->isProductArtifact());
        $versionsToDisplay = $mavenProductInfo->getVersionsToDisplay($showDevVersions, $requestVersion);
        if (empty($initVersion) && !empty($versionsToDisplay)) {
          $version = $versionsToDisplay[0];
        }
        $docArtifact = $mavenProductInfo->getFirstDocArtifact();
        if ($docArtifact != null) {
          $exists = (new ProductMavenArtifactDownloader())->downloadArtifact($product, $docArtifact, $version);
          if ($exists) {
            $docUrl = $docArtifact->getDocUrl($product, $version);
          }
        }
      }
    }

    $installButton = self::createInstallButton($request, $product, $version);
    
    $getInTouchLink = 'https://www.axonivy.com/marketplace/contact/?market_solutions=' . $product->getKey();

    if (!empty($version)) {      
      (new ProductMavenArtifactDownloader())->download($product, $version);
    }
    $productDescription = ProductDescription::create($product, $version);
    
    $openApiProvider = new OpenAPIProvider($product);
    $openApiUrl = $openApiProvider->getOpenApiUrl($version);
    
    $productVersion = $version;
    if (empty($productVersion)) {
      $productVersion = $product->getVersion();
    }
    
    return $this->view->render($response, 'market/product.twig', [
      'product' => $product,
      'mavenProductInfo' => $mavenProductInfo,
      'productDescription' => $productDescription,
      'mavenArtifacts' => $mavenArtifacts,
      'mavenArtifactsAsDependency' => $mavenArtifactsAsDependency,
      'selectedVersion' => $version,
      'installButton' => $installButton,
      'getInTouchLink' => $getInTouchLink,
      'openApiUrl' => $openApiUrl,
      'version' => $productVersion,
      'docUrl' => $docUrl,
      'installNow' => $installNow,
      'versionsToDisplay' => $versionsToDisplay,
      'switchVersion' => $showDevVersions ? "hide development versions" : "show development versions",
      'showDevVersionsLink' => $showDevVersionsLink
    ]);
  }

  private static function createInstallButton(Request $request, Product $product, string $currentVersion): InstallButton
  {
    $version = self::readIvyVersionCookie($request);
    $isDesigner = !empty($version);
    $reason = $product->getReasonWhyNotInstallable($isDesigner, $currentVersion);
    $isShow = $product->isInstallable($currentVersion);
    return new InstallButton($isDesigner, $reason, $product, $isShow, $request, $currentVersion);
  }

  private static function readIvyVersionCookie(Request $request)
  {
    $cookies = $request->getCookieParams();
    return $cookies['ivy-version'] ?? CookieUtil::setCookieOfQueryParam($request, 'ivy-version');
  }
  
  private static function findBestMatchingVersionFromCookie(Request $request, MavenProductInfo $mavenProductInfo)
  {
    $version = self::readIvyVersionCookie($request);
    if (empty($version)) {
      return '';
    }
    return $mavenProductInfo->findBestMatchingVersion($version);
  }

  private static function versionToShow(MavenProductInfo $mavenProductInfo, string $version) : ?string {
     // redirect to latest version at all
     if ($version == 'dev' || $version == 'nightly' || $version == 'sprint') {
      $v = $mavenProductInfo->getLatestVersion();
      if ($v == null) {
        return null;
      }
      return $v;
    }

    // redirect latest
    if ($version == 'latest') {
      $v = $mavenProductInfo->getLatestVersionToDisplay(true, false);
      if ($v == null) {
        return null;
      }
      return $v;
    }

    // redirect to real version if major e.g (9), minor (9.1) version is given 
    $v = $mavenProductInfo->getLatestReleaseVersion($version);
    if ($v == null) {
      return null;
    }
    return $v;
  }
}



class InstallButton
{
  public bool $isDesigner;
  public string $reason;
  public bool $isShow;
  private Product $product;
  private Request $request;
  private string $currentVersion;
  
  function __construct(bool $isDesigner, string $reason, Product $product, bool $isShow, Request $request, string $currentVersion)
  {
    $this->isDesigner = $isDesigner;
    $this->reason = $reason;
    $this->product = $product;
    $this->isShow = $isShow;
    $this->request = $request;
    $this->currentVersion = $currentVersion;
  }

  public function isEnabled(): bool
  {
    return empty($this->reason);
  }

  public function getJavascriptCallback(): string
  {
    return "install('" . $this->getProductJsonUrl($this->currentVersion) . "')";
  }
  
  public function getMultipleVersions(): bool
  {
    return $this->product->getMavenProductInfo() != null;
  }

  public function getProductJsonUrl($version): string
  {
    $uri = $this->request->getUri();
    $baseUrl = $uri->getScheme() . '://' . $uri->getHost();
    return $baseUrl . $this->product->getProductJsonUrl($version);
  }
}
