<?php

/**
 * This file is part of the pantarei/oauth2 package.
 *
 * (c) Wong Hoi Sing Edison <hswong3i@pantarei-design.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Pantarei\OAuth2\GrantType;

use Pantarei\OAuth2\Util\ScopeUtils;

/**
 * Client credentials grant type implementation.
 *
 * @see http://tools.ietf.org/html/rfc6749#section-4.4.2
 *
 * @author Wong Hoi Sing Edison <hswong3i@pantarei-design.com>
 */
class ClientCredentialsGrantType implements GrantTypeInterface
{
  /**
   * REQUIRED. Value MUST be set to "client_credentials".
   *
   * @see http://tools.ietf.org/html/rfc6749#section-4.4.2
   */
  private $grantType = 'client_credentials';

  /**
   * OPTIONAL. The scope of the access request as described by
   * Section 3.3.
   *
   * @see http://tools.ietf.org/html/rfc6749#section-4.4.2
   */
  private $scope = '';

  public function getGrantType()
  {
    return $this->grantType;
  }

  public function setScope($scope)
  {
    $this->scope = $scope;
    return $this;
  }

  public function getScope()
  {
    return $this->scope;
  }

  public function __construct($query, $filtered_query)
  {
    // Validate and set scope.
    if (ScopeUtils::check($query, $filtered_query)) {
      $this->setScope($query['scope']);
    }
  }
}