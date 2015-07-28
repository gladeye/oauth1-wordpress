<?php
namespace Gladeye\OAuth1\Client\Server;

use League\OAuth1\Client\Credentials\TokenCredentials;
use League\OAuth1\Client\Server\Server;
use League\OAuth1\Client\Server\User;

class Wordpress extends Server
{
    /**
     * The base URL, used to generate the auth endpoints.
     *
     * @var string
     */
    protected $base;

    /**
     * The base path to obtain user information
     *
     * @var string
     */
    protected $me;

    /**
     * {@inheritDoc}
     */
    public function __construct($clientCredentials, SignatureInterface $signature = null)
    {
        parent::__construct($clientCredentials, $signature);

        if (is_array($clientCredentials)) {
            $this->parseConfigurationArray($clientCredentials);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function urlTemporaryCredentials()
    {
        return $this->base.'/oauth1/request';
    }

    /**
     * {@inheritDoc}
     */
    public function urlAuthorization()
    {
        return $this->base.'/oauth1/authorize?oauth_callback=' . $this->clientCredentials->getCallbackUri();
    }

    /**
     * {@inheritDoc}
     */
    public function urlTokenCredentials()
    {
        return $this->base.'/oauth1/access';
    }

    /**
     * {@inheritDoc}
     */
    public function urlUserDetails()
    {
        return $this->base.'/'.$this->me;
    }

    /**
     * {@inheritDoc}
     */
    public function userDetails($data, TokenCredentials $tokenCredentials)
    {
        // If the API has broke, return nothing
        if (!isset($data['body']['id']) || !is_array($data['body'])) {
            return;
        }

        $user = new User();

        $user->name = $data['body']['name'];
        $user->nickname = $data['body']['name'];
        $user->uid = $data['body']['id'];

        // Save all extra data
        $used = array('name', 'id');
        $user->extra = array_diff_key($data['body'], array_flip($used));

        return $user;
    }

    /**
     * {@inheritDoc}
     */
    public function userUid($data, TokenCredentials $tokenCredentials)
    {
        // If the API has broke, return nothing
        if (!isset($data['body']['id']) || !is_array($data['body'])) {
            return;
        }

        return $data['body']['id'];
    }

    /**
     * {@inheritDoc}
     */
    public function userEmail($data, TokenCredentials $tokenCredentials)
    {
        return;
    }

    /**
     * {@inheritDoc}
     */
    public function userScreenName($data, TokenCredentials $tokenCredentials)
    {
        // If the API has broke, return nothing
        if (!isset($data['body']['id']) || !is_array($data['body'])) {
            return;
        }

        return $data['body']['name'];
    }

    /**
     * Parse configuration array to set attributes.
     *
     * @param array $configuration
     *
     * @throws InvalidArgumentException
     */
    private function parseConfigurationArray(array $configuration = array())
    {
        if (!isset($configuration['base'])) {
            throw new \InvalidArgumentException('Missing Wordpress API base');
        }

        $configuration = array_merge(
            $configuration,
            array(
                "me" => "wp-json/wp/v2/users/me?_envelope"
            )
        );

        $this->base = trim($configuration['base'], '/');
        $this->me = trim($configuration['me'], '/');
    }
}
