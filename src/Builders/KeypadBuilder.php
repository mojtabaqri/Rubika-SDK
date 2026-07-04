<?php

namespace RubikaBot\Builders;

use RubikaBot\Enums\ButtonType;

class Button
{
    /** @var string */
    private $id;

    /** @var string */
    private $type;

    /** @var string */
    private $buttonText;

    /** @var array */
    private $extra = array();

    public function __construct($id, $buttonText, $type = ButtonType::SIMPLE)
    {
        $this->id = $id;
        $this->buttonText = $buttonText;
        $this->type = $type;
    }

    public static function simple($id, $text)
    {
        return new self($id, $text, ButtonType::SIMPLE);
    }

    public static function link($id, $text, $url)
    {
        $button = new self($id, $text, ButtonType::LINK);
        $button->extra['link_url'] = $url;
        return $button;
    }

    public static function location($id, $text)
    {
        return new self($id, $text, ButtonType::LOCATION);
    }

    public static function askPhoneNumber($id, $text)
    {
        return new self($id, $text, ButtonType::ASK_MY_PHONE_NUMBER);
    }

    public static function askLocation($id, $text)
    {
        return new self($id, $text, ButtonType::ASK_MY_LOCATION);
    }

    public function toArray()
    {
        return array_merge(array(
            'id' => $this->id,
            'type' => $this->type,
            'button_text' => $this->buttonText,
        ), $this->extra);
    }
}

class KeypadRow
{
    /** @var array */
    private $buttons = array();

    public function addButton(Button $button)
    {
        $this->buttons[] = $button;
        return $this;
    }

    public function addButtons(Button ...$buttons)
    {
        foreach ($buttons as $button) {
            $this->buttons[] = $button;
        }
        return $this;
    }

    public function toArray()
    {
        $rows = array();
        foreach ($this->buttons as $button) {
            $rows[] = $button->toArray();
        }

        return array(
            'buttons' => $rows,
        );
    }
}

class Keypad
{
    /** @var array */
    private $rows = array();

    /** @var bool */
    private $resizeKeyboard = false;

    /** @var bool */
    private $oneTimeKeyboard = false;

    public function addRow(KeypadRow $row)
    {
        $this->rows[] = $row;
        return $this;
    }

    public function resizeKeyboard($resize = true)
    {
        $this->resizeKeyboard = $resize;
        return $this;
    }

    public function oneTimeKeyboard($oneTime = true)
    {
        $this->oneTimeKeyboard = $oneTime;
        return $this;
    }

    public function toArray()
    {
        $rows = array();
        foreach ($this->rows as $row) {
            $rows[] = $row->toArray();
        }

        return array(
            'rows' => $rows,
            'resize_keyboard' => $this->resizeKeyboard,
            'one_time_keyboard' => $this->oneTimeKeyboard,
        );
    }
}
