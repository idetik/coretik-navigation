<?php

namespace Coretik\Navigation\Parts;

class Search extends Part
{
    public function title(): string
    {
        return $this->title ?? sprintf(__('Résultat de la recherche pour "%s"', 'coretik'), \get_search_query());
    }

    public function url(): string
    {
        return '';
    }
}
