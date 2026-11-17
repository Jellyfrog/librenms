<?php

use LibreNMS\Authentication\ActiveDirectoryCommon;

uses(LibreNMS\Tests\TestCase::class);

class DummyAdAuthorizer
{
    use ActiveDirectoryCommon;

    public function testUserFilter(string $username): string
    {
        return $this->userFilter($username);
    }

    public function testGroupFilter(string $groupname): string
    {
        return $this->groupFilter($groupname);
    }

    protected function getConnection(): ?\LDAP\Connection
    {
        return null;
    }
}

beforeEach(function (): void {
    if (! function_exists('ldap_escape')) {
        $this->markTestSkipped('LDAP extension is not available.');
    }
});

test('user filter escapes wildcards and parentheses', function (): void {
    $dummy = new DummyAdAuthorizer();

    $wildcardFilter = $dummy->testUserFilter('*');
    expect($wildcardFilter)->not->toContain('(samaccountname=*)');
    expect($wildcardFilter)->toContain('\2a');

    $injectedFilter = $dummy->testUserFilter('admin)(|(uid=*');
    expect($injectedFilter)->not->toContain('admin)(|(uid=*');
    expect($injectedFilter)->toContain('\28');
    expect($injectedFilter)->toContain('\29');
});

test('group filter escapes special chars', function (): void {
    $dummy = new DummyAdAuthorizer();

    $filter = $dummy->testGroupFilter('Domain Admins)(member=*');
    expect($filter)->not->toContain('(samaccountname=Domain Admins)(member=*)');
});
