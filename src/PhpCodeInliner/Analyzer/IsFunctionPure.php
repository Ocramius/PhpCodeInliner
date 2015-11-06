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

namespace PhpCodeInliner\Analyzer;

use PhpCodeInliner\Analyzer\Visitor\ReturnStatementLocatorVisitor;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\FunctionLike;
use PhpParser\NodeTraverser;

final class IsFunctionPure
{
    public function __invoke(FunctionLike $function) : bool
    {
        if ($this->getByRefParameters($function) || $this->getByRefUseParameters($function)) {
            return false;
        }

        return true;
    }

    private function getReturnNodes(FunctionLike $function)
    {
        $traverser     = new NodeTraverser();
        $returnLocator = new ReturnStatementLocatorVisitor();

        $traverser->addVisitor(new ReturnStatementLocatorVisitor());
        $traverser->traverse([$function]);

        return $returnLocator;
    }

    /**
     * @param FunctionLike $function
     *
     * @return string[]
     */
    private function getByRefParameters(FunctionLike $function) : array
    {
        $byRefParams = [];

        foreach ($function->getParams() as $param) {
            if ($param->byRef) {
                $byRefParams[] = $param->name;
            }
        }

        return $byRefParams;
    }

    /**
     * @param FunctionLike $function
     *
     * @return string[]
     */
    private function getByRefUseParameters(FunctionLike $function) : array
    {
        if (! $function instanceof Closure) {
            return [];
        }

        $byRefUses = [];

        foreach ($function->uses as $closureUse) {
            if ($closureUse->byRef) {
                $byRefUses[] = $closureUse->var;
            }
        }

        return $byRefUses;
    }
}
