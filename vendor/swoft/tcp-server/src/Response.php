<?php declare(strict_types=1);

namespace Swoft\Tcp\Server;

use Swoft;
use Swoft\Bean\Annotation\Mapping\Bean;
use Swoft\Log\Helper\CLog;
use Swoft\Tcp\Protocol;
use Swoft\Tcp\Response as TcpResponse;
use Swoft\Tcp\Server\Contract\ResponseInterface;
use Swoft\Tcp\Server\Exception\TcpResponseException;
use Swoole\Server;

/**
 * Class Response
 *
 * @since 2.0
 * @Bean(name="tcpResponse", scope=Bean::PROTOTYPE)
 */
class Response extends TcpResponse implements ResponseInterface
{
    /**
     * Response fd
     *
     * @var int
     */
    private $fd = -1;

    /**
     * Request fd
     *
     * @var int
     */
    private $reqFd = -1;

    /**
     * Mark whether the response has been sent
     *
     * @var bool
     */
    private $sent = false;

    /**
     * @param int $fd
     *
     * @return self|TcpResponse
     */
    public static function new(int $fd = -1): TcpResponse
    {
        /** @var self $self */
        $self = Swoft::getBean('tcpResponse');

        // Set properties
        $self->fd    = $fd;
        $self->sent  = false;
        $self->reqFd = $fd;

        return $self;
    }

    /**
     * @param Server|null $server
     *
     * @return int
     * @throws TcpResponseException
     */
    public function send(Server $server = null): int
    {
        // Deny repeat call send.
        // But if you want send again, you can call `setSent(false)` before call it.
        if ($this->sent) {
            return 0;
        }

        $this->sent = true;

        // Content is empty, skip send
        if ($this->isEmpty()) {
            CLog::warning('cannot send empty content to tcp client');
            return 0;
        }

        /** @var Protocol $protocol */
        $protocol = Swoft::getBean('tcpServerProtocol');
        $content  = $protocol->packResponse($this);

        $server = $server ?: Swoft::server()->getSwooleServer();

        // Trigger event before push message content to client
        Swoft::trigger(TcpServerEvent::CONTENT_SEND, $server, $content, $this);

        // Do send content
        if ($server->send($this->fd, $content) === false) {
            $code = $server->getLastError();
            throw new TcpResponseException("Error on send data to client #{$this->fd}", $code);
        }

        return 1;
    }

    /**
     * @return int
     */
    public function getReqFd(): int
    {
        return $this->reqFd;
    }

    /**
     * @return bool
     */
    public function isSent(): bool
    {
        return $this->sent;
    }

    /**
     * @param bool $sent
     */
    public function setSent(bool $sent): void
    {
        $this->sent = $sent;
    }

    /**
     * @return int
     */
    public function getFd(): int
    {
        return $this->fd;
    }

    /**
     * @param int $fd
     */
    public function setFd(int $fd): void
    {
        $this->fd = $fd;
    }
}
