<?php

namespace Lemon\Librarien;

use Parsedown;

class MarkDownGenerator
{
    private Parsedown $pd;

    private string $sidebar;

    public function __construct(
        private Config $config
    ) {
        $this->pd = new Parsedown();
    }

    public function build(): void
    {
        $repo = $this->config->get('repository');
        exec('git clone '.$repo.' '.$this->config->get('tmp'));

        foreach (scandir($this->config->get('tmp')) as $file) {
            if (str_ends_with($file, 'docs.md')) {
                $this->sidebar = $this->pd->parse(file_get_contents($file));
            }

            if (is_dir($file)) {
                $this->buildDir($file);
            }
        }

        Filesystem::delete($this->config->get('tmp'));
    }

    public function buildDir(string $dir): void
    {
        $part = $this->config->get('out').array_pop(explode('/', $dir));
        mkdir($part);
        foreach (scandir($dir) as $file) {
            $name = explode('.', array_pop(explode('/', $file)))[0].'.html';
            $out = $this->pd->parse(file_get_contents($file));

            file_put_contents($part.'/'.$name, $this->render($out));
        }
    }

    public function render(string $content): string
    {
        ob_start();

        (function(string $content, string $sidebar, string $file) {
            require $file; 
        })($content, $this->sidebar, $this->config->get('template'));

        return ob_get_clean();
    }
}
