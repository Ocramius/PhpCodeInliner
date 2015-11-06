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

use PhpCodeInliner\Analyzer\Visitor\ReturnStatementLocatorVisitor;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Const_;
use PhpParser\Node\Stmt\Return_;
use PHPUnit_Framework_TestCase;

/**
 * @covers \PhpCodeInliner\Analyzer\Visitor\ReturnStatementLocatorVisitor
 */
final class ReturnStatementLocatorVisitorTest extends PHPUnit_Framework_TestCase
{
    public function testWillRetrieveReturnStatements()
    {
        $return1 = new Return_();
        $return2 = new Return_();
        $anExpr  = new Const_([]);
        $return3 = new Return_();

        $visitor = new ReturnStatementLocatorVisitor();

        $visitor->beforeTraverse([$return2, $return1, $anExpr, $return3]);
        $visitor->enterNode($return2);
        $visitor->enterNode($return1);
        $visitor->enterNode($anExpr);
        $visitor->enterNode($return3);
        $visitor->leaveNode($return3);
        $visitor->leaveNode($anExpr);
        $visitor->leaveNode($return1);
        $visitor->leaveNode($return2);
        $visitor->afterTraverse([$return2, $return1, $anExpr, $return3]);

        $this->assertSame([$return2, $return1, $return3], $visitor->getFoundReturnStatements());
    }

    public function testWillIgnoreReturnStatementsInClosureNodes()
    {
        $return1  = new Return_();
        $return2  = new Return_();
        $closure1 = new Closure();
        $return3  = new Return_();
        $closure2 = new Closure();
        $return4  = new Return_();
        $return5  = new Return_();

        $visitor = new ReturnStatementLocatorVisitor();

        $visitor->beforeTraverse([$return1, $return2, $closure1, $return3, $closure2, $return4, $return5]);
        $visitor->enterNode($return1);
        $visitor->enterNode($return2);
        $visitor->enterNode($closure1);
        $visitor->enterNode($return3);
        $visitor->enterNode($closure2);
        $visitor->enterNode($return4);
        $visitor->leaveNode($return4);
        $visitor->leaveNode($closure2);
        $visitor->leaveNode($return3);
        $visitor->leaveNode($closure1);
        $visitor->enterNode($return5);
        $visitor->leaveNode($return5);
        $visitor->leaveNode($return2);
        $visitor->leaveNode($return1);
        $visitor->afterTraverse([$return1, $return2, $closure1, $return3, $closure2, $return4, $return5]);

        $this->assertSame([$return1, $return2, $return5], $visitor->getFoundReturnStatements());
    }

    public function testWillIgnoreReturnStatementsInSubClassNodes()
    {
        $return1 = new Return_();
        $return2 = new Return_();
        $class1  = new Class_('foo');
        $return3 = new Return_();
        $class2  = new Class_('bar');
        $return4 = new Return_();
        $return5 = new Return_();

        $visitor = new ReturnStatementLocatorVisitor();

        $visitor->beforeTraverse([$return1, $return2, $class1, $return3, $class2, $return4, $return5]);
        $visitor->enterNode($return1);
        $visitor->enterNode($return2);
        $visitor->enterNode($class1);
        $visitor->enterNode($return3);
        $visitor->enterNode($class2);
        $visitor->enterNode($return4);
        $visitor->leaveNode($return4);
        $visitor->leaveNode($class2);
        $visitor->leaveNode($return3);
        $visitor->leaveNode($class1);
        $visitor->enterNode($return5);
        $visitor->leaveNode($return5);
        $visitor->leaveNode($return2);
        $visitor->leaveNode($return1);
        $visitor->afterTraverse([$return1, $return2, $class1, $return3, $class2, $return4, $return5]);

        $this->assertSame([$return1, $return2, $return5], $visitor->getFoundReturnStatements());
    }
}
