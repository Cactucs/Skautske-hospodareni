<?php

namespace Model\DTO\Payment;

use DateTimeImmutable;
use Model\Payment\Payment\State;
use Model\Payment\Payment\Transaction;
use Nette\SmartObject;

/**
 * @property-read int $id
 * @property-read string $name
 * @property-read float $amount
 * @property-read string|NULL $email
 * @property-read DateTimeImmutable $dueDate
 * @property-read int|NULL $variableSymbol
 * @property-read int|NULL $constantSymbol
 * @property-read string $note
 * @property-read bool $closed
 * @property-read State $state
 * @property-read Transaction $transaction
 * @property-read DateTimeImmutable|NULL $closedAt
 * @property-read int|NULL $personId
 */
class Payment
{

    use SmartObject;

    /** @var int */
    private $id;

    /** @var string */
    private $name;

    /** @var float */
    private $amount;

    /** @var string|NULL */
    private $email;

    /** @var DateTimeImmutable */
    private $dueDate;

    /** @var int|NULL */
    private $variableSymbol;

    /** @var int|NULL */
    private $constantSymbol;

    /** @var string */
    private $note;

    /** @var bool */
    private $closed;

    /** @var State */
    private $state;

    /** @var Transaction|NULL */
    private $transaction;

    /** @var DateTimeImmutable|NULL */
    private $closedAt;

    /** @var int|NULL */
    private $personId;

    public function __construct(
        int $id,
        string $name,
        float $amount,
        ?string $email,
        DateTimeImmutable $dueDate,
        ?int $variableSymbol,
        ?int $constantSymbol,
        string $note,
        bool $closed,
        State $state,
        ?Transaction $transaction,
        ?DateTimeImmutable $closedAt,
        ?int $personId
    )
    {
        $this->id = $id;
        $this->name = $name;
        $this->amount = $amount;
        $this->email = $email;
        $this->dueDate = $dueDate;
        $this->variableSymbol = $variableSymbol;
        $this->constantSymbol = $constantSymbol;
        $this->note = $note;
        $this->closed = $closed;
        $this->state = $state;
        $this->transaction = $transaction;
        $this->closedAt = $closedAt;
        $this->personId = $personId;
    }

    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return float
     */
    public function getAmount(): float
    {
        return $this->amount;
    }

    /**
     * @return NULL|string
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * @return \DateTimeImmutable
     */
    public function getDueDate(): DateTimeImmutable
    {
        return $this->dueDate;
    }

    /**
     * @return int|NULL
     */
    public function getVariableSymbol(): ?int
    {
        return $this->variableSymbol;
    }

    /**
     * @return int|NULL
     */
    public function getConstantSymbol(): ?int
    {
        return $this->constantSymbol;
    }

    /**
     * @return string
     */
    public function getNote(): string
    {
        return $this->note;
    }

    public function isClosed(): bool
    {
        return $this->closed;
    }

    public function getState(): State
    {
        return $this->state;
    }

    public function getTransaction(): ?Transaction
    {
        return $this->transaction;
    }

    public function getClosedAt(): ?DateTimeImmutable
    {
        return $this->closedAt;
    }

    public function getPersonId()
    {
        return $this->personId;
    }

}
