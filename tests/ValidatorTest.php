<?php

use App\Core\Validator;

elms_test('validator_required_missing', function (): void {
    $v = Validator::make([], ['name' => 'required']);
    assert_true($v->fails());
});

elms_test('validator_required_present', function (): void {
    $v = Validator::make(['name' => 'bob'], ['name' => 'required']);
    assert_false($v->fails());
});

elms_test('validator_email_valid', function (): void {
    $v = Validator::make(['e' => 'a@b.com'], ['e' => 'email']);
    assert_false($v->fails());
});

elms_test('validator_email_invalid', function (): void {
    $v = Validator::make(['e' => 'not-an-email'], ['e' => 'email']);
    assert_true($v->fails());
});

elms_test('validator_int_valid', function (): void {
    $v = Validator::make(['n' => '42'], ['n' => 'int']);
    assert_false($v->fails());
});

elms_test('validator_int_invalid', function (): void {
    $v = Validator::make(['n' => 'abc'], ['n' => 'int']);
    assert_true($v->fails());
});

elms_test('validator_in_allowed', function (): void {
    $v = Validator::make(['s' => 'active'], ['s' => 'in:active,suspended']);
    assert_false($v->fails());
});

elms_test('validator_in_disallowed', function (): void {
    $v = Validator::make(['s' => 'deleted'], ['s' => 'in:active,suspended']);
    assert_true($v->fails());
});

elms_test('validator_domain_valid', function (): void {
    $v = Validator::make(['d' => 'example.com'], ['d' => 'domain']);
    assert_false($v->fails());
});

elms_test('validator_domain_with_scheme', function (): void {
    $v = Validator::make(['d' => 'https://example.com/path'], ['d' => 'domain']);
    assert_false($v->fails());
});

elms_test('validator_domain_invalid', function (): void {
    $v = Validator::make(['d' => 'not a domain!'], ['d' => 'domain']);
    assert_true($v->fails());
});

elms_test('validator_min_length', function (): void {
    assert_true(Validator::make(['s' => 'abc'], ['s' => 'min:5'])->fails());
    assert_false(Validator::make(['s' => 'abcdef'], ['s' => 'min:5'])->fails());
});
