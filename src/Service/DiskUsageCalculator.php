<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Contracts\Translation\TranslatorInterface;

class DiskUsageCalculator
{
    /**
     * @var TranslatorInterface
     */
    private TranslatorInterface $translator;

    /**
     * @var EntityManagerInterface
     */
    private EntityManagerInterface $em;

    /**
     * @var string
     */
    private string $publicPath;

    /**
     * DiskUsageCalculator constructor.
     * @param TranslatorInterface $translator
     * @param EntityManagerInterface $em
     * @param string $publicPath
     */
    public function __construct(TranslatorInterface $translator, EntityManagerInterface $em, string $publicPath)
    {
        $this->translator = $translator;
        $this->em = $em;
        $this->publicPath = $publicPath;
    }

    public function getSpaceUsedByUsers() : float
    {
        $uploadFolderPath = $this->publicPath . '/uploads';

        if (is_dir($uploadFolderPath)) {
            return $this->getFolderSize($uploadFolderPath);
        }

        return 0;
    }

    /**
     * @param User $user
     * @return float
     */
    public function getSpaceUsedByUser(User $user) : float
    {
        $userFolderPath = $this->publicPath . '/uploads/' . $user->getId();

        if (is_dir($userFolderPath)) {
            return $this->getFolderSize($userFolderPath);
        }

        return 0;
    }

    /**
     * @param User $user
     * @param File $file
     * @throws \Exception
     */
    public function hasEnoughSpaceForUpload(User $user, File $file)
    {
        if ($user->getDiskSpaceAllowed() - $this->getSpaceUsedByUser($user) < $file->getSize()) {
            throw new \Exception($this->translator->trans('error.not_enough_space'));
        }
    }

    private function getFolderSize($path) : float
    {
        $size = 0;
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path)) as $file) {
            $size += $file->getSize();
        }

        return $size;
    }
}
