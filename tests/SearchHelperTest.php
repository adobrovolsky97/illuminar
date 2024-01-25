<?php

namespace Adobrovolsky97\Illuminar\Tests;

use Adobrovolsky97\Illuminar\Helpers\SearchHelper;
use Adobrovolsky97\Illuminar\Watchers\EventWatcher;
use Adobrovolsky97\Illuminar\Watchers\QueryWatcher;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Class SearchHelperTest
 */
class SearchHelperTest extends TestCase
{
    /**
     * @var SearchHelper
     */
    private SearchHelper $searchHelper;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->searchHelper = new SearchHelper();
    }

    /**
     * @return void
     */
    public function testEmptyFiltersShouldNotReturnData(): void
    {
        $data = [
            ['type' => QueryWatcher::getName(), 'hash' => 'hash1'],
            ['type' => QueryWatcher::getName(), 'hash' => 'hash2'],
        ];

        $result = $this->searchHelper->filterData($data);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertEquals(0, $result->total());
    }

    /**
     * @return void
     */
    public function testTypeFilter(): void
    {
        $data = [
            ['type' => QueryWatcher::getName()],
            ['type' => EventWatcher::getName()],
        ];

        $filters = ['types' => [QueryWatcher::getName(), EventWatcher::getName()]];

        $result = $this->searchHelper->filterData($data, $filters);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertEquals(2, $result->total());
    }

    /**
     * @return void
     */
    public function testSearchFilter(): void
    {
        $data = [
            ['type' => QueryWatcher::getName(), 'hash' => 'hash1', 'sql' => 'SELECT * FROM users'],
            ['type' => QueryWatcher::getName(), 'hash' => 'hash2', 'sql' => 'SELECT * FROM orders'],
        ];

        $filters = ['search' => 'users', 'types' => [QueryWatcher::getName()]];

        $result = $this->searchHelper->filterData($data, $filters);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertEquals(1, $result->total());
    }

    /**
     * @return void
     */
    public function testNoMatches(): void
    {
        $data = [
            ['type' => QueryWatcher::getName(), 'hash' => 'hash1', 'sql' => 'SELECT * FROM users'],
            ['type' => QueryWatcher::getName(), 'hash' => 'hash2', 'sql' => 'SELECT * FROM orders'],
        ];

        $filters = ['search' => 'products'];

        $result = $this->searchHelper->filterData($data, $filters);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertEquals(0, $result->total());
    }

    /**
     * @return void
     */
    public function testSlowGroupedAndDuplicatedQueries(): void
    {
        $data = [
            ['type' => QueryWatcher::getName(), 'hash' => 'hash1', 'is_slow' => true],
            ['type' => QueryWatcher::getName(), 'hash' => 'hash1', 'is_slow' => true],
            ['type' => QueryWatcher::getName(), 'hash' => 'hash2', 'is_slow' => false],
            ['type' => QueryWatcher::getName(), 'hash' => 'hash3', 'is_slow' => true],
            ['type' => QueryWatcher::getName(), 'hash' => 'hash4', 'is_slow' => false],
        ];

        $filters = ['is_slow' => true, 'is_duplicate' => true, 'types' => [QueryWatcher::getName()]];

        $result = $this->searchHelper->filterData($data, $filters);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertEquals(2, $result->total());

        $filters = [
            'is_duplicate'             => true,
            'group_duplicated_queries' => true,
            'types'                    => [QueryWatcher::getName()]
        ];

        $result = $this->searchHelper->filterData($data, $filters);
        $this->assertEquals(1, $result->total());
    }
}
