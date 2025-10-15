<?php

test('basic assertion works', function (): void {
    expect(true)->toBeTrue();
});

test('pest custom expectation works', function (): void {
    expect(1)->toBeOne();
});

test('arithmetic operations', function (): void {
    expect(2 + 2)->toBe(4);
    expect(10 * 5)->toBe(50);
    expect(100 / 4)->toBe(25);
});

test('string operations', function (): void {
    $string = 'Hello, Pest!';

    expect($string)
        ->toBeString()
        ->toContain('Pest')
        ->toHaveLength(12);
});

test('array operations', function (): void {
    $array = ['apple', 'banana', 'orange'];

    expect($array)
        ->toBeArray()
        ->toHaveCount(3)
        ->toContain('banana')
        ->not->toContain('grape');
});

test('using datasets', function ($input, $expected): void {
    expect($input * 2)->toBe($expected);
})->with([
    [1, 2],
    [2, 4],
    [5, 10],
    [10, 20],
]);
