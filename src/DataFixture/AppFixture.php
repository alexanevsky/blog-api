<?php

namespace App\DataFixture;

use App\Entity\Blog\Category as BlogCategory;
use App\Entity\Blog\Comment as BlogComment;
use App\Entity\Blog\Post as BlogPost;
use App\Entity\User\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\Filesystem\Filesystem;
use function Symfony\Component\String\u;

class AppFixture extends Fixture
{
    public const ALIASES = [
        User::class =>          'users',
        BlogCategory::class =>  'blog_categories',
        BlogPost::class =>      'blog_posts',
        // BlogComment::class =>   'blog_comments'
    ];

    public const SETTERS = [
        User::class => [
            'password' => 'setPasswordHashed'
        ]
    ];

    public const TYPES_BOOLEAN = [
        BlogCategory::class =>  ['isActive'],
        BlogComment::class =>   ['isRemoved'],
        BlogPost::class =>      ['isPublished', 'isRemoved'],
        User::class =>          ['isEmailHidden', 'isBanned', 'isCommunicationBanned', 'isRemoved', 'isErased', 'isAllowedAdvNotifications']
    ];

    public const TYPES_INTEGER = [
        BlogCategory::class =>  ['sorting'],
        User::class =>          ['sorting']
    ];

    public const TYPES_JSON = [
        BlogComment::class =>   ['content'],
        BlogCategory::class =>  ['description'],
        BlogPost::class =>      ['description', 'content'],
        User::class =>          ['contacts', 'biography']
    ];

    public const TYPES_SIMPLE_ARRAY = [
        User::class => ['roles']
    ];

    public const TYPES_DATETIME = [];

    public const TYPES_DATETIME_MODIFIED = [
        BlogComment::class =>   ['createdAt', 'updatedAt', 'removedAt'],
        BlogPost::class =>      ['createdAt', 'publishedAt', 'updatedAt', 'removedAt'],
        User::class =>          ['createdAt', 'updatedAt', 'removedAt']
    ];

    public const TYPES_ENTITY = [
        BlogComment::class => [
            'author' =>         User::class,
            'post' =>           BlogPost::class,
            'parentComment' =>  BlogComment::class
        ],
        BlogPost::class => [
            'author' => User::class
        ]
    ];

    public const TYPES_COLLECTION = [
        BlogPost::class => [
            'categories' => BlogCategory::class
        ]
    ];

    private string $dir;

    private array $files = [];
    private array $entities = [];

    public function __construct(
        private ContainerBagInterface   $parameters,
        private Filesystem              $filesystem
    )
    {
        $this->dir = $this->parameters->get('app.dir.demo');
    }

    /**
     * Loads all fictures.
     */
    public function load(ObjectManager $manager): void
    {
        // Load files
        $files = glob(sprintf('%s/csv/*.csv', $this->dir));

        $this->files = array_combine(array_map(function($file) {
            return u(substr($file, strrpos($file, '/') + 1, -4))->snake();
        }, $files), $files);

        // Parse files
        foreach (self::ALIASES as $class => $alias) {
            if (!$file = $this->files[$alias]) {
                continue;
            }

            $f = fopen($file, 'r');
            $keys = null;

            while (false !== ($row = fgetcsv($f))) {
                if (!isset($keys)) {
                    $keys = array_flip(array_map(function($key) {return (string) u(strtolower($key))->camel();}, $row));
                    continue;
                }

                $entity = new $class();

                foreach ($row as $i => $value) {
                    if ($keys['id'] === $i) {
                        continue;
                    } elseif (false === array_search($i, $keys)) {
                        continue;
                    }

                    $property = array_search($i, $keys);
                    $value = trim($value);

                    // Convert boolean
                    if (in_array($property, self::TYPES_BOOLEAN[$class] ?? [])) {
                        $value = (!$value || 'false' === strtolower($value) || '0' === $value) ? false : true;
                    }

                    // Convert integer
                    elseif (in_array($property, self::TYPES_INTEGER[$class] ?? [])) {
                        $value = (int) $value;
                    }

                    // Convert json array
                    elseif (in_array($property, self::TYPES_JSON[$class] ?? [])) {
                        $value = 'null' === strtolower($value) ? null : json_decode($value, true);
                    }

                    // Convert simple array
                    elseif (in_array($property, self::TYPES_SIMPLE_ARRAY[$class] ?? [])) {
                        $value = array_map('trim', explode(',', $value));
                    }

                    // Convert datetime
                    elseif (in_array($property, self::TYPES_DATETIME[$class] ?? [])) {
                        $value = (!$value || 'null' === strtolower($value)) ? null : (new \DateTime($value));
                    }

                    // Convert and modify datetime
                    elseif (in_array($property, self::TYPES_DATETIME_MODIFIED[$class] ?? [])) {
                        $value = (!$value || 'null' === strtolower($value)) ? null : (new \DateTime())->modify($value);
                    }

                    // Convert entity
                    elseif (in_array($property, array_keys(self::TYPES_ENTITY[$class] ?? []))) {
                        $value = (!$value || 'null' === strtolower($value)) ? null : $this->entities[self::ALIASES[self::TYPES_ENTITY[$class][$property]]][$value];
                    }

                    // Convert collection
                    elseif (in_array($property, array_keys(self::TYPES_COLLECTION[$class] ?? []))) {
                        $value = (!$value) ? [] : array_map(function($val) use ($class, $property) {
                            return $this->entities[self::ALIASES[self::TYPES_COLLECTION[$class][$property]]][trim($val)];
                        }, explode(',', $value));
                    }

                    // Convert
                    if (in_array($property, array_keys(self::SETTERS[$class] ?? []))) {
                        $setter = self::SETTERS[$class][$property];
                    } elseif (0 === strpos($property, 'is')) {
                        $setter = 'set' . substr($property, 2);
                    } else {
                        $setter = (in_array($property, array_keys(self::TYPES_COLLECTION[$class] ?? [])) ? 'add' : 'set') . ucwords($property);
                    }

                    $entity->$setter($value);
                }

                $this->entities[$alias][$row[$keys['id']]] = $entity;

                $manager->persist($entity);
            }

            unset($keys);
            fclose($f);
        }

        // Execute
        $manager->flush();

        // Load uploads
        $this->loadUploads();
    }

    /**
     * Loads all uploads.
     */
    private function loadUploads(): void
    {
        foreach (glob(sprintf('%s/public/uploads/*', $this->parameters->get('kernel.project_dir'))) as $item) {
            $this->filesystem->remove($item);
        }

        $this->filesystem->mirror(
            sprintf('%s/uploads', $this->dir),
            sprintf('%s/public/uploads', $this->parameters->get('kernel.project_dir'))
        );
    }
}
