<?php

namespace Test;

use Hyperf\Config\Config;
use Hyperf\Context\ApplicationContext;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Di\Container;
use Hyperf\Di\Definition\DefinitionSourceFactory;

// 定义BASE_PATH常量
define('BASE_PATH', dirname(__DIR__));

abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Container
     */
    protected $container;

    protected function setUp(): void
    {
        parent::setUp();
        // 初始化容器
        $this->container = ApplicationContext::setContainer(new Container((new DefinitionSourceFactory())()));
        // 注册配置服务
        $this->registerConfig();
    }

    /**
     * 注册配置服务
     */
    protected function registerConfig()
    {
        // 加载配置文件
        $configPath = __DIR__ . '/../publish/zego.php';
        $configData = [];
        if (file_exists($configPath)) {
            $configData = require $configPath;
        }

        // 创建配置实例
        $config = new Config($configData);

        // 注册配置服务
        $this->container->set(ConfigInterface::class, $config);
    }

    /**
     * 获取容器实例
     * @return Container
     */
    protected function getContainer()
    {
        return $this->container;
    }
}
