<?php
namespace app\pages\internal;

use Slim\Views\Twig;
use app\domain\market\Market;
use app\domain\market\Product;
use Slim\Psr7\Request;
use Slim\Psr7\Response;
use Slim\Exception\HttpNotFoundException;
use app\domain\market\ProductMavenArtifactDownloader;

class MarketRCPTTAction
{

  private Twig $view;

  public function __construct(Twig $view)
  {
    $this->view = $view;
  }

  public function __invoke(Request $request, Response $response, $args)
  {
    $queryParams = $request->getQueryParams();
    $designerVersion = $queryParams['designerVersion'] ?? null;

    if ($designerVersion == null) {
      throw new HttpNotFoundException($request);
    }

    $products = self::products($designerVersion);
    $baseUrl = self::baseUrl($request);

    $urls = [];
    foreach ($products as $product) {
      $mavenInfo = $product->getMavenProductInfo();
      $bestMatchingVersion = $mavenInfo->getBestMatchVersion(true, $designerVersion);
      $urls[] = $baseUrl . $product->getProductJsonUrl($bestMatchingVersion);
    }

    $response = $response->withHeader('Content-Type', 'text/plain');
    return $this->view->render($response, 'internal/market-rcptt.twig', [
      'urls' => $urls
    ]);
  }

  private static function products(string $version): array {
    $products = Market::listed();
    $products = array_filter($products, fn (Product $product) => $product->getMavenProductInfo() != null);
    $products = array_filter($products, fn (Product $product) => $product->toValidate());
    $products = array_filter($products, fn (Product $product) => self::isInstallableForDesignerVersion($version, $product));
    return $products;
  }
  
  private static function isInstallableForDesignerVersion(string $designerVersion, Product $product) {
    $info = $product->getMavenProductInfo();
    $bestMatchVersion = $info->getBestMatchVersion(true, $designerVersion);
    (new ProductMavenArtifactDownloader())->download($product, $bestMatchVersion);
    return $product->isInstallable($bestMatchVersion);
  }

  private static function baseUrl($request): string {
    $uri = $request->getUri();
    return $uri->getScheme() . '://' . $uri->getHost();
  }
}
