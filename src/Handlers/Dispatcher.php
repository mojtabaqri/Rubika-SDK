<?php

namespace RubikaBot\Handlers;

use RubikaBot\Models\Update;
use RubikaBot\Enums\UpdateType;

class Dispatcher
{
    /** @var array */
    private $handlers = array();

    /**
     * @param callable $handler
     * @return self
     */
    public function onNewMessage(callable $handler)
    {
        $this->handlers[UpdateType::NEW_MESSAGE] = $handler;
        return $this;
    }

    /**
     * @param callable $handler
     * @return self
     */
    public function onUpdatedMessage(callable $handler)
    {
        $this->handlers[UpdateType::UPDATED_MESSAGE] = $handler;
        return $this;
    }

    /**
     * @param callable $handler
     * @return self
     */
    public function onRemovedMessage(callable $handler)
    {
        $this->handlers[UpdateType::REMOVED_MESSAGE] = $handler;
        return $this;
    }

    /**
     * @param callable $handler
     * @return self
     */
    public function onStartedBot(callable $handler)
    {
        $this->handlers[UpdateType::STARTED_BOT] = $handler;
        return $this;
    }

    /**
     * @param callable $handler
     * @return self
     */
    public function onStoppedBot(callable $handler)
    {
        $this->handlers[UpdateType::STOPPED_BOT] = $handler;
        return $this;
    }

    /**
     * @param array $updateData
     */
    public function dispatch(array $updateData)
    {
        $update = Update::fromArray($updateData);

        if (!$update || !$update->type) {
            return;
        }

        $handler = isset($this->handlers[$update->type]) ? $this->handlers[$update->type] : null;

        if ($handler) {
            $handler($update);
        }
    }
}
