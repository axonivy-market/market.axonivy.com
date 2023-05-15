<?php

namespace test\domain\maven;

use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use app\domain\market\Market;
use app\domain\market\Product;
use app\domain\market\ProductMavenArtifactDownloader;
use app\domain\maven\MavenArtifact;

class MavenArtifactTest extends TestCase
{
  public function testFilterSnapshotsWhichAreRealesed()
  {
    $versions = [
      '9.3.0', '9.2.0', '9.2.0-SNAPSHOT', '9.1.0'
    ];
    $result = MavenArtifact::filterSnapshotsWhichAreRealesed($versions);
    
    Assert::assertCount(3, $result);
    Assert::assertEquals('9.3.0', $result[0]);
    Assert::assertEquals('9.2.0', $result[1]);
    Assert::assertEquals('9.1.0', $result[2]);
  }
  
  public function testFilterSnapshotsBetweenReleasedVersions()
  {
    $versions = [
      '10.0.0', '9.2.2-SNAPSHOT', '9.2.1', '9.2.0', '9.2.0-SNAPSHOT', '9.1.0'
    ];
    $result = MavenArtifact::filterSnapshotsBetweenReleasedVersions($versions);
    
    Assert::assertCount(5, $result);
    Assert::assertEquals('10.0.0', $result[0]);
    Assert::assertEquals('9.2.2-SNAPSHOT', $result[1]);
    Assert::assertEquals('9.2.1', $result[2]);
    Assert::assertEquals('9.2.0', $result[3]);
    Assert::assertEquals('9.1.0', $result[4]);
  }

  public function testGetMavenArtifact()
  {
    (new ProductMavenArtifactDownloader())->download(Market::getProductByKey('workflow-demo'), "10.0.0");
    $artifact = self::getMavenArtifact('workflow-demos', 'iar');
    $this->assertEquals('Workflow Demos', $artifact->getName());
  }

  public function testGetMavenArtifact_notExisting()
  {
    $artifact = self::getMavenArtifact('does not exist', '');
    $this->assertNull($artifact);
  }

  public function testGetWorkflowDemo()
  {
    (new ProductMavenArtifactDownloader())->download(Market::getProductByKey('workflow-demo'), "10.0.0");
    $artifact = self::getMavenArtifact('workflow-demos', 'iar');
    $this->assertEquals('Workflow Demos', $artifact->getName());
  }

  public function testMavenArtifact()
  {
    (new ProductMavenArtifactDownloader())->download(Market::getProductByKey('connectivity-demo'), "10.0.0");
    $artifact = self::getMavenArtifact('connectivity-demos', 'iar');
    $this->assertEquals('Connectivity Demos', $artifact->getName());
    $this->assertEquals('com.axonivy.demo', $artifact->getGroupId());
    $this->assertEquals('connectivity-demos', $artifact->getArtifactId());
  }

  public function testParseLatestVersionFromXml()
  {
    $xml = file_get_contents(dirname(__FILE__) . '/maven-metadata.xml');
    $versions = MavenArtifact::parseVersions($xml);
    $this->assertEquals('7.2.0-SNAPSHOT', $versions[0]);
    $this->assertEquals('7.3.0-SNAPSHOT', $versions[1]);
  }

  public function testParseVersionIdentifierFromXml()
  {
    $xml = file_get_contents(dirname(__FILE__) . '/maven-metadata-specific.xml');
    $version = MavenArtifact::parseVersionIdentifierFromXml($xml);
    $this->assertEquals('7.3.0-20181115.013605-5', $version);
  }

  public function test_type()
  {
    $artifact = self::getMavenArtifact('visualvm-plugin', 'nbm');
    Assert::assertEquals('nbm', $artifact->getType());

    (new ProductMavenArtifactDownloader())->download(Market::getProductByKey('demos-app'), "10.0.0");
    $artifact = self::getMavenArtifact('ivy-demos-app', 'zip');
    Assert::assertEquals('zip', $artifact->getType());

    (new ProductMavenArtifactDownloader())->download(Market::getProductByKey('workflow-demo'), "10.0.0");
    $artifact = self::getMavenArtifact('workflow-demos', 'iar');
    Assert::assertEquals('iar', $artifact->getType());
  }

  public function test_makeSenseAsMavenDependency()
  {
    $artifact = self::getMavenArtifact('visualvm-plugin', 'nbm');
    Assert::assertFalse($artifact->getMakesSenseAsMavenDependency());

    (new ProductMavenArtifactDownloader())->download(Market::getProductByKey('doc-factory'), "10.0.0");
    $artifact = self::getMavenArtifact('doc-factory', 'iar');
    Assert::assertTrue($artifact->getMakesSenseAsMavenDependency());
  }
  
  public function test_repoUrl()
  {
    $artifact = self::getMavenArtifact('visualvm-plugin', 'nbm');
    Assert::assertEquals('https://maven.axonivy.com/', $artifact->getRepoUrl());
  }

  public function test_isDocumentation()
  {
    $artifact = self::getMavenArtifact('visualvm-plugin', 'nbm');
    Assert::assertFalse($artifact->isDocumentation());

    (new ProductMavenArtifactDownloader())->download(Market::getProductByKey('doc-factory'), "10.0.0");
    $artifact = self::getMavenArtifact('doc-factory-doc', 'zip');
    Assert::assertTrue($artifact->isDocumentation());
  }

  public function test_name()
  {
    (new ProductMavenArtifactDownloader())->download(Market::getProductByKey('visualvm-plugin'), "8.0.0");
    $artifact = self::getMavenArtifact('visualvm-plugin', 'nbm');
    Assert::assertEquals('Visual VM Plugin', $artifact->getName());

    (new ProductMavenArtifactDownloader())->download(Market::getProductByKey('doc-factory'), "10.0.0");
    $artifact = self::getMavenArtifact('doc-factory-doc', 'zip');
    Assert::assertEquals('DocFactory Documentation', $artifact->getName());
  }

  public static function getMavenArtifact(string $artifactId, string $type): ?MavenArtifact
  {
    $artifacts = self::getAll();
    foreach ($artifacts as $artifact) {
      if ($artifact->getArtifactId() == $artifactId && $artifact->getType() == $type) {        
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
        $all = array_merge($all, $info->getMavenArtifacts("10.0.0"));
      }
    }
    return $all;
  }
}
