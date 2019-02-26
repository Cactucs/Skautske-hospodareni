<?php

declare(strict_types=1);

namespace Model\Travel\Command;

use Cake\Chronos\Date;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Nette\SmartObject;

/**
 * @ORM\Embeddable()
 * @property-read DateTimeImmutable $date
 * @property-read string $transportType
 * @property-read string $startPlace
 * @property-read string $endPlace
 */
class TravelDetails
{
    use SmartObject;

    /**
     * @var DateTimeImmutable
     * @ORM\Column(type="datetime_immutable", name="start_date")
     */
    private $date;

    /**
     * @var string
     * @ORM\Column(type="string", name="type")
     */
    private $transportType;

    /**
     * @var string
     * @ORM\Column(type="string")
     */
    private $startPlace;

    /**
     * @var string
     * @ORM\Column(type="string")
     */
    private $endPlace;

    public function __construct(DateTimeImmutable $date, string $transportType, string $startPlace, string $endPlace)
    {
        $this->date          = $date;
        $this->transportType = $transportType;
        $this->startPlace    = $startPlace;
        $this->endPlace      = $endPlace;
    }

    public function getDate() : Date
    {
        return Date::instance($this->date);
    }

    public function getTransportType() : string
    {
        return $this->transportType;
    }

    public function getStartPlace() : string
    {
        return $this->startPlace;
    }

    public function getEndPlace() : string
    {
        return $this->endPlace;
    }
}
