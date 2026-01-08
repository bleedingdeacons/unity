<?php

declare(strict_types=1);

namespace Unity\Groups;

use Unity\Groups\Interfaces\GroupInterface;

/**
 * Concrete Group class
 */
class Group implements GroupInterface
{
    private int $id;
    private string $title;
    private string $email;
    private array $meetingIds;
    private string $link;

    /**
     * Group constructor
     * 
     * @param int    $id          Post ID
     * @param string $title       Group title
     * @param string $email       Email address
     * @param array  $meetingIds  Associated meeting IDs
     * @param string $link        Link URL
     */
    public function __construct(
        int $id = 0,
        string $title = '',
        string $email = '',
        array $meetingIds = [],
        string $link = ''
    ) {
        $this->id = $id;
        $this->title = $title;
        $this->email = $email;
        $this->meetingIds = $meetingIds;
        $this->link = $link;
    }

    /**
     * {@inheritdoc}
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * {@inheritdoc}
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * {@inheritdoc}
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * {@inheritdoc}
     */
    public function getMeetingIds(): array
    {
        return $this->meetingIds;
    }

    /**
     * {@inheritdoc}
     */
    public function getLink(): string
    {
        return $this->link;
    }

    /**
     * {@inheritdoc}
     */
    public function isValid(): bool
    {
        return $this->id > 0
            && !empty($this->title)
            && count($this->meetingIds) >= 1;
    }
}
