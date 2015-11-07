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
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Stmt\Return_;
use PhpParser\Node\Stmt\StaticVar;

/**
 * @internal this object is used to represent a function/method call, and to resolve to
 *           that function/method call body (when possible)
 *
 * @todo may split into 3 separate VOs
 */
final class FunctionCall
{
    /**
     * @var StaticCall|null
     */
    private $staticCall;

    /**
     * @var MethodCall|null
     */
    private $instanceCall;

    /**
     * @var FuncCall|null
     */
    private $funcCall;

    private function __construct()
    {
    }

    public static function fromStaticCall(StaticCall $staticCall) : self
    {
        $instance = new self();

        $instance->staticCall = $staticCall;

        return $instance;
    }

    public static function fromInstanceCall(MethodCall $instanceCall) : self
    {
        $instance = new self();

        $instance->instanceCall = $instanceCall;

        return $instance;
    }

    public static function fromFunctionCall(FuncCall $funcCall) : self
    {
        $instance = new self();

        $instance->funcCall = $funcCall;

        return $instance;
    }

    /**
     * @param array $variableValueTypes
     *
     * @return null|FunctionReference
     */
    public function buildReference(array $variableValueTypes)
    {
        return $this->resolveFunctionReference($variableValueTypes)
            ?? $this->resolveStaticFunctionCall($variableValueTypes)
            ?? $this->resolveInstanceCall($variableValueTypes);
    }

    /**
     * @param array $variableValueTypes
     *
     * @return null|FunctionReference
     */
    private function resolveFunctionReference(array $variableValueTypes)
    {
        if (! $this->funcCall) {
            return null;
        }

        $functionName = $this->funcCall->name;

        if (
            $functionName instanceof Variable
            && ($functionVariableName = $functionName->name)
            && isset($variableValueTypes[$functionVariableName])
        ) {
            // @todo wtf are `$variableValueTypes` here then? Should they be maps of reflection references?
            return FunctionReference::fromFunctionName($variableValueTypes[$functionVariableName]);
        }

        if ($functionName instanceof Node\Name) {
            return FunctionReference::fromFunctionName((string) $functionName);
        }
    }

    /**
     * @return null|FunctionReference
     */
    private function resolveStaticFunctionCall(array $variableValueTypes)
    {
        if (! $this->staticCall) {
            return null;
        }

        $class = $this->staticCall->class;
        $name  = $this->staticCall->name;

        // note: we currently do not support expression evaluation
        if (! ($class instanceof Node\Name && is_string($name))) {
            return null;
        }

        return FunctionReference::fromClassAndMethodName((string) $class, $name);
    }

    /**
     * @return null|FunctionReference
     */
    private function resolveInstanceCall(array $variableValueTypes)
    {
        if (! $this->instanceCall) {
            return null;
        }

        // note: we currently do not support expression evaluation, and only support `$this`

        $object     = $this->instanceCall->var;
        $methodName = $this->instanceCall->name;

        if (! ($object instanceof Variable && is_string($methodName))) {
            return null;
        }

        $objectType = $variableValueTypes[$object->name] ?? null;

        if (! is_string($objectType)) {
            return null;
        }

        return FunctionReference::fromClassAndMethodName($objectType, $methodName);
    }
}
