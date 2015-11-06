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

use PhpCodeInliner\Analyzer\Visitor\VariableAccess;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\BinaryOp\Mul;
use PhpParser\Node\Expr\Variable;
use PHPUnit_Framework_TestCase;

/**
 * @covers \PhpCodeInliner\Analyzer\Visitor\VariableAccess
 */
final class VariableAccessTest extends PHPUnit_Framework_TestCase
{
    public static function testVariableAccessConstructor()
    {
        self::assertInstanceOf(
            VariableAccess::class,
            VariableAccess::fromVariableAndOperation(new Variable('foo'))
        );
        self::assertInstanceOf(
            VariableAccess::class,
            VariableAccess::fromVariableAndOperation(
                new Variable('foo'),
                new Mul(new Variable('foo'), new Variable('bar'))
            )
        );
    }

    /**
     * @dataProvider sideEffectCasesProvider
     */
    public function testChecksIfAccessCanCauseSideEffects(
        bool $mayCauseSideEffects,
        array $variableTypes,
        string $varName,
        Node $operation = null
    ) {
        self::assertSame(
            $mayCauseSideEffects,
            VariableAccess::fromVariableAndOperation(new Variable($varName), $operation)
                ->canCauseSideEffects($variableTypes)
        );
    }

    public function sideEffectCasesProvider() : array
    {
        return [
            'simple variable access, mixed type (implicit)' => [
                false,
                [],
                'foo',
            ],
            'simple variable access, mixed type (explicit)' => [
                false,
                ['foo' => null],
                'foo',
            ],
            'simple variable access, int type (explicit)' => [
                false,
                ['foo' => 'int'],
                'foo',
            ],
            'simple variable access, object type (explicit)' => [
                false,
                ['foo' => 'stdClass'],
                'foo',
            ],
            'string cast expression, string type (explicit)' => [
                false,
                ['foo' => 'string'],
                'foo',
                new Expr\Cast\String_(new Variable('foo')),
            ],
            'string cast expression, int type (explicit)' => [
                false,
                ['foo' => 'int'],
                'foo',
                new Expr\Cast\String_(new Variable('foo')),
            ],
            'string cast expression, float type (explicit)' => [
                false,
                ['foo' => 'float'],
                'foo',
                new Expr\Cast\String_(new Variable('foo')),
            ],
            'string cast expression, bool type (explicit)' => [
                false,
                ['foo' => 'bool'],
                'foo',
                new Expr\Cast\String_(new Variable('foo')),
            ],
            'string cast expression, mixed type (explicit)' => [
                true,
                ['foo' => null],
                'foo',
                new Expr\Cast\String_(new Variable('foo')),
            ],
            'string cast expression, mixed type (implicit)' => [
                true,
                [],
                'foo',
                new Expr\Cast\String_(new Variable('foo')),
            ],
            'string cast expression, object type (explicit)' => [
                true,
                ['foo' => 'stdClass'],
                'foo',
                new Expr\Cast\String_(new Variable('foo')),
            ],
            'property fetch expression, object type (explicit)' => [
                true,
                ['foo' => 'stdClass'],
                'foo',
                new Expr\PropertyFetch(new Variable('foo'), 'bar'),
            ],
            'property fetch expression, scalar type (explicit)' => [
                true,
                ['foo' => 'int'],
                'foo',
                new Expr\PropertyFetch(new Variable('foo'), 'bar'),
            ],
            'array key access expression, object type (explicit)' => [
                true,
                ['foo' => 'stdClass'],
                'foo',
                new Expr\ArrayDimFetch(new Variable('foo'), 'bar'),
            ],
        ];
    }
}
