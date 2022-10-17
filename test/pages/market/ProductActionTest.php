<?php

namespace test\pages\market;

use PHPUnit\Framework\TestCase;
use test\AppTester;
use app\domain\market\Market;
use PHPUnit\Framework\Assert;

class ProductActionTest extends TestCase
{

  public function testBasicWorkflowUi()
  {
    AppTester::assertThatGet('/basic-workflow-ui')
      ->ok()
      ->bodyContains('Basic Workflow UI');
  }

  public function testPortal()
  {
    AppTester::assertThatGet('/portal')
      ->ok()
      ->bodyContains('Portal');
  }

  public function test_minorVersion_redirectToLatestBugfixVersion()
  {
    $product = Market::getProductByKey('portal');
    $version = $product->getMavenProductInfo()->getLatestReleaseVersion("8.0");

    Assert::assertNotEquals("8.0", $version);
    AppTester::assertThatGet('/portal/8.0')->redirect("/portal/$version");
  }

  public function test_minorVersion_redirectToLatestBugfixVersion_withSubPath()
  {
    $product = Market::getProductByKey('portal');
    $version = $product->getMavenProductInfo()->getLatestReleaseVersion("8.0");

    Assert::assertNotEquals("8.0", $version);
    AppTester::assertThatGet('/portal/8.0/doc/deep/deeper/index.html')->redirect("/portal/$version/doc/deep/deeper/index.html");
  }

  public function test_minorVersion_notFoundIfNoVersionExist()
  {
    AppTester::assertThatGet('/portal/1000.0')->notFound();
  }

  public function test_majorVersion_redirectToLatestBugfixVersion()
  {
    $product = Market::getProductByKey('portal');
    $version = $product->getMavenProductInfo()->getLatestReleaseVersion("8");

    Assert::assertNotEquals("8", $version);
    AppTester::assertThatGet('/portal/8')->redirect("/portal/$version");
  }

  public function test_majorVersion_notFoundIfNoVersionExist()
  {
    AppTester::assertThatGet('/portal/1000')->notFound();
  }

  public function test_bugfixVersion()
  {
    $product = Market::getProductByKey('portal');
    $version = $product->getMavenProductInfo()->getLatestReleaseVersion("8");

    Assert::assertNotEquals("8", $version);
    AppTester::assertThatGet("/portal/$version")
      ->ok()
      ->bodyContains("Portal");
  }

  public function test_bugfixVersion_nonExisting()
  {
    AppTester::assertThatGet('/portal/1000.0.0')->notFound();
  }

  public function test_devReleases()
  {
    $product = Market::getProductByKey('portal');
    $version = $product->getMavenProductInfo()->getLatestVersion();
    AppTester::assertThatGet('/portal/dev')->redirect("/portal/$version");
    AppTester::assertThatGet('/portal/sprint')->redirect("/portal/$version");
    AppTester::assertThatGet('/portal/nightly')->redirect("/portal/$version");
  }

  public function test_latest()
  {
    AppTester::assertThatGet('/portal/latest')->redirect('/portal/' . Market::getProductByKey('portal')->getMavenProductInfo()->getLatestVersionToDisplay(true, null));
  }

  public function testPortalDoc()
  {
    $product = Market::getProductByKey('portal');
    $version = $product->getMavenProductInfo()->getLatestReleaseVersion("8");
    AppTester::assertThatGet('/portal/8.0.3/doc')->redirect('/market-cache/portal/portal-guide/8.0.3');
    AppTester::assertThatGet('/portal/8.0/doc')->redirect("/portal/$version/doc");
  }

  public function testPortalDocWithDocument()
  {
    $product = Market::getProductByKey('portal');
    $version = $product->getMavenProductInfo()->getLatestReleaseVersion("8");
    AppTester::assertThatGet('/portal/8.0.3/doc/test.html')->redirect('/market-cache/portal/portal-guide/8.0.3/test.html');
    AppTester::assertThatGet('/portal/8.0/doc/test.html')->redirect("/portal/$version/doc/test.html");
  }

  public function testPortalDocWithDocumentInSubfolder()
  {
    $product = Market::getProductByKey('portal');
    $version = $product->getMavenProductInfo()->getLatestReleaseVersion("8");
    AppTester::assertThatGet('/portal/8.0.3/doc/subfolder/test.html')->redirect('/market-cache/portal/portal-guide/8.0.3/subfolder/test.html');
    AppTester::assertThatGet('/portal/8.0/doc/subfolder/test.html')->redirect("/portal/$version/doc/subfolder/test.html");
  }

