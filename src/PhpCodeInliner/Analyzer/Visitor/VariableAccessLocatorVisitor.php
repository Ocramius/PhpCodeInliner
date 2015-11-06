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
use PhpParser\NodeVisitorAbstract;

/**
 * @todo test coverage needed
 */
final class VariableAccessLocatorVisitor extends NodeVisitorAbstract
{
    /**
     * @var Node[]
     */
    private $lastVisitedNodes = [];

    /**
     * @var VariableAccess[]
     */
    private $foundVariableAccesses = [];

    /**
     * {@inheritDoc}
     */
    public function beforeTraverse(array $nodes)
    {
        $this->foundVariableAccesses     = [];
    }

    /**
     * {@inheritDoc}
     */
    public function enterNode(Node $node)
    {
        if ($node instanceof Variable) {
            $this->foundVariableAccesses[] = VariableAccess::fromVariableAndOperation(
                $node,
                end($this->lastVisitedNodes) ?: null
            );
        }

        $this->lastVisitedNodes[] = $node;
    }

    /**
     * {@inheritDoc}
     */
    public function leaveNode(Node $node)
    {
        array_pop($this->lastVisitedNodes);
    }

    /**
     * @return VariableAccess[]
     */
    public function getFoundVariableAccesses()
    {
        return $this->foundVariableAccesses;
    }
}
