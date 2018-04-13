<?php
namespace test\util;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Assert;
use app\util\StringUtil;

class TeamActionTest extends TestCase
{
    
    public function testStartsWith()
    {
        Assert::assertTrue(StringUtil::startsWith('abc', 'abc'));
        Assert::assertTrue(StringUtil::startsWith('abc', 'ab'));
        Assert::assertTrue(StringUtil::startsWith('abc', 'a'));
        Assert::assertTrue(StringUtil::startsWith('!abc', '!abc'));
        Assert::assertTrue(StringUtil::startsWith('', ''));
        Assert::assertTrue(StringUtil::startsWith(null, null));
    }
    
    public function testStartsNotWith()
    {
        Assert::assertFalse(StringUtil::startsWith('abc', 'bc'));
        Assert::assertFalse(StringUtil::startsWith('abc', 'c'));
    }
    
    public function testEndsWith()
    {
        Assert::assertTrue(StringUtil::endsWith('abc', 'abc'));
        Assert::assertTrue(StringUtil::endsWith('abc', 'bc'));
        Assert::assertTrue(StringUtil::endsWith('abc', 'c'));
        Assert::assertTrue(StringUtil::endsWith('!abc', '!abc'));
        Assert::assertTrue(StringUtil::endsWith('', ''));
        Assert::assertTrue(StringUtil::endsWith(null, null));
    }
    
    public function testEndsNotWith()
    {
        Assert::assertFalse(StringUtil::endsWith('abc', 'ab'));
        Assert::assertFalse(StringUtil::endsWith('abc', 'a'));
    }
}