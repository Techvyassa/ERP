<?php

use Tests\TestCase;

test('example pest test passes', function () {
    expect(true)->toBeTrue();
});

test('can perform basic assertions', function () {
    $value = 'Laravel';
    
    expect($value)
        ->toBeString()
        ->toBe('Laravel')
        ->not->toBeEmpty();
});
