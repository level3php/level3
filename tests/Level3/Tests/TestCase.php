<?php
/*
 * This file is part of the Level3 package.
 *
 * (c) Máximo Cuadros <maximo@yunait.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Level3\Tests;
use Level3\ResourceHub;
use Level3\Mocks\Mapper;
use Level3\Mocks\ResourceManager;
use Hal\Resource;
use Mockery as m;

class TestCase extends \PHPUnit_Framework_TestCase
{
    const IRRELEVANT_HREF = 'XX';

    protected $resourceHubMock;

    protected function repositoryHubShouldHavePair($key, $value)
    {
        $this->repositoryHubKeyShouldExist($key);
        $this->repositoryHubMock->shouldReceive('offsetGet')->with($key)->once()->andReturn($value);
    }

    protected function repositoryHubKeyShouldExist($key)
    {
        $this->repositoryHubMock->shouldReceive('offsetExists')->with($key)->andReturn(true);
    }
}