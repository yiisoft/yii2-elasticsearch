<?php

namespace yiiunit\extensions\elasticsearch;

use yii\elasticsearch\Command;

/**
 * Class CommandTest
 * @package yiiunit\extensions\elasticsearch
 */
class CommandTest extends TestCase
{
    /** @var Command */
    private $command;

    private $index = 'alias_test';
    private $index1 = 'alias_test1';
    private $index2 = 'alias_test2';

    protected function setUp()
    {
        parent::setUp();
        $this->command = $this->getConnection()->createCommand();

        $testAlias = 'test';
        $this->command->deleteIndex($this->index);
        $this->command->deleteIndex($testAlias);
        $this->command->deleteIndex($this->index1);
        $this->command->deleteIndex($this->index2);
    }

    /**
     * @test
     */
    public function aliasExists_noAliasesSet_returnsFalse()
    {
        $testAlias = 'test';
        $aliasExists = $this->command->aliasExists($testAlias);

        $this->assertFalse($aliasExists);
    }

    /**
     * @test
     */
    public function aliasExists_AliasesAreSetButWithDifferentName_returnsFalse()
    {
        $testAlias = 'test';
        $fooAlias1 = 'alias';
        $fooAlias2 = 'alias2';

        $this->command->createIndex($this->index);
        $this->command->addAlias($this->index, $fooAlias1);
        $this->command->addAlias($this->index, $fooAlias2);
        $aliasExists = $this->command->aliasExists($testAlias);
        $this->command->deleteIndex($this->index);

        $this->assertFalse($aliasExists);
    }

    /**
     * @test
     */
    public function aliasExists_AliasIsSetWithSameName_returnsTrue()
    {
        $testAlias = 'test';

        $this->command->createIndex($this->index);
        $this->command->addAlias($this->index, $testAlias);
        $aliasExists = $this->command->aliasExists($testAlias);
        $this->command->deleteIndex($this->index);

        $this->assertTrue($aliasExists);
    }

