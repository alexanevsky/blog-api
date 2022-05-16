<?php

namespace App\Component\File;

use App\Component\Exception\LoggedException;
use Intervention\Image\Image as InterventionImage;
use Intervention\Image\ImageManagerStatic as InterventionManager;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;

class ImageResolver
{
    public const MIME_EXTENSIONS = [
        'image/jpeg' => 'jpg',
        'image/png' =>  'png',
        'image/gif' =>  'gif',
        'image/webp' => 'webp'
    ];

    private ?array $allowedMimeTypes = null;
    private ?int $maxSize = null;

    private InterventionImage $image;

    public function __construct(string $content)
    {
        $this->image = InterventionManager::make($content);
    }

    public function getMimeType(): string
    {
        return $this->image->mime();
    }

    public function getExtension(): string
    {
        if (!isset(self::MIME_EXTENSIONS[$this->getMimeType()])) {
            throw $this->createException('Can not get extension because MIME type is incorrect', ['mime' => $this->getMimeType()]);
        }

        return self::MIME_EXTENSIONS[$this->getMimeType()];
    }

    public function getSize(): int
    {
        return $this->image->filesize();
    }

    public function isValid(): bool
    {
        if (!$this->isValidMimeType()) {
            return false;
        } elseif (!$this->isValidSize()) {
            return false;
        }

        return true;
    }

    public function isValidMimeType(): bool
    {
        return in_array($this->getMimeType(), $this->getAllowedMimeTypes());
    }

    public function getAllowedMimeTypes(): array
    {
        return $this->allowedMimeTypes ?? array_keys(self::MIME_EXTENSIONS);
    }

    public function getAllowedExtensions(): array
    {
        return array_map(function ($mimeType) {
            return self::MIME_EXTENSIONS[$mimeType];
        }, $this->getAllowedMimeTypes());
    }

    public function setAllowedMimeTypes(?array $mimeTypes): self
    {
        if (array_diff($mimeTypes, array_keys(self::MIME_EXTENSIONS))) {
            throw $this->createException('Can not set allowed given MIME types');
        }

        $this->allowedMimeTypes = $mimeTypes;
        return $this;
    }

    public function isValidSize(): bool
    {
        return null === $this->maxSize || $this->getSize() <= $this->maxSize;
    }

    public function getMaxSize(): ?int
    {
        return $this->maxSize;
    }

    public function setMaxSize(?int $size): self
    {
        $this->maxSize = $size;
        return $this;
    }

    public function crop(): self
    {
        $this->image->resizeCanvas(min($this->image->getWidth(), $this->image->getHeight()), min($this->image->getWidth(), $this->image->getHeight()));
        return $this;
    }

    public function resize(int $width, ?int $height = null): self
    {
        $height ??= $width;

        $this->image->resize($width, $height);

        return $this;
    }

    public function resizeMax(int $width, ?int $height = null): self
    {
        $height ??= $width;

        if ($width < $this->image->getWidth()) {
            $this->image->resize($width, null, function ($constraint) {$constraint->aspectRatio();});
        }

        if ($height < $this->image->getHeight()) {
            $this->image->resize(null, $height, function ($constraint) {$constraint->aspectRatio();});
        }

        return $this;
    }

    public function save(string $path, string $name): self
    {
        if ('.' . $this->getExtension() !== substr($name, -(strlen($this->getExtension()) + 1))) {
            $name .= '.' . $this->getExtension();
        }

        $filesystem = new Filesystem();

        if (!$filesystem->exists($path)) {
            $filesystem->mkdir($path);
        }

        $this->image->save(str_replace('//', '/', $path . '/' . $name));

        return $this;
    }

    public static function fromRequest(Request $request): ImageResolver
    {
        return new ImageResolver($request->getContent());
    }

    public static function fromUrl(string $url): ImageResolver
    {
        $content = file_get_contents($url);

        return new ImageResolver($content);
    }

    private function createException(string $message, array $context = []): LoggedException
    {
        return (new LoggedException($message))
            ->setLoggedContext($context)
            ->setExceptionedClass(self::class);
    }
}
