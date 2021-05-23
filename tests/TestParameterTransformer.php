<?php declare(strict_types=1);
/**
 * e-Arc Framework - the explicit Architecture Framework
 *
 * @package earc/data
 * @link https://github.com/Koudela/eArc-data/
 * @copyright Copyright (c) 2019-2021 Thomas Koudela
 * @license http://opensource.org/licenses/MIT MIT License
 */

namespace eArc\ParameterTransformerTests;

use eArc\DI\DI;
use eArc\DI\Exceptions\InvalidArgumentException;
use eArc\ParameterTransformer\Configuration;
use eArc\ParameterTransformer\Exceptions\DiException;
use eArc\ParameterTransformer\Exceptions\FactoryException;
use eArc\ParameterTransformer\Exceptions\NoInputException;
use eArc\ParameterTransformer\Exceptions\NullValueException;
use eArc\ParameterTransformer\Interfaces\ParameterTransformerFactoryServiceInterface;
use eArc\ParameterTransformer\ParameterTransformer;
use eArc\ParameterTransformer\PrivateServices\ArgumentTransformer;
use eArc\ParameterTransformer\PrivateServices\InputProvider;
use eArc\ParameterTransformerTests\classes\AccessCountTestClass;
use eArc\ParameterTransformerTests\classes\ConstructorTestClass;
use eArc\ParameterTransformerTests\classes\DIConstructedClass;
use eArc\ParameterTransformerTests\classes\MethodsFistTestClass;
use eArc\ParameterTransformerTests\classes\PTFSConstructedClass;
use eArc\ParameterTransformerTests\classes\PTF;
use eArc\ParameterTransformerTests\classes\PTFService;
use Exception;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionProperty;

class TestParameterTransformer extends TestCase
{
    /**
     * @throws InvalidArgumentException
     */
    public function init(): void
    {
        if (!function_exists('helloWorld')) {
            /**
             * @noinspection PhpUnused
             * @noinspection PhpUnusedParameterInspection
             * @noinspection PhpDocSignatureInspection
             * @noinspection PhpUndefinedClassInspection
             * @noinspection PhpOptionalBeforeRequiredParametersInspection
             */
            function helloWorld(
                ?string $hello,
                int $int,
                bool $bool,
                float $float,
                string $string,
                ?InputProvider $inputProvider = null,
                ?PTF $ptf = null,
                ?PTFSConstructedClass $constructedClass = null,
                ?DIConstructedClass $diConstructedClass = null,
                NonExistentClass | DIConstructedClass | AnotherNoType $unionType = null,
                NonExistentClass|int $defaultValue = 42,
                int $noNullValue,
            ): ?string {
                return $hello;
            }
        }

        DI::init();
    }

    public function getInput(): array
    {
        return [
            'hello' => 'world',
            0 => 'deep sea',
            'earc' => 'parameter-transformer',
            3 => 42,
            'lucky' => 'man',
            5 => 'crazy horse',
            'key23' => [0 => 'fifty', '2' => 2],
            2 => 'last effort 42',
        ];
    }

    /**
     * @throws DiException | FactoryException | NoInputException | NullValueException | InvalidArgumentException
     */
    public function testArgumentTransformation(): void
    {
        $this->init();

        $this->choosingTheInput();
        $this->transformingTypeHints();
    }

