<?php
namespace Gladeye\OAuth1\Client\Test\Server;

use Gladeye\OAuth1\Client\Server\Wordpress;
use Mockery as m;

class WordpressTest extends \PHPUnit_Framework_TestCase
{

    public function testGettingUserDetails()
    {
        $server = m::mock(
            'Gladeye\OAuth1\Client\Server\Wordpress[createHttpClient,protocolHeader]',
            [$this->getMockClientCredentials()]
        );

        $temporaryCredentials = m::mock('League\OAuth1\Client\Credentials\TokenCredentials');
        $temporaryCredentials->shouldReceive('getIdentifier')->andReturn('tokencredentialsidentifier');
        $temporaryCredentials->shouldReceive('getSecret')->andReturn('tokencredentialssecret');

        $server->shouldReceive('createHttpClient')->andReturn($client = m::mock('stdClass'));

        $me = $this;
        $client->shouldReceive('get')->with('http://wordpress.dev/core/wp-json/wp/v2/users/me?_envelope', m::on(function($headers) use ($me) {
            $me->assertTrue(isset($headers['Authorization']));

            // OAuth protocol specifies a strict number of
            // headers should be sent, in the correct order.
            // We'll validate that here.
            $pattern = '/OAuth oauth_consumer_key=".*?", oauth_nonce="[a-zA-Z0-9]+", oauth_signature_method="HMAC-SHA1", oauth_timestamp="\d{10}", oauth_version="1.0", oauth_token="tokencredentialsidentifier", oauth_signature=".*?"/';

            $matches = preg_match($pattern, $headers['Authorization']);
            $me->assertEquals(1, $matches, 'Asserting that the authorization header contains the correct expression.');

            return true;
        }))->once()->andReturn($request = m::mock('stdClass'));

        $request->shouldReceive('send')->once()->andReturn($response = m::mock('stdClass'));
        $response->shouldReceive('json')->once()->andReturn($this->getUserPayload());

        $user = $server
            ->getUserDetails($temporaryCredentials);
        $this->assertInstanceOf('League\OAuth1\Client\Server\User', $user);
        $this->assertEquals('test', $user->name);
        $this->assertEquals('test', $user->nickname);
        $this->assertEquals(1, $server->getUserUid($temporaryCredentials));
    }

    protected function getMockClientCredentials()
    {
        return array(
            'identifier' => $this->getApplicationKey(),
            'secret' => 'mysecret',
            'callback_uri' => 'http://app.dev/',
            'base' => 'http://wordpress.dev/core',
        );
    }

    protected function getAccessToken()
    {
        return 'lmnopqrstuvwxyz';
    }

    protected function getApplicationKey()
    {
        return 'abcdefghijk';
    }

    private function getUserPayload()
    {
        $user = '{"body":{"avatar_urls":{"24":"http:\/\/2.gravatar.com\/avatar\/55b103d91f67f70fd10558ba3a58a820?s=24&d=mm&r=g","48":"http:\/\/2.gravatar.com\/avatar\/55b103d91f67f70fd10558ba3a58a820?s=48&d=mm&r=g","96":"http:\/\/2.gravatar.com\/avatar\/55b103d91f67f70fd10558ba3a58a820?s=96&d=mm&r=g"},"description":"","id":1,"link":"http:\/\/wordpress.dev\/core\/author\/test\/","name":"test","url":"","_links":{"self":[{"href":"http:\/\/wordpress.dev\/core\/wp-json\/wp\/v2\/users\/1"}],"collection":[{"href":"http:\/\/wordpress.dev\/core\/wp-json\/wp\/v2\/users"}]}},"status":302,"headers":{"Location":"http:\/\/wordpress.dev\/core\/wp-json\/wp\/v2\/users\/1","Allow":"GET"}}';

        return json_decode($user, true);
    }
}
