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
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Return_;
use PhpParser\Node\Stmt\StaticVar;
use PhpParser\NodeVisitorAbstract;

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
     * @var Node|null
     */
    private $operation;

    /**
     * VariableAccess constructor.
     *
     * @param Variable|StaticVar $variable
     * @param Node|null          $operation
     */
    private function __construct($variable, Node $operation = null)
    {
        $this->variable  = $variable;
        $this->operation = $operation;
    }

    /**
     * @param Variable|StaticVar $variable
     * @param Node|null          $operation
     *
     * @return self
     *
     * @throws \InvalidArgumentException
     */
    public static function fromVariableAndOperation($variable, Node $operation = null)
    {
        if (! ($variable instanceof Variable || $variable instanceof StaticVar)) {
            throw new \InvalidArgumentException(sprintf(
                'Provided $variable must be one of %s or %s, %s given',
                Variable::class,
                StaticVar::class,
                is_object($variable) ? get_class($variable) : gettype($variable)
            ));
        }

        return new self($variable, $operation);
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

        // @todo any of the following operations on array sub-keys is also to be banished, so we need to consider
        //       any operation on any array key as on "mixed" and re-run this check.
        if (null === $this->operation) {
            return false;
        }

        $isScalar = $this->isScalarType($variableTypes);

        if ($this->operation instanceof Node\Expr\Cast\String_ && ! $isScalar) {
            return true;
        }

        if ($this->operation instanceof Node\Expr\Cast) {
            return false;
        }

        if ($this->operation instanceof Node\Expr\Cast\String_ && $isScalar) {
            return false;
        }

        if ($this->operation instanceof Node\Expr\ArrayDimFetch && $isScalar) {
            return false;
        }

        if (
            (
                $this->operation instanceof Node\Expr\BinaryOp\Concat
                || $this->operation instanceof Node\Expr\AssignOp\Concat
            )
            && ! $isScalar) {
            return true;
        }

        if (
            $this->operation instanceof Node\Expr\BinaryOp
            || $this->operation instanceof Node\Expr\AssignOp
        ) {
            return false;
        }

        return ! ($this->operation instanceof Return_ || $this->operation instanceof Node\Expr\Assign);
    }

    private function isScalarType(array $variableTypes) : bool
    {
        if (! isset($variableTypes[$this->variable->name])) {
            return false;
        }

        return in_array(strtolower($variableTypes[$this->variable->name]), self::SCALAR_TYPES, true);
    }
}