    /**
     * @throws DiException | FactoryException | NoInputException | NullValueException
     */
    public function choosingTheInput(): void
    {
        $reflection = new ReflectionFunction('\eArc\ParameterTransformerTests\helloWorld');
        $argumentTransformer = new ArgumentTransformer();

        // test plain
        $inputProvider = new InputProvider($this->getInput(), null);
        $result = $argumentTransformer->transform($reflection->getParameters()[0], $inputProvider);
        $this->assertEquals('world', $result);

        // test default resource
        $inputProvider = new InputProvider(null, (new Configuration())->setDefaultResource(function () { return ['hello' => 'default resource'];}));
        $result = $argumentTransformer->transform($reflection->getParameters()[0], $inputProvider);
        $this->assertEquals('default resource', $result);

        // test mapping (1.)
        $inputProvider = new InputProvider($this->getInput(), (new Configuration())->setMapping(['hello' => 5]));
        $result = $argumentTransformer->transform($reflection->getParameters()[0], $inputProvider);
        $this->assertEquals('crazy horse', $result);

        // test positional argument (2.)
        $inputProvider = new InputProvider($this->getInput(), (new Configuration())->setMapping(['hello' => 7]));
        $result = $argumentTransformer->transform($reflection->getParameters()[0], $inputProvider);
        $this->assertEquals('deep sea', $result);

        // test stepping positional argument (2.) and no input exception (3.)
        $caught = false;
        try {
            $argumentTransformer->transform($reflection->getParameters()[0], $inputProvider);
        } catch (NoInputException) {
            $caught = true;
        }
        $this->assertTrue($caught);

        // test stepping positional argument (2.) and setting no input allowed (3.)
        $inputProvider = new InputProvider(
            $this->getInput(),
            (new Configuration())->setMapping(['hello' => 7])->setNoInputIsAllowed(true)
        );
        $result = $argumentTransformer->transform($reflection->getParameters()[0], $inputProvider);
        $this->assertEquals('deep sea', $result);
        $result = $argumentTransformer->transform($reflection->getParameters()[0], $inputProvider);
        $this->assertEquals(null, $result);
    }

    /**
     * @throws DiException | FactoryException | NoInputException | NullValueException
     */
    public function transformingTypeHints(): void
    {
        $reflection = new ReflectionFunction('\eArc\ParameterTransformerTests\helloWorld');
        $argumentTransformer = new ArgumentTransformer();

        // 'null' string transformation
        $inputProvider = new InputProvider(['hello' => 'null'], null);
        $result = $argumentTransformer->transform($reflection->getParameters()[0], $inputProvider);
        $this->assertEquals(null, $result);

        $inputProvider = new InputProvider(['string' => 'null'], null);
        $result = $argumentTransformer->transform($reflection->getParameters()[4], $inputProvider);
        $this->assertEquals('null', $result);

        // int
        $inputProvider = new InputProvider(['int' => '23 times bla bla'], null);
        $result = $argumentTransformer->transform($reflection->getParameters()[1], $inputProvider);
        $this->assertEquals(23, $result);

        // bool
        $inputProvider = new InputProvider(['bool' => 'false'], null);
        $result = $argumentTransformer->transform($reflection->getParameters()[2], $inputProvider);
        $this->assertEquals(true, $result);

        // float
        $inputProvider = new InputProvider(['float' => '3.5'], null);
        $result = $argumentTransformer->transform($reflection->getParameters()[3], $inputProvider);
        $this->assertEquals(3.5, $result);

        // string
        $inputProvider = new InputProvider(['string' => '42 days after tomorrow'], null);
        $result = $argumentTransformer->transform($reflection->getParameters()[4], $inputProvider);
        $this->assertEquals('42 days after tomorrow', $result);

        // predefined type hint value
        $config = new Configuration();
        $inputProvider = new InputProvider(['inputProvider' => null], $config);
        $config->setPredefinedTypeHints([InputProvider::class => $inputProvider]);
        $result = $argumentTransformer->transform($reflection->getParameters()[5], $inputProvider);
        $this->assertSame($inputProvider, $result);

        // ParameterTransformerFactoryInterface
        $inputProvider = new InputProvider(['ptf' => 'ptf-interface-test'], null);
        $result = $argumentTransformer->transform($reflection->getParameters()[6], $inputProvider);
        $this->assertInstanceOf(PTF::class, $result);
        if ($result instanceof PTF) {
            $this->assertEquals('ptf-interface-test', $result->myId);
        }

        // ParameterTransformerFactoryServiceInterface
        di_tag(ParameterTransformerFactoryServiceInterface::class, PTFService::class);
        $inputProvider = new InputProvider(['constructedClass' => 'ptf-service-interface-test'], null);
        $result = $argumentTransformer->transform($reflection->getParameters()[7], $inputProvider);
        $this->assertInstanceOf(PTFSConstructedClass::class, $result);
        if ($result instanceof PTFSConstructedClass) {
            $this->assertEquals('ptf-service-interface-test', $result->myId);
        }

        // di_get()
        $inputProvider = new InputProvider(['diConstructedClass' => null], null);
        $result = $argumentTransformer->transform($reflection->getParameters()[8], $inputProvider);
        $this->assertInstanceOf(DIConstructedClass::class, $result);

        // union type hint
        $inputProvider = new InputProvider(['unionType' => null], null);
        $result = $argumentTransformer->transform($reflection->getParameters()[9], $inputProvider);
        $this->assertInstanceOf(DIConstructedClass::class, $result);

        // default value
        $inputProvider = new InputProvider(['defaultValue' => null], null);
        $result = $argumentTransformer->transform($reflection->getParameters()[10], $inputProvider);
        $this->assertEquals(42, $result);

        // no `null` value allowed
        $inputProvider = new InputProvider(['noNullValue' => null], null);
        $exception = null;
        try {
            $argumentTransformer->transform($reflection->getParameters()[11], $inputProvider);
        } catch (Exception $exception) {

        }
        $this->assertInstanceOf(NullValueException::class, $exception);
        $inputProvider = new InputProvider(['noNullValue' => null], (new Configuration())->setNullIsAllowed(true));
        $result = $argumentTransformer->transform($reflection->getParameters()[11], $inputProvider);
        $this->assertEquals(null, $result);


        // 10. Transformed input values are removed from the input array
        $inputProvider = new InputProvider(['int' => '23 times bla bla'], (new Configuration())->setNoInputIsAllowed(true)->setNullIsAllowed(true));
        $result = $argumentTransformer->transform($reflection->getParameters()[1], $inputProvider);
        $this->assertEquals(23, $result);
        $result = $argumentTransformer->transform($reflection->getParameters()[1], $inputProvider);
        $this->assertEquals(null, $result);
    }

