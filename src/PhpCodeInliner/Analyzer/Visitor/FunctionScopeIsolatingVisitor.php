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
final class FunctionScopeIsolatingVisitor implements NodeVisitor
{
    /**
     * @var Class_|Closure
     */
    private $currentSubScope;

    /**
     * @var NodeVisitor
     */
    private $wrappedVisitor;

    /**
     * FunctionScopeIsolatingVisitor constructor.
     *
     * @param NodeVisitor $wrappedVisitor
     */
    public function __construct(NodeVisitor $wrappedVisitor)
    {
        $this->wrappedVisitor = $wrappedVisitor;
    }

    /**
     * {@inheritDoc}
     */
    public function beforeTraverse(array $nodes)
    {
        $this->currentSubScope = null;

        return $this->wrappedVisitor->beforeTraverse($nodes);
    }

    /**
     * {@inheritDoc}
     */
    public function enterNode(Node $node)
    {
        if ($this->currentSubScope) {
            // skip any analysis on nodes that are children of anonymous classes or functions
            return null;
        }

        if ($node instanceof Closure || $node instanceof Class_) {
            $this->currentSubScope = $node;
        }

        return $this->wrappedVisitor->enterNode($node);
    }

    /**
     * {@inheritDoc}
     */
    public function leaveNode(Node $node)
    {
        if ($node === $this->currentSubScope) {
            $this->currentSubScope = null;
        }

        if ($this->currentSubScope) {
            // skip any analysis on nodes that are children of anonymous classes or functions
            return null;
        }

        return $this->wrappedVisitor->leaveNode($node);
    }

    public function afterTraverse(array $nodes)
    {
        return $this->wrappedVisitor->afterTraverse($nodes);
    }
}
