<?php

/*
 * This file is part of the pantarei/oauth2 package.
 *
 * (c) Wong Hoi Sing Edison <hswong3i@pantarei-design.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Pantarei\Oauth2\Test\TokenType;

use Pantarei\Oauth2\TokenType\MacTokenType;

/**
 * Test MAC token type functionality.
 *
 * @author Wong Hoi Sing Edison <hswong3i@pantarei-design.com>
 */
class MacTokenTypeTest extends \PHPUnit_Framework_TestCase
{
  public function testGetTokenType()
  {
    $grant_type = new MacTokenType();
    $this->assertEquals('mac', $grant_type->getTokenType());
  }
}