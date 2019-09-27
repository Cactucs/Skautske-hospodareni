<?php

declare(strict_types=1);

namespace Model\Cashbook\Commands\Cashbook;

use Model\DTO\Participant\ParticipantCreation;
use Model\Event\SkautisEventId;

/**
 * @see CreateEventParticipantHandler
 */
final class CreateEventParticipant
{
    /** @var SkautisEventId */
    private $eventId;

    /** @var ParticipantCreation */
    private $participant;

    public function __construct(SkautisEventId $eventId, ParticipantCreation $participant)
    {
        $this->eventId     = $eventId;
        $this->participant = $participant;
    }

    public function getEventId() : SkautisEventId
    {
        return $this->eventId;
    }

    public function getParticipant() : ParticipantCreation
    {
        return $this->participant;
    }
}
