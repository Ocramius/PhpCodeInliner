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

namespace PhpCodeInliner\Analyzer\Visitor;

use PhpParser\Node;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Stmt\Return_;
use PhpParser\Node\Stmt\StaticVar;

/**
 * @internal this object is used to represent an access to a variable, and to describe
 *           whether that access may cause side-effects or not (given a variable type)
 */
final class VariableAccess
{
    const GLOBAL_VAR   = 'GLOBALS';
    const SCALAR_TYPES = ['array', 'int', 'float', 'string', 'bool'];

    /**
     * @var Variable|StaticVar
     */
    private $variable;

    /**
     * @var Node[]
     */
    private $parentOperations;

    /**
     * VariableAccess constructor.
     *
     * @param Variable|StaticVar $variable
     * @param Node[]             $parentOperations
     */
    private function __construct($variable, array $parentOperations)
    {
        $this->variable         = $variable;
        $this->parentOperations = $parentOperations;
    }

    /**
     * @param Variable|StaticVar $variable
     * @param Node|null          $operation
     *
     * @return self
     *
     * @throws \InvalidArgumentException
     *
     * @todo split into separate named constructors later on
     */
    public static function fromVariableAndOperation(Variable $variable, Node $operation = null) : self
    {
        if (! ($variable instanceof Variable || $variable instanceof StaticVar)) {
            throw new \InvalidArgumentException(sprintf(
                'Provided $variable must be one of %s or %s, %s given',
                Variable::class,
                StaticVar::class,
                is_object($variable) ? get_class($variable) : gettype($variable)
            ));
        }

        return new self($variable, array_filter([$operation]));
    }

    public static function fromStaticVariableAndOperations(StaticVar $staticVar, Node ...$operations) : self
    {
        return new self($staticVar, $operations);
    }

    /**
     * @param string[] $variableTypes indexed by variable name
     *
     * @return bool
     */
    public function canCauseSideEffects(array $variableTypes) : bool
    {
        if (self::GLOBAL_VAR === $this->variable->name) {
            return true;
        }

        $lastOperation = $this->getLastOperation();

        // @todo any of the following operations on array sub-keys is also to be banished, so we need to consider
        //       any operation on any array key as on "mixed" and re-run this check.
        if (null === $lastOperation) {
            return false;
        }

        $isScalar = $this->isScalarType($variableTypes);

        if ($lastOperation instanceof Node\Expr\Cast\String_ && ! $isScalar) {
            return true;
        }

        if ($lastOperation instanceof Node\Expr\Cast) {
            return false;
        }

        if ($lastOperation instanceof Node\Expr\Cast\String_ && $isScalar) {
            return false;
        }

        if ($lastOperation instanceof Node\Expr\ArrayDimFetch && $isScalar) {
            return false;
        }

        if (
            (
                $lastOperation instanceof Node\Expr\BinaryOp\Concat
                || $lastOperation instanceof Node\Expr\AssignOp\Concat
            )
            && ! $isScalar) {
            return true;
        }

        if (
            $lastOperation instanceof Node\Expr\BinaryOp
            || $lastOperation instanceof Node\Expr\AssignOp
        ) {
            return false;
        }

        return ! ($lastOperation instanceof Return_ || $lastOperation instanceof Node\Expr\Assign);
    }

    private function isScalarType(array $variableTypes) : bool
    {
        if (! isset($variableTypes[$this->variable->name])) {
            return false;
        }

        return in_array(strtolower($variableTypes[$this->variable->name]), self::SCALAR_TYPES, true);
    }

    /**
     * @return Node|null
     */
    private function getLastOperation()
    {
        return end($this->parentOperations) ?: null;
    }
}
