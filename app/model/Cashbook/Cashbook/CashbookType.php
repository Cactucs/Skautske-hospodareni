<?php

declare(strict_types=1);

namespace Model\Cashbook\Cashbook;

use Consistence\Enum\Enum;
use Model\Cashbook\ObjectType;

use function array_filter;
use function array_keys;
use function array_map;
use function count;
use function in_array;

class CashbookType extends Enum
{
    public const EVENT         = 'general';
    public const OFFICIAL_UNIT = 'official_unit';
    public const TROOP         = 'troop';
    public const CAMP          = 'camp';
    public const EDUCATION     = 'education';

    private const TRANSFER_FROM_CATEGORY_IDS = [
        self::OFFICIAL_UNIT => 9, // "Převod z pokladny střediska"
        self::TROOP => 13, // "Převod z odd. pokladny"
        self::EVENT => 15, // "Převod z akce"
        self::CAMP => 15, // "Převod z akce"
        self::EDUCATION => 15, // "Převod z akce"
    ];

    private const TRANSFER_TO_CATEGORY_IDS = [
        self::OFFICIAL_UNIT => 7, // "Převod do stř. pokladny"
        self::TROOP => 14, // "Převod do odd. pokladny"
        self::EVENT => 16, // "Převod do akce"
        self::CAMP => 16, // "Převod do akce"
        self::EDUCATION => 16, // "Převod do akce"
    ];

    public function getSkautisObjectType(): ObjectType
    {
        if (in_array($this->getValue(), [self::OFFICIAL_UNIT, self::TROOP], true)) {
            return ObjectType::get(ObjectType::UNIT);
        }

        return ObjectType::get($this->getValue());
    }

    public function getTransferFromCategoryId(): int
    {
        return self::TRANSFER_FROM_CATEGORY_IDS[$this->getValue()];
    }

    public function getTransferToCategoryId(): int
    {
        return self::TRANSFER_TO_CATEGORY_IDS[$this->getValue()];
    }

    /** @return CashbookType[] */
    public static function getInverseCashbookTypes(int $chitCategoryId): array
    {
        foreach ([self::TRANSFER_FROM_CATEGORY_IDS, self::TRANSFER_TO_CATEGORY_IDS] as $categoryIdsByType) {
            $types = array_filter(
                array_keys($categoryIdsByType),
                fn (string $cashbookType) => $categoryIdsByType[$cashbookType] === $chitCategoryId
            );

            if (count($types) !== 0) {
                return array_map(
                    function (string $type): self {
                        return CashbookType::get($type);
                    },
                    $types,
                );
            }
        }

        return [];
    }

    public function isUnit(): bool
    {
        return in_array($this->getValue(), [self::OFFICIAL_UNIT, self::TROOP], true);
    }

    public function __toString(): string
    {
        return (string) $this->getValue();
    }
}
