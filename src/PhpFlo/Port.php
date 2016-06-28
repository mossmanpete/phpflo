<?php
/*
 * This file is part of the phpflo/phpflo package.
 *
 * (c) Henri Bergius <henri.bergius@iki.fi>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PhpFlo;

use PhpFlo\Exception\InvalidDefinitionException;
use PhpFlo\Exception\InvalidTypeException;
use PhpFlo\Exception\PortException;
use PhpFlo\Exception\SocketException;

/**
 * Class Port
 *
 * @package PhpFlo
 * @author Henri Bergius <henri.bergius@iki.fi>
 */
final class Port extends AbstractPort
{
    /**
     * @param SocketInterface $socket
     * @throws InvalidDefinitionException
     */
    public function attach(SocketInterface $socket)
    {
        if ($this->socket) {
            throw new InvalidDefinitionException("{$this->name} socket already attached {$this->socket->getId()}");
        }

        $this->socket = $socket;
        $this->attachSocket($socket);
    }

    /**
     * @param mixed $data
     * @param SocketInterface $socket
     */
    public function onData($data, SocketInterface $socket)
    {
        $this->emit('data', [$data, $socket]);
    }

    /**
     * @param string $groupName
     * @param SocketInterface $socket
     */
    public function onBeginGroup($groupName, SocketInterface $socket)
    {
        $this->emit('beginGroup', [$groupName, $socket]);
    }

    /**
     * @param string $groupName
     * @param SocketInterface $socket
     */
    public function onEndGroup($groupName, SocketInterface $socket)
    {
        $this->emit('endGroup', [$groupName, $socket]);
    }

    /**
     * @throws SocketException
     */
    public function connect()
    {
        if (!$this->socket) {
            throw new SocketException("No socket available");
        }
        $this->socket->connect();
    }

    public function disconnect()
    {
        if (!$this->socket) {
            return;
        }

        $this->socket->disconnect();
    }

    /**
     * @return bool
     */
    public function isConnected()
    {
        if (!$this->socket) {
            return false;
        }

        return $this->socket->isConnected();
    }

    /**
     * Checks if port is attached.
     *
     * @return bool
     */
    public function isAttached()
    {
        if (!$this->socket) {
            return false;
        }

        return true;
    }

    /**
     * @param $groupName
     * @throws PortException
     */
    public function endGroup($groupName)
    {
        if (!$this->socket) {
            throw new PortException("This port is not connected");
        }

        $this->socket->endGroup($groupName);
    }

    /**
     * @param mixed $data
     * @return mixed|null
     * @throws InvalidTypeException
     */
    public function send($data)
    {
        if (!$this->socket) {
            throw new PortException("This port is not connected");
        }

        if ($this->isConnected()) {
            return $this->socket->send($data);
        }

        $this->socket->once('connect', function (SocketInterface $socket) use ($data) {
            $socket->send($data);
        });

        $this->socket->connect();
    }

    /**
     * @param SocketInterface $socket
     */
    public function detach(SocketInterface $socket)
    {
        $this->emit('detach', [$socket]);
        $this->from = null;
        $this->socket = null;
    }

    /**
     * @param string $groupName
     * @return null
     * @throws PortException
     */
    public function beginGroup($groupName)
    {
        if (!$this->socket) {
            throw new PortException("This port is not connected");
        }

        if ($this->isConnected()) {
            return $this->socket->beginGroup($groupName);
        }

        $this->socket->once('connect', function (SocketInterface $socket) use ($groupName) {
            $socket->beginGroup($groupName);
        });

        $this->socket->connect();
    }
}
