<?php

namespace Tests\Feature;

use App\Models\Food;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The one-open-report guards lock the product row inside a transaction so two
 * concurrent submissions cannot both pass the check. SQLite ignores the lock,
 * so this pins that the query actually requests one on the production engine.
 */
class FoodRowLockTest extends TestCase
{
    public function test_food_lock_query_compiles_to_for_update_on_mysql(): void
    {
        // Resolving the grammar is lazy: no connection is opened.
        $grammar = DB::connection('mysql')->getQueryGrammar();
        $sql = $grammar->compileSelect(
            Food::query()->whereKey(1)->lockForUpdate()->getQuery()
        );

        $this->assertSame('mysql', DB::connection('mysql')->getDriverName());
        $this->assertStringContainsString('for update', strtolower($sql));
    }
}
