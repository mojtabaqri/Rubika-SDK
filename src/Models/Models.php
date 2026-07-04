<?php

namespace RubikaBot\Models;

class Chat extends Model
{
    /** @var string|null */ public $chat_id = null;
    /** @var string|null */ public $chat_type = null;
    /** @var string|null */ public $user_id = null;
    /** @var string|null */ public $first_name = null;
    /** @var string|null */ public $last_name = null;
    /** @var string|null */ public $title = null;
    /** @var string|null */ public $username = null;
}

class File extends Model
{
    /** @var string|null */ public $file_id = null;
    /** @var string|null */ public $file_name = null;
    /** @var string|null */ public $size = null;
}

class ForwardedFrom extends Model
{
    /** @var string|null */ public $type_from = null;
    /** @var string|null */ public $message_id = null;
    /** @var string|null */ public $from_chat_id = null;
    /** @var string|null */ public $from_sender_id = null;
}

class MessageTextUpdate extends Model
{
    /** @var string|null */ public $message_id = null;
    /** @var string|null */ public $text = null;
}

class Bot extends Model
{
    /** @var string|null */ public $bot_id = null;
    /** @var string|null */ public $bot_title = null;
    /** @var File|null */ public $avatar = null;
    /** @var string|null */ public $description = null;
    /** @var string|null */ public $username = null;
    /** @var string|null */ public $start_message = null;
    /** @var string|null */ public $share_url = null;
}

class BotCommand extends Model
{
    /** @var string|null */ public $command = null;
    /** @var string|null */ public $description = null;
}

class Sticker extends Model
{
    /** @var string|null */ public $sticker_id = null;
    /** @var File|null */ public $file = null;
    /** @var string|null */ public $emoji_character = null;
}

class ContactMessage extends Model
{
    /** @var string|null */ public $phone_number = null;
    /** @var string|null */ public $first_name = null;
    /** @var string|null */ public $last_name = null;
}

class PollStatus extends Model
{
    /** @var string|null */ public $state = null;
    /** @var int|null */ public $selection_index = null;
    /** @var array */ public $percent_vote_options = array();
    /** @var int|null */ public $total_vote = null;
    /** @var bool|null */ public $show_total_votes = null;
}

class Poll extends Model
{
    /** @var string|null */ public $question = null;
    /** @var array */ public $options = array();
    /** @var PollStatus|null */ public $poll_status = null;
}

class Location extends Model
{
    /** @var string|null */ public $longitude = null;
    /** @var string|null */ public $latitude = null;
}

class ButtonSelectionItem extends Model
{
    /** @var string|null */ public $text = null;
    /** @var string|null */ public $image_url = null;
    /** @var string|null */ public $type = null;
}

class ButtonSelection extends Model
{
    /** @var string|null */ public $selection_id = null;
    /** @var string|null */ public $search_type = null;
    /** @var string|null */ public $get_type = null;
    /** @var array */ public $items = array();
    /** @var bool|null */ public $is_multi_selection = null;
    /** @var string|null */ public $columns_count = null;
    /** @var string|null */ public $title = null;
}

class ButtonCalendar extends Model
{
    /** @var string|null */ public $default_value = null;
    /** @var string|null */ public $type = null;
    /** @var string|null */ public $min_year = null;
    /** @var string|null */ public $max_year = null;
    /** @var string|null */ public $title = null;
}

class ButtonNumberPicker extends Model
{
    /** @var string|null */ public $min_value = null;
    /** @var string|null */ public $max_value = null;
    /** @var string|null */ public $default_value = null;
    /** @var string|null */ public $title = null;
}

class ButtonStringPicker extends Model
{
    /** @var array */ public $items = array();
    /** @var string|null */ public $default_value = null;
    /** @var string|null */ public $title = null;
}

class ButtonTextbox extends Model
{
    /** @var string|null */ public $type_line = null;
    /** @var string|null */ public $type_keypad = null;
    /** @var string|null */ public $place_holder = null;
    /** @var string|null */ public $title = null;
    /** @var string|null */ public $default_value = null;
}

class ButtonLocation extends Model
{
    /** @var Location|null */ public $default_pointer_location = null;
    /** @var Location|null */ public $default_map_location = null;
    /** @var string|null */ public $type = null;
    /** @var string|null */ public $title = null;
}

class AuxData extends Model
{
    /** @var string|null */ public $start_id = null;
    /** @var string|null */ public $button_id = null;
}

class Button extends Model
{
    /** @var string|null */ public $id = null;
    /** @var string|null */ public $type = null;
    /** @var string|null */ public $button_text = null;
    /** @var ButtonSelection|null */ public $button_selection = null;
    /** @var ButtonCalendar|null */ public $button_calendar = null;
    /** @var ButtonNumberPicker|null */ public $button_number_picker = null;
    /** @var ButtonStringPicker|null */ public $button_string_picker = null;
    /** @var ButtonLocation|null */ public $button_location = null;
    /** @var ButtonTextbox|null */ public $button_textbox = null;
}

class KeypadRow extends Model
{
    /** @var array */ public $buttons = array();
}

class Keypad extends Model
{
    /** @var array */ public $rows = array();
    /** @var bool|null */ public $resize_keyboard = null;
    /** @var bool|null */ public $one_time_keyboard = null;
}

class MessageKeypadUpdate extends Model
{
    /** @var string|null */ public $message_id = null;
    /** @var Keypad|null */ public $inline_keypad = null;
}

class Message extends Model
{
    /** @var string|null */ public $message_id = null;
    /** @var string|null */ public $text = null;
    /** @var int|null */ public $time = null;
    /** @var bool|null */ public $is_edited = null;
    /** @var string|null */ public $sender_type = null;
    /** @var string|null */ public $sender_id = null;
    /** @var AuxData|null */ public $aux_data = null;
    /** @var File|null */ public $file = null;
    /** @var string|null */ public $reply_to_message_id = null;
    /** @var ForwardedFrom|null */ public $forwarded_from = null;
    /** @var string|null */ public $forwarded_no_link = null;
    /** @var Location|null */ public $location = null;
    /** @var Sticker|null */ public $sticker = null;
    /** @var ContactMessage|null */ public $contact_message = null;
    /** @var Poll|null */ public $poll = null;
}

class Update extends Model
{
    /** @var string|null */ public $type = null;
    /** @var string|null */ public $chat_id = null;
    /** @var string|null */ public $removed_message_id = null;
    /** @var Message|null */ public $new_message = null;
    /** @var Message|null */ public $updated_message = null;
}

class InlineMessage extends Model
{
    /** @var string|null */ public $sender_id = null;
    /** @var string|null */ public $text = null;
    /** @var File|null */ public $file = null;
    /** @var Location|null */ public $location = null;
    /** @var AuxData|null */ public $aux_data = null;
    /** @var string|null */ public $message_id = null;
    /** @var string|null */ public $chat_id = null;
}

class Metadata extends Model
{
    /** @var array */ public $meta_data_parts = array();
}

class MetadataPart extends Model
{
    /** @var string|null */ public $type = null;
    /** @var int|null */ public $from_index = null;
    /** @var int|null */ public $length = null;
    /** @var string|null */ public $link_url = null;
    /** @var string|null */ public $mention_text_user_id = null;
}
