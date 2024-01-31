<?php

namespace Adobrovolsky97\Illuminar\Formatters;

use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\HtmlDumper as SymfonyHtmlDumper;

/**
 * Class Dumper
 */
class HtmlDumper
{
    /**
     * @var VarCloner
     */
    protected VarCloner $cloner;

    /**
     * @var SymfonyHtmlDumper
     */
    protected SymfonyHtmlDumper $dumper;

    /**
     * @param SymfonyHtmlDumper $dumper
     * @param VarCloner $cloner
     */
    public function __construct(SymfonyHtmlDumper $dumper, VarCloner $cloner)
    {
        $this->dumper = $dumper;
        $this->cloner = $cloner;

        $this->dumper->setDumpHeader('');
        $this->dumper->setDisplayOptions(['maxDepth' => config('illuminar.dump_depth', 2)]);
    }

    /**
     * Dump data in HTML format
     *
     * @param mixed $var
     * @return string
     */
    public function dump($var): string
    {
        return $this->dumper->dump($this->cloner->cloneVar($var), true);
    }
}
