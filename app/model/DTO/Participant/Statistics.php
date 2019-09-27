<?php

declare(strict_types=1);

namespace Model\DTO\Participant;

class Statistics
{
    /** @var int */
    private $personDays;

    /** @var int */
    private $count;

    public function __construct(int $personDays, int $count)
    {
        $this->personDays = $personDays;
        $this->count      = $count;
    }

    public function getPersonDays() : int
    {
        return $this->personDays;
    }

    public function getCount() : int
    {
        return $this->count;
    }
}
