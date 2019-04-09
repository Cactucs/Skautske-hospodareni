<?php

declare(strict_types=1);

namespace Model\Cashbook;

use Consistence\Doctrine\Enum\EnumAnnotation;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Model\Cashbook\Cashbook\CashbookId;
use Model\Cashbook\Cashbook\CashbookType;
use Model\Cashbook\Cashbook\Chit;
use Model\Cashbook\Cashbook\ChitBody;
use Model\Cashbook\Cashbook\ChitNumber;
use Model\Cashbook\Cashbook\PaymentMethod;
use Model\Cashbook\Events\ChitWasAdded;
use Model\Cashbook\Events\ChitWasRemoved;
use Model\Cashbook\Events\ChitWasUpdated;
use Model\Common\Aggregate;
use Nette\Utils\Strings;
use function array_map;
use function max;
use function sprintf;

/**
 * @ORM\Entity()
 * @ORM\Table(name="ac_cashbook")
 */
class Cashbook extends Aggregate
{
    /**
     * @var CashbookId
     * @ORM\Id()
     * @ORM\Column(type="cashbook_id")
     */
    private $id;

    /**
     * @var CashbookType
     * @ORM\Column(type="string_enum")
     * @EnumAnnotation(class=CashbookType::class)
     */
    private $type;

    /**
     * @var string|NULL
     * @ORM\Column(type="string", nullable=true)
     */
    private $chitNumberPrefix;

    /**
     * @var ArrayCollection|Chit[]
     * @ORM\OneToMany(targetEntity=Chit::class, mappedBy="cashbook", cascade={"persist", "remove"}, orphanRemoval=true)
     */
    private $chits;

    /**
     * @var string
     * @ORM\Column(type="text")
     */
    private $note;

    public function __construct(CashbookId $id, CashbookType $type)
    {
        $this->id    = $id;
        $this->type  = $type;
        $this->chits = new ArrayCollection();
        $this->note  = '';
    }

    public function getId() : CashbookId
    {
        return $this->id;
    }

    public function getType() : CashbookType
    {
        return $this->type;
    }

    public function getChitNumberPrefix() : ?string
    {
        return $this->chitNumberPrefix;
    }

    public function getNote() : string
    {
        return $this->note;
    }

    public function updateChitNumberPrefix(?string $chitNumberPrefix) : void
    {
        if ($chitNumberPrefix !== null && Strings::length($chitNumberPrefix) > 6) {
            throw new \InvalidArgumentException('Chit number prefix too long');
        }

        $this->chitNumberPrefix = $chitNumberPrefix;
    }

    public function updateNote(string $note) : void
    {
        $this->note = $note;
    }

    public function addChit(ChitBody $chitBody, ICategory $category, PaymentMethod $paymentMethod) : void
    {
        $this->chits[] = new Chit($this, $chitBody, $this->getChitCategory($category), $paymentMethod);
        $this->raise(new ChitWasAdded($this->id, $category->getId()));
    }

    /**
     * Adds inverse chit for chit in specified cashbook
     *
     * @throws InvalidCashbookTransfer
     */
    public function addInverseChit(Cashbook $cashbook, int $chitId) : void
    {
        $originalChit       = $cashbook->getChit($chitId);
        $originalCategoryId = $originalChit->getCategoryId();

        if ($this->type->getTransferToCategoryId() === $originalCategoryId) {
            // chit is transfer TO this cashbook
            $categoryId = $cashbook->type->getTransferFromCategoryId();
        } elseif ($this->type->getTransferFromCategoryId() === $originalCategoryId) {
            // chit is transfer FROM this cashbook
            $categoryId = $cashbook->type->getTransferToCategoryId();
        } else {
            throw new InvalidCashbookTransfer(
                sprintf("Can't create inverse chit to chit with category '%s'", $originalCategoryId)
            );
        }

        $category = new Cashbook\Category(
            $categoryId,
            $originalChit->getOperation()->getInverseOperation()
        );

        $newChitBody   = $originalChit->getBody()->withoutChitNumber();
        $this->chits[] = new Chit($this, $newChitBody, $category, $originalChit->getPaymentMethod());

        $this->raise(new ChitWasAdded($this->id, $categoryId));
    }

    /**
     * @throws ChitNotFound
     * @throws ChitLocked
     */
    public function updateChit(int $chitId, ChitBody $chitBody, ICategory $category, PaymentMethod $paymentMethod) : void
    {
        $chit          = $this->getChit($chitId);
        $oldCategoryId = $chit->getCategoryId();

        if ($chit->isLocked()) {
            throw new ChitLocked();
        }

        $chit->update($chitBody, $this->getChitCategory($category), $paymentMethod);

        $this->raise(new ChitWasUpdated($this->id, $oldCategoryId, $category->getId()));
    }

