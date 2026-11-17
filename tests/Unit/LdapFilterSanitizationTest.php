<?php

uses(\LibreNMS\Tests\TestCase::class);
uses(\LibreNMS\Authentication\ActiveDirectoryCommon::class);
use \LibreNMS\Tests\Unit\DummyAdAuthorizer;
use \LibreNMS\Tests\TestCase;


test('user filter', function (string $username) {
    return $this->userFilter($username);
});

test('group filter', function (string $groupname) {
    return $this->groupFilter($groupname);
});

function getConnection(): ?\LDAP\Connection
{
    return null;
}

class LdapFilterSanitizationTest extends TestCase
{
    beforeEach(function () {
        if (! function_exists('ldap_escape')) {
            $this->markTestSkipped('LDAP extension is not available.');
        }
    });

    test('user filter escapes wildcards and parentheses', function () {
        $dummy = new DummyAdAuthorizer();

        $wildcardFilter = $dummy->testUserFilter('*');
        $this->assertStringNotContainsString('(samaccountname=*)', $wildcardFilter);
        $this->assertStringContainsString('\2a', $wildcardFilter);

        $injectedFilter = $dummy->testUserFilter('admin)(|(uid=*');
        $this->assertStringNotContainsString('admin)(|(uid=*', $injectedFilter);
        $this->assertStringContainsString('\28', $injectedFilter);
        $this->assertStringContainsString('\29', $injectedFilter);
    });

    test('group filter escapes special chars', function () {
        $dummy = new DummyAdAuthorizer();

        $filter = $dummy->testGroupFilter('Domain Admins)(member=*');
        $this->assertStringNotContainsString('(samaccountname=Domain Admins)(member=*)', $filter);
    });
}