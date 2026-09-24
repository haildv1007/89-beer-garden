<?php

namespace App\Support;

class PostHtml
{
    public function clean(string $html): string
    {
        $config = \HTMLPurifier_Config::createDefault();
        $config->set('Cache.DefinitionImpl', null);
        $config->set('HTML.Allowed', 'p[style],br,h2,h3,h4,strong,b,em,i,u,s,ul,ol,li,blockquote,a[href|title],img[src|alt|width|height|title],table,thead,tbody,tr,th[colspan|rowspan],td[colspan|rowspan],caption,hr,span[style],pre,code');
        $config->set('CSS.AllowedProperties', ['text-align', 'color', 'background-color']);
        $config->set('URI.AllowedSchemes', ['http' => true, 'https' => true, 'mailto' => true, 'tel' => true]);

        return (new \HTMLPurifier($config))->purify($html);
    }
}
