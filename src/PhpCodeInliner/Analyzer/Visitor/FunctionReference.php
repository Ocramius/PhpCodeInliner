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

final class FunctionReference
{
    /**
     * @var string|null
     */
    private $className;

    /**
     * @var string
     */
    private $functionName;

    private function __construct()
    {
    }

    public static function fromClosure(string $functionName) : self
    {
        // @todo incomplete?
        $instance = new self();

        $instance->functionName = $functionName;

        return $instance;
    }

    public static function fromFunctionName(string $functionName) : self
    {
        $instance = new self();

        $instance->functionName = $functionName;

        return $instance;
    }

    public static function fromClassAndMethodName(string $className, string $methodName) : self
    {
        $instance = new self();

        $instance->className    = $className;
        $instance->functionName = $methodName;

        return $instance;
    }

    public function getName() : string
    {
        return ($this->className ? $this->className . '::' : '') . $this->functionName;
    }

    public function getFunctionAst(/* pass in AST locator here */) : Node\FunctionLike
    {
        // @todo to be done
    }
}
