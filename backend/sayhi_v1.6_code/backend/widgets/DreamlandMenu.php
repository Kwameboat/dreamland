<?php

namespace backend\widgets;

use dmstr\widgets\Menu;

/**
 * Admin sidebar menu with correct Font Awesome icon classes.
 */
class DreamlandMenu extends Menu
{
    public $defaultIconHtml = '<span class="dl-menu-icon-wrap"><i class="fa fa-circle-o"></i></span> ';

    protected function buildIconClass(?string $icon): string
    {
        if ($icon === null || $icon === '') {
            return 'fa fa-circle-o';
        }

        $icon = trim($icon);
        if (preg_match('/\bfa[\s-]/', $icon) || strpos($icon, 'glyphicon') !== false) {
            return $icon;
        }

        return static::$iconClassPrefix . $icon;
    }

    protected function renderIconHtml(?string $icon): string
    {
        $iconClass = $this->buildIconClass($icon);

        return '<span class="dl-menu-icon-wrap" aria-hidden="true"><i class="' . $iconClass . '"></i></span> ';
    }

    protected function renderItem($item)
    {
        if (isset($item['items'])) {
            $labelTemplate = '<a href="{url}">{icon} {label} <span class="pull-right-container"><i class="fa fa-angle-left pull-right"></i></span></a>';
            $linkTemplate = '<a href="{url}">{icon} {label} <span class="pull-right-container"><i class="fa fa-angle-left pull-right"></i></span></a>';
        } else {
            $labelTemplate = $this->labelTemplate;
            $linkTemplate = $this->linkTemplate;
        }

        $replacements = [
            '{label}' => strtr($this->labelTemplate, ['{label}' => $item['label']]),
            '{icon}' => empty($item['icon']) && ($item['icon'] ?? null) !== '0'
                ? $this->defaultIconHtml
                : $this->renderIconHtml($item['icon'] ?? null),
            '{url}' => isset($item['url']) ? \yii\helpers\Url::to($item['url']) : 'javascript:void(0);',
        ];

        $template = \yii\helpers\ArrayHelper::getValue($item, 'template', isset($item['url']) ? $linkTemplate : $labelTemplate);

        return strtr($template, $replacements);
    }
}
