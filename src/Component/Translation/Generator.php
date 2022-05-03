<?php

namespace App\Component\Translation;

use Adbar\Dot;
use App\Component\Exception\LoggedException;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Yaml\Yaml;

class Generator
{
    private const TRANSLATION_FILE_NAME_MASK = '%s/%s+intl-icu.%s.yaml';

    private Filesystem  $filesystem;
    private string      $langsDir;
    private string      $translationsDir;

    public function __construct(
        private ContainerBagInterface $parameters
    )
    {
        $this->filesystem =         new Filesystem();
        $this->langsDir =           $parameters->get('app.dir.langs');
        $this->translationsDir =    $this->parameters->get('translator.default_path');
    }

    public function generate(): void
    {
        if (!$this->filesystem->exists($this->langsDir)) {
            throw $this->createException(sprintf('Source directory "%s" does not exists', $this->langsDir));
        }

        $currentTranslations = (new Finder())->in($this->translationsDir);

        foreach ($currentTranslations as $currentTranslation) {
            $this->filesystem->remove($currentTranslation->getPathname());
        }

        $langs = (new Finder())->in($this->langsDir)->depth(0)->directories()->sortByName();

        foreach ($langs as $lang) {
            $this->generateLang($lang);
        }
    }

    private function generateLang(SplFileInfo $lang): void
    {
        $contents = $this->getLangContents($lang);

        foreach ($contents->flatten() as $key => $value) {
            $contents->set($key, $this->convertContentValue($value, $contents));
        }

        $this->dumpContents($lang->getRelativePathname(), $contents);
    }

    private function getLangContents(SplFileInfo $lang): Dot
    {
        $langPath = $lang->getPathname();
        $langName = $lang->getRelativePathname();

        if (!is_dir($langPath)) {
            throw $this->createException(sprintf('"%s" is not a directory', $langPath));
        } elseif (2 !== strlen($langName)) {
            throw $this->createException(sprintf('The name of language must be 2 letters, "%s" given', $langPath));
        }

        $files = (new Finder())->in($langPath)->files()->name('*.yaml')->sortByName();

        $contents = new Dot();

        foreach ($files as $file) {
            $filePath = $file->getPathname();
            $fileName = substr($file->getRelativePathname(), 0, -5);
            $fileName = str_replace('/', '.', $fileName);

            $contents->set($fileName, Yaml::parseFile($filePath));
        };

        return $contents;
    }

    private function dumpContents(string $langName, Dot $contents): void
    {
        $yaml = Yaml::dump($contents->all());

        foreach (['messages', 'security', 'validators'] as $target) {
            $targetPath =  sprintf(self::TRANSLATION_FILE_NAME_MASK, $this->translationsDir, $target, $langName);

            $this->filesystem->dumpFile($targetPath, $yaml);
        }
    }

    private function convertContentValue(string $value, Dot $contents): string
    {
        $linkMatches = [];
        preg_match_all('/%([\w.]+)%/', $value, $linkMatches);
        $linkMatches = array_unique($linkMatches[1]);

        if ($linkMatches) {
            foreach ($linkMatches as $match) {
                $replacement = $contents->get($match);

                if (!$replacement || !is_string($replacement)) {
                    continue;
                }

                $replacement = $this->convertContentValue($replacement, $contents);
                $value = str_replace('%' . $match . '%', $replacement, $value);
            }
        }

        return $value;
    }

    private function createException(string $message, array $context = []): LoggedException
    {
        return (new LoggedException($message))
            ->setExceptionedClass(self::class)
            ->setLoggedContext($context);
    }
}