    public function testCallableTransformation()
    {
        DI::init();

        $parameterTransformer = new ParameterTransformer();
        $result = $parameterTransformer->callableTransform(
            ConstructorTestClass::class,
            ['hello' => '12'],
            (new Configuration())->setNoInputIsAllowed(true));
        $this->assertEquals([
            'hello' => 12,
            'world' => 'hello hello',
            'di' => di_get(DIConstructedClass::class),
        ], $result);

        $constructorTestClass = (new ReflectionClass(ConstructorTestClass::class))->newInstanceWithoutConstructor();
        $result = $parameterTransformer->callableTransform(
            [$constructorTestClass, '__construct'],
            ['hello' => '12'],
            (new Configuration())->setNoInputIsAllowed(true));
        $this->assertEquals([
            'hello' => 12,
            'world' => 'hello hello',
            'di' => di_get(DIConstructedClass::class),
        ], $result);

        $result = $parameterTransformer->callableTransform(
            function(int $hello, string $world = 'hello hello', DIConstructedClass $di = null) {},
            ['hello' => '12'],
            (new Configuration())->setNoInputIsAllowed(true));
        $this->assertEquals([
            'hello' => 12,
            'world' => 'hello hello',
            'di' => di_get(DIConstructedClass::class),
        ], $result);

        if (!function_exists('eArc\ParameterTransformerTests\testFunction')) {
            function testFunction(int $hello, string $world = 'hello hello', DIConstructedClass $di = null) {}
        }

        $result = $parameterTransformer->callableTransform(
            'eArc\ParameterTransformerTests\testFunction',
            ['hello' => '12'],
            (new Configuration())->setNoInputIsAllowed(true));
        $this->assertEquals([
            'hello' => 12,
            'world' => 'hello hello',
            'di' => di_get(DIConstructedClass::class),
        ], $result);
    }

