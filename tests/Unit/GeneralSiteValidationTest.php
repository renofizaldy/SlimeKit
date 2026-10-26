<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Helpers\General;
use App\Lib\Valkey;

class GeneralSiteValidationTest extends TestCase
{
  private General $general;
  private ?string $originalAllowedSites;

  protected function setUp(): void
  {
    parent::setUp();
    $mockValkey = $this->createMock(Valkey::class);
    $this->general = new General($mockValkey);
    $this->originalAllowedSites = $_ENV['ALLOWED_SITES'] ?? null;
  }

  protected function tearDown(): void
  {
    if ($this->originalAllowedSites !== null) {
      $_ENV['ALLOWED_SITES'] = $this->originalAllowedSites;
    } else {
      unset($_ENV['ALLOWED_SITES']);
    }
    parent::tearDown();
  }

  public function test_get_allowed_sites_returns_empty_array_when_not_set()
  {
    $_ENV['ALLOWED_SITES'] = '';
    $this->assertSame([], General::getAllowedSites());
  }

  public function test_get_allowed_sites_parses_comma_separated_sites()
  {
    $_ENV['ALLOWED_SITES'] = 'blokbaca, lolitamakeup, other-site';
    $this->assertSame(['blokbaca', 'lolitamakeup', 'other-site'], General::getAllowedSites());
  }

  public function test_is_valid_site_with_allowed_whitelist()
  {
    $_ENV['ALLOWED_SITES'] = 'blokbaca,lolitamakeup';

    $this->assertTrue(General::isValidSite('blokbaca'));
    $this->assertTrue(General::isValidSite('lolitamakeup'));
    $this->assertFalse(General::isValidSite('unauthorized-site'));
    $this->assertFalse(General::isValidSite('blok')); // prefix collision test
  }

  public function test_is_valid_site_rejects_dangerous_characters()
  {
    $_ENV['ALLOWED_SITES'] = '';

    $this->assertFalse(General::isValidSite('invalid:colon*'));
    $this->assertFalse(General::isValidSite('site:name'));
    $this->assertFalse(General::isValidSite('site*wildcard'));
    $this->assertFalse(General::isValidSite('site with spaces'));
    $this->assertFalse(General::isValidSite('site/path'));
    $this->assertFalse(General::isValidSite('site;injection'));
    $this->assertFalse(General::isValidSite('site?query=1'));
  }

  public function test_is_valid_site_rejects_empty_or_null()
  {
    $_ENV['ALLOWED_SITES'] = '';

    $this->assertFalse(General::isValidSite(null));
    $this->assertFalse(General::isValidSite(''));
    $this->assertFalse(General::isValidSite('   '));
  }

  public function test_is_valid_site_allows_safe_dummy_sites_when_whitelist_empty()
  {
    $_ENV['ALLOWED_SITES'] = '';

    $this->assertTrue(General::isValidSite('site1'));
    $this->assertTrue(General::isValidSite('slimego'));
    $this->assertTrue(General::isValidSite('example.com'));
    $this->assertTrue(General::isValidSite('site-name_123.id'));
  }

  public function test_instance_method_call_works_identically()
  {
    $_ENV['ALLOWED_SITES'] = 'blokbaca,lolitamakeup';

    $this->assertTrue($this->general->isValidSite('blokbaca'));
    $this->assertFalse($this->general->isValidSite('invalid:colon*'));
  }
}
