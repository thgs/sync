<?php

namespace Amp\Sync;

use Amp\ByteStream\Pipe;
use Amp\ByteStream\ReadableStream;
use Amp\ByteStream\StreamException;
use Amp\ByteStream\WritableStream;
use Amp\PHPUnit\AsyncTestCase;

class StreamChannelTest extends AsyncTestCase
{
    public function testSendReceive()
    {
        $pipe = new Pipe(0);
        $channel = new StreamChannel($pipe->getSource(), $pipe->getSink());

        $message = 'hello';

        $channel->send($message);
        $data = $channel->receive();
        $this->assertSame($message, $data);
    }

    /**
     * @depends testSendReceive
     */
    public function testSendReceiveLongData()
    {
        $pipe = new Pipe(0);
        $channel = new StreamChannel($pipe->getSource(), $pipe->getSink());

        $length = 0xffff;
        $message = '';
        for ($i = 0; $i < $length; ++$i) {
            $message .= \chr(\mt_rand(0, 255));
        }

        $channel->send($message);
        $data = $channel->receive();
        $this->assertSame($message, $data);
    }

    /**
     * @depends testSendReceive
     */
    public function testInvalidDataReceived()
    {
        $this->expectException(ChannelException::class);

        $pipe = new Pipe(0);
        $sink = $pipe->getSink();
        $channel = new StreamChannel($pipe->getSource(), $sink);

        // Close $a. $b should close on next read...
        $sink->write(\pack('L', 10) . '1234567890');
        $data = $channel->receive();
    }

    /**
     * @depends testSendReceive
     */
    public function testSendUnserializableData()
    {
        $this->expectException(ChannelException::class);

        $pipe = new Pipe(0);
        $sink = $pipe->getSink();
        $channel = new StreamChannel($pipe->getSource(), $sink);

        // Close $a. $b should close on next read...
        $channel->send(fn () => null);
        $data = $channel->receive();
    }

    /**
     * @depends testSendReceive
     */
    public function testSendAfterClose()
    {
        $this->expectException(ChannelException::class);

        $mock = $this->createMock(WritableStream::class);
        $mock->expects($this->once())
            ->method('write')
            ->will($this->throwException(new StreamException));

        $a = new StreamChannel($this->createMock(ReadableStream::class), $mock);
        $b = new StreamChannel(
            $this->createMock(ReadableStream::class),
            $this->createMock(WritableStream::class)
        );

        $a->send('hello');
    }

    /**
     * @depends testSendReceive
     */
    public function testReceiveAfterClose()
    {
        $mock = $this->createMock(ReadableStream::class);
        $mock->expects($this->once())
            ->method('read')
            ->willReturn(null);

        $a = new StreamChannel($mock, $this->createMock(WritableStream::class));

        $this->expectException(ChannelException::class);
        $this->expectExceptionMessage('channel closed');
        $a->receive();
    }
}
