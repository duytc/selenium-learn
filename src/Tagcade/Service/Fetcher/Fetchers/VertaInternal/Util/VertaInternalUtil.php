<?php

namespace Tagcade\Service\Fetcher\Fetchers\VertaInternal\Util;

class VertaInternalUtil
{
    const BUTTON = 'button';
    const BUTTON_REGEX = 'button-%s';

    const BUTTON_SPLIT = 'button_split';
    const BUTTON_SPLIT_REGEX = 'splitbutton-%s-btnEl';

    const RADIO = 'radio';
    const RADIO_REGEX = 'radiofield-%s-displayEl';

    const CUSTOM_DATE_PICKER = 'custom';
    const CUSTOM_DATE_PICKER_REGEX = 'videedatepicker-%s-eventEl';

    const MONTH_PICKER = 'month';
    const MONTH_PICKER_REGEX = 'monthpicker-%s-monthEl';

    const YEAR_PICKER = 'year';
    const YEAR_PICKER_REGEX = 'monthpicker-%s-yearEl';

    const MENU_ITEM = 'menu_item';
    const MENU_ITEM_REGEX = 'menuitem-%s-textEl';

    public static function getId($type, $id) {
        switch ($type) {
            case self::BUTTON:
                return sprintf(self::BUTTON_REGEX, $id);
            case self::BUTTON_SPLIT:
                return sprintf(self::BUTTON_SPLIT_REGEX, $id);
            case self::RADIO:
                return sprintf(self::RADIO_REGEX, $id);
            case self::CUSTOM_DATE_PICKER:
                return sprintf(self::CUSTOM_DATE_PICKER_REGEX, $id);
            case self::MONTH_PICKER:
                return sprintf(self::MONTH_PICKER_REGEX, $id);
            case self::YEAR_PICKER:
                return sprintf(self::YEAR_PICKER_REGEX, $id);
            case self::MENU_ITEM:
                return sprintf(self::MENU_ITEM_REGEX, $id);
        }
        return '';
    }
}