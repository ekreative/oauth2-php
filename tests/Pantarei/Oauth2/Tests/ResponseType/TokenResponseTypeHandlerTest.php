<?php

/**
 * This file is part of the pantarei/oauth2 package.
 *
 * (c) Wong Hoi Sing Edison <hswong3i@pantarei-design.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Pantarei\Oauth2\Tests\ResponseType;

use Pantarei\Oauth2\Tests\WebTestCase;
use Symfony\Component\HttpFoundation\Request;

class TokenResponseTypeHandlerTest extends WebTestCase
{
    public function testExceptionTokenNoClientId()
    {
        $parameters = array(
            'response_type' => 'token',
            'redirect_uri' => 'http://democlient1.com/redirect_uri',
        );
        $server = array(
            'PHP_AUTH_USER' => 'demousername1',
            'PHP_AUTH_PW' => 'demopassword1',
        );
        $client = $this->createClient();
        $crawler = $client->request('GET', '/authorize', $parameters, array(), $server);
        $this->assertEquals(400, $client->getResponse()->getStatusCode());
        $this->assertNotNull(json_decode($client->getResponse()->getContent()));
        $token_response = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals('invalid_request', $token_response['error']);
    }

    public function testExceptionTokenBadClientId()
    {
        $parameters = array(
            'response_type' => 'token',
            'client_id' => 'http://badclient1.com/',
            'redirect_uri' => 'http://democlient1.com/redirect_uri',
        );
        $server = array(
            'PHP_AUTH_USER' => 'demousername1',
            'PHP_AUTH_PW' => 'demopassword1',
        );
        $client = $this->createClient();
        $crawler = $client->request('GET', '/authorize', $parameters, array(), $server);
        $this->assertEquals(400, $client->getResponse()->getStatusCode());
        $this->assertNotNull(json_decode($client->getResponse()->getContent()));
        $token_response = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals('invalid_client', $token_response['error']);
    }

    public function testExceptionTokenNoSavedNoPassedRedirectUri()
    {
        // Insert client without redirect_uri.
        $modelManager =  $this->app['oauth2.model_manager.factory']->getModelManager('client');
        $model = $modelManager->createClient();
        $model->setClientId('http://democlient4.com/')
            ->setClientSecret('demosecret4');
        $modelManager->updateClient($model);

        $parameters = array(
            'response_type' => 'token',
            'client_id' => 'http://democlient4.com/',
        );
        $server = array(
            'PHP_AUTH_USER' => 'demousername1',
            'PHP_AUTH_PW' => 'demopassword1',
        );
        $client = $this->createClient();
        $crawler = $client->request('GET', '/authorize', $parameters, array(), $server);
        $this->assertEquals(400, $client->getResponse()->getStatusCode());
        $this->assertNotNull(json_decode($client->getResponse()->getContent()));
        $token_response = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals('invalid_request', $token_response['error']);
    }

    public function testExceptionTokenBadRedirectUri()
    {
        $parameters = array(
            'response_type' => 'token',
            'client_id' => 'http://democlient1.com/',
            'redirect_uri' => 'http://democlient1.com/wrong_uri',
        );
        $server = array(
            'PHP_AUTH_USER' => 'demousername1',
            'PHP_AUTH_PW' => 'demopassword1',
        );
        $client = $this->createClient();
        $crawler = $client->request('GET', '/authorize', $parameters, array(), $server);
        $this->assertEquals(400, $client->getResponse()->getStatusCode());
        $this->assertNotNull(json_decode($client->getResponse()->getContent()));
        $token_response = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals('invalid_request', $token_response['error']);
    }

    public function testErrorTokenBadScopeFormat()
    {
        $parameters = array(
            'response_type' => 'token',
            'client_id' => 'http://democlient1.com/',
            'redirect_uri' => 'http://democlient1.com/redirect_uri',
            'scope' => "aaa\x22bbb\x5Cccc\x7Fddd",
        );
        $server = array(
            'PHP_AUTH_USER' => 'demousername1',
            'PHP_AUTH_PW' => 'demopassword1',
        );
        $client = $this->createClient();
        $crawler = $client->request('GET', '/authorize', $parameters, array(), $server);
        $this->assertTrue($client->getResponse()->isRedirect());
        $auth_response = Request::create($client->getResponse()->headers->get('Location'), 'GET');
        $token_response = $auth_response->query->all();
        $this->assertEquals('invalid_request', $token_response['error']);
    }

    public function testErrorTokenBadScope()
    {
        $parameters = array(
            'response_type' => 'token',
            'client_id' => 'http://democlient1.com/',
            'redirect_uri' => 'http://democlient1.com/redirect_uri',
            'scope' => "badscope1",
        );
        $server = array(
            'PHP_AUTH_USER' => 'demousername1',
            'PHP_AUTH_PW' => 'demopassword1',
        );
        $client = $this->createClient();
        $crawler = $client->request('GET', '/authorize', $parameters, array(), $server);
        $this->assertTrue($client->getResponse()->isRedirect());
        $auth_response = Request::create($client->getResponse()->headers->get('Location'), 'GET');
        $token_response = $auth_response->query->all();
        $this->assertEquals('invalid_scope', $token_response['error']);
    }

    public function testGoodToken()
    {
        $parameters = array(
            'response_type' => 'token',
            'client_id' => 'http://democlient1.com/',
            'redirect_uri' => 'http://democlient1.com/redirect_uri',
        );
        $server = array(
            'PHP_AUTH_USER' => 'demousername1',
            'PHP_AUTH_PW' => 'demopassword1',
        );
        $client = $this->createClient();
        $crawler = $client->request('GET', '/authorize', $parameters, array(), $server);
        $this->assertTrue($client->getResponse()->isRedirect());

        $parameters = array(
            'response_type' => 'token',
            'client_id' => 'http://democlient1.com/',
            'redirect_uri' => 'http://democlient1.com/redirect_uri',
            'scope' => 'demoscope1',
        );
        $server = array(
            'PHP_AUTH_USER' => 'demousername1',
            'PHP_AUTH_PW' => 'demopassword1',
        );
        $client = $this->createClient();
        $crawler = $client->request('GET', '/authorize', $parameters, array(), $server);
        $this->assertTrue($client->getResponse()->isRedirect());

        $parameters = array(
            'response_type' => 'token',
            'client_id' => 'http://democlient3.com/',
            'redirect_uri' => 'http://democlient3.com/redirect_uri',
            'scope' => 'demoscope1 demoscope2 demoscope3',
        );
        $server = array(
            'PHP_AUTH_USER' => 'demousername3',
            'PHP_AUTH_PW' => 'demopassword3',
        );
        $client = $this->createClient();
        $crawler = $client->request('GET', '/authorize', $parameters, array(), $server);
        $this->assertTrue($client->getResponse()->isRedirect());

        $parameters = array(
            'response_type' => 'token',
            'client_id' => 'http://democlient3.com/',
            'redirect_uri' => 'http://democlient3.com/redirect_uri',
            'scope' => 'demoscope1 demoscope2 demoscope3',
            'state' => 'example state',
        );
        $server = array(
            'PHP_AUTH_USER' => 'demousername3',
            'PHP_AUTH_PW' => 'demopassword3',
        );
        $client = $this->createClient();
        $crawler = $client->request('GET', '/authorize', $parameters, array(), $server);
        $this->assertTrue($client->getResponse()->isRedirect());
    }

    public function testGoodTokenNoPassedRedirectUri()
    {
        // Insert client with redirect_uri, test empty pass in.
        $modelManager =  $this->app['oauth2.model_manager.factory']->getModelManager('client');
        $model = $modelManager->createClient();
        $model->setClientId('http://democlient4.com/')
            ->setClientSecret('demosecret4')
            ->setRedirectUri('http://democlient4.com/redirect_uri');
        $modelManager->updateClient($model);

        $parameters = array(
            'response_type' => 'token',
            'client_id' => 'http://democlient4.com/',
        );
        $server = array(
            'PHP_AUTH_USER' => 'demousername1',
            'PHP_AUTH_PW' => 'demopassword1',
        );
        $client = $this->createClient();
        $crawler = $client->request('GET', '/authorize', $parameters, array(), $server);
        $this->assertTrue($client->getResponse()->isRedirect());
    }

    public function testGoodTokenNoStoredRedirectUri()
    {
        // Insert client without redirect_uri, test valid pass in.
        $modelManager =  $this->app['oauth2.model_manager.factory']->getModelManager('client');
        $model = $modelManager->createClient();
        $model->setClientId('http://democlient5.com/')
            ->setClientSecret('demosecret5');
        $modelManager->updateClient($model);

        $parameters = array(
            'response_type' => 'token',
            'client_id' => 'http://democlient5.com/',
            'redirect_uri' => 'http://democlient5.com/redirect_uri',
        );
        $server = array(
            'PHP_AUTH_USER' => 'demousername1',
            'PHP_AUTH_PW' => 'demopassword1',
        );
        $client = $this->createClient();
        $crawler = $client->request('GET', '/authorize', $parameters, array(), $server);
        $this->assertTrue($client->getResponse()->isRedirect());
    }
}
