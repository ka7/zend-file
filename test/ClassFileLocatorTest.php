<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\File;

use Zend\File\ClassFileLocator;
use Zend\File\Exception;

/**
 * Test class for Zend\File\ClassFileLocator
 *
 * @group      Zend_File
 */
class ClassFileLocatorTest extends \PHPUnit_Framework_TestCase
{
    public function testConstructorThrowsInvalidArgumentExceptionForInvalidStringDirectory()
    {
        $this->setExpectedException(Exception\InvalidArgumentException::class);
        $locator = new ClassFileLocator('__foo__');
    }

    public function testConstructorThrowsInvalidArgumentExceptionForNonDirectoryIteratorArgument()
    {
        $iterator = new \ArrayIterator([]);
        $this->setExpectedException(Exception\InvalidArgumentException::class);
        $locator = new ClassFileLocator($iterator);
    }

    public function testIterationShouldReturnOnlyPhpFiles()
    {
        $locator = new ClassFileLocator(__DIR__);
        foreach ($locator as $file) {
            $this->assertRegexp('/\.php$/', $file->getFilename());
        }
    }

    public function testIterationShouldReturnOnlyPhpFilesContainingClasses()
    {
        $locator = new ClassFileLocator(__DIR__);
        $found = false;
        foreach ($locator as $file) {
            if (preg_match('/locator-should-skip-this\.php$/', $file->getFilename())) {
                $found = true;
            }
        }
        $this->assertFalse($found, "Found PHP file not containing a class?");
    }

    public function testIterationShouldReturnInterfaces()
    {
        $locator = new ClassFileLocator(__DIR__);
        $found = false;
        foreach ($locator as $file) {
            if (preg_match('/LocatorShouldFindThis\.php$/', $file->getFilename())) {
                $found = true;
            }
        }
        $this->assertTrue($found, "Locator skipped an interface?");
    }

    public function testIterationShouldInjectNamespaceInFoundItems()
    {
        $locator = new ClassFileLocator(__DIR__);
        $found = false;
        foreach ($locator as $file) {
            $classes = $file->getClasses();
            foreach ($classes as $class) {
                if (strpos($class, '\\', 1)) {
                    $found = true;
                }
            }
        }
        $this->assertTrue($found);
    }

    public function testIterationShouldInjectNamespacesInFileInfo()
    {
        $locator = new ClassFileLocator(__DIR__);
        foreach ($locator as $file) {
            $namespaces = $file->getNamespaces();
            $this->assertNotEmpty($namespaces);
        }
    }

    public function testIterationShouldInjectClassInFoundItems()
    {
        $locator = new ClassFileLocator(__DIR__);
        $found = false;
        foreach ($locator as $file) {
            $classes = $file->getClasses();
            foreach ($classes as $class) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found);
    }

    public function testIterationShouldFindMultipleClassesInMultipleNamespacesInSinglePhpFile()
    {
        $locator = new ClassFileLocator(__DIR__);
        $foundFirst = false;
        $foundSecond = false;
        $foundThird = false;
        $foundFourth = false;
        foreach ($locator as $file) {
            if (preg_match('/MultipleClassesInMultipleNamespaces\.php$/', $file->getFilename())) {
                $classes = $file->getClasses();
                foreach ($classes as $class) {
                    if ($class === TestAsset\LocatorShouldFindFirstClass::class) {
                        $foundFirst = true;
                    }
                    if ($class === TestAsset\LocatorShouldFindSecondClass::class) {
                        $foundSecond = true;
                    }
                    if ($class === TestAsset\SecondTestNamespace\LocatorShouldFindThirdClass::class) {
                        $foundThird = true;
                    }
                    if ($class === TestAsset\SecondTestNamespace\LocatorShouldFindFourthClass::class) {
                        $foundFourth = true;
                    }
                }
            }
        }
        $this->assertTrue($foundFirst);
        $this->assertTrue($foundSecond);
        $this->assertTrue($foundThird);
        $this->assertTrue($foundFourth);
    }

    /**
     * @group 6946
     * @group 6814
     */
    public function testIterationShouldNotCountFQCNScalarResolutionConstantAsClass()
    {
        if (PHP_VERSION_ID < 50500) {
            $this->markTestSkipped('Only applies to PHP >=5.5');
        }

        foreach (new ClassFileLocator(__DIR__ .'/TestAsset') as $file) {
            if (! preg_match('/ClassNameResolutionCompatibility\.php$/', $file->getFilename())) {
                continue;
            }
            $this->assertCount(1, $file->getClasses());
        }
    }
}
