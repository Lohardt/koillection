<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\AbstractController;
use App\Entity\Collection;
use App\Entity\Item;
use App\Entity\Tag;
use App\Entity\User;
use App\Entity\Wish;
use App\Entity\Wishlist;
use App\Service\CommandExecutor;
use App\Service\DatabaseDumper;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\Annotation\Route;
use ZipStream\Option\Archive;
use ZipStream\ZipStream;

/**
 * @IsGranted("ROLE_ADMIN")
 */
class AdminController extends AbstractController
{
    /**
     * @Route({
     *     "en": "/admin",
     *     "fr": "/admin"
     * }, name="app_admin_index", methods={"GET"})
     *
     * @return Response
     */
    public function index() : Response
    {
        $em = $this->getDoctrine()->getManager();

        return $this->render('App/Admin/Admin/index.html.twig', [
            'freeSpace' => disk_free_space('/'),
            'totalSpace' => disk_total_space('/'),
            'counters' => [
                'users' => $em->getRepository(User::class)->count([]),
                'collections' => $em->getRepository(Collection::class)->count([]),
                'items' => $em->getRepository(Item::class)->count([]),
                'tags' => $em->getRepository(Tag::class)->count([]),
                'wishlists' => $em->getRepository(Wishlist::class)->count([]),
                'wishes' => $em->getRepository(Wish::class)->count([]),
            ],
            'symfonyVersion' => Kernel::VERSION,
            'phpVersion' => phpversion(),
            'isOpcacheAvailable' => function_exists('opcache_get_status') && opcache_get_status() && opcache_get_status()['opcache_enabled']
        ]);
    }

    /**
     * @Route({
     *     "en": "/admin/clean-up",
     *     "fr": "/admin/nettoyer"
     * }, name="app_admin_clean_up", methods={"GET"})
     *
     * @param CommandExecutor $commandExecutor
     * @return Response
     */
    public function cleanUp(CommandExecutor $commandExecutor) : Response
    {
        $this->addFlash('notice', $commandExecutor->execute('app:clean-up'));

        return $this->redirectToRoute('app_admin_index');
    }

    /**
     * @Route({
     *     "en": "/admin/regenerate-thumbnails",
     *     "fr": "/admin/regenerer-les-miniatures"
     * }, name="app_admin_regenerate_thumbnails", methods={"GET"})
     *
     * @param CommandExecutor $commandExecutor
     * @return Response
     */
    public function regenerateThumbnails(CommandExecutor $commandExecutor) : Response
    {
        $this->addFlash('notice', $commandExecutor->execute('app:regenerate-thumbnails'));

        return $this->redirectToRoute('app_admin_index');
    }

    /**
     * @Route({
     *     "en": "/admin/backup",
     *     "fr": "/admin/sauvegarde"
     * }, name="app_admin_backup", methods={"GET"})
     *
     * @param DatabaseDumper $databaseDumper
     * @return StreamedResponse
     */
    public function backup(DatabaseDumper $databaseDumper) : StreamedResponse
    {
        $users = $this->getDoctrine()->getRepository(User::class)->findAll();

        return new StreamedResponse(function () use ($databaseDumper, $users) {
            $options = new Archive();
            $options->setContentType('text/event-stream');
            $options->setFlushOutput(true);
            $options->setSendHttpHeaders(true);

            $zipFilename = (new \DateTime())->format('Ymd') . '-koillection-backup.zip';
            $zip = new ZipStream($zipFilename, $options);

            foreach ($users as $user) {
                $path = $this->getParameter('kernel.project_dir').'/public/uploads/'. $user->getId();

                $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path), \RecursiveIteratorIterator::LEAVES_ONLY);
                foreach ($files as $name => $file) {
                    if (!$file->isDir()) {
                        $zip->addFileFromStream('/public/uploads/'. $user->getId() . '/' . $file->getFilename(), fopen($file->getRealPath(), 'r'));
                    }
                }
            }

            $fh = fopen('php://memory', 'r+');
            foreach ($databaseDumper->dump() as $row) {
                fwrite($fh, $row);
            }
            rewind($fh);
            $zip->addFileFromStream((new \DateTime())->format('Ymd') . '-koillection-export.sql', $fh);
            fclose($fh);

            $zip->finish();
        });
    }
}
