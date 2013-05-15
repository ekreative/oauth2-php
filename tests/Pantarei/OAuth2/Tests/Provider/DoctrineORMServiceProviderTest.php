<?php

/**
 * This file is part of the pantarei/oauth2 package.
 *
 * (c) Wong Hoi Sing Edison <hswong3i@pantarei-design.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Pantarei\OAuth2\Tests\Database;

use Pantarei\OAuth2\Entity\Scopes;
use Pantarei\OAuth2\Provider\DoctrineORMServiceProvider;
use Pantarei\OAuth2\Tests\OAuth2WebTestCase;
use Silex\Application;
use Silex\Provider\DoctrineServiceProvider;

/**
 * Test base OAuth2.0 exception.
 *
 * @author Wong Hoi Sing Edison <hswong3i@pantarei-design.com>
 */
class DatabaseTest extends OAuth2WebTestCase
{
  public function createApplication()
  {
    $app = new Application();
    $app['debug'] = TRUE;
    $app['session'] = TRUE;
    $app['exception_handler']->disable();

    $app->register(new DoctrineServiceProvider, array(
      'db.options' => array(
        'driver' => 'pdo_sqlite',
        'memory' => TRUE,
      ),
    ));
    $app->register(new DoctrineORMServiceProvider, array(
      'orms.options' => array(
        'master' => array(
          'connection' => 'default',
          'path' => __DIR__ . '/Entity',
        ),
        'slave' => array(
          'connection' => 'default',
          'path' => __DIR__ . '/Entity',
        ),
      ),
    ));

    return $app;
  }

  public function testFind()
  {
    $result = $this->app['orm']->find('Pantarei\OAuth2\Entity\AccessTokens', 1);
    $this->assertEquals('eeb5aa92bbb4b56373b9e0d00bc02d93', $result->getAccessToken());
  }

  public function testFindOnSlave()
  {
    $result = $this->app['orms']['slave']->find('Pantarei\OAuth2\Entity\AccessTokens', 1);
    $this->assertEquals('eeb5aa92bbb4b56373b9e0d00bc02d93', $result->getAccessToken());
  }

  public function testFindBy()
  {
    $result = $this->app['orm']->getRepository('Pantarei\OAuth2\Entity\AccessTokens')->findBy(array(
      'access_token' => 'eeb5aa92bbb4b56373b9e0d00bc02d93',
    ));
    $this->assertEquals('eeb5aa92bbb4b56373b9e0d00bc02d93', $result[0]->getAccessToken());
  }

  public function testFindOneBy()
  {
    $result = $this->app['orm']->getRepository('Pantarei\OAuth2\Entity\AccessTokens')->findOneBy(array(
      'access_token' => 'eeb5aa92bbb4b56373b9e0d00bc02d93',
    ));
    $this->assertEquals('eeb5aa92bbb4b56373b9e0d00bc02d93', $result->getAccessToken());
  }

  public function testFindAll()
  {
    $result = $this->app['orm']->getRepository('Pantarei\OAuth2\Entity\Clients')->findAll();
    $this->assertEquals(3, count($result));
  }

  public function testPersist()
  {
    $data = new Scopes();
    $data->setScope('demoscope4');
    $this->app['orm']->persist($data);
    $this->app['orm']->flush();

    $result = $this->app['orm']->getRepository('Pantarei\OAuth2\Entity\Scopes')->findAll();
    $this->assertEquals(4, count($result));
    $this->assertEquals('demoscope4', $result[3]->getScope());
  }

  public function testRemove()
  {
    $data = new Scopes();
    $data->setScope('demoscope4');
    $this->app['orm']->persist($data);
    $this->app['orm']->flush();

    $result = $this->app['orm']->getRepository('Pantarei\OAuth2\Entity\Scopes')->findAll();
    $this->assertEquals(4, count($result));
    $this->assertEquals('demoscope4', $result[3]->getScope());

    $this->app['orm']->remove($data);
    $this->app['orm']->flush();
    $result = $this->app['orm']->getRepository('Pantarei\OAuth2\Entity\Scopes')->findAll();
    $this->assertEquals(3, count($result));
  }
}
