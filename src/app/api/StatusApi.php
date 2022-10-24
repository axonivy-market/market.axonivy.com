<?php

namespace app\api;

use app\domain\market\Market;

class StatusApi
{
  public function __invoke($request, $response, $args)
  {
    $data = $this->status();
    $response->getBody()->write((string) json_encode($data));
    $response = $response->withHeader('Content-Type', 'application/json');
    return $response;
  }

  private function status()
  {
    return [
      'site' => [
        'phpVersion' => phpversion()
      ],
      'data' => [
        'market' => $this->getMarketProducts()
      ]
    ];
  }

  private function getMarketProducts(): array
  {
    $products = [];
    foreach (Market::all() as $product) {
      $p = [
        'key' => $product->getKey(),
        'name' => $product->getName(),
        'url' => $product->getUrl()
      ];
      $mavenProductInfo = $product->getMavenProductInfo();
      if ($mavenProductInfo != null) {
        
        $latestVersionToDisplay = 'unavailable';
        $latestVersionAvailable = 'unavailable';
        try {
          $latestVersionToDisplay = $mavenProductInfo->getLatestVersionToDisplay(false, null);
          $latestVersionAvailable = $mavenProductInfo->getLatestVersion();
        } catch (\Exception $ex) { }
        $p['latest-version-to-display'] = $latestVersionToDisplay;
        $p['latest-version-available'] = $latestVersionAvailable;
      }
      $products[] = $p;
    }
    return $products;
  }
}
