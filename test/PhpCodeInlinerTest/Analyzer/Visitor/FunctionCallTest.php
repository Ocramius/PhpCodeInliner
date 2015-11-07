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

namespace PhpCodeInlinerTest\Analyzer\Visitor;

use PhpCodeInliner\Analyzer\Visitor\FunctionCall;
use PhpCodeInliner\Analyzer\Visitor\FunctionReference;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PHPUnit_Framework_TestCase;

/**
 * @covers \PhpCodeInliner\Analyzer\Visitor\FunctionCall
 */
final class FunctionCallTest extends PHPUnit_Framework_TestCase
{
    public function testConstructFromFuncCall()
    {
        self::assertInstanceOf(
            FunctionCall::class,
            FunctionCall::fromFunctionCall(new Expr\FuncCall(new Node\Name('foo')))
        );
    }

    public function testConstructFromStaticCall()
    {
        self::assertInstanceOf(
            FunctionCall::class,
            FunctionCall::fromStaticCall(new Expr\StaticCall(new Node\Name('Foo'), 'bar'))
        );
    }

    public function testConstructFromMethodCall()
    {
        self::assertInstanceOf(
            FunctionCall::class,
            FunctionCall::fromInstanceCall(new Expr\MethodCall(new Expr\Variable('foo'), 'bar'))
        );
    }

    /**
     * @dataProvider resolvedCallsDataProvider
     */
    public function testWillEvaluateFunctionRelativeName(Expr $node, array $variableValues, array $expectedType)
    {
        $functionCall = null;

        if ($node instanceof Expr\FuncCall) {
            $functionCall = FunctionCall::fromFunctionCall($node);
        }

        if ($node instanceof Expr\StaticCall) {
            $functionCall = FunctionCall::fromStaticCall($node);
        }

        if ($node instanceof Expr\MethodCall) {
            $functionCall = FunctionCall::fromInstanceCall($node);
        }

        if (! $functionCall) {
            throw new \InvalidArgumentException(sprintf('Unrecognized node of type "%s"', get_class($node)));
        }

        if (! $expectedType) {
            $this->assertNull($functionCall->buildReference($variableValues));

            return;
        }

        $reference = $functionCall->buildReference($variableValues);

        $this->assertInstanceOf(FunctionReference::class, $reference);
        $this->assertSame(implode('::', $expectedType), $reference->getName());
    }

    public function resolvedCallsDataProvider()
    {
        return [
            'static call with string class name' => [
                new Expr\StaticCall(new Node\Name('Foo'), 'bar'),
                [],
                ['Foo', 'bar'],
            ],
        ];
    }
}