    /**
     * @test
     */
    public function getAliasInfo_noAliasSet_returnsEmptyArray()
    {
        $expectedResult = [];
        $actualResult = $this->command->getAliasInfo();

        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     * @dataProvider provideDataForGetAliasInfo
     *
     * @param string $index
     * @param array|null $indexConfig
     * @param string $alias
     * @param array $expectedResult
     * @param array $aliasParameters
     */
    public function getAliasInfo_singleAliasIsSet_returnsInfoForAlias(
        $index,
        $indexConfig,
        $alias,
        $expectedResult,
        $aliasParameters
    ) {
        $this->command->createIndex($index, $indexConfig);
        $this->command->addAlias($index, $alias, $aliasParameters);
        $actualResult = $this->command->getAliasInfo();
        $this->command->deleteIndex($index);

        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * @return array
     */
    public function provideDataForGetAliasInfo()
    {
        $alias = 'test';
        $filter = [
            'filter' => [
                'term' => [
                    'user' => 'satan',
                ],
            ],
        ];
        $mapping = [
            'mappings' => [
                'type1' => [
                    'properties' => [
                        'user' => [
                            'type' => 'string',
                            'index' => 'not_analyzed',
                        ],
                    ],
                ],
            ],
        ];
        $singleRouting = [
            'routing' => '1',
        ];
        $singleExpectedRouting = [
            'index_routing' => '1',
            'search_routing' => '1',
        ];
        $differentRouting = [
            'index_routing' => '2',
            'search_routing' => '1,2',
        ];

        return [
            [
                $this->index,
                null,
                $alias,
                [
                    $this->index => [
                        'aliases' => [
                            $alias => [],
                        ],
                    ],
                ],
                [],
            ],
            [
                $this->index,
                $mapping,
                $alias,
                [
                    $this->index => [
                        'aliases' => [
                            $alias => $filter,
                        ]
                    ],
                ],
                $filter,
            ],
            [
                $this->index,
                null,
                $alias,
                [
                    $this->index => [
                        'aliases' => [
                            $alias => $singleExpectedRouting,
                        ],
                    ],
                ],
                $singleRouting,
            ],
            [
                $this->index,
                null,
                $alias,
                [
                    $this->index => [
                        'aliases' => [
                            $alias => $differentRouting,
                        ],
                    ],
                ],
                $differentRouting
            ],
            [
                $this->index,
                $mapping,
                $alias,
                [
                    $this->index => [
                        'aliases' => [
                            $alias => array_merge($filter, $singleExpectedRouting)
                        ],
                    ],
                ],
                array_merge($filter, $singleRouting),
            ],
            [
                $this->index,
                $mapping,
                $alias,
                [
                    $this->index => [
                        'aliases' => [
                            $alias => array_merge($filter, $differentRouting)
                        ],
                    ],
                ],
                array_merge($filter, $differentRouting),
            ]
        ];
    }

    /**
     * @test
     */
    public function getIndexInfoByAlias_noAliasesSet_returnsEmptyArray()
    {
        $testAlias = 'test';
        $expectedResult = [];

        $actualResult = $this->command->getIndexInfoByAlias($testAlias);

        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function getIndexInfoByAlias_oneIndexIsSetToAlias_returnsDataForThatIndex()
    {
        $testAlias = 'test';
        $expectedResult = [
            $this->index => [
                'aliases' => [
                    $testAlias => [],
                ],
            ],
        ];

        $this->command->createIndex($this->index);
        $this->command->addAlias($this->index, $testAlias);
        $actualResult = $this->command->getIndexInfoByAlias($testAlias);
        $this->command->deleteIndex($this->index);

        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function getIndexInfoByAlias_twoIndexesAreSetToSameAlias_returnsDataForBothIndexes()
    {
        $testAlias = 'test';
        $expectedResult = [
            $this->index1 => [
                'aliases' => [
                    $testAlias => [],
                ],
            ],
            $this->index2 => [
                'aliases' => [
                    $testAlias => [],
                ],
            ],
        ];

        $this->command->createIndex($this->index1);
        $this->command->createIndex($this->index2);
        $this->command->addAlias($this->index1, $testAlias);
        $this->command->addAlias($this->index2, $testAlias);
        $actualResult = $this->command->getIndexInfoByAlias($testAlias);
        $this->command->deleteIndex($this->index1);
        $this->command->deleteIndex($this->index2);

        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function getIndexesByAlias_noAliasesSet_returnsEmptyArray()
    {
        $expectedResult = [];
        $testAlias = 'test';

        $actualResult = $this->command->getIndexesByAlias($testAlias);

        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function getIndexesByAlias_oneIndexIsSetToAlias_returnsArrayWithNameOfThatIndex()
    {
        $testAlias = 'test';
        $expectedResult = [$this->index];

        $this->command->createIndex($this->index);
        $this->command->addAlias($this->index, $testAlias);
        $actualResult = $this->command->getIndexesByAlias($testAlias);
        $this->command->deleteIndex($this->index);

        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function getIndexesByAlias_twoIndexesAreSetToSameAlias_returnsArrayWithNamesForBothIndexes()
    {
        $testAlias = 'test';
        $expectedResult = [
            $this->index1,
            $this->index2,
        ];

        $this->command->createIndex($this->index1);
        $this->command->createIndex($this->index2);
        $this->command->addAlias($this->index1, $testAlias);
        $this->command->addAlias($this->index2, $testAlias);
        $actualResult = $this->command->getIndexesByAlias($testAlias);
        $this->command->deleteIndex($this->index1);
        $this->command->deleteIndex($this->index2);

        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function getIndexAliases_noAliasesSet_returnsEmptyArray()
    {
        $expectedResult = [];

        $actualResult = $this->command->getIndexAliases($this->index);

        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     * @todo maybe add more test with alias settings
     */
    public function getIndexAliases_SingleAliasIsSet_returnsDataForThatAlias()
    {
        $testAlias = 'test_alias';
        $expectedResult = [
            $testAlias => [],
        ];

        $this->command->createIndex($this->index);
        $this->command->addAlias($this->index, $testAlias);
        $actualResult = $this->command->getIndexAliases($this->index);
        $this->command->deleteIndex($this->index);

        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     * @todo maybe add more test with alias settings
     */
    public function getIndexAliases_MultipleAliasesAreSet_returnsDataForThoseAliases()
    {
        $testAlias1 = 'test_alias1';
        $testAlias2 = 'test_alias2';
        $expectedResult = [
            $testAlias1 => [],
            $testAlias2 => [],
        ];

        $this->command->createIndex($this->index);
        $this->command->addAlias($this->index, $testAlias1);
        $this->command->addAlias($this->index, $testAlias2);
        $actualResult = $this->command->getIndexAliases($this->index);
        $this->command->deleteIndex($this->index);

        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function removeAlias_noAliasIsSetForIndex_returnsFalse()
    {
        $testAlias = 'test_alias';

        $this->command->createIndex($this->index);
        $actualResult = $this->command->removeAlias($this->index, $testAlias);
        $this->command->deleteIndex($this->index);

        $this->assertFalse($actualResult);
    }

    /**
     * @test
     */
    public function removeAlias_aliasWasSetForIndex_returnsTrue()
    {
        $testAlias = 'test_alias';

        $this->command->createIndex($this->index);
        $this->command->addAlias($this->index, $testAlias);
        $actualResult = $this->command->removeAlias($this->index, $testAlias);
        $this->command->deleteIndex($this->index);

        $this->assertTrue($actualResult);
    }

    /**
     * @test
     */
    public function addAlias_aliasNonExistingIndex_returnsFalse()
    {
        $testAlias = 'test_alias';

        $actualResult = $this->command->addAlias($this->index, $testAlias);

        $this->assertFalse($actualResult);
    }

    /**
     * @test
     */
    public function addAlias_aliasExistingIndex_returnsTrue()
    {
        $testAlias = 'test_alias';

        $this->command->createIndex($this->index);
        $actualResult = $this->command->addAlias($this->index, $testAlias);
        $this->command->deleteIndex($this->index);

        $this->assertTrue($actualResult);
    }

    /**
     * @test
     */
    public function aliasActions_makingOperationOverNonExistingIndex_returnsFalse()
    {
        $testAlias = 'test_alias';

        $actualResult = $this->command->aliasActions([
            ['add' => ['index' => $this->index, 'alias' => $testAlias]],
            ['remove' => ['index' => $this->index, 'alias' => $testAlias]],
        ]);

        $this->assertFalse($actualResult);
    }

    /**
     * @test
     */
    public function aliasActions_makingOperationOverExistingIndex_returnsTrue()
    {
        $testAlias = 'test_alias';

        $this->command->createIndex($this->index);
        $actualResult = $this->command->aliasActions([
            ['add' => ['index' => $this->index, 'alias' => $testAlias]],
            ['remove' => ['index' => $this->index, 'alias' => $testAlias]],
        ]);
        $this->command->deleteIndex($this->index);

        $this->assertTrue($actualResult);
    }
}
