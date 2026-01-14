<?php

declare(strict_types=1);

namespace Unity\Contact;

use Unity\Contact\Interfaces\ContactFactoryInterface;
use Unity\Contact\Interfaces\ContactInterface;

/**
 * Class ContactFactory
 *
 * Factory for creating Contact objects.
 */
class ContactFactory implements ContactFactoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function createFromSource(array $source): ContactInterface
    {
        return new Contact(
            $source['name'] ?? '',
            $source['email'] ?? '',
            $source['phone'] ?? ''
        );
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $name = '', string $email = '', string $phone = ''): ContactInterface
    {
        return new Contact($name, $email, $phone);
    }
}
