<?php

namespace Trademachines\Riemann;

use DrSlump\Protobuf;
use DrSlump\Protobuf\Codec\PhpArray;
use Trademachines\Riemann\Message\Event;
use Trademachines\Riemann\Message\Message;
use Trademachines\Riemann\Transport\TransportInterface;
use Trademachines\Riemann\Transport\UdpSocket;

/**
 * Riemann Client
 */
class Client
{
    /** @var UdpSocket */
    protected $socket;

    /** @var array */
    protected $events = [];

    /** @var int **/
    protected $flushAfter = 20;

    /** @var bool */
    protected $active = true;

    /**
     * @param TransportInterface $socket
     */
    public function __construct(TransportInterface $socket)
    {
        $this->socket = $socket;
        $this->codec = new PhpArray();
    }

    /**
     * Automatically flush before destruction
     */
    public function __destruct()
    {
        $this->flush();
    }

    /**
     * @param int $flushAfter
     */
    public function setFlushAfter($flushAfter)
    {
        $this->flushAfter = $flushAfter;
    }

    /**
     * @param boolean $active
     */
    public function setActive($active)
    {
        $this->active = $active;
    }

    /**
     * @param Event $event
     */
    public function send(Event $event)
    {
        $this->events[] = $event;

        if (count($this->events) >= $this->flushAfter) {
            $this->flush();
        }
    }

    /**
     * @param array $eventData
     */
    public function sendEvent(array $eventData = [])
    {
        $event = new Event();
        $event->parse($eventData, $this->codec);

        $this->send($event);
    }

    /**
     * Flush the event queues and send the data
     *
     * @return int|bool
     */
    public function flush()
    {
        $res = true;

        if (count($this->events)) {
            if ($this->active) {
                $message = new Message();
                $message->events = $this->events;

                $res = $this->socket->write($message->serialize());
            }

            $this->events = [];
        }

        return $res;
    }
}