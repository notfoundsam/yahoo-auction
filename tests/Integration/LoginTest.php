<?php

namespace Integration;

use GuzzleHttp\Exception\GuzzleException;
use PHPUnit\Framework\TestCase;
use Yahooauc\Browser;
use Yahooauc\Exceptions\CaptchaException;
use Yahooauc\Exceptions\LoginException;

class LoginTest extends TestCase
{
    /**
     * @throws GuzzleException
     */
    public function testCheckLogin()
    {
        $browser = new Browser('test_user', 'secret_password', null, []);
        $result = $browser->checkLogin();

        $this->assertTrue($result);
    }

    /**
     * @throws GuzzleException
     */
    public function testCheckLoginFailed()
    {
        $browser = new Browser('test_user2', 'secret_password', null, []);
        $result = $browser->checkLogin();

        $this->assertFalse($result);
    }

    /**
     * @throws CaptchaException
     * @throws GuzzleException
     * @throws LoginException
     */
    public function testLogin()
    {
        $browser = new Browser('test_user', 'secret_password', null, []);
        $result = $browser->login();

        $this->assertTrue($result);
    }

    /**
     * @throws CaptchaException
     * @throws GuzzleException
     */
    public function testLoginFailed()
    {
        $this->expectException(LoginException::class);

        $browser = new Browser('test_user2', 'secret_password', null, []);
        $browser->login();
    }

    /**
     * @throws GuzzleException
     * @throws LoginException
     */
    public function testLoginThrowsCaptchaException()
    {
        $this->expectException(CaptchaException::class);

        // It seems there is a bug in the debug logic. User should be `the test_user`.
        $browser = new Browser('test_user2', 'secret_password', null, []);
        $browser->debugShowCaptcha(true);
        $browser->login();
    }
}
