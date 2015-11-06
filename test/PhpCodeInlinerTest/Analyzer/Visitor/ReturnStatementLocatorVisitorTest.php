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
use PhpParser\Node\Stmt\Const_;
use PhpParser\Node\Stmt\Return_;
use PHPUnit_Framework_TestCase;

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
}
