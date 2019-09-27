<?php

declare(strict_types=1);

namespace Model\Skautis;

use Model\DTO\Event\StatisticsItem;
use Model\Event\Event;
use Model\Event\EventNotFound;
use Model\Event\Repositories\IEventRepository;
use Model\Event\SkautisEventId;
use Model\Skautis\Factory\EventFactory;
use Skautis\Wsdl\PermissionException;
use Skautis\Wsdl\WebServiceInterface;
use stdClass;
use function array_column;
use function count;
use function max;

final class EventRepository implements IEventRepository
{
    /** @var WebServiceInterface */
    private $webService;

    /** @var string */
    private $skautisType = 'eventGeneral';

    /** @var EventFactory */
    private $eventFactory;

    public function __construct(WebServiceInterface $webService, EventFactory $eventFactory)
    {
        $this->webService   = $webService;
        $this->eventFactory = $eventFactory;
    }

    public function find(SkautisEventId $id) : Event
    {
        try {
            $skautisEvent = $this->webService->EventGeneralDetail(['ID' => $id->toInt()]);

            return $this->createEvent($skautisEvent);
        } catch (PermissionException $exc) {
            throw new EventNotFound($exc->getMessage(), $exc->getCode(), $exc);
        }
    }

    public function open(Event $event) : void
    {
        $this->webService->EventGeneralUpdateOpen(['ID' => $event->getId()->toInt()], $this->skautisType);
    }

    public function close(Event $event) : void
    {
        $this->webService->EventGeneralUpdateClose(['ID' => $event->getId()->toInt()], $this->skautisType);
    }

    public function update(Event $event) : void
    {
        $this->webService->eventGeneralUpdate([
            'ID' => $event->getId()->toInt(),
            'Location' => $event->getLocation(),
            'Note' => $event->getNote(),
            'ID_EventGeneralScope' => $event->getScopeId(),
            'ID_EventGeneralType' => $event->getTypeId(),
            'ID_Unit' => $event->getUnitId()->toInt(),
            'DisplayName' => $event->getDisplayName(),
            'StartDate' => $event->getStartDate()->format('Y-m-d'),
            'EndDate' => $event->getEndDate()->format('Y-m-d'),
        ], 'eventGeneral');
    }

    public function getNewestEventId() : ?int
    {
        $events = $this->webService->eventGeneralAll(['IsRelation' => true]);

        $ids = array_column($events, 'ID');

        if (count($ids) === 0) {
            return null;
        }

        return max($ids);
    }

    /**
     * @return StatisticsItem[]
     */
    public function getStatistics(SkautisEventId $eventId) : array
    {
        $skautisData = $this->webService->EventStatisticAllEventGeneral(['ID_EventGeneral' => $eventId->toInt()]);

        $result = [];

        foreach ($skautisData as $row) {
            $result[$row->ID_ParticipantCategory] = new StatisticsItem($row->ParticipantCategory, $row->Count);
        }

        return $result;
    }

    private function createEvent(stdClass $skautisEvent) : Event
    {
        return $this->eventFactory->create($skautisEvent);
    }
}
