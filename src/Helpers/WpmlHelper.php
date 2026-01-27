<?php

namespace WeAreHausTech\WpProductSync\Helpers;

class WpmlHelper
{
    public function hasWpml()
    {
        if (!defined('ICL_SITEPRESS_VERSION')) {
            return false;
        }

        return true;
    }

    public function getAvalibleTranslations()
    {

        if (!$this->hasWpml()) {
            return [];
        }

        $wpmlLanguages = apply_filters('wpml_active_languages', null, 'skip_missing=0');

        if (!isset($wpmlLanguages)) {
            return [];
        }

        foreach ($wpmlLanguages as $lang) {
            $avalibleTranslations[] = $lang['code'];
        }

        return $avalibleTranslations;
    }

    public function getDefaultLanguage()
    {
        if (!$this->hasWpml()) {
            return null;
        }

        return apply_filters('wpml_default_language', null);
    }

    public function getTermLanguage($termId, $taxonomy)
    {
        if (!$this->hasWpml()) {
            return null;
        }

        $wpml_language_code = apply_filters(
            'wpml_element_language_code',
            null,
            array(
                'element_id' => $termId,
                'element_type' => 'tax_' . $taxonomy,
            )
        );

        return $wpml_language_code;
    }

    public function setLanguageDetails($orignal, $translations, $type)
    {
        $wpmlElementType = apply_filters('wpmlElementType', $type);
        $getLanguageArgs = array('element_id' => $orignal, 'element_type' => $type);
        $goriginalTermLanguageInfo = apply_filters('wpml_element_language_details', null, $getLanguageArgs);

        foreach ($translations as $lang => $translation) {
            $setLanguageArgs = array(
                'element_id' => $translation,
                'element_type' => $wpmlElementType,
                'trid' => $goriginalTermLanguageInfo->trid,
                'language_code' => $lang,
                'source_language_code' => $goriginalTermLanguageInfo->language_code
            );

            do_action('wpml_set_element_language_details', $setLanguageArgs);

        }
    }
}