    /**
     * @return float[] Category totals indexed by category IDs
     */
    public function getCategoryTotals() : array
    {
        $totalByCategories = [];

        foreach ($this->chits as $chit) {
            $categoryId                     = $chit->getCategoryId();
            $totalByCategories[$categoryId] = ($totalByCategories[$categoryId] ?? 0) + $chit->getAmount()->getValue();
        }

        return $totalByCategories;
    }

    public function removeChit(int $chitId) : void
    {
        $chit = $this->getChit($chitId);

        if ($chit->isLocked()) {
            throw new ChitLocked();
        }

        $this->chits->removeElement($chit);
        $this->raise(new ChitWasRemoved($this->id, $chit->getPurpose()));
    }

    public function lockChit(int $chitId, int $userId) : void
    {
        $chit = $this->getChit($chitId);

        if ($chit->isLocked()) {
            return;
        }

        $chit->lock($userId);
    }

    public function unlockChit(int $chitId) : void
    {
        $chit = $this->getChit($chitId);

        if (! $chit->isLocked()) {
            return;
        }

        $chit->unlock();
    }

    public function lock(int $userId) : void
    {
        foreach ($this->chits as $chit) {
            if ($chit->isLocked()) {
                continue;
            }

            $chit->lock($userId);
        }
    }

    /**
     * @param int[] $chitIds
     * @throws ChitNotFound
     */
    public function copyChitsFrom(array $chitIds, Cashbook $sourceCashbook) : void
    {
        $chits = array_map(
            function (int $chitId) use ($sourceCashbook) : Chit {
                return $sourceCashbook->getChit($chitId);
            },
            $chitIds
        );

        foreach ($chits as $chit) {
            /** @var Chit $chit */
            $newChit = $this->type->equals($sourceCashbook->type) && ! $this->type->equalsValue(CashbookType::CAMP)
                ? $chit->copyToCashbook($this)
                : $chit->copyToCashbookWithUndefinedCategory($this);

            $this->chits->add($newChit);

            $this->raise(new ChitWasAdded($this->id, $newChit->getCategoryId()));
        }
    }

    /**
     * Only for Read model
     * @deprecated use Doctrine directly in read model
     * @return Chit[]
     */
    public function getChits() : array
    {
        return $this->chits
            ->map(
                function (Chit $c) : Chit {
                    // clone to avoid modification of cashbook
                    return clone $c;
                }
            )
            ->toArray();
    }

    public function clear() : void
    {
        $this->chits->clear();
    }

    /**
     * @throws ChitNotFound
     */
    private function getChit(int $id) : Chit
    {
        foreach ($this->chits as $chit) {
            if ($chit->getId() === $id) {
                return $chit;
            }
        }

        throw new ChitNotFound();
    }

    private function getChitCategory(ICategory $category) : Cashbook\Category
    {
        return new Cashbook\Category($category->getId(), $category->getOperationType());
    }

    /**
     * @throws MaxChitNumberNotFound
     * @throws NonNumericChitNumbers
     */
    private function getMaxChitNumber(PaymentMethod $paymentMethod) : int
    {
        if (! $this->hasOnlyNumericChitNumbers()) {
            throw new NonNumericChitNumbers();
        }
        $defaultMax = -1;
        $res        = $defaultMax;
        /** @var Chit $ch */
        foreach ($this->chits as $ch) {
            $number = $ch->getBody()->getNumber();
            if (! $ch->getPaymentMethod()->equals($paymentMethod) || $number === null || $number->containsLetter()) {
                continue;
            }

            $res = max($res, (int) $number->toString());
        }

        if ($res === $defaultMax) {
            throw new MaxChitNumberNotFound();
        }
        return $res;
    }

    public function hasOnlyNumericChitNumbers() : bool
    {
        /** @var Chit $ch */
        foreach ($this->chits as $ch) {
            $number = $ch->getBody()->getNumber();
            if ($number !== null && $number->containsLetter()) {
                return false;
            }
        }
        return true;
    }

    /**
     * @throws MaxChitNumberNotFound
     * @throws NonNumericChitNumbers
     */
    public function generateChitNumbers(PaymentMethod $paymentMethod) : void
    {
        $maxChitNumber = $this->getMaxChitNumber($paymentMethod);
        /** @var Chit $chit */
        foreach ($this->chits as $chit) {
            if (! $chit->getPaymentMethod()->equals($paymentMethod) || $chit->getBody()->getNumber() !== null || $chit->isLocked()) {
                continue;
            }
            $body    = $chit->getBody();
            $newBody = $body->withNewNumber(new ChitNumber((string) (++$maxChitNumber)));

            $chit->update($newBody, $chit->getCategory(), $chit->getPaymentMethod());
            $this->raise(new ChitWasUpdated($this->id, $chit->getCategoryId(), $chit->getCategoryId()));
        }
    }
}
