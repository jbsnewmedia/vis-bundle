<?php

declare(strict_types=1);

namespace JBSNewMedia\VisBundle\Tests\DependencyInjection\Compiler;

use JBSNewMedia\VisBundle\Attribute\VisPlugin;
use JBSNewMedia\VisBundle\DependencyInjection\Compiler\VisPluginPass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class VisPluginPassTest extends TestCase
{
    public function testAddsTagFromAttribute(): void
    {
        $container = new ContainerBuilder();

        // Define a dummy service with class that has the VisPlugin attribute
        $definition = new Definition(DummyPlugin::class);
        $definition->setPublic(true);
        $container->setDefinition('test.dummy_plugin', $definition);

        // Define a service without class to test 'continue'
        $container->setDefinition('test.no_class', (new Definition())->setSynthetic(true));

        // Define a service with non-existent class to test 'continue'
        $container->setDefinition('test.invalid_class', (new Definition('NonExistentClass'))->setPublic(true));

        $container->addCompilerPass(new VisPluginPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, 0);
        $container->compile();

        $def = $container->getDefinition('test.dummy_plugin');
        $this->assertTrue($def->hasTag('VisPlugin'));
        $tags = $def->getTag('VisPlugin');
        $this->assertNotEmpty($tags);
        $this->assertSame('foo', $tags[0]['plugin']);
        $this->assertSame(10, $tags[0]['priority']);

        $this->assertTrue($container->hasDefinition('test.no_class'));
        $defNoClass = $container->getDefinition('test.no_class');
        $this->assertFalse($defNoClass->hasTag('VisPlugin'));

        $defInvalidClass = $container->getDefinition('test.invalid_class');
        $this->assertFalse($defInvalidClass->hasTag('VisPlugin'));
    }

    public function testRealReflectionException(): void
    {
        // Internal classes that cannot be reflected? Hard to find one that class_exists returns true for.
        // Let's use a simpler approach: test the getAttributes method directly with a non-existent class.
        $pass = new class extends VisPluginPass {
            public function callGetAttributes(string $class): array
            {
                return $this->getAttributes($class);
            }
        };

        $this->assertEquals([], $pass->callGetAttributes('NonExistentClassForReal'));
    }
}

#[VisPlugin(plugin: 'foo', priority: 10)]
class DummyPlugin
{
}
