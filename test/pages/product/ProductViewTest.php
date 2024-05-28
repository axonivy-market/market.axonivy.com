<?php

namespace test\pages\product;

use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use app\domain\market\Market;
use app\pages\product\ProductView;
use test\AppTester;

class ProductViewTest extends TestCase
{
  public function testMavenArtifacts_notDuplicated()
  {
    $uniqueArtifacts = self::getProductView()->getMavenArtifacts();
    $this->assertCount(1, $uniqueArtifacts);
  }

  public function getProductView()
  {
    $product = Market::getProductByKey('adobe-acrobat-sign-connector');
    $version = '10.0.20';
    return new ProductView(
      AppTester::assertThatGetWithCookie('http://localhost/adobe-acrobat-sign-connector', ['ivy-version' => $version, 'installNow' => false])->ok(),
      $product,
      $version,
      '10.0.17',
      false,
      $product->getMavenProductInfo()
    );
  }
}
