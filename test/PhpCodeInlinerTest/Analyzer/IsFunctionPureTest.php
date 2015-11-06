<?php
/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the MIT license.
 */

declare(strict_types=1);

namespace PhpCodeInlinerTest\Analyzer;

use PhpCodeInliner\Analyzer\IsFunctionPure;
use PhpParser\Node\FunctionLike;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\ParserFactory;
use PHPUnit_Framework_TestCase;
use SuperClosure\Analyzer\Visitor\ClosureLocatorVisitor;

final class IsFunctionPureTest extends PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider pureFunctionsProvider
     */
    public function testPureFunction(callable $function)
    {
        self::assertTrue($this->buildFunctionPure()->__invoke($this->getFunctionAst($function)));
    }

    /**
     * @dataProvider nonPureFunctionsProvider
     */
    public function testNonPureFunction(callable $function)
    {
        self::assertFalse($this->buildFunctionPure()->__invoke($this->getFunctionAst($function)));
    }

    /**
     * Data provider
     */
    public function pureFunctionsProvider() : array
    {
        $baz = null;

        return [
            'empty function' => [function () {
            }],
            'empty function with parameters' => [function ($foo, $bar) {
            }],
            'empty function with by-ref parameters' => [function (& $foo, & $bar) {
            }],
            'empty function with use statement' => [function (& $foo, & $bar) use ($baz) {
            }],
            'function with constant return value' => [function () {
                return 'baz';
            }],
            'function with return constant expression' => [function () {
                return 1 + 1;
            }],
            'function with return value being a parameter' => [function ($returned) {
                return $returned;
            }],
            'function with return value being an expression with the parameters' => [function ($a, $b) {
                return $a + $b;
            }],
            'function with return value being an use statement value' => [function () use ($baz) {
                return $baz;
            }],
            'function with string cast of string type' => [function (string $foo) {
                return (string) $foo;
            }],
            'function with int cast of int type' => [function (int $foo) {
                return (int) $foo;
            }],
            'function with int cast of unknown type' => [function ($foo) {
                return (int) $foo;
            }],
            'function with int cast of object type' => [function ($foo) {
                return (int) $foo;
            }],
            'function with array cast of unknown type' => [function ($foo) {
                return (array) $foo;
            }],
            'function with array cast of object type' => [function (\stdClass $foo) {
                return (array) $foo;
            }],
            'function with concatenation of string type' => [function (string $foo) {
                return $foo . 'bar';
            }],
            'function with concatenation of int type' => [function (int $foo) {
                return $foo . 'bar';
            }],
            'function with multiplication of int type' => [function (int $foo) {
                return $foo * 2;
            }],
            'function with multiplication of string type' => [function (string $foo) {
                return $foo * 2;
            }],
            'function with multiplication of unknown type' => [function ($foo) {
                return $foo * 2;
            }],
            'function with multiplication of object type' => [function (\stdClass $foo) {
                return $foo * 2;
            }],
        ];
    }

    /**
     * Data provider
     */
    public function nonPureFunctionsProvider() : array
    {
        $baz = null;

        return [
            'function with return value being a by-ref use statement parameter' => [function () use (& $baz) {
                return $baz;
            }],
            'function with by-ref return value being a by-ref parameter' => [function & (& $baz) {
                return $baz;
            }],
            'function with by-ref parameter being overwritten' => [function (& $baz) {
                $baz = 'foo';
            }],
            'function with by-ref use statement being overwritten' => [function () use (& $baz) {
                $baz = 'foo';
            }],
            'function with string cast of unknown type' => [function ($foo) {
                return (string) $foo;
            }],
            'function with string cast of object type' => [function (\stdClass $foo) {
                return (string) $foo;
            }],
            'function with concatenation of unknown type' => [function ($foo) {
                return $foo . 'bar';
            }],
            'function with concatenation of object type' => [function (\stdClass $foo) {
                return $foo . 'bar';
            }],
        ];
    }

    private function buildFunctionPure() : IsFunctionPure
    {
        return new IsFunctionPure();
    }

    private function getFunctionAst(callable $function) : FunctionLike
    {
        $reflection = new \ReflectionFunction($function);

        if (! $sourceFile = $reflection->getFileName()) {
            throw new \UnexpectedValueException(sprintf(
                'Reflection function "%s" source file not found',
                $reflection->getName()
            ));
        }

        $locator   = new ClosureLocatorVisitor($reflection);
        $traverser = new NodeTraverser();

        $traverser->addVisitor(new NameResolver());
        $traverser->addVisitor($locator);

        $traverser->traverse(
            (new ParserFactory())
                ->create(ParserFactory::ONLY_PHP7)
                ->parse(file_get_contents($sourceFile))
        );

        return $locator->closureNode;
    }
}
