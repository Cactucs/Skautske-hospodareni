<?php

declare(strict_types=1);

namespace Model\Budget\Repositories;

use Model\Budget\CategoryNotFound;
use Model\Budget\Unit\Category;
use Model\Cashbook\Operation;

interface ICategoryRepository
{
    /** @throws CategoryNotFound */
    public function find(int $id): Category;

    /** @return Category[] */
    public function findCategories(int $unitId, Operation $operationType): array;

    public function save(Category $vehicle): void;
}
