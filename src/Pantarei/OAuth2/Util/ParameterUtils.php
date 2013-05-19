<?php

/**
 * This file is part of the pantarei/oauth2 package.
 *
 * (c) Wong Hoi Sing Edison <hswong3i@pantarei-design.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Pantarei\OAuth2\Util;

use Pantarei\OAuth2\Exception\InvalidGrantException;
use Pantarei\OAuth2\Exception\InvalidRequestException;
use Pantarei\OAuth2\Exception\InvalidScopeException;
use Pantarei\OAuth2\Exception\UnauthorizedClientException;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * A simple Doctrine ORM service provider for OAuth2.
 *
 * @author Wong Hoi Sing Edison <hswong3i@pantarei-design.com>
 */
abstract class ParameterUtils
{
  private static $syntax;

  private static $regexp;

  private static $definition;

  private static function initializer()
  {
    static $initialized = FALSE;

    if ($initialized) {
      return;
    }
    $initialized = TRUE;

    self::$syntax = array(
      'VSCHAR'            => '[\x20-\x7E]',
      'NQCHAR'            => '[\x21\x22-\x5B\x5D-\x7E]',
      'NQSCHAR'           => '[\x20-\x21\x23-\x5B\x5D-\x7E]',
      'UNICODECHARNOCRLF' => '[\x09\x20-\x7E\x80-\x{D7FF}\x{E000}-\x{FFFD}\x{10000}-\x{10FFFF}]',
    );

    self::$regexp = array(
      'client_id'         => '/^(' . self::$syntax['VSCHAR'] . '*)$/',
      'client_secret'     => '/^(' . self::$syntax['VSCHAR'] . '*)$/',
      'response_type'     => '/^([a-z0-9\_]+)$/',
      'scope'             => '/^(' . self::$syntax['NQCHAR'] . '+(?:\s*' . self::$syntax['NQCHAR'] . '+(?R)*)*)$/',
      'state'             => '/^(' . self::$syntax['VSCHAR'] . '+)$/',
      'error'             => '/^(' . self::$syntax['NQCHAR'] . '+)$/',
      'error_description' => '/^(' . self::$syntax['NQCHAR'] . '+)$/',
      'grant_type'        => '/^([a-z0-9\_\-\.]+)$/',
      'code'              => '/^(' . self::$syntax['VSCHAR'] . '+)$/',
      'access_token'      => '/^(' . self::$syntax['VSCHAR'] . '+)$/',
      'token_type'        => '/^([a-z0-9\_\-\.]+)$/',
      'expires_in'        => '/^([0-9]+)$/',
      'username'          => '/^(' . self::$syntax['UNICODECHARNOCRLF'] . '*)$/u',
      'password'          => '/^(' . self::$syntax['UNICODECHARNOCRLF'] . '*)$/u',
      'refresh_token'     => '/^(' . self::$syntax['VSCHAR'] . '+)$/',
    );

    self::$definition = array(
      'client_id'         => array('filter' => FILTER_VALIDATE_REGEXP, 'flags' => FILTER_REQUIRE_SCALAR, 'options' => array('regexp' => self::$regexp['client_id'])),
      'client_secret'     => array('filter' => FILTER_VALIDATE_REGEXP, 'flags' => FILTER_REQUIRE_SCALAR, 'options' => array('regexp' => self::$regexp['client_secret'])),
      'response_type'     => array('filter' => FILTER_VALIDATE_REGEXP, 'flags' => FILTER_REQUIRE_SCALAR, 'options' => array('regexp' => self::$regexp['response_type'])),
      'scope'             => array('filter' => FILTER_VALIDATE_REGEXP, 'flags' => FILTER_REQUIRE_SCALAR, 'options' => array('regexp' => self::$regexp['scope'])),
      'state'             => array('filter' => FILTER_VALIDATE_REGEXP, 'flags' => FILTER_REQUIRE_SCALAR, 'options' => array('regexp' => self::$regexp['state'])),
      'redirect_uri'      => array('filter' => FILTER_SANITIZE_URL),
      'error'             => array('filter' => FILTER_VALIDATE_REGEXP, 'flags' => FILTER_REQUIRE_SCALAR, 'options' => array('regexp' => self::$regexp['error'])),
      'error_description' => array('filter' => FILTER_VALIDATE_REGEXP, 'flags' => FILTER_REQUIRE_SCALAR, 'options' => array('regexp' => self::$regexp['error_description'])),
      'error_uri'         => array('filter' => FILTER_SANITIZE_URL),
      'grant_type'        => array('filter' => FILTER_VALIDATE_REGEXP, 'flags' => FILTER_REQUIRE_SCALAR, 'options' => array('regexp' => self::$regexp['grant_type'])),
      'code'              => array('filter' => FILTER_VALIDATE_REGEXP, 'flags' => FILTER_REQUIRE_SCALAR, 'options' => array('regexp' => self::$regexp['code'])),
      'access_token'      => array('filter' => FILTER_VALIDATE_REGEXP, 'flags' => FILTER_REQUIRE_SCALAR, 'options' => array('regexp' => self::$regexp['access_token'])),
      'token_type'        => array('filter' => FILTER_VALIDATE_REGEXP, 'flags' => FILTER_REQUIRE_SCALAR, 'options' => array('regexp' => self::$regexp['token_type'])),
      'expires_in'        => array('filter' => FILTER_VALIDATE_INT),
      'username'          => array('filter' => FILTER_VALIDATE_REGEXP, 'flags' => FILTER_REQUIRE_SCALAR, 'options' => array('regexp' => self::$regexp['username'])),
      'password'          => array('filter' => FILTER_VALIDATE_REGEXP, 'flags' => FILTER_REQUIRE_SCALAR, 'options' => array('regexp' => self::$regexp['password'])),
      'refresh_token'     => array('filter' => FILTER_VALIDATE_REGEXP, 'flags' => FILTER_REQUIRE_SCALAR, 'options' => array('regexp' => self::$regexp['refresh_token'])),
    );
  }

