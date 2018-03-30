<?php

namespace Model;

use Dibi\Row;
use eGen\MessageBus\Bus\CommandBus;
use eGen\MessageBus\Bus\QueryBus;
use Model\Cashbook\Commands\Cashbook\UpdateCampCategoryTotal;
use Model\Cashbook\ObjectType;
use Model\Cashbook\Operation;
use Model\Cashbook\ReadModel\Queries\CategoryPairsQuery;
use Model\Skautis\Mapper;
use Skautis\Skautis;

class ChitService extends MutableBaseService
{

    const CHIT_UNDEFINED_OUT = 8;
    const CHIT_UNDEFINED_IN = 12;
    const SKAUTIS_BUDGET_RESERVE = 15;

    /** @var Mapper */
    private $skautisMapper;

    /** @var ChitTable */
    private $table;

    /** @var CommandBus */
    private $commandBus;

    /** @var QueryBus */
    private $queryBus;

    public function __construct(
        string $name,
        ChitTable $table,
        Skautis $skautIS,
        Mapper $skautisMapper,
        CommandBus $commandBus,
        QueryBus $queryBus
    )
    {
        parent::__construct($name, $skautIS);
        $this->table = $table;
        $this->skautisMapper = $skautisMapper;
        $this->commandBus = $commandBus;
        $this->queryBus = $queryBus;
    }

    /**
     * seznam paragonů k akci
     * @param int $skautisEventId
     * @return Row[]
     */
    public function getAll($skautisEventId, $onlyUnlocked = FALSE): array
    {
        $list = $this->table->getAll($this->getLocalId($skautisEventId), $onlyUnlocked);
        if (!empty($list) && $this->type == self::TYPE_CAMP) {
            $categories = $this->getCategoriesPairs(NULL, $skautisEventId);
            foreach ($list as $k => $i) {
                $i->ctype = array_key_exists($i->category, $categories['in']) ? "in" : "out";
                $i->clabel = $categories[$i->ctype][$i->category];
                $i->cshort = mb_substr($categories[$i->ctype][$i->category], 0, 5, 'UTF-8');

                $list[$k] = $i;
            }
            uasort($list, function ($a, $b) {
                if ($a->date == $b->date) {
                    if (($tmpCat = strlen($a->ctype) - strlen($b->ctype))) {
                        return $tmpCat; //pokud je název kratší, je dříve, platí pro in a out
                    }
                    return ($a->id < $b->id) ? -1 : 1;
                }
                return ($a->date < $b->date) ? -1 : 1;
            });
        }
        return $list;
    }

    /**
     * vrací pole paragonů s ID zadanými v $list
     * použití - hromadný tisk
     * @param int $skautisEventId
     * @param int[] $chitIds
     * @return array
     */
    public function getIn($skautisEventId, array $chitIds)
    {
        $ret = $this->table->getIn($this->getLocalId($skautisEventId), $chitIds);
        if ($this->type == self::TYPE_CAMP) {
            $categories = $this->getCategoriesPairs(NULL, $skautisEventId);
            foreach ($ret as $k => $v) {
                $ret[$k]->ctype = array_key_exists($ret[$k]->category, $categories['in']) ? "in" : "out";
            }
        }
        return $ret;
    }

    /**
     * smazat všechny paragony dané akce
     * použití při rušení celé akce
     */
    public function deleteAll(int $skautisEventId): void
    {
        $this->table->deleteAll($this->getLocalId($skautisEventId));
    }

    /**
     * seznam všech kategorií pro daný typ
     * @param bool $isEstimate - předpoklad?
     * @return array
     */
    public function getCategories(int $skautisEventId, bool $isEstimate = FALSE): array
    {
        if ($this->type == self::TYPE_CAMP) {
            //přidání kategorií k táborům
            $res = [//8 a 12 jsou ID použitá i u výprav
                self::CHIT_UNDEFINED_OUT => (object)["ID" => self::CHIT_UNDEFINED_OUT, "IsRevenue" => FALSE, "EventCampStatementType" => "Neurčeno", "Ammount" => 0],
                self::CHIT_UNDEFINED_IN => (object)["ID" => self::CHIT_UNDEFINED_IN, "IsRevenue" => TRUE, "EventCampStatementType" => "Neurčeno", "Ammount" => 0],
            ];
            foreach ($this->skautis->event->EventCampStatementAll(["ID_EventCamp" => $skautisEventId, "IsEstimate" => $isEstimate]) as $i) { //prepisuje na tvar s klíčem jako ID
                if ($isEstimate == FALSE && $i->ID_EventCampStatementType == self::SKAUTIS_BUDGET_RESERVE) {
                    continue;
                }
                $res[$i->ID] = $i;
            }
            return $res;
        }

        $cashbookId = $this->getCashbookIdFromSkautisId($skautisEventId);

        return $this->queryBus->handle(new CategoryPairsQuery($cashbookId));
    }