  public function testPortalBrokenLink()
  {
    $product = Market::getProductByKey('portal');
    $version = $product->getMavenProductInfo()->getLatestReleaseVersion("8");
    AppTester::assertThatGet('/portal/8.0/doc/portal-developer-guide/introduction/index.html#new-and-noteworthy')
      ->redirect("/portal/$version/doc/portal-developer-guide/introduction/index.html");

    AppTester::assertThatGet('/portal/8.0.28/doc/portal-developer-guide/introduction/index.html#new-and-noteworthy')
      ->redirect("/market-cache/portal/portal-guide/8.0.28/portal-developer-guide/introduction/index.html");
  }

  public function testSonatypeArtifact()
  {
    AppTester::assertThatGet('/web-tester')
      ->ok()
      ->bodyContains('Web Tester');
  }

  public function testInstallButton_canNotInstallInOfficalMarket()
  {
    AppTester::assertThatGet('/genderize-io-connector')
      ->ok()
      ->bodyContains("Please open the");
  }

  public function testInstallButton_canInstallInDesignerMarket()
  {
    AppTester::assertThatGetWithCookie('http://localhost/genderize-io-connector', ['ivy-version' => '9.2.0'])
      ->ok()
      ->bodyContains("'http://localhost/_market/genderize-io-connector/_product.json?version=");
  }

  public function testInstallButton_byDefaultWithCurrentVersion()
  {
    $product = Market::getProductByKey('doc-factory');
    $version = $product->getMavenProductInfo()->getLatestVersionToDisplay(false, null);

    AppTester::assertThatGetWithCookie('http://localhost/doc-factory', ['ivy-version' => $version])
      ->ok()
      ->bodyContains("$version");
  }
  
  public function testInstallButton_byDefaulNoSnapshotVersions()
  {
    $product = Market::getProductByKey('doc-factory');
    AppTester::assertThatGet('http://localhost/doc-factory')
      ->ok()
      ->bodyContains("9.4.0")
      ->bodyDoesNotContain("SNAPSHOT");
  }

  public function testInstallButton_respectCookie_ltsMatchInstaller()
  {
    $product = Market::getProductByKey('doc-factory');
    // grab latest minor version of doc factory
    $version = '';    
    foreach ($product->getMavenProductInfo()->getVersionsToDisplay(false, '9.4.0') as $v)
    {
        if (str_starts_with($v, '9.4')) {
           $version = $v;
           break;
        }
    }
    AppTester::assertThatGetWithCookie('http://localhost/doc-factory', ['ivy-version' => '9.4.0'])
      ->ok()
      ->bodyContains($version);
  }

  public function testInstallButton_respectCookie_bestMatchInstaller()
  {
      AppTester::assertThatGetWithCookie('http://localhost/portal', ['ivy-version' => '8.0.10'])
        ->ok()
        ->bodyContains("8.0.10");
  }
  
  public function testInstallButton_respectCookie_bestMatchInstaller_showSnapshotIfNoMatch()
  {
      AppTester::assertThatGetWithCookie('http://localhost/doc-factory', ['ivy-version' => '9.4.1'])
        ->ok()
        ->bodyContains("9.4.1-SNAPSHOT");
  }

  public function testInstallButton_respectCookie_bestMatchInstaller_showSnapshotIfNoMatch_bugFixReleaseAware()
  {
      AppTester::assertThatGetWithCookie('http://localhost/doc-factory', ['ivy-version' => '9.4.2'])
        ->ok()
        ->bodyContains("9.4.1-SNAPSHOT");
  }

  public function testInstallButton_respectCookie_bestMatchInstaller_dontShowSnapshotIfNoNeeded()
  {
      AppTester::assertThatGetWithCookie('http://localhost/doc-factory', ['ivy-version' => '9.4.0'])
        ->ok()
        ->bodyContains("9.4.0")
        ->bodyDoesNotContain("9.3.1-SNAPSHOT");
  }

