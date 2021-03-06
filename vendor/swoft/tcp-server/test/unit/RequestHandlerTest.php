<?php declare(strict_types=1);

namespace SwoftTest\Tcp\Server\Unit;

use PHPUnit\Framework\TestCase;
use Swoft\Tcp\Server\Exception\TcpMiddlewareException;
use Swoft\Tcp\Server\RequestHandler;
use Swoft\Tcp\Server\Request;
use SwoftTest\Tcp\Server\Testing\Middleware\CoreMiddleware;
use SwoftTest\Tcp\Server\Testing\Middleware\User1Middleware;
use SwoftTest\Tcp\Server\Testing\Middleware\User2Middleware;

/**
 * Class RequestHandlerTest
 */
class RequestHandlerTest extends TestCase
{
    /**
     * @throws TcpMiddlewareException
     */
    public function testRun(): void
    {
        $coreMdl = new CoreMiddleware();

        $mc = RequestHandler::new($coreMdl);
        $mc->middle(new User1Middleware());
        $mc->add(new User2Middleware());

        $req  = Request::new(1, '', 1);
        $resp = $mc->run($req);

        // $this->assertSame(100, $resp->getSender());
        $this->assertSame('>user1 >user2 [CORE] user2> user1>', $resp->getData());
    }
}