    /**
     * @deprecated Use query bus with CategoryPairsQuery
     * @see CategoryPairsQuery
     *
     * vrací rozpočtové kategorie rozdělené na příjmy a výdaje (camp)
     *
     * @return array array(in=>(id=>DisplayName, ...), out=>(...))
     */
    private function getCategoriesPairs(?string $typeInOut, int $skautisEventId): array
    {
        $cashbookId = $this->getCashbookIdFromSkautisId($skautisEventId);

        if ($typeInOut === NULL) {
            return [
                Operation::INCOME => $this->getCategoriesPairs(Operation::INCOME, $skautisEventId),
                Operation::EXPENSE => $this->getCategoriesPairs(Operation::EXPENSE, $skautisEventId),
            ];
        }

        return $this->queryBus->handle(
            new CategoryPairsQuery($cashbookId, Operation::get($typeInOut))
        );
    }

    /**
     * CAMP ONLY
     * vrací ID kategorie pro příjmy od účastníků
     * @param string|NULL $category
     * @throws \Nette\InvalidStateException
     * @return int
     */
    public function getParticipantIncomeCategory(?int $skautisEventId = NULL, $category = NULL)
    {
        if ($this->type !== self::TYPE_CAMP) {
            throw new \InvalidArgumentException('This method is only for camps');
        }

        //@TODO: předělat na konstanty
        $catId = ($category == "adult") ? 3 : 1;
        foreach ($this->getCategories($skautisEventId) as $k => $val) {
            if (isset($val->ID_EventCampStatementType) && $val->ID_EventCampStatementType == $catId) {
                return $k;
            }
        }

        throw new \Nette\InvalidStateException("Chybí typ pro příjem od účastníků pro skupinu " . $category, 500);
    }

    /**
     * vrací soucet v kazdé kategorii
     * používá se pouze u táborů
     * @param int $skautisEventId
     * @return array (ID=>SUM)
     */
    public function getCategoriesSum(int $skautisEventId)
    {
        $db = $this->table->getTotalInCategories($this->getLocalId($skautisEventId));
        $all = $this->getCategories($skautisEventId, FALSE);
        foreach (array_keys($all) as $key) {
            $all[$key] = array_key_exists($key, $db) ? $db[$key] : 0;
        }
        return $all;
    }

    /**
     * ověřuje konzistentnost dat mezi paragony a SkautISem
     * @param array|NULL $toRepair
     */
    public function isConsistent(int $skautisEventId, bool $repair = FALSE, array &$toRepair = NULL): bool
    {
        $sumSkautis = $this->getCategories($skautisEventId, FALSE);
        $cashbookId = $this->getCashbookIdFromSkautisId($skautisEventId);
        foreach ($this->getCategoriesSum($skautisEventId) as $catId => $ammount) {
            if ($ammount != $sumSkautis[$catId]->Ammount) {
                if ($repair) { //má se kategorie opravit?
                    $this->commandBus->handle(new UpdateCampCategoryTotal($cashbookId, $catId));
                } elseif ($toRepair !== NULL) {
                    $toRepair[$catId] = $ammount; //seznam ID vadných kategorií a jejich částek
                }
            }
        }
        return empty($toRepair);
    }

    /**
     * nastavuje kategorie z rozpočtu
     * @param int $chitId
     * @param int|NULL $in
     * @param int|NULL $out
     */
    public function setBudgetCategories($chitId, $in = NULL, $out = NULL): void
    {
        $this->table->update($chitId, ["budgetCategoryIn" => $in, "budgetCategoryOut" => $out]);
    }

    /**
     * @param array $categories
     * @return array
     */
    public function getBudgetCategoriesSummary($categories)
    {
        return $this->table->getBudgetCategoriesSummary(array_keys($categories['in']), 'in') + $this->table->getBudgetCategoriesSummary(array_keys($categories['out']), 'out');
    }

    public function getLocalId(int $skautisEventId, string $type = NULL): int
    {
        return $this->skautisMapper->getLocalId($skautisEventId, $type ?? $this->type);
    }

    public function getCashbookIdFromSkautisId(int $skautisid): int
    {
        return $this->getLocalId($skautisid);
    }

}
