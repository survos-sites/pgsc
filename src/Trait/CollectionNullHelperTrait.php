<?php
declare(strict_types=1);

namespace App\Trait;

use Doctrine\Common\Collections\Collection;

trait CollectionNullHelperTrait
{
    /**
     * Returns the first element or null if empty.
     */
    protected function firstOrNull(Collection $collection): mixed
    {
        return $collection->isEmpty() ? null : $collection->first();
    }

    /**
     * Returns the last element or null if empty.
     */
    protected function lastOrNull(Collection $collection): mixed
    {
        return $collection->isEmpty() ? null : $collection->last();
    }
}
