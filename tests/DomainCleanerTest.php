<?php

use App\Services\LicenseService;

elms_test('clean_domain_basic', function (): void {
    assert(LicenseService::cleanDomain('example.com') === 'example.com');
});

elms_test('clean_domain_with_https', function (): void {
    assert(LicenseService::cleanDomain('https://my.hostnibo.com') === 'my.hostnibo.com');
    assert(LicenseService::cleanDomain('http://my.hostnibo.com/') === 'my.hostnibo.com');
});

elms_test('clean_domain_with_www', function (): void {
    assert(LicenseService::cleanDomain('www.example.com') === 'example.com');
    assert(LicenseService::cleanDomain('https://www.example.com/sub/path') === 'example.com');
});

elms_test('clean_domain_with_port_and_path', function (): void {
    assert(LicenseService::cleanDomain('https://sub.domain.com:8443/whmcs/clientarea.php') === 'sub.domain.com');
});

elms_test('clean_domain_wildcard_and_empty', function (): void {
    assert(LicenseService::cleanDomain('*') === null);
    assert(LicenseService::cleanDomain('') === null);
    assert(LicenseService::cleanDomain(null) === null);
});
