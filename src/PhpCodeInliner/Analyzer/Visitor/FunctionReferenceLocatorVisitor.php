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
use PhpParser\Node\Stmt\Class_;
use PhpParser\NodeVisitor;

/**
 * Visitor that wraps around given visitors, and filters the traversal so that only the current scope
 * is considered in the traversal
 */
final class FunctionReferenceLocatorVisitor implements NodeVisitor
{
    private $functionReferences = [];

    /**
     * {@inheritDoc}
     */
    public function beforeTraverse(array $nodes)
    {
        $this->functionReferences = [];
    }

    /**
     * {@inheritDoc}
     */
    public function enterNode(Node $node)
    {
        if ($node instanceof Node\Expr\FuncCall) {
            $this->functionReferences = FunctionCall::fromFunctionCall($node);
        }

        if ($node instanceof Node\Expr\MethodCall) {
            $this->functionReferences = FunctionCall::fromInstanceCall($node);
        }

        if ($node instanceof Node\Expr\StaticCall) {
            $this->functionReferences = FunctionCall::fromStaticCall($node);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function leaveNode(Node $node)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function afterTraverse(array $nodes)
    {
    }
}
