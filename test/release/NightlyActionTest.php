<?php
namespace test\release;

use PHPUnit\Framework\TestCase;
use test\AppTester;

class NightlyActionTest extends TestCase
{
    
    public function testRender()
    {
        AppTester::assertThatGet('/download/nightly')
            ->statusCode(200)
            ->bodyContains('Nightly Builds')
            ->bodyContains('AxonIvyDesigner7.0.1.56047_Linux_x64.zip')
            ->bodyContains('https://d3ao4l46dir7t.cloudfront.net/ivy/nightly/current/AxonIvyDesigner7.0.1.56047_Linux_x64.zip');
    }
    
    public function testRedirectToP2()
    {
        AppTester::assertThatGet('/download/nightly/p2')
        ->statusCode(302)
        ->header('Location', '/dev-releases/ivy/nightly/current/p2');
    }
    
    public function testAlsoAvailableUnderHtmlUrl()
    {
        $body1 = AppTester::assertThatGet('/download/nightly')->statusCode(200)->bodyContains('Nightly Builds')->getBody();
        $body2 = AppTester::assertThatGet('/download/nightly.html')->getBody();
        self::assertEquals($body1, $body2);
    }
}