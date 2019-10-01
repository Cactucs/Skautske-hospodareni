<?php

declare(strict_types=1);

namespace Model\Cashbook\ReadModel\Queries;

use Model\Cashbook\ReadModel\QueryHandlers\CampParticipantListQueryHandler;
use Model\Event\SkautisCampId;

/**
 * @see CampParticipantListQueryHandler
 */
final class CampParticipantListQuery
{
    /** @var SkautisCampId */
    private $eventId;

    public function __construct(SkautisCampId $id)
    {
        $this->eventId = $id;
    }

    public function getEventId() : SkautisCampId
    {
        return $this->eventId;
    }
}
