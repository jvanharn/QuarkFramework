<?php
/**
 * Glyph Icon helper
 *
 * @package		Quark-Framework
 * @author		Jeffrey van Harn <Jeffrey at lessthanthree.nl>
 * @since		July 15, 2015
 * @copyright	Copyright (C) 2015 Jeffrey van Harn. All rights reserved.
 * @license		http://opensource.org/licenses/gpl-3.0.html GNU Public License Version 3
 */

// Define Namespace
namespace Quark\Libraries\Bootstrap;
use Quark\Document\baseIndependentElement;
use Quark\Document\Document;
use Quark\Document\IIndependentElement;

// Prevent individual file access
if(!defined('DIR_BASE')) exit;

/**
 * Glyphicon Helper Class
 * @version 3.3.5
 */
class Glyphicon implements IIndependentElement {
    use baseIndependentElement;

    const ICO_ASTERISK = 'asterisk';
    const ICO_PLUS = 'plus';
    const ICO_EURO = 'euro';
    const ICO_EUR = 'eur';
    const ICO_MINUS = 'minus';
    const ICO_CLOUD = 'cloud';
    const ICO_ENVELOPE = 'envelope';
    const ICO_PENCIL = 'pencil';
    const ICO_GLASS = 'glass';
    const ICO_MUSIC = 'music';
    const ICO_SEARCH = 'search';
    const ICO_HEART = 'heart';
    const ICO_STAR = 'star';
    const ICO_STAR_EMPTY = 'star-empty';
    const ICO_USER = 'user';
    const ICO_FILM = 'film';
    const ICO_TH_LARGE = 'th-large';
    const ICO_TH = 'th';
    const ICO_TH_LIST = 'th-list';
    const ICO_OK = 'ok';
    const ICO_REMOVE = 'remove';
    const ICO_ZOOM_IN = 'zoom-in';
    const ICO_ZOOM_OUT = 'zoom-out';
    const ICO_OFF = 'off';
    const ICO_SIGNAL = 'signal';
    const ICO_COG = 'cog';
    const ICO_TRASH = 'trash';
    const ICO_HOME = 'home';
    const ICO_FILE = 'file';
    const ICO_TIME = 'time';
    const ICO_ROAD = 'road';
    const ICO_DOWNLOAD_ALT = 'download-alt';
    const ICO_DOWNLOAD = 'download';
    const ICO_UPLOAD = 'upload';
    const ICO_INBOX = 'inbox';
    const ICO_PLAY_CIRCLE = 'play-circle';
    const ICO_REPEAT = 'repeat';
    const ICO_REFRESH = 'refresh';
    const ICO_LIST_ALT = 'list-alt';
    const ICO_LOCK = 'lock';
    const ICO_FLAG = 'flag';
    const ICO_HEADPHONES = 'headphones';
    const ICO_VOLUME_OFF = 'volume-off';
    const ICO_VOLUME_DOWN = 'volume-down';
    const ICO_VOLUME_UP = 'volume-up';
    const ICO_QRCODE = 'qrcode';
    const ICO_BARCODE = 'barcode';
    const ICO_TAG = 'tag';
    const ICO_TAGS = 'tags';
    const ICO_BOOK = 'book';
    const ICO_BOOKMARK = 'bookmark';
    const ICO_PRINT = 'print';
    const ICO_CAMERA = 'camera';
    const ICO_FONT = 'font';
    const ICO_BOLD = 'bold';
    const ICO_ITALIC = 'italic';
    const ICO_TEXT_HEIGHT = 'text-height';
    const ICO_TEXT_WIDTH = 'text-width';
    const ICO_ALIGN_LEFT = 'align-left';
    const ICO_ALIGN_CENTER = 'align-center';
    const ICO_ALIGN_RIGHT = 'align-right';
    const ICO_ALIGN_JUSTIFY = 'align-justify';
    const ICO_LIST = 'list';
    const ICO_INDENT_LEFT = 'indent-left';
    const ICO_INDENT_RIGHT = 'indent-right';
    const ICO_FACETIME_VIDEO = 'facetime-video';
    const ICO_PICTURE = 'picture';
    const ICO_MAP_MARKER = 'map-marker';
    const ICO_ADJUST = 'adjust';
    const ICO_TINT = 'tint';
    const ICO_EDIT = 'edit';
    const ICO_SHARE = 'share';
    const ICO_CHECK = 'check';
    const ICO_MOVE = 'move';
    const ICO_STEP_BACKWARD = 'step-backward';
    const ICO_FAST_BACKWARD = 'fast-backward';
    const ICO_BACKWARD = 'backward';
    const ICO_PLAY = 'play';
    const ICO_PAUSE = 'pause';
    const ICO_STOP = 'stop';
    const ICO_FORWARD = 'forward';
    const ICO_FAST_FORWARD = 'fast-forward';
    const ICO_STEP_FORWARD = 'step-forward';
    const ICO_EJECT = 'eject';
    const ICO_CHEVRON_LEFT = 'chevron-left';
    const ICO_CHEVRON_RIGHT = 'chevron-right';
    const ICO_PLUS_SIGN = 'plus-sign';
    const ICO_MINUS_SIGN = 'minus-sign';
    const ICO_REMOVE_SIGN = 'remove-sign';
    const ICO_OK_SIGN = 'ok-sign';
    const ICO_QUESTION_SIGN = 'question-sign';
    const ICO_INFO_SIGN = 'info-sign';
    const ICO_SCREENSHOT = 'screenshot';
    const ICO_REMOVE_CIRCLE = 'remove-circle';
    const ICO_OK_CIRCLE = 'ok-circle';
    const ICO_BAN_CIRCLE = 'ban-circle';
    const ICO_ARROW_LEFT = 'arrow-left';
    const ICO_ARROW_RIGHT = 'arrow-right';
    const ICO_ARROW_UP = 'arrow-up';
    const ICO_ARROW_DOWN = 'arrow-down';
    const ICO_SHARE_ALT = 'share-alt';
    const ICO_RESIZE_FULL = 'resize-full';
    const ICO_RESIZE_SMALL = 'resize-small';
    const ICO_EXCLAMATION_SIGN = 'exclamation-sign';
    const ICO_GIFT = 'gift';
    const ICO_LEAF = 'leaf';
    const ICO_FIRE = 'fire';
    const ICO_EYE_OPEN = 'eye-open';
    const ICO_EYE_CLOSE = 'eye-close';
    const ICO_WARNING_SIGN = 'warning-sign';
    const ICO_PLANE = 'plane';
    const ICO_CALENDAR = 'calendar';
    const ICO_RANDOM = 'random';
    const ICO_COMMENT = 'comment';
    const ICO_MAGNET = 'magnet';
    const ICO_CHEVRON_UP = 'chevron-up';
    const ICO_CHEVRON_DOWN = 'chevron-down';
    const ICO_RETWEET = 'retweet';
    const ICO_SHOPPING_CART = 'shopping-cart';
    const ICO_FOLDER_CLOSE = 'folder-close';
    const ICO_FOLDER_OPEN = 'folder-open';
    const ICO_RESIZE_VERTICAL = 'resize-vertical';
    const ICO_RESIZE_HORIZONTAL = 'resize-horizontal';
    const ICO_HDD = 'hdd';
    const ICO_BULLHORN = 'bullhorn';
    const ICO_BELL = 'bell';
    const ICO_CERTIFICATE = 'certificate';
    const ICO_THUMBS_UP = 'thumbs-up';
    const ICO_THUMBS_DOWN = 'thumbs-down';
    const ICO_HAND_RIGHT = 'hand-right';
    const ICO_HAND_LEFT = 'hand-left';
    const ICO_HAND_UP = 'hand-up';
    const ICO_HAND_DOWN = 'hand-down';
    const ICO_CIRCLE_ARROW_RIGHT = 'circle-arrow-right';
    const ICO_CIRCLE_ARROW_LEFT = 'circle-arrow-left';
    const ICO_CIRCLE_ARROW_UP = 'circle-arrow-up';
    const ICO_DOWN = 'circle-arrow-down';
    const ICO_GLOBE = 'globe';
    const ICO_WRENCH = 'wrench';
    const ICO_TASKS = 'tasks';
    const ICO_FILTER = 'filter';
    const ICO_BRIEFCASE = 'briefcase';
    const ICO_FULLSCREEN = 'fullscreen';
    const ICO_DASHBOARD = 'dashboard';
    const ICO_PAPERCLIP = 'paperclip';
    const ICO_HEART_EMPTY = 'heart-empty';
    const ICO_LINK = 'link';
    const ICO_PHONE = 'phone';
    const ICO_PUSHPIN = 'pushpin';
    const ICO_USD = 'usd';
    const ICO_GBP = 'gbp';
    const ICO_SORT = 'sort';
    const ICO_SORT_BY_ALPHABET = 'sort-by-alphabet';
    const ICO_SORT_BY_ALPHABET_ALT = 'sort-by-alphabet-alt';
    const ICO_SORT_BY_ORDER = 'sort-by-order';
    const ICO_SORT_BY_ORDER_ALT = 'sort-by-order-alt';
    const ICO_SORT_BY_ATTRIBUTES = 'sort-by-attributes';
    const ICO_SORT_BY_ATTRIBUTES_ALT = 'sort-by-attributes-alt';
    const ICO_UNCHECKED = 'unchecked';
    const ICO_EXPAND = 'expand';
    const ICO_COLLAPSE_DOWN = 'collapse-down';
    const ICO_COLLAPSE_UP = 'collapse-up';
    const ICO_LOG_IN = 'log-in';
    const ICO_FLASH = 'flash';
    const ICO_LOG_OUT = 'log-out';
    const ICO_NEW_WINDOW = 'new-window';
    const ICO_RECORD = 'record';
    const ICO_SAVE = 'save';
    const ICO_OPEN = 'open';
    const ICO_SAVED = 'saved';
    const ICO_IMPORT = 'import';
    const ICO_EXPORT = 'export';
    const ICO_SEND = 'send';
    const ICO_FLOPPY_DISK = 'floppy-disk';
    const ICO_FLOPPY_SAVED = 'floppy-saved';
    const ICO_FLOPPY_REMOVE = 'floppy-remove';
    const ICO_FLOPPY_SAVE = 'floppy-save';
    const ICO_FLOPPY_OPEN = 'floppy-open';
    const ICO_CREDIT_CARD = 'credit-card';
    const ICO_TRANSFER = 'transfer';
    const ICO_CUTLERY = 'cutlery';
    const ICO_HEADER = 'header';
    const ICO_COMPRESSED = 'compressed';
    const ICO_EARPHONE = 'earphone';
    const ICO_PHONE_ALT = 'phone-alt';
    const ICO_TOWER = 'tower';
    const ICO_STATS = 'stats';
    const ICO_SD_VIDEO = 'sd-video';
    const ICO_HD_VIDEO = 'hd-video';
    const ICO_SUBTITLES = 'subtitles';
    const ICO_SOUND_STEREO = 'sound-stereo';
    const ICO_SOUND_DOLBY = 'sound-dolby';
    const ICO_SOUND_5_1 = 'sound-5-1';
    const ICO_SOUND_6_1 = 'sound-6-1';
    const ICO_SOUND_7_1 = 'sound-7-1';
    const ICO_COPYRIGHT_MARK = 'copyright-mark';
    const ICO_REGISTRATION_MARK = 'registration-mark';
    const ICO_CLOUD_DOWNLOAD = 'cloud-download';
    const ICO_CLOUD_UPLOAD = 'cloud-upload';
    const ICO_TREE_CONIFER = 'tree-conifer';
    const ICO_TREE_DECIDUOUS = 'tree-deciduous';
    const ICO_CD = 'cd';
    const ICO_SAVE_FILE = 'save-file';
    const ICO_OPEN_FILE = 'open-file';
    const ICO_LEVEL_UP = 'level-up';
    const ICO_COPY = 'copy';
    const ICO_PASTE = 'paste';
    const ICO_ALERT = 'alert';
    const ICO_EQUALIZER = 'equalizer';
    const ICO_KING = 'king';
    const ICO_QUEEN = 'queen';
    const ICO_PAWN = 'pawn';
    const ICO_BISHOP = 'bishop';
    const ICO_KNIGHT = 'knight';
    const ICO_BABY_FORMULA = 'baby-formula';
    const ICO_TENT = 'tent';
    const ICO_BLACKBOARD = 'blackboard';
    const ICO_BED = 'bed';
    const ICO_APPLE = 'apple';
    const ICO_ERASE = 'erase';
    const ICO_HOURGLASS = 'hourglass';
    const ICO_LAMP = 'lamp';
    const ICO_DUPLICATE = 'duplicate';
    const ICO_PIGGY_BANK = 'piggy-bank';
    const ICO_SCISSORS = 'scissors';
    const ICO_BITCOIN = 'bitcoin';
    const ICO_BTC = 'btc';
    const ICO_XBT = 'xbt';
    const ICO_YEN = 'yen';
    const ICO_JPY = 'jpy';
    const ICO_RUBLE = 'ruble';
    const ICO_RUB = 'rub';
    const ICO_SCALE = 'scale';
    const ICO_ICE_LOLLY = 'ice-lolly';
    const ICO_ICE_LOLLY_TASTED = 'ice-lolly-tasted';
    const ICO_EDUCATION = 'education';
    const ICO_OPTION_HORIZONTAL = 'option-horizontal';
    const ICO_OPTION_VERTICAL = 'option-vertical';
    const ICO_MENU_HAMBURGER = 'menu-hamburger';
    const ICO_MODAL_WINDOW = 'modal-window';
    const ICO_OIL = 'oil';
    const ICO_GRAIN = 'grain';
    const ICO_SUNGLASSES = 'sunglasses';
    const ICO_TEXT_SIZE = 'text-size';
    const ICO_TEXT_COLOR = 'text-color';
    const ICO_TEXT_BACKGROUND = 'text-background';
    const ICO_OBJECT_ALIGN_TOP = 'object-align-top';
    const ICO_OBJECT_ALIGN_BOTTOM = 'object-align-bottom';
    const ICO_OBJECT_ALIGN_HORIZONTAL = 'object-align-horizontal';
    const ICO_OBJECT_ALIGN_LEFT = 'object-align-left';
    const ICO_OBJECT_ALIGN_VERTICAL = 'object-align-vertical';
    const ICO_OBJECT_ALIGN_RIGHT = 'object-align-right';
    const ICO_TRIANGLE_RIGHT = 'triangle-right';
    const ICO_TRIANGLE_LEFT = 'triangle-left';
    const ICO_TRIANGLE_BOTTOM = 'triangle-bottom';
    const ICO_TRIANGLE_TOP = 'triangle-top';
    const ICO_CONSOLE = 'console';
    const ICO_SUPERSCRIPT = 'superscript';
    const ICO_SUBSCRIPT = 'subscript';
    const ICO_MENU_LEFT = 'menu-left';
    const ICO_MENU_RIGHT = 'menu-right';
    const ICO_MENU_DOWN = 'menu-down';
    const ICO_MENU_UP = 'menu-up';

