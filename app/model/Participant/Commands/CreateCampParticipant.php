<?php

declare(strict_types=1);

namespace Model\Cashbook\Commands\Cashbook;

use Model\DTO\Participant\ParticipantCreation;
use Model\Event\SkautisCampId;

/**
 * @see CreateEventParticipantHandler
 */
final class CreateCampParticipant
{
    /** @var SkautisCampId */
    private $campId;

    /** @var ParticipantCreation */
    private $participant;

    public function __construct(SkautisCampId $campId, ParticipantCreation $participant)
    {
        $this->campId      = $campId;
        $this->participant = $participant;
    }

    public function getCampId() : SkautisCampId
    {
        return $this->campId;
    }

    public function getParticipant() : ParticipantCreation
    {
        return $this->participant;
    }
}
