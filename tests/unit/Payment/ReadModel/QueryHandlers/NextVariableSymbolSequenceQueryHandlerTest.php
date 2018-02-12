<?php

declare(strict_types=1);

namespace Model\Payment\ReadModel\QueryHandlers;

use Codeception\Test\Unit;
use eGen\MessageBus\Bus\QueryBus;
use Model\Payment\Group;
use Model\Payment\ReadModel\Queries\NextVariableSymbolSequenceQuery;
use Model\Payment\Repositories\IGroupRepository;
use Model\Payment\VariableSymbol;
use Mockery as m;
use Model\Unit\ReadModel\Queries\UnitQuery;

class NextVariableSymbolSequenceQueryHandlerTest extends Unit
{

    private const YEAR = '17';
    private const UNIT_ID = 1;

    public function testWithZeroGroups(): void
    {
        $this->assertReturnsVariableSymbol(self::YEAR . '11101001', 0, '111');
    }

    public function testUnitWithMoreThanOrEqualTo99GroupsReturnsNull(): void
    {
        $this->assertReturnsVariableSymbol(NULL, 99, '111');
    }

    public function testUnitWithDashInRegistratioNumber(): void
    {
        $this->assertReturnsVariableSymbol(self::YEAR . '14166001', 65, '14-1');
    }

    public function testUnitWithShortRegistrationNumber(): void
    {
        $this->assertReturnsVariableSymbol(self::YEAR . '01402001', 1, '014');
    }

    public function testWithLongRegistrationNumber(): void
    {
        $this->assertReturnsVariableSymbol(self::YEAR . '14102001', 1, '014-1');
    }


    private function assertReturnsVariableSymbol(?string $expectedSymbol, int $groupsCount, string $unitRegistrationNumber): void
    {
        $unitDTO = m::mock(Unit::class, [
            'getShortRegistrationNumber' => $unitRegistrationNumber,
        ]);

        $queryBus = m::mock(QueryBus::class);
        $queryBus->shouldReceive('handle')
            ->withArgs(function(UnitQuery $query) {
                return $query->getUnitId() === self::UNIT_ID;
            })
            ->andReturn($unitDTO);

        $groupRepository = m::mock(IGroupRepository::class);

        $now = (new \DateTimeImmutable())->setDate((int) self::YEAR, 1, 1);

        $groups = array_merge(
            array_fill(0, $groupsCount, $this->mockGroup($now)),
            // add some groups from different year to test that these are filtered out
            array_fill(0, 10, $this->mockGroup($now->modify('- 1 year')))
        );

        $groupRepository->shouldReceive('findByUnits')
            ->withArgs([[1], FALSE])
            ->andReturn($groups);

        $handler = new NextVariableSymbolSequenceQueryHandler($groupRepository, $queryBus);

        $actualSymbol = $handler->handle(new NextVariableSymbolSequenceQuery(self::UNIT_ID, $now->setTime(1, 1, 1)));

        $this->assertEquals($expectedSymbol !== NULL ? new VariableSymbol($expectedSymbol) : NULL, $actualSymbol);
    }

    private function mockGroup(\DateTimeImmutable $time): Group
    {
        return m::mock(Group::class, [
            'getCreatedAt' => $time,
        ]);
    }
}