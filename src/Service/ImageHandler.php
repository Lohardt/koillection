<?php

declare(strict_types=1);

namespace App\Service;

use App\Annotation\Upload;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\Security\Core\Security;

class ImageHandler
{
    /**
     * @var RandomStringGenerator
     */
    private RandomStringGenerator $randomStringGenerator;

    /**
     * @var ThumbnailGenerator
     */
    private ThumbnailGenerator $thumbnailGenerator;

    /**
     * @var string
     */
    private string $publicPath;

    /**
     * @var PropertyAccessor
     */
    private PropertyAccessor $accessor;

    /**
     * @var DiskUsageCalculator
     */
    private DiskUsageCalculator $diskUsageCalculator;

    /**
     * @var Security
     */
    private Security $security;

    /**
     * ImageHandler constructor.
     * @param RandomStringGenerator $randomStringGenerator
     * @param ThumbnailGenerator $thumbnailGenerator
     * @param Security $security
     * @param DiskUsageCalculator $diskUsageCalculator
     * @param string $publicPath
     */
    public function __construct(
        RandomStringGenerator $randomStringGenerator,
        ThumbnailGenerator $thumbnailGenerator,
        Security $security,
        DiskUsageCalculator $diskUsageCalculator,
        string $publicPath
    ) {
        $this->randomStringGenerator = $randomStringGenerator;
        $this->thumbnailGenerator = $thumbnailGenerator;
        $this->security = $security;
        $this->diskUsageCalculator = $diskUsageCalculator;
        $this->publicPath = $publicPath;
        $this->accessor = PropertyAccess::createPropertyAccessor();
    }

    /**
     * @param object $entity
     * @param string $property
     * @param Upload $annotation
     * @throws \Exception
     */
    public function upload(object $entity, string $property, Upload $annotation)
    {
        $file = $this->accessor->getValue($entity, $property);

        if ($file instanceof UploadedFile) {
            $user = $this->security->getUser();
            $relativePath = 'uploads/'.$user->getId().'/';
            $absolutePath = $this->publicPath . '/' . $relativePath;

            $generatedName = $this->randomStringGenerator->generate(20);
            $extension = $file->guessExtension();

            $fileName = $generatedName;
            $fileName .=  $extension ? '.' . $extension : '';

            $this->diskUsageCalculator->hasEnoughSpaceForUpload($user, $file);
            $this->removeOldFile($entity, $annotation);
            $file->move($absolutePath, $fileName);
            $this->accessor->setValue($entity, $annotation->getPath(), $relativePath.$fileName);

            if ($annotation->getSmallThumbnailPath() !== null) {
                $smallThumbnailFileName = $generatedName . '_small.' . $extension;
                $result = $this->thumbnailGenerator->generate($absolutePath.'/'.$fileName, $absolutePath.'/'.$smallThumbnailFileName, 300);
                $this->accessor->setValue($entity, $annotation->getSmallThumbnailPath(), $result ? $relativePath.$smallThumbnailFileName : null);
            }

            if ($annotation->getOriginalFilenamePath() !== null) {
                $this->accessor->setValue($entity, $annotation->getOriginalFilenamePath(), $file->getClientOriginalName());
            }
        }
    }

    public function setFileFromFilename(object $entity, string $property, Upload $annotation)
    {
        $path = $this->accessor->getValue($entity, $annotation->getPath());

        if ($path !== null) {
            $file = new File($this->publicPath.'/'.$path,false);
            $this->accessor->setValue($entity, $property, $file);
        }
    }

    public function removeOldFile(object $entity, Upload $annotation)
    {
        if ($annotation->getPath() !== null) {
            $path = $this->accessor->getValue($entity, $annotation->getPath());
            if ($path !== null) {
                @unlink($this->publicPath.'/'.$path);
            }
        }

        if ($annotation->getSmallThumbnailPath() !== null) {
            $path = $this->accessor->getValue($entity, $annotation->getSmallThumbnailPath());
            if ($path !== null) {
                @unlink($this->publicPath.'/'.$path);
            }
        }
    }
}
