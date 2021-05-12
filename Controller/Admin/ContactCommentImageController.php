<?php

/*
 * This file is part of the ContactManagement Plugin
 *
 * Copyright (C) 2020 Diezon.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\ContactManagement\Controller\Admin;

use Eccube\Controller\AbstractController;
use Plugin\ContactManagement\Repository\ContactCommentImageRepository;
use Plugin\ContactManagement\Service\ContactValidator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ContactCommentImageController extends AbstractController
{
    /**
     * @var ContactCommentImageRepository
     */
    protected $contactCommentImageRepository;

    /**
     * @var ContactValidator
     */
    protected $contactValidator;

    /**
     * IdentificationImageController constructor.
     * @param ContactCommentImageRepository $contactCommentImageRepository
     * @param ContactValidator $contactValidator
     */
    public function __construct(ContactCommentImageRepository $contactCommentImageRepository,
                                ContactValidator $contactValidator)
    {
        $this->contactCommentImageRepository = $contactCommentImageRepository;
        $this->contactValidator = $contactValidator;
    }

    /**
     * @Route("/contact_comment/temp" , name="admin_contact_comment_get_image")
     * This get Image temp when user drop image or click upload image (not save to Identification)
     * @param Request $request
     * @return Response
     */
    public function getTempImage(Request $request)
    {
        // Not check customer because when user drop image or click upload
        // Image not save to contact yet so it only get from temp folder
        $filename = $request->get('file_name');

        if (!$this->contactValidator->fileExistsInDirs($filename, [$this->eccubeConfig['eccube_temp_image_contact_comment_dir']])) {
            throw new NotFoundHttpException();
        }

        // config url temp image
        $filepath = $this->eccubeConfig['eccube_temp_image_contact_comment_dir'].'/'.$filename;

        $response = new Response();

        // Set headers
        $response->headers->set('Cache-Control', 'private');
        $response->headers->set('Content-type', mime_content_type($filepath));
        $response->headers->set('Content-Disposition', 'attachment; filename="'.basename($filepath).'";');
        $response->headers->set('Content-length', filesize($filepath));

        // Send headers before outputting anything
        $response->sendHeaders();

        $response->setContent(file_get_contents($filepath));

        return $response;
    }

    /**
     * @Route("/contact_comment_image" , name="admin_contact_comment_image")
     * @param Request $request
     * @return Response
     */
    public function getSavedImage(Request $request)
    {
        if ($this->session->has('_security_admin') || $this->session->has('_security_customer')) {
            // check customer because image has been save to Identification table
            $ContactCommentImage = $this->contactCommentImageRepository->findOneBy([
                'file_name' => $request->get('file_name'),
            ]);

            if (!$ContactCommentImage) {
                return $this->json(['status' => 'NG'], 404);
            }

            $ContactComment = $ContactCommentImage->getContactComment();

            if (!$ContactComment) {
                return $this->json(['status' => 'NG'], 404);
            }

            $filename = $request->get('file_name');

            // config url saved image
            $filepath = $this->eccubeConfig['eccube_image_contact_comment_dir'].'/'.$filename;

            $response = new Response();

            // Set headers
            $response->headers->set('Cache-Control', 'private');
            $response->headers->set('Content-type', mime_content_type($filepath));
            $response->headers->set('Content-Disposition', 'attachment; filename="'.basename($filepath).'";');
            $response->headers->set('Content-length', filesize($filepath));

            // Send headers before outputting anything
            $response->sendHeaders();

            $response->setContent(file_get_contents($filepath));

            return $response;
        }
    }
}
