<?php

namespace RubikaBot\Enums;

class UpdateType
{
    public const NEW_MESSAGE = 'NewMessage';
    public const UPDATED_MESSAGE = 'UpdatedMessage';
    public const REMOVED_MESSAGE = 'RemovedMessage';
    public const STARTED_BOT = 'StartedBot';
    public const STOPPED_BOT = 'StoppedBot';
}

class ButtonType
{
    public const SIMPLE = 'Simple';
    public const SELECTION = 'Selection';
    public const CALENDAR = 'Calendar';
    public const NUMBER_PICKER = 'NumberPicker';
    public const STRING_PICKER = 'StringPicker';
    public const LOCATION = 'Location';
    public const CAMERA_IMAGE = 'CameraImage';
    public const CAMERA_VIDEO = 'CameraVideo';
    public const GALLERY_IMAGE = 'GalleryImage';
    public const GALLERY_VIDEO = 'GalleryVideo';
    public const FILE = 'File';
    public const AUDIO = 'Audio';
    public const RECORD_AUDIO = 'RecordAudio';
    public const TEXTBOX = 'Textbox';
    public const LINK = 'Link';
    public const ASK_MY_PHONE_NUMBER = 'AskMyPhoneNumber';
    public const ASK_MY_LOCATION = 'AskMyLocation';
    public const BARCODE = 'Barcode';
}

class ChatKeypadType
{
    public const NONE = 'None';
    public const NEW = 'New';
    public const REMOVE = 'Remove';
}

class ChatType
{
    public const USER = 'User';
    public const BOT = 'Bot';
    public const GROUP = 'Group';
    public const CHANNEL = 'Channel';
}

class FileType
{
    public const FILE = 'File';
    public const IMAGE = 'Image';
    public const VOICE = 'Voice';
    public const VIDEO = 'Video';
    public const MUSIC = 'Music';
    public const GIF = 'Gif';
}

class MetadataType
{
    public const BOLD = 'Bold';
    public const ITALIC = 'Italic';
    public const MONO = 'Mono';
    public const UNDERLINE = 'Underline';
    public const STRIKE = 'Strike';
    public const SPOILER = 'Spoiler';
    public const LINK = 'Link';
    public const MENTION_TEXT = 'MentionText';
    public const PRE = 'Pre';
    public const QUOTE = 'Quote';
}

class MessageSender
{
    public const USER = 'User';
    public const BOT = 'Bot';
}

class PollStatusState
{
    public const OPEN = 'Open';
    public const CLOSED = 'Closed';
}

class ButtonSelectionType
{
    public const TEXT_ONLY = 'TextOnly';
    public const TEXT_IMG_THU = 'TextImgThu';
    public const TEXT_IMG_BIG = 'TextImgBig';
}

class ButtonCalendarType
{
    public const DATE_PERSIAN = 'DatePersian';
    public const DATE_GREGORIAN = 'DateGregorian';
}

class ButtonTextboxTypeKeypad
{
    public const STRING = 'String';
    public const NUMBER = 'Number';
}

class ButtonTextboxTypeLine
{
    public const SINGLE_LINE = 'SingleLine';
    public const MULTI_LINE = 'MultiLine';
}

class ButtonLocationType
{
    public const PICKER = 'Picker';
    public const VIEW = 'View';
}

class ForwardedFromType
{
    public const USER = 'User';
    public const CHANNEL = 'Channel';
    public const BOT = 'Bot';
}

class UpdateEndpointType
{
    public const RECEIVE_UPDATE = 'ReceiveUpdate';
    public const RECEIVE_INLINE_MESSAGE = 'ReceiveInlineMessage';
    public const RECEIVE_QUERY = 'ReceiveQuery';
    public const GET_SELECTION_ITEM = 'GetSelectionItem';
    public const SEARCH_SELECTION_ITEMS = 'SearchSelectionItems';
}
