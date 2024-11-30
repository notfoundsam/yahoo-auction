<?php

namespace Unit\Parser;

use PHPUnit\Framework\TestCase;
use Tests\Utils\FileReader;
use Yahooauc\Exceptions\ParserException;
use Yahooauc\Parser;

class LoginTest extends TestCase
{
    const TEST_DATA_DIR = __DIR__ . '/../..';

    private $fileReader;

    protected function setUp(): void
    {
        $this->fileReader = new FileReader(self::TEST_DATA_DIR);
    }

    public function testCheckLogin()
    {
        $html = $this->fileReader->readFile('login_url_post.html');
        $userName = 'test_user';
        $result = Parser::checkLogin($html, $userName);

        $this->assertTrue($result);
    }

    public function testCheckLoginFailed()
    {
        $html = $this->fileReader->readFile('login_url_post.html');
        $userName = 'test_user2';
        $result = Parser::checkLogin($html, $userName);

        $this->assertFalse($result);
    }

    /**
     * @throws ParserException
     */
    public function testLoginGetHiddenInputs()
    {
        $html = $this->fileReader->readFile('login_url_get.html');
        $result = Parser::getHiddenInputs($html);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
    }

    public function testGetHiddenInputsFormNotFound()
    {
        $this->expectException(ParserException::class);

        $html = $this->fileReader->readFile('login_url_post.html');
        Parser::getHiddenInputs($html);
    }
}
