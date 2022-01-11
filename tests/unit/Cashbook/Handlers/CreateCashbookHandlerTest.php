<?php

declare(strict_types=1);

namespace Model\Cashbook\Handlers;

use Codeception\Test\Unit;
use Mockery as m;
use Model\Cashbook\Cashbook;
use Model\Cashbook\Commands\Cashbook\CreateCashbook;
use Model\Cashbook\Handlers\Cashbook\CreateCashbookHandler;
use Model\Cashbook\Repositories\ICashbookRepository;

class CreateCashbookHandlerTest extends Unit
{
    public function test(): void
    {
        $type       = Cashbook\CashbookType::get(Cashbook\CashbookType::CAMP);
        $cashbookId = Cashbook\CashbookId::generate();

        $repository = m::mock(ICashbookRepository::class);
        $repository->shouldReceive('save')
            ->once()
            ->withArgs(static function (Cashbook $cashbook) use ($type, $cashbookId) {
                return $cashbook->getId()->equals($cashbookId)
                    && $cashbook->getType()->equals($type);
            });

        $handler = new CreateCashbookHandler($repository);

        $handler(new CreateCashbook($cashbookId, $type));
    }
}
