<?php

namespace Coretik\Navigation\Parts;

class Page404 extends Part
{
    public function title(): string
    {
        return $this->title ?? __('Page introuvable', 'coretik');
    }

    public function url(): string
    {
        return '';
    }
}
