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
    public function testResetsCollectedFunctionCalls()
    {
        $visitor = new FunctionReferenceLocatorVisitor();

        $this->assertEmpty($visitor->getCollectedFunctionCalls());

        $node = new Node\Expr\FuncCall(new Node\Name('foo'));

        $visitor->beforeTraverse([$node]);

        $visitor->enterNode($node);
        $visitor->leaveNode($node);

        $visitor->afterTraverse([$node]);

        $this->assertNotEmpty($visitor->getCollectedFunctionCalls());

        $visitor->beforeTraverse([$node]);

        $this->assertEmpty($visitor->getCollectedFunctionCalls());
    }
}
