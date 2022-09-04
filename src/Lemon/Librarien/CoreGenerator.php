<?php

namespace Lemon\Librarien;

use Parsedown;
use Reflection;
use ReflectionClass;
use ReflectionMethod;
use ReflectionParameter;

class CoreGenerator
{
    private string $dir = '';

    public function __construct(
        private Config $config,
        private Parsedown $parsedown
    ) {
        $this->dir = $this->config->get('tmp').'/'.$this->config->get('core-src');
    }

    public function build(): void
    {
        exec('git clone '.$this->config->get('core-repository').' '.$this->config->get('tmp'));

    }

    public function buildDir(string $dir): void
    {
        // vytvori hlavni stranu te slozky - readme plus seznam trid
        // taky vytvori soubory pro kazde tridy jakdyz

        $main = [];

        foreach (scandir($dir) as $file) {
            if (is_file($file)) {
                if (self::isClass($file)) {
                    $main['classes'][] = $this->buildClass($file);
                }

                if (str_ends_with($file, 'README.md')) {
                    $main['description'] = $this->parsedown->parse(
                        file_get_contents($file)
                    );
                }

                if (str_ends_with($file, '.php')) {
            
                }
            }
        }
    }

    public function buildClass(string $file): string
    {
        $content = file_get_contents($file);

        preg_match('/namespace\s+([A-Za-z0-9\\\\]+?);/', $content, $matches);
        $namespace = $matches[1] ?? '';
        
        preg_match('/class\s*([A-Za-z0-9]+?)\s++/', $content, $matches);
        $class = $matches[1];

        $class = $namespace.'\\'.$class;                       

        require $file;
        $reflection = new ReflectionClass($class);

        $methods = [];

        foreach ($reflection->getMethods() as $method) {
            $methods[] = $this->parseMethod($method);
        }

        ob_start();
        (function(array $methods, string $path) {
            require $path;
        })($methods, $this->config->get('class-template'));

        $file = preg_replace('/^'.$this->dir.'(.+?)\.php$/', $this->config->get('out').'/api-docs/$1.html', $file);

        file_put_contents($file, ob_get_clean());

        return $class;
    }

    public function parseMethod(ReflectionMethod $method): array
    {
        $docblock = $this->parseDocBlock($method->getDocComment());

        $signature = 
            implode(' ', Reflection::getModifierNames($method->getModifiers()))
            .' '
            .$method->getName()
            .'('.$this->parseArguments($method->getParameters(), $docblock).')'
            .': '.($docblock['return'] ?? $method->getReturnType() ?? 'void')
        ;

        return [
            'description' => $docblock['description'],
            'signature' => $signature
        ];
    }

    public function parseArguments(array $args, array $docblock): string
    {
        return implode(', ', array_map(
            fn(ReflectionParameter $item) => 
                ($docblock['params'][$item->getName()] ?? $item->getType() ?? 'mixed')
                .' '.$item->getName()
                .($item->isDefaultValueAvailable() ? ' = '.$item->getDefaultValue() : '')
            , $args)
        );
    }

    public function parseDocBlock(string $docblock): array
    {
        $doc = explode("\n", $docblock);
        $result = [];

        foreach ($doc as $line) {
            $line = trim($line, '* ');
            if (!$line) {
                continue;
            }

            if (str_starts_with($line, '@')) {
                $line = explode(' ', trim($line, '@'));

                switch ($line[0]) {
                    case 'param':
                        $result['params'][$line[1]] = $line[2];
                        break;
                    case 'return':
                        $result['return'] = $line[1];
                        break;
                }

                continue;
            }

            $result['description'] = $line;
        }

        return $result;
    }

    public function buildFunctions(string $file): array
    {

    }

    public static function isClass(string $file): bool
    {
        return preg_match('~^(.+?)/([A-Z][a-zA-Z]+?)\.php$~', $file);
    }
}