  public static function filter($query, $params = NULL)
  {
    self::initializer();

    $filtered_query = array_filter(filter_var_array($query, self::$definition));

    // Return entire result set, or only specific key(s).
    if ($params != NULL) {
      return array_intersect_key($filtered_query, array_flip($params));
    }
    return $filtered_query;
  }

  public static function checkClientId(Request $request, Application $app, $method = 'GET')
  {
    // Try to fetch client_id from HTTP basic auth, if no client_id from
    // incoming query.
    switch ($method) {
      case 'GET':
        $client_id = $request->query->get('client_id');
        break;
      case 'POST':
        $client_id = $request->getUser() ? $request->getUser() : $request->request->get('client_id');
        break;
    }

    // Check client_id with database record.
    $result = $app['oauth2.orm']->getRepository('Pantarei\OAuth2\Entity\Clients')->findOneBy(array(
      'client_id' => $client_id,
    ));
    if ($result === NULL) {
      throw new UnauthorizedClientException();
    }

    return $client_id;
  }

  public static function checkScope(Request $request, Application $app, $method = 'GET')
  {
    switch ($method) {
      case 'GET':
        $scopes = $request->query->get('scope');
        break;
      case 'POST':
        $scopes = $request->request->get('scope');
        break;
    }

    // Check scope with database record.
    foreach (preg_split('/\s+/', $scopes) as $scope) {
      $result = $app['oauth2.orm']->getRepository('Pantarei\OAuth2\Entity\Scopes')->findOneBy(array(
        'scope' => $scope,
      ));
      if ($result === NULL) {
        throw new InvalidScopeException();
      }
    }

    return $scopes;
  }

  public static function checkRedirectUri(Request $request, Application $app, $method = 'GET')
  {
    switch ($method) {
      case 'GET':
        $redirect_uri = $request->query->get('redirect_uri');
        $client_id = $request->query->get('client_id');
        break;
      case 'POST':
        $redirect_uri = $request->request->get('redirect_uri');
        $client_id = $request->getUser() ? $request->getUser() : $request->request->get('client_id');
        break;
    }

    // redirect_uri is not required if already established via other channels,
    // check an existing redirect URI against the one supplied.
    $stored = NULL;
    $result = $app['oauth2.orm']->getRepository('Pantarei\OAuth2\Entity\Clients')->findOneBy(array(
      'client_id' => $client_id,
    ));
    if ($result !== NULL && $result->getRedirectUri()) {
      $stored = $result->getRedirectUri();
    }

    // At least one of: existing redirect URI or input redirect URI must be
    // specified.
    if (!$stored && !$redirect_uri) {
      throw new InvalidRequestException();
    }

    // If there's an existing uri and one from input, verify that they match.
    if ($stored && $redirect_uri) {
      // Ensure that the input uri starts with the stored uri.
      if (strcasecmp(substr($redirect_uri, 0, strlen($stored)), $stored) !== 0) {
        throw new InvalidRequestException();
      }
    }

    return $redirect_uri ? $redirect_uri : $stored;
  }

  public static function checkCode(Request $request, Application $app)
  {
    $code = $request->request->get('code');
    $client_id = $request->getUser() ? $request->getUser() : $request->request->get('client_id');

    // Check code with database record.
    $result = $app['oauth2.orm']->getRepository('Pantarei\OAuth2\Entity\Codes')->findOneBy(array(
      'client_id' => $client_id,
      'code' => $code,
    ));
    if ($result === NULL) {
      throw new InvalidGrantException();
    }
    elseif ($result->getExpires() < time()) {
      throw new InvalidGrantException();
    }

    return $code;
  }

  public static function checkUsername(Request $request, Application $app)
  {
    $username = $request->request->get('username');

    // Check username with database record.
    $result = $app['oauth2.orm']->getRepository('Pantarei\OAuth2\Entity\Users')->findOneBy(array(
      'username' => $username,
    ));
    if ($result === NULL) {
      throw new InvalidGrantException();
    }

    return $username;
  }

  public static function checkPassword(Request $request, Application $app)
  {
    $username = $request->request->get('username');
    $password = $request->request->get('password');

    // Check username with database record.
    $result = $app['oauth2.orm']->getRepository('Pantarei\OAuth2\Entity\Users')->findOneBy(array(
      'username' => $username,
      'password' => $password,
    ));
    if ($result === NULL) {
      throw new InvalidGrantException();
    }

    return $password;
  }

  public static function checkRefreshToken(Request $request, Application $app)
  {
    $refresh_token = $request->request->get('refresh_token');
    $client_id = $request->getUser() ? $request->getUser() : $request->request->get('client_id');

    // Check refresh_token with database record.
    $result = $app['oauth2.orm']->getRepository('Pantarei\OAuth2\Entity\RefreshTokens')->findOneBy(array(
      'client_id' => $client_id,
      'refresh_token' => $refresh_token,
    ));
    if ($result === NULL) {
      throw new InvalidGrantException();
    }
    elseif ($result->getExpires() < time()) {
      throw new InvalidRequestException();
    }

    return $refresh_token;
  }
}