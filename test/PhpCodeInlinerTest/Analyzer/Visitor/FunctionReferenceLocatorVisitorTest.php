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

use PhpCodeInliner\Analyzer\Visitor\FunctionReferenceLocatorVisitor;
use PhpParser\Node;
use PhpParser\NodeVisitor;
use PHPUnit_Framework_TestCase;

/**
 * @covers \PhpCodeInliner\Analyzer\Visitor\FunctionReferenceLocatorVisitor
 */
final class FunctionReferenceLocatorVisitorTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var FunctionReferenceLocatorVisitor
     */
    private $visitor;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->visitor = new FunctionReferenceLocatorVisitor();
    }

    public function testResetsCollectedFunctionCalls()
    {
        $this->assertEmpty($this->visitor->getCollectedFunctionCalls());

        $node = new Node\Expr\FuncCall(new Node\Name('foo'));

        $this->visitor->beforeTraverse([$node]);

        $this->visitor->enterNode($node);
        $this->visitor->leaveNode($node);

        $this->visitor->afterTraverse([$node]);

        $this->assertNotEmpty($this->visitor->getCollectedFunctionCalls());

        $this->visitor->beforeTraverse([$node]);

        $this->assertEmpty($this->visitor->getCollectedFunctionCalls());
    }

    public function testWillCollectFunctionCalls()
    {
        $node1 = new Node\Expr\FuncCall(new Node\Name('foo'));
        $node2 = new Node\Expr\MethodCall(new Node\Expr\Variable('foo'), new Node\Name('bar'));
        $node3 = new Node\Expr\StaticCall(new Node\Name('Foo'), new Node\Name('bar'));

        $this->visitor->enterNode($node1);
        $this->visitor->enterNode($node2);
        $this->visitor->enterNode($node3);
        $this->visitor->leaveNode($node3);
        $this->visitor->leaveNode($node2);
        $this->visitor->leaveNode($node1);

        $this->assertCount(3, $this->visitor->getCollectedFunctionCalls());
    }
}
