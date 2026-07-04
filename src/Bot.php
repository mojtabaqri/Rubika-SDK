<?php

namespace RubikaBot;

use RubikaBot\Models\Update;
use RubikaBot\Handlers\Dispatcher;

class Bot extends RubikaClient
{
    /** @var Dispatcher */
    private $dispatcher;

    public function __construct($token)
    {
        parent::__construct($token);
        $this->dispatcher = new Dispatcher();
    }

    /**
     * @return Dispatcher
     */
    public function dispatcher()
    {
        return $this->dispatcher;
    }

    /**
     * @return array
     */
    public function getMe()
    {
        return parent::getMe();
    }

    /**
     * @param string $url
     * @param string $type
     * @return array
     */
    public function updateWebhook($url, $type = 'ReceiveUpdate')
    {
        return parent::updateWebhook($url, $type);
    }

    /**
     * @param mixed $params
     * @param string|null $text
     * @param array $options
     * @return array
     */
    public function sendMessage($params, $text = null, array $options = array())
    {
        return parent::sendMessage($params, $text, $options);
    }

    /**
     * @param array $updateData
     */
    public function handleWebhook(array $updateData)
    {
        $this->dispatcher->dispatch($updateData);
    }
}
