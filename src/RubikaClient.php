<?php

namespace RubikaBot;

class RubikaClient
{
    /** @var string */
    private $token;

    /** @var string */
    private $baseUrl = 'https://botapi.rubika.ir/v3';

    /**
     * @param string $token
     */
    public function __construct($token)
    {
        $this->token = $token;
    }

    /**
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * @param string $method
     * @param array $params
     * @return array
     */
    public function call($method, array $params = array())
    {
        $url = sprintf('%s/%s/%s', $this->baseUrl, $this->token, $method);
        $payload = http_build_query($params, '', '&', PHP_QUERY_RFC3986);

        \RubikaBot\debugLog('Outgoing external API request', array(
            'method' => $method,
            'url' => $url,
            'payload' => $params,
            'raw_payload' => $payload,
        ));

        $ch = curl_init($url);

        curl_setopt_array($ch, array(
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => array('Content-Type: application/x-www-form-urlencoded'),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 20,
        ));

        $response = curl_exec($ch);

        if ($response === false) {
            $error = curl_error($ch);
            curl_close($ch);
            \RubikaBot\debugLog('Outgoing external API request failed', array(
                'method' => $method,
                'url' => $url,
                'error' => $error,
            ), true);
            throw new \RuntimeException("خطای cURL در فراخوانی {$method}: {$error}");
        }

        curl_close($ch);

        $decoded = json_decode($response, true);
        \RubikaBot\debugLog('Incoming external API response', array(
            'method' => $method,
            'url' => $url,
            'response' => $decoded,
            'raw_response' => $response,
        ));

        return is_array($decoded) ? $decoded : array();
    }

    /**
     * @return array
     */
    public function getMe()
    {
        return $this->call('getMe');
    }

    /**
     * @param mixed $params
     * @param string|null $text
     * @param array $options
     * @return array
     */
    public function sendMessage($params, $text = null, array $options = array())
    {
        if (is_array($params) && !isset($params['chat_id']) && $text !== null) {
            $params = array_merge(array(
                'chat_id' => $params,
                'text' => $text,
            ), $options);
        } elseif (!is_array($params) && $text !== null) {
            $params = array_merge(array(
                'chat_id' => $params,
                'text' => $text,
            ), $options);
        }

        return $this->call('sendMessage', $params);
    }

    /**
     * @param array $params
     * @return array
     */
    public function sendPoll(array $params)
    {
        return $this->call('sendPoll', $params);
    }

    /**
     * @param array $params
     * @return array
     */
    public function sendLocation(array $params)
    {
        return $this->call('sendLocation', $params);
    }

    /**
     * @param array $params
     * @return array
     */
    public function sendContact(array $params)
    {
        return $this->call('sendContact', $params);
    }

    /**
     * @param array $params
     * @return array
     */
    public function getChat(array $params)
    {
        return $this->call('getChat', $params);
    }

    /**
     * @param array $params
     * @return array
     */
    public function getUpdates(array $params = array())
    {
        return $this->call('getUpdates', $params);
    }

    /**
     * @param array $params
     * @return array
     */
    public function forwardMessage(array $params)
    {
        return $this->call('forwardMessage', $params);
    }

    /**
     * @param array $params
     * @return array
     */
    public function editMessageText(array $params)
    {
        return $this->call('editMessageText', $params);
    }

    /**
     * @param array $params
     * @return array
     */
    public function editMessageKeypad(array $params)
    {
        return $this->call('editMessageKeypad', $params);
    }

    /**
     * @param array $params
     * @return array
     */
    public function deleteMessage(array $params)
    {
        return $this->call('deleteMessage', $params);
    }

    /**
     * @param array $params
     * @return array
     */
    public function setCommands(array $params)
    {
        return $this->call('setCommands', $params);
    }

    /**
     * @param array $params
     * @return array
     */
    public function updateBotEndpoints(array $params)
    {
        return $this->call('updateBotEndpoints', $params);
    }

    /**
     * @param string $url
     * @param string $type
     * @return array
     */
    public function updateWebhook($url, $type = 'GetSelectionItem')
    {
        return $this->updateBotEndpoints(array(
            'url' => $url,
            'type' => $type,
        ));
    }

    /**
     * @param array $params
     * @return array
     */
    public function editChatKeypad(array $params)
    {
        return $this->call('editChatKeypad', $params);
    }

    /**
     * @param array $params
     * @return array
     */
    public function getFile(array $params)
    {
        return $this->call('getFile', $params);
    }

    /**
     * @param array $params
     * @return array
     */
    public function sendFile(array $params)
    {
        return $this->call('sendFile', $params);
    }

    /**
     * @param array $params
     * @return array
     */
    public function requestSendFile(array $params)
    {
        return $this->call('requestSendFile', $params);
    }

    /**
     * @param array $params
     * @return array
     */
    public function banChatMember(array $params)
    {
        return $this->call('banChatMember', $params);
    }

    /**
     * @param array $params
     * @return array
     */
    public function unbanChatMember(array $params)
    {
        return $this->call('unbanChatMember', $params);
    }

    /**
     * @param string $uploadUrl
     * @param string $filePath
     * @return array
     */
    public function uploadFile($uploadUrl, $filePath)
    {
        if (!is_file($filePath)) {
            throw new \RuntimeException("فایل برای آپلود یافت نشد: {$filePath}");
        }

        $ch = curl_init($uploadUrl);
        $file = new \CURLFile($filePath);

        curl_setopt_array($ch, array(
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => array('file' => $file),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 60,
        ));

        $response = curl_exec($ch);
        curl_close($ch);

        $decoded = json_decode($response, true);
        return is_array($decoded) ? $decoded : array();
    }
}
