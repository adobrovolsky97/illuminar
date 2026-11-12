<?php

namespace Adobrovolsky97\Illuminar\Formatters;

use Adobrovolsky97\Illuminar\Support\EditorLink;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\HtmlDumper as SymfonyHtmlDumper;

/**
 * Class HtmlDumper
 */
class HtmlDumper
{
    protected VarCloner $cloner;

    protected SymfonyHtmlDumper $dumper;

    public function __construct(SymfonyHtmlDumper $dumper, VarCloner $cloner)
    {
        $this->dumper = $dumper;
        $this->cloner = $cloner;

        $this->cloner->setMaxItems((int)config('illuminar.dump_max_items', 2500));
        $this->cloner->setMaxString((int)config('illuminar.dump_max_string', 10000));

        $this->dumper->setDumpHeader('');

        $options = ['maxDepth' => (int)config('illuminar.dump_depth', 2)];

        if ($fileLinkFormat = EditorLink::fileLinkFormat()) {
            $options['fileLinkFormat'] = $fileLinkFormat;
        }

        $this->dumper->setDisplayOptions($options);
    }

    /**
     * Dump data in HTML format
     *
     * @param mixed $var
     * @return string
     */
    public function dump($var): string
    {
        return (string)$this->dumper->dump($this->cloner->cloneVar($var), true);
    }

    /**
     * Symfony dumper JS & CSS which must be included to the page once
     */
    public static function header(): string
    {
        $dumper = new class extends SymfonyHtmlDumper {
            public function header(): string
            {
                return $this->getDumpHeader();
            }
        };

        return $dumper->header();
    }
}
