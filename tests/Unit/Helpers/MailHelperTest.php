<?php

use Everware\LaravelCherry\Tests\TestCase;
use Illuminate\Mail\Mailables\Address;

pest()->extends(TestCase::class);

test('prettifyAddress', function () {
    expect(\HMail::prettifyAddress('some string'))
        ->toBe('some string');

    expect(\HMail::prettifyAddress(new Address('richard@batsbak.nl', 'Richard Batsbak')))
        ->toBe('Richard Batsbak <richard@batsbak.nl>');

    expect(\HMail::prettifyAddress([
        new Address('richard@batsbak.nl', 'Richard Batsbak'),
        new Address('gerrie@vanboven.nl', 'Gerrie van Boven'),
    ]))
        ->toBe('Richard Batsbak <richard@batsbak.nl>, Gerrie van Boven <gerrie@vanboven.nl>');

    expect(\HMail::prettifyAddress([
        'address' => 'rikkert@biemans.nl',
    ]))
        ->toBe('rikkert@biemans.nl');

    expect(\HMail::prettifyAddress([
        'address' => 'rikkert@biemans.nl',
        'name' => 'Rikkert Biemans',
    ]))
        ->toBe('Rikkert Biemans <rikkert@biemans.nl>');
});