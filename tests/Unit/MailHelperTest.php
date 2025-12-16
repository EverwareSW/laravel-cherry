<?php

use Illuminate\Mail\Mailables\Address;

test('that true is true', function () {
    \HMail::prettifyAddress(new Address('ken@everware.nl', 'Ken'));
});