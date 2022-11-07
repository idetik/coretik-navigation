<?php

namespace Coretik\Navigation\Parts;

class Search extends Part
{
    public function title(): string
    {
        return sprintf(__('Résultat de la recherche pour <i>"%s"</i>', 'coretik'), \get_search_query());
    }

    public function url(): string
    {
        return '';
    }
}
