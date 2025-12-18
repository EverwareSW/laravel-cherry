<?php

namespace Everware\LaravelCherry\Helpers;

use Illuminate\Mail\Mailables\Address;

class MailHelper
{
    /**
     * Converts address(es) to a readable string.
     * Can be used in conjunction with e.g. {@see Mailable::$to}, $cc, $bcc & $from ({@see Mailable::to()}).
     *
     * @param array|string|Address|null $address
     * @return string|null
     */
    public static function prettifyAddress(Address|string|array|null $address): ?string
    {
        // Could also be empty array.
        if (empty($address)) {
            return null;
        }
        if (is_string($address)) {
            return $address;
        }
        if ($address instanceof Address) {
            $address = (array) $address;
        }
        if (isset($address[0])) {
            return join(', ', array_map([static::class, 'prettifyAddress'], $address));
        }

        /** {@see Mailable::setAddress()} called from {@see Mailable::to()} */
        $email = $address['address'] ?? $address['email']; // I'm not sure if we need ['email'], but doesn't hurt.
        return empty($address['name']) ? $email : "$address[name] <$email>";
    }
}