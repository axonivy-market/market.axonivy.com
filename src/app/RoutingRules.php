<?php

namespace app;

use app\pages\market\MarketAction;
use app\pages\market\ProductAction;
use app\pages\sitemap\SitemapAction;
use app\permalink\PortalPermalinkAction;
use app\permalink\ProductPermalinkAction;
use app\permalink\LibraryPermalinkAction;
use app\pages\market\ProductJsonFromMarketRepoAction;
use app\pages\market\ProductJsonFromProductRepoAction;
use app\pages\market\OpenApiAction;
use app\pages\internal\MarketRCPTTAction;
use app\pages\market\MarketProductLogoRedirector;
use app\api\StatusApi;

class RoutingRules
{
  public static function installRoutes($app)
  {
    $app->get('/', MarketAction::class);

    $app->get('/permalink/{version}/{file}', ProductPermalinkAction::class);
    $app->get('/permalink/lib/{version}/{name}', LibraryPermalinkAction::class);

    $app->get('/market', MarketAction::class);
    $app->get('/market/{key}[/{version}]', ProductAction::class);
    $app->get('/_market/{key}/_product.json', ProductJsonFromMarketRepoAction::class);
    $app->get('/market-cache/{key}/{artifactId}/{version}/logo.png', MarketProductLogoRedirector::class);
    $app->get('/market-cache/{key}/{artifactId}/{version}/_product.json', ProductJsonFromProductRepoAction::class);
    $app->get('/_market/{key}/{version}/openapi', OpenApiAction::class);
    $app->get('/_market/{key}/openapi', OpenApiAction::class);
    $app->get('/internal/market-rcptt', MarketRCPTTAction::class);

    $app->get('/portal[/{version}[/{topic}[/{path:.*}]]]', PortalPermalinkAction::class);
    $app->get('/sitemap.xml', SitemapAction::class);

    $app->get('/api/status', StatusApi::class);
  }
}
