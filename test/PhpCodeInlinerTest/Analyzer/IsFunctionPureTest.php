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