    /**
     * @var string The icon that is represented by this class.
     */
    private $icon = '';

    /**
     * @var string Alternative text for visually impaired users. (Aria)
     */
    private $alt = null;

    /**
     * @var array|null list of all available icons.
     */
    private static $icons = null;

    /**
     * @param string $icon Icon name. One of the ICO_* class-constants.
     * @param string $alt Alternative text for visually impaired users. (Aria label etc.)
     */
    public function __construct($icon, $alt=null){
        if(empty($icon) || !ctype_alpha(str_replace('-','',$icon)))
            throw new \InvalidArgumentException('Argument "icon" should be of type string, and only contain alphabetical-characters.');
        $this->icon = $icon;
        $this->alt = $alt;
    }

    /**
     * Retrieve the HTML representation of the element
     * @param Document $context The context within which the Element gets saved. (Contains data like encoding, XHTML or not etc.)
     * @param int $depth
     * @return String HTML Representation
     */
    public function save(Document $context=null, $depth=0){
        return $this->independentSave($depth);
    }

    /**
     * Saves the icon to HTML.
     * @param int $depth
     * @return string
     */
    public function independentSave($depth=0){
        return '<span class="glyphicon glyphicon-'.$this->icon.'" aria-hidden="true"'.(!is_null($this->alt) ? ' aria-label="'.$this->alt.'"' : '').'></span>';
    }

    /**
     * Get all the available icons as an array.
     * @return array
     */
    public static function all(){
        if(self::$icons == null)
            self::$icons = (new \ReflectionClass(get_called_class()))->getConstants();
        return self::$icons;
    }

    /**
     * Get the appropriate html for the given icon(-name).
     * @param string $icon Icon name. One of the ICO_* class-constants.
     * @return string
     */
    public static function html($icon){
        return (string) new Glyphicon($icon);
    }

    /**
     * Shortcut method for icon creation.
     * @param string $icon Icon name without the 'glyphicon-' prefix. Use one of the ICO_* class constants.
     * @return Glyphicon
     */
    public static function make($icon){
        return new Glyphicon($icon);
    }
}