    public function testObjectTransformation()
    {
        DI::init();

        $parameterTransformer = new ParameterTransformer();

        // 1. The methods are processed first and then the properties.
        $result = $parameterTransformer->objectTransform(new MethodsFistTestClass(), [
            'hello' => 'test',
            'world' => 'case',
        ], (new Configuration()));
        $this->assertEquals('hello', $result->hello);
        $this->assertEquals('world', $result->world);

        $result = $parameterTransformer->objectTransform(new MethodsFistTestClass(), [
            'hello' => 'test',
            'world' => 'case',
        ], (new Configuration())->setMethodsFirst(false));
        $this->assertEquals('test', $result->hello);
        $this->assertEquals('case', $result->world);

        // 2. All public methods that have exactly one parameter are processed
        // until the complete input array is processed.
        $result = $parameterTransformer->objectTransform(new AccessCountTestClass(), [
            'hello' => 'test',
            'world' => 'case',
        ], (new Configuration()));
        $this->assertEquals(1, $result->hello);
        $this->assertEquals(1, $result->world);

        $result = $parameterTransformer->objectTransform(new AccessCountTestClass(), [
            'hello' => 'test',
            'world' => 'case',
            'two' => 14,
            'fold' => 42,
        ], (new Configuration()));
        $this->assertEquals(1, $result->hello);
        $this->assertEquals(1, $result->world);
        $this->assertEquals(0, $result->twofold);

        $result = $parameterTransformer->objectTransform(new AccessCountTestClass(), [
            'hello' => 'test',
            'world' => 'case',
            'two' => 14,
            'fold' => 42,
        ], (new Configuration())->setMaxParameterCount(2));
        $this->assertEquals(1, $result->hello);
        $this->assertEquals(1, $result->world);
        $this->assertEquals(1, $result->twofold);

        $result = $parameterTransformer->objectTransform(new AccessCountTestClass(), [
            'hello' => 'test',
            'world' => 'case',
            'two' => 14,
            'fold' => 42,
        ], (new Configuration())->setFilterMethods(ReflectionMethod::IS_PUBLIC | ReflectionMethod::IS_PROTECTED));
        $this->assertEquals(1, $result->hello);
        $this->assertEquals(1, $result->world);
        $this->assertEquals(0, $result->twofold);
        $this->assertEquals(1, $result->two);
        $this->assertEquals(1, $result->fold);

        $aCTC = new AccessCountTestClass();
        $aCTC->hello = 42;
        $aCTC->world = 42;
        $aCTC->twofold = 42;
        $aCTC->two = 42;
        $aCTC->fold = 42;
        $result = $parameterTransformer->objectTransform($aCTC, [
        ], (new Configuration())->setNoInputIsAllowed(true));
        $this->assertEquals(42, $result->hello);
        $this->assertEquals(42, $result->world);
        $this->assertEquals(42, $result->twofold);
        $this->assertEquals(42, $result->two);
        $this->assertEquals(42, $result->fold);

        $aCTC = new AccessCountTestClass();
        $aCTC->hello = 42;
        $aCTC->world = 42;
        $aCTC->twofold = 42;
        $aCTC->two = 42;
        $aCTC->fold = 42;
        $result = $parameterTransformer->objectTransform(new AccessCountTestClass(), [
            'two' => 55,
            'public' => 42,
            'protected' => 42,
        ], (new Configuration())->setFilterProperties(ReflectionProperty::IS_PROTECTED));
        $this->assertEquals(0, $result->hello);
        $this->assertEquals(0, $result->world);
        $this->assertEquals(0, $result->twofold);
        $this->assertEquals(0, $result->two);
        $this->assertEquals(0, $result->fold);
        $this->assertEquals(0, $result->public);
        $this->assertEquals(42, $result->getProtected());

        $aCTC = new AccessCountTestClass();
        $aCTC->hello = 42;
        $aCTC->world = 42;
        $aCTC->twofold = 42;
        $aCTC->two = 42;
        $aCTC->fold = 42;
        $aCTC->public = 14;
        $result = $parameterTransformer->objectTransform($aCTC, [
            'two' => 55,
            'public' => 42,
            'protected' => 42,
        ], (new Configuration())->setUsePropertyTransformation(false));
        $this->assertEquals(42, $result->hello);
        $this->assertEquals(42, $result->world);
        $this->assertEquals(42, $result->twofold);
        $this->assertEquals(42, $result->two);
        $this->assertEquals(42, $result->fold);
        $this->assertEquals(14, $result->public);
        $this->assertEquals(0, $result->getProtected());
    }
}
