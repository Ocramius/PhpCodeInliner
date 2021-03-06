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

use PhpCodeInliner\Analyzer\Visitor\FunctionScopeIsolatingVisitor;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\NodeVisitor;
use PHPUnit_Framework_TestCase;

/**
 * @covers \PhpCodeInliner\Analyzer\Visitor\FunctionScopeIsolatingVisitor
 */
final class FunctionScopeIsolatingVisitorTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var FunctionScopeIsolatingVisitor
     */
    private $visitor;

    /**
     * @var NodeVisitor|\PHPUnit_Framework_MockObject_MockObject
     */
    private $wrappedVisitor;

    /**
     * {@inhertDoc}
     */
    protected function setUp()
    {
        $this->wrappedVisitor = $this->getMock(NodeVisitor::class);
        $this->visitor        = new FunctionScopeIsolatingVisitor($this->wrappedVisitor);
    }

    public function testBeforeTraverseIsInvokingWrappedVisitor()
    {
        $nodesIn  = [$this->getMock(Node::class)];
        $nodesOut = [$this->getMock(Node::class)];

        $this->wrappedVisitor->expects(self::once())->method('beforeTraverse')->with($nodesIn)->willReturn($nodesOut);

        self::assertSame($nodesOut, $this->visitor->beforeTraverse($nodesIn));
    }

    public function testAfterTraverseIsInvokingWrappedVisitor()
    {
        $nodesIn  = [$this->getMock(Node::class)];
        $nodesOut = [$this->getMock(Node::class)];

        $this->wrappedVisitor->expects(self::once())->method('afterTraverse')->with($nodesIn)->willReturn($nodesOut);

        self::assertSame($nodesOut, $this->visitor->afterTraverse($nodesIn));
    }

    public function testBeforeTraverseIsResettingTheCurrentSubScopeFilter()
    {
        $this->visitor->beforeTraverse([]);
        $this->visitor->enterNode(new Expr\Closure());

        $this->visitor->beforeTraverse([]);

        /* @var $node Node */
        $node = $this->getMock(Node::class);

        $this->wrappedVisitor->expects(self::once())->method('enterNode')->with($node);
        $this->visitor->enterNode($node);
    }

    public function testEnterNodeDispatchesWrappedVisitorEnterNode()
    {
        /* @var $nodeIn Node */
        $nodeIn  = $this->getMock(Node::class);
        $nodeOut = $this->getMock(Node::class);

        $this->wrappedVisitor->expects(self::once())->method('enterNode')->with($nodeIn)->willReturn($nodeOut);

        self::assertSame($nodeOut, $this->visitor->enterNode($nodeIn));
    }

    public function testLeaveNodeDispatchesWrappedVisitorLeaveNode()
    {
        /* @var $nodeIn Node */
        $nodeIn  = $this->getMock(Node::class);
        $nodeOut = $this->getMock(Node::class);

        $this->wrappedVisitor->expects(self::once())->method('leaveNode')->with($nodeIn)->willReturn($nodeOut);

        self::assertSame($nodeOut, $this->visitor->leaveNode($nodeIn));
    }

    /**
     * @dataProvider subScopeNodesProvider
     */
    public function testWillEnterAndExitNodeEvenWhenEnteringAClosureNode(Node $subScopeNode)
    {
        $nodeOut1 = $this->getMock(Node::class);
        $nodeOut2 = $this->getMock(Node::class);

        $this->wrappedVisitor->expects(self::once())->method('enterNode')->with($subScopeNode)->willReturn($nodeOut1);
        $this->wrappedVisitor->expects(self::once())->method('leaveNode')->with($subScopeNode)->willReturn($nodeOut2);

        $this->assertSame($nodeOut1, $this->visitor->enterNode($subScopeNode));
        $this->assertSame($nodeOut2, $this->visitor->leaveNode($subScopeNode));
    }

    /**
     * @dataProvider subScopeNodesProvider
     */
    public function testWillNotEnterNodesLowerThanTheSubScopeNode(Node $subScopeNode)
    {
        /* @var $outerScopeNode Node */
        $outerScopeNode   = $this->getMock(Node::class);
        /* @var $innerScopeNode Node */
        $innerScopeNode   = $this->getMock(Node::class);
        $nodeReplacement1 = $this->getMock(Node::class);
        $nodeReplacement2 = $this->getMock(Node::class);
        $nodeReplacement3 = $this->getMock(Node::class);
        $nodeReplacement4 = $this->getMock(Node::class);

        $this
            ->wrappedVisitor
            ->expects(self::exactly(2))
            ->method('enterNode')
            ->with(self::logicalOr($outerScopeNode, $subScopeNode))
            ->will($this->returnValueMap([
                [$outerScopeNode, $nodeReplacement1],
                [$subScopeNode, $nodeReplacement2],
            ]));

        $this
            ->wrappedVisitor
            ->expects(self::exactly(2))
            ->method('leaveNode')
            ->with(self::logicalOr($outerScopeNode, $subScopeNode))
            ->will($this->returnValueMap([
                [$outerScopeNode, $nodeReplacement3],
                [$subScopeNode, $nodeReplacement4],
            ]));

        $this->assertSame($nodeReplacement1, $this->visitor->enterNode($outerScopeNode));
        $this->assertSame($nodeReplacement2, $this->visitor->enterNode($subScopeNode));
        $this->assertNull($this->visitor->enterNode($innerScopeNode));
        $this->assertNull($this->visitor->leaveNode($innerScopeNode));
        $this->assertSame($nodeReplacement4, $this->visitor->leaveNode($subScopeNode));
        $this->assertSame($nodeReplacement3, $this->visitor->leaveNode($outerScopeNode));
    }

    public function subScopeNodesProvider() : array
    {
        return [
            'anonymous function' => [new Expr\Closure()],
            'anonymous class'    => [new Node\Stmt\Class_(null)],
        ];
    }
}
