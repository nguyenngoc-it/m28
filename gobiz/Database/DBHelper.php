<?php

namespace Gobiz\Database;

use Generator;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use RuntimeException;

class DBHelper
{
    /**
     * Chunk the results of a query by comparing IDs and return generator
     *
     * @param EloquentBuilder|QueryBuilder $query
     * @param int $count
     * @param string $column
     * @param string|null $alias
     * @return Generator
     */
    public static function chunkByIdGenerator($query, $count, $column = 'id', $alias = null)
    {
        $alias = $alias ?? $column;
        $lastId = null;

        do {
            $clone = clone $query;
            $results = $clone->forPageAfterId($count, $lastId, $column)->get();
            $countResults = $results->count();

            if ($countResults == 0) {
                break;
            }

            $lastId = $results->last()->{$alias};

            if ($lastId === null) {
                throw new RuntimeException("The chunkById operation was aborted because the [{$alias}] column is not present in the query result.");
            }

            yield $results;

            unset($results);
        } while ($countResults == $count);
    }
}
