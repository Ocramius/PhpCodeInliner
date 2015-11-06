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
use PhpParser\Node\Stmt\Return_;
use PhpParser\NodeVisitorAbstract;

/**
 * Visitor responsible for locating any usage of the `return` statement inside of a portion of code
 *
 * Supposed to be used with a {@see \PhpParser\NodeTraverserInterface} instance
 */
final class ReturnStatementLocatorVisitor extends NodeVisitorAbstract
{
    /**
     * @var Class_|Closure
     */
    private $currentAnonymousConstruct;

    /**
     * @var Return_[]
     */
    private $foundReturnStatements = [];

    /**
     * {@inheritDoc}
     */
    public function beforeTraverse(array $nodes)
    {
        $this->currentAnonymousConstruct = null;
        $this->foundReturnStatements     = [];
    }

    /**
     * {@inheritDoc}
     */
    public function enterNode(Node $node)
    {
        if ($this->currentAnonymousConstruct) {
            // skip any analysis on nodes that are children of anonymous classes or functions
            return;
        }

        if ($node instanceof Closure || $node instanceof Class_) {
            $this->currentAnonymousConstruct = $node;

            return;
        }

        if ($node instanceof Return_) {
            $this->foundReturnStatements[] = $node;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function leaveNode(Node $node)
    {
        if ($node === $this->currentAnonymousConstruct) {
            $this->currentAnonymousConstruct = null;
        }
    }

    /**
     * @return Return_[]
     */
    public function getFoundReturnStatements()
    {
        return $this->foundReturnStatements;
    }
}