  public function testInstallButton_respectCookie_bestMatchInstaller_ifNotExistUseLast()
  {
    $product = Market::getProductByKey('portal');
    $version = '';
    // grab latest minor version of doc factory
    foreach ($product->getMavenProductInfo()->getVersionsToDisplay(true, null) as $v)
    {
        if (str_starts_with($v, '8.0')) {
            $version = $v;
            break;
        }
    }
    AppTester::assertThatGetWithCookie('http://localhost/portal', ['ivy-version' => '8.0.99'])
      ->ok()
      ->bodyContains($version);
  }

  public function testInstallButton_useSpecificVersion()
  {
    AppTester::assertThatGetWithCookie('http://localhost/doc-factory/8.0.8', ['ivy-version' => '9.2.0'])
      ->ok()
      ->bodyContains("8.0.8");
  }

  public function testNotFoundWhenVersionDoesNotExistOfMavenBackedArtifact()
  {
    AppTester::assertThatGet('/basic-workflow-ui')->ok();
    AppTester::assertThatGet('/basic-workflow-ui/444')->notFound();
  }

  public function testNotFoundWhenVersionDoesNotExistOfNonMavenArtifact()
  {
    AppTester::assertThatGet('/genderize-io-connector')->ok();
    AppTester::assertThatGet('/genderize-io-connector/444')->notFound();
  }

  public function testAPIBrowserButton_exists()
  {
    AppTester::assertThatGet('/genderize-io-connector')
      ->ok()
      ->bodyContains("/api-browser?url=/_market/genderize-io-connector/openapi");
  }

  public function testAPIBrowserButton_existsExtern()
  {
    AppTester::assertThatGet('/uipath')
      ->ok()
      ->bodyContains("/api-browser?url=https%3A%2F%2Fcloud.uipath.com%2FAXONPRESALES%2FAXONPRESALES%2Fswagger%2Fv13.0%2Fswagger.json");
  }

  public function testAPIBrowserButton_existsForYaml()
  {
    AppTester::assertThatGet('/amazon-lex')
      ->ok()
      ->bodyContains("/api-browser?url=/market-cache/amazon-lex/amazon-lex-connector-product");
  }

  public function testAPIBrowserButton_existsNot()
  {
    AppTester::assertThatGet('/basic-workflow-ui')
      ->ok()
      ->bodyDoesNotContain("/api-browser?url");
  }
  
  public function testGetInTouchLink()
  {
    AppTester::assertThatGet('/employee-onboarding')
      ->ok()
      ->bodyContains('a class="button special install" href="https://www.axonivy.com/marketplace/contact/?market_solutions=employee-onboarding');
  }
  
  public function testDontShowInstallCountForUninstallableProducts() 
  {
    AppTester::assertThatGet('/employee-onboarding')
      ->ok()
      ->bodyDoesNotContain('Installations');
  }

  public function testDontDisplaySnapshotInVersionDropdown() 
  {
    AppTester::assertThatGet('/doc-factory')
      ->ok()
      ->bodyDoesNotContain('-SNAPSHOT</option>');
  }
  
  /**
    * @runInSeparateProcess
    */
  public function testDontDisplaySnapshotInVersionDropdownWhenEnabled() 
  {
    AppTester::assertThatGet('/doc-factory?showDevVersions=true')
      ->ok()
      ->bodyContains('-SNAPSHOT</option>');
  }

  /**
    * @runInSeparateProcess
    */
  public function testDontDisplaySnapshotInVersionDropdownWhenHiding() 
  {
    AppTester::assertThatGet('/doc-factory?showDevVersions=false')
      ->ok()
      ->bodyDoesNotContain('-SNAPSHOT</option>');
  }

  public function testShowBuildStatusBadge() 
  {
    AppTester::assertThatGet('/excel-connector')
      ->ok()
      ->bodyContains('<img src="https://github.com/axonivy-market/excel-connector/actions/workflows/ci.yml/badge.svg" />');
  }

  public function testExternalVendor() 
  {
    AppTester::assertThatGet('/jira-connector')
      ->ok()
      ->bodyContains('src="/_market/jira-connector/frox.png"')
      ->bodyContains('alt="FROX AG"')
      ->bodyContains('href="https://www.frox.ch"');

  }

  public function testVendor() 
  {
    AppTester::assertThatGet('/visualvm-plugin')
      ->ok()
      ->bodyContains('src="/images/misc/axonivy-logo-black.svg"')
      ->bodyContains('alt="Axon Ivy AG"')      
      ->bodyContains('href="https://www.axonivy.com"');
  }
}
