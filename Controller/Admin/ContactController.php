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
use Eccube\Entity\BaseInfo;
use Plugin\ContactManagement\Entity\ContactCommentImage;
use Plugin\ContactManagement\Form\Type\Admin\ContactType;
use Plugin\ContactManagement\Form\Type\Admin\SearchContactType;
use Eccube\Repository\BaseInfoRepository;
use Eccube\Repository\Master\PageMaxRepository;
use Eccube\Util\FormUtil;
use Knp\Component\Pager\Paginator;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Form\FormInterface;
use Plugin\ContactManagement\Service\ContactService;
use Plugin\ContactManagement\Entity\Contact;
use Eccube\Form\Type\Admin\SearchCustomerType;
use Eccube\Entity\Master\CustomerStatus;
use Eccube\Repository\CustomerRepository;
use Plugin\ContactManagement\Repository\ContactRepository;
use Plugin\ContactManagement\Repository\ContactCommentRepository;
use Plugin\ContactManagement\Repository\ContactTemplateRepository;
use Plugin\ContactManagement\Repository\Master\ContactStatusRepository;
use Plugin\ContactManagement\Entity\Master\ContactStatus;
use Twig\Environment;
use Eccube\Repository\MemberRepository;
use Plugin\ContactManagement\Service\MailService;
use Twig\Error\LoaderError;
use Plugin\ContactManagement\Repository\ContactCommentImageRepository;
use Plugin\ContactManagement\Entity\ContactComment;


class ContactController extends AbstractController
{
    /**
     * @var BaseInfo
     */
    protected $BaseInfo;

    /**
     * @var PageMaxRepository
     */
    protected $pageMaxRepository;

    /**
     * @var ContactRepository
     */
    protected $contactRepository;

    /**
     * @var ContactCommentRepository
     */
    protected $contactCommentRepository;

    /**
     * @var TokenStorageInterface
     */
    protected $tokenStorage;

    /**
     * @var ContactService
     */
    protected $contactService;

    /**
     * @var ContactTemplateRepository
     */
    protected $contactTemplateRepository;

    /**
     * @var ContactStatusRepository
     */
    protected $contactStatusRepository;

    /**
     * @var MemberRepository
     */
    protected $memberRepository;

    /**
     * @var MailService
     */
    protected $mailService;

    /**
     * @var CustomerRepository
     */
    protected $customerRepository;

    /**
     * @var ContactCommentImageRepository
     */
    protected $contactCommentImageRepository;

    /**
     * ContactController constructor.
     *
     * @param BaseInfoRepository $baseInfoRepository
     * @param PageMaxRepository $pageMaxRepository
     * @param ContactService $contactService
     * @param CustomerRepository $customerRepository
     * @param ContactRepository $contactRepository
     * @param ContactCommentRepository $contactCommentRepository
     * @param TokenStorageInterface $tokenStorage
     * @param ContactTemplateRepository $contactTemplateRepository
     * @param ContactStatusRepository $contactStatusRepository
     * @param MemberRepository $memberRepository
     * @param MailService $mailService
     * @param ContactCommentImageRepository $contactCommentImageRepository
     */
    public function __construct(
        BaseInfoRepository $baseInfoRepository,
        PageMaxRepository $pageMaxRepository,
        ContactService $contactService,
        CustomerRepository $customerRepository,
        ContactRepository $contactRepository,
        ContactCommentRepository $contactCommentRepository,
        TokenStorageInterface $tokenStorage,
        ContactTemplateRepository $contactTemplateRepository,
        ContactStatusRepository $contactStatusRepository,
        MemberRepository $memberRepository,
        MailService $mailService,
        ContactCommentImageRepository $contactCommentImageRepository
    ) {
        $this->BaseInfo = $baseInfoRepository->get();
        $this->pageMaxRepository = $pageMaxRepository;
        $this->contactService = $contactService;
        $this->customerRepository = $customerRepository;
        $this->contactRepository = $contactRepository;
        $this->contactCommentRepository = $contactCommentRepository;
        $this->tokenStorage = $tokenStorage;
        $this->contactTemplateRepository = $contactTemplateRepository;
        $this->contactStatusRepository = $contactStatusRepository;
        $this->memberRepository = $memberRepository;
        $this->mailService = $mailService;
        $this->contactCommentImageRepository = $contactCommentImageRepository;
    }

    /**
     * @Route("/%eccube_admin_route%/contact", name="admin_contact")
     * @Route("/%eccube_admin_route%/contact/page/{page_no}", requirements={"page_no" = "\d+"}, name="admin_contact_page")
     * @Template("@ContactManagement/admin/Contact/index.twig")
     */
    public function index(Request $request, $page_no = null, Paginator $paginator)
    {
        $builder = $this->formFactory
            ->createBuilder(SearchContactType::class);

        $searchForm = $builder->getForm();

        /**
         * ページの表示件数は, 以下の順に優先される.
         * - リクエストパラメータ
         * - セッション
         * - デフォルト値
         * また, セッションに保存する際は mtb_page_maxと照合し, 一致した場合のみ保存する.
         **/
        $page_count = $this->session->get('eccube.admin.contact.search.page_count', 100);

        $page_count_param = (int) $request->get('page_count');
        $pageMaxis = $this->pageMaxRepository->findAll();

        if ($page_count_param) {
            foreach ($pageMaxis as $pageMax) {
                if ($page_count_param == $pageMax->getName()) {
                    $page_count = $pageMax->getName();
                    $this->session->set('eccube.admin.contact.search.page_count', $page_count);
                    break;
                }
            }
        }

        if ('POST' === $request->getMethod()) {
            $searchForm->handleRequest($request);

            if ($searchForm->isValid()) {
                /**
                 * 検索が実行された場合は, セッションに検索条件を保存する.
                 * ページ番号は最初のページ番号に初期化する.
                 */
                $page_no = 1;
                $searchData = $searchForm->getData();

                // 検索条件, ページ番号をセッションに保持.
                $this->session->set('eccube.admin.contact.search', FormUtil::getViewData($searchForm));
                $this->session->set('eccube.admin.contact.search.page_no', $page_no);
            } else {
                // 検索エラーの際は, 詳細検索枠を開いてエラー表示する.
                return [
                    'searchForm' => $searchForm->createView(),
                    'pagination' => [],
                    'pageMaxis' => $pageMaxis,
                    'page_no' => $page_no,
                    'page_count' => $page_count,
                    'has_errors' => true,
                ];
            }
        } else {
            if (null !== $page_no || $request->get('resume')) {
                /*
                 * ページ送りの場合または、他画面から戻ってきた場合は, セッションから検索条件を復旧する.
                 */
                if ($page_no) {
                    // ページ送りで遷移した場合.
                    $this->session->set('eccube.admin.contact.search.page_no', (int) $page_no);
                } else {
                    // 他画面から遷移した場合.
                    $page_no = $this->session->get('eccube.admin.contact.search.page_no', 1);
                }
                $viewData = $this->session->get('eccube.admin.contact.search', []);
                $searchData = FormUtil::submitAndGetData($searchForm, $viewData);
            } else {
                /**
                 * 初期表示の場合.
                 */
                $page_no = 1;
                $viewData = [];
                if ($statusId = (int) $request->get('contact_status_id')) {
                    $viewData['status'] = $statusId;
                } else {
                    // submit default value
                    $viewData = FormUtil::getViewData($searchForm);
                }

                $searchData = FormUtil::submitAndGetData($searchForm, $viewData);
                // セッション中の検索条件, ページ番号を初期化.
                $this->session->set('eccube.admin.contact.search', $viewData);
                $this->session->set('eccube.admin.contact.search.page_no', $page_no);
            }
        }

        $qb = $this->contactRepository->getQueryBuilderBySearchDataForAdmin($searchData);

        $pagination = $paginator->paginate(
            $qb,
            $page_no,
            $page_count
        );

        $lowPriority = [ContactStatus::RESOLVED,
            ContactStatus::DO_NOT_CORRESPOND, ];

        return [
            'searchForm' => $searchForm->createView(),
            'pagination' => $pagination,
            'pageMaxis' => $pageMaxis,
            'page_no' => $page_no,
            'page_count' => $page_count,
            'has_errors' => false,
            'lowPriority' => $lowPriority,
        ];
    }

    /**
     * @Route("/%eccube_admin_route%/contact/contact/new", name="admin_contact_contact_new")
     * @Route("/%eccube_admin_route%/contact/contact/{id}/edit", requirements={"id" = "\d+"}, name="admin_contact_contact_edit")
     * @Template("@ContactManagement/admin/Contact/edit.twig")
     */
    public function edit(Request $request, $id = null)
    {
        if (is_null($id)) {
            $Contact = new Contact();
            $ContactStatus = $this->contactStatusRepository->find(ContactStatus::NEW_RECEPTION);
            $Contact->setUrl($this->contactRepository->getUniqueUrl());
            $Contact->setReplied(false);
            $Contact->setContactStatus($ContactStatus);
        } else {
            $Contact = $this->contactRepository->find($id);
        }

        $saveDraft = false;
        if ($request->get('mode') == 'saveDraft') {
            $saveDraft = true;
        }
        $builder = $this->formFactory
            ->createBuilder(ContactType::class, $Contact, [
                'saveDraft' => $saveDraft,
            ]);

        $LoginedMember = $this->tokenStorage->getToken()->getUser();
        $form = $builder->getForm();

        if ('POST' === $request->getMethod()) {
            $form->handleRequest($request);
            if ($form->isValid()) {
                if ($request->get('mode') == 'send') {
                    $Contact = $form->getData();
                    if (is_null($Contact->getChargeMember())) {
                        $Contact->setChargeMember($LoginedMember);
                    }

                    $ContactComment = $form['ContactComment']->getData();

                    $CustomerId = null;
                    if ($Contact->getCustomer()) {
                        $CustomerId = $Contact->getCustomer()->getId();
                    }
                    $baseUrl = $this->generateUrl('homepage', [], UrlGeneratorInterface::ABSOLUTE_URL);
                    $replyUrl = $Contact->getReplyUrl($baseUrl);
                    $LatestCustomerComment = null;
                    if ($Contact->getId()) {
                        $LatestCustomerContactComment = $this->contactCommentRepository->getLatestCommentsFromCustomer($Contact);
                        if ($LatestCustomerContactComment) {
                            $LatestCustomerComment = $LatestCustomerContactComment->getComment();
                        }
                    }

                    $twig = new \Twig_Environment(new \Twig_Loader_Array([]));
                    $template = $twig->createTemplate($ContactComment->getComment());
                    $comment = $template->render(['ContactFullName' => $Contact->getName01().$Contact->getName02(),
                        'CustomerId' => $CustomerId,
                        'replyUrl' => $replyUrl,
                        'LatestCustomerComment' => $LatestCustomerComment,
                    ]);

                    $ContactComment->setComment($comment);
                    $ContactComment->setContact($Contact);
                    $ContactComment->setMember($LoginedMember);
                    $ContactComment->setSend(true);
                    $ContactComment->setMemo(false);

                    $Contact->setUpdateMember($LoginedMember);
                    $Contact->setReplied(true);
                    $Contact->addContactComment($ContactComment);

                    $this->contactService->deleteTempImage($form['ContactComment']);
                    $this->registerContactCommentImage($form['ContactComment'], $ContactComment);

                    $this->entityManager->persist($Contact);
                    $this->entityManager->flush();

                    // メール送信
                    if ($ContactComment->isSend()) {
                        $this->mailService->sendContactMailFromStore($Contact);
                    }

                    $this->addSuccess('admin.contact.send_complete', 'admin');

                    return $this->redirectToRoute('admin_contact_contact_edit', ['id' => $Contact->getId()]);
                } elseif ($request->get('mode') == 'memo') {
                    $Contact = $form->getData();
                    if (is_null($Contact->getChargeMember())) {
                        $Contact->setChargeMember($LoginedMember);
                    }
                    $Contact->setUpdateMember($LoginedMember);

                    if ($form['ContactComment']->get('subject')->getData() || $form['ContactComment']->get('comment')->getData()) {
                        $ContactComment = $form['ContactComment']->getData();
                        $ContactComment->setContact($Contact);
                        $ContactComment->setMember($LoginedMember);
                        $ContactComment->setSend(false);
                        $ContactComment->setMemo(false);

                        $Contact->addContactComment($ContactComment);

                        $this->contactService->deleteTempImage($form['ContactComment']);
                        $this->registerContactCommentImage($form['ContactComment'], $ContactComment);
                    }

                    $ContactComment = new ContactComment();
                    $ContactComment->setContact($Contact);
                    $ContactComment->setComment($form['ContactComment']->get('memo_comment')->getData());
                    $ContactComment->setMember($LoginedMember);
                    $ContactComment->setSend(false);
                    $ContactComment->setMemo(true);
                    $Contact->addContactComment($ContactComment);

                    $this->entityManager->persist($Contact);
                    $this->entityManager->flush();

                    $this->addSuccess('admin.contact.registration_complete', 'admin');

                    return $this->redirectToRoute('admin_contact_contact_edit', ['id' => $Contact->getId()]);
                } else {
                    $Contact = $form->getData();
                    if (is_null($Contact->getChargeMember())) {
                        $Contact->setChargeMember($LoginedMember);
                    }
                    $Contact->setUpdateMember($LoginedMember);

                    $ContactComment = $form['ContactComment']->getData();
                    $ContactComment->setContact($Contact);
                    $ContactComment->setMember($LoginedMember);
                    $ContactComment->setSend(false);
                    $ContactComment->setMemo(false);

                    $Contact->addContactComment($ContactComment);

                    $this->contactService->deleteTempImage($form['ContactComment']);
                    $this->registerContactCommentImage($form['ContactComment'], $ContactComment);

                    $this->entityManager->persist($Contact);
                    $this->entityManager->flush();

                    $this->addSuccess('admin.common.save_complete', 'admin');

                    return $this->redirectToRoute('admin_contact_contact_edit', ['id' => $Contact->getId()]);
                }
            }
        }

        // 会員検索フォーム
        $builder = $this->formFactory
            ->createBuilder(SearchCustomerType::class);

        $searchCustomerModalForm = $builder->getForm();

        $tradingHistories = null;
        $SearchedCustomer = null;
        if ($Contact->getCustomer()) {
            $Customer = $Contact->getCustomer();
            $tradingHistories = $this->contactService->searchTransactionHistoryByCustomerId($Customer);
        } else {
            $email = $Contact->getEmail();
            $tradingHistories = $this->contactService->searchTransactionHistoryByEmail($email);
            $SearchedCustomer = $this->customerRepository->findOneby(['email' => $email]);
        }

        $haveExistingSubject = false;

        if ($Contact->getId()) {
            $haveExistingSubject = $this->contactCommentRepository->haveExistingSubject($Contact);
        }

        return [
            'Contact' => $Contact,
            'form' => $form->createView(),
            'id' => $id,
            'searchCustomerModalForm' => $searchCustomerModalForm->createView(),
            'tradingHistories' => $tradingHistories,
            'SearchedCustomer' => $SearchedCustomer,
            'LoginedMemberId' => $LoginedMember->getId(),
            'BaseInfo' => $this->BaseInfo,
            'haveExistingSubject' => $haveExistingSubject,
        ];
    }

    /**
     * @Route("/%eccube_admin_route%/contact/image/add", name="admin_contact_image_add", methods={"POST"})
     */
    public function addImage(Request $request)
    {
        if ($this->session->has('_security_admin')) {
            if (!$request->isXmlHttpRequest()) {
                throw new BadRequestHttpException();
            }

            $images = $request->files->get('contact')['ContactComment'];

            $allowExtensions = ['jpg', 'jpeg', 'png', 'JPG', 'JPEG', 'PNG'];
            $files = [];
            if (count($images) > 0) {
                foreach ($images as $image) {
                    //ファイルフォーマット検証
                    $mimeType = $image->getMimeType();
                    if (0 !== strpos($mimeType, 'image')) {
                        throw new UnsupportedMediaTypeHttpException();
                    }

                    // 拡張子
                    $extension = $image->getClientOriginalExtension();
                    if (!in_array($extension, $allowExtensions)) {
                        throw new UnsupportedMediaTypeHttpException();
                    }

                    $filename = date('mdHis').uniqid('_').'.'.$extension;
                    $image->move($this->eccubeConfig['eccube_temp_image_contact_comment_dir'], $filename);
                    $files[] = $filename;
                }
            }

            return $this->json(['files' => $files], 200);
        }
    }

    /**
     * Register ContactCommentImage.
     *
     * @param $form FormInterface
     * @param $ContactComment ContactComment
     */
    private function registerContactCommentImage($form, $ContactComment)
    {
        $ContactCommentImageNames = [];
        if (!empty($form->get('image_name_1')->getData())) {
            $ContactCommentImageNames[] = $form->get('image_name_1')->getData();
        }
        if (!empty($form->get('image_name_2')->getData())) {
            $ContactCommentImageNames[] = $form->get('image_name_2')->getData();
        }
        if ($ContactComment->getContactCommentImages()) {
            $ContactCommentImages = $ContactComment->getContactCommentImages();
            foreach ($ContactCommentImages as $ContactCommentImage) {
                $ContactCommentImageFileName = $ContactCommentImage->getFileName();
                if (empty($ContactCommentImageNames) || !in_array($ContactCommentImageFileName, $ContactCommentImageNames)) {
                    $ContactComment->removeContactCommentImage($ContactCommentImage);
                    $this->entityManager->remove($ContactCommentImage);
                    $fs = new Filesystem();
                    $fs->remove($this->eccubeConfig['eccube_temp_image_contact_comment_dir'].'/'.$ContactCommentImageFileName);
                }
            }
        }

        if (!empty($ContactCommentImageNames)) {
            foreach ($ContactCommentImageNames as $key => $ContactCommentImageName) {
                if ($ContactComment->isSend()) {
                    $ContactCommentImage = $this->eccubeConfig['eccube_temp_image_contact_comment_dir'].'/'.$ContactCommentImageName;
                    if (file_exists($ContactCommentImage)) {
                        $file = new File($ContactCommentImage);
                        $file->move($this->eccubeConfig['eccube_image_contact_comment_dir']);
                    }
                }
                $ContactCommentImage = $this->contactCommentImageRepository->findOneBy([
                    'file_name' => $ContactCommentImageName,
                ]);
                if (!$ContactCommentImage) {
                    $ContactCommentImage = new ContactCommentImage();
                    $ContactCommentImage
                        ->setContactComment($ContactComment)
                        ->setFileName($ContactCommentImageName)
                        ->setSortNo($key + 1);
                    $ContactComment->addContactCommentImage($ContactCommentImage);
                    $this->entityManager->persist($ContactCommentImage);
                }
            }
        }
    }

    /**
     * 顧客情報を検索する.
     *
     * @Route("/%eccube_admin_route%/contact/search/contact/html", name="admin_contact_search_customer_html")
     * @Route("/%eccube_admin_route%/contact/search/contact/html/page/{page_no}", requirements={"page_No" = "\d+"}, name="admin_contact_search_customer_html_page")
     * @Template("@ContactManagement/admin/Contact/search_customer.twig")
     *
     * @param Request $request
     * @param integer $page_no
     * @param Paginator $paginator
     *
     * @return array
     */
    public function searchCustomerHtml(Request $request, $page_no = null, Paginator $paginator)
    {
        if ($request->isXmlHttpRequest() && $this->isTokenValid()) {
            log_debug('search customer start.');
            $page_count = $this->eccubeConfig['eccube_default_page_count'];
            $session = $this->session;

            if ('POST' === $request->getMethod()) {
                $page_no = 1;

                $searchData = [
                    'multi' => $request->get('search_word'),
                    'customer_status' => [
                        CustomerStatus::REGULAR,
                    ],
                ];

                $session->set('eccube.admin.order.customer.search', $searchData);
                $session->set('eccube.admin.order.customer.search.page_no', $page_no);
            } else {
                $searchData = (array) $session->get('eccube.admin.order.customer.search');
                if (is_null($page_no)) {
                    $page_no = intval($session->get('eccube.admin.order.customer.search.page_no'));
                } else {
                    $session->set('eccube.admin.order.customer.search.page_no', $page_no);
                }
            }

            $qb = $this->customerRepository->getQueryBuilderBySearchData($searchData);

            /** @var \Knp\Component\Pager\Pagination\SlidingPagination $pagination */
            $pagination = $paginator->paginate(
                $qb,
                $page_no,
                $page_count,
                ['wrap-queries' => true]
            );

            /** @var $Customers \Eccube\Entity\Customer[] */
            $Customers = $pagination->getItems();

            if (empty($Customers)) {
                log_debug('search customer not found.');
            }

            $data = [];
            $formatName = '%s%s(%s%s)';
            foreach ($Customers as $Customer) {
                $data[] = [
                    'id' => $Customer->getId(),
                    'name' => sprintf($formatName, $Customer->getName01(), $Customer->getName02(),
                        $Customer->getKana01(),
                        $Customer->getKana02()),
                    'phone_number' => $Customer->getPhoneNumber(),
                    'email' => $Customer->getEmail(),
                ];
            }

            return [
                'data' => $data,
                'pagination' => $pagination,
            ];
        }
    }

    /**
     * 顧客情報を検索する.
     *
     * @Route("/%eccube_admin_route%/contact/search/customer/id", name="admin_contact_search_customer_by_id", methods={"POST"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function searchCustomerById(Request $request)
    {
        if ($request->isXmlHttpRequest() && $this->isTokenValid()) {
            log_debug('search customer by id start.');

            /** @var $Customer \Eccube\Entity\Customer */
            $Customer = $this->customerRepository
                ->find($request->get('id'));

            if (is_null($Customer)) {
                log_debug('search customer by id not found.');

                return $this->json([], 404);
            }

            log_debug('search customer by id found.');

            $data = [
                'id' => $Customer->getId(),
                'name01' => $Customer->getName01(),
                'name02' => $Customer->getName02(),
                'kana01' => $Customer->getKana01(),
                'kana02' => $Customer->getKana02(),
                'email' => $Customer->getEmail(),
                'phone_number' => $Customer->getPhoneNumber(),
                'note' => $Customer->getNote(),
            ];

            return $this->json($data);
        }
    }

    /**
     * @Route("/%eccube_admin_route%/contact/search/mail_template/id", name="admin_contact_search_mail_template_by_id", methods={"POST"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * @throws LoaderError
     */
    public function searchContactMailTemplateById(Request $request, Environment $twig)
    {
        if ($request->isXmlHttpRequest() && $this->isTokenValid()) {
            log_debug('search mail template by id start.');

            /** @var $Customer \Eccube\Entity\Customer */
            $ContactTemplate = $this->contactTemplateRepository
                ->find($request->get('id'));

            if (is_null($ContactTemplate)) {
                log_debug('search mail template by id not found.');

                return $this->json(false);
            }

            log_debug('search mail template by id found.');

            $comment = $twig->getLoader()
                ->getSourceContext($ContactTemplate->getFileName())
                ->getCode();

            $data = [
                'subject' => $ContactTemplate->getMailSubject(),
                'comment' => $comment,
            ];

            return $this->json($data);
        }
    }

    /**
     * コメントを削除する
     *
     * @Route("/%eccube_admin_route%/contact/contact/{id}/delete", requirements={"id" = "\d+"}, name="admin_contact_comment_delete", methods={"DELETE"})
     */
    public function delete(Request $request, ContactComment $ContactComment)
    {
        $this->isTokenValid();

        log_info('コメント削除開始', [$ContactComment->getId()]);
        $Contact = $ContactComment->getContact();
        try {
            $this->contactCommentRepository->delete($ContactComment);

            $this->addSuccess('admin.common.delete_complete', 'admin');

            log_info('コメント削除完了', [$ContactComment->getId()]);
        } catch (\Exception $e) {
            log_info('コメント削除エラー', [$ContactComment->getId(), $e]);

            $message = trans('admin.common.delete_error');
            $this->addError($message, 'admin');
        }

        return $this->redirectToRoute('admin_contact_contact_edit', ['id' => $Contact->getId()]);
    }

    /**
     * 閲覧情報を記録する
     *
     * @Route("/%eccube_admin_route%/contact/browsing_record", name="admin_contact_browsing_record", methods={"POST"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * @throws Exception
     */
    public function contactBrowsingRecord(Request $request)
    {
        $Contact = $this->contactRepository->find($request->request->get('id'));
        $BrowseMember = $this->memberRepository->find($request->request->get('memberId'));

        if (!$Contact) {
            throw new NotFoundHttpException();
        }

        $now = new \DateTime();

        $Contact->setBrowseMember($BrowseMember);
        $Contact->setBrowseDate($now);

        $this->entityManager->flush();

        $message = ['status' => 'OK'];
        $response = $this->json($message);

        return $response;
    }

    /**
     * @Route("/%eccube_admin_route%/contact/another_browsing", name="admin_contact_another_browsing", methods={"POST"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function contactAnotherBrowsing(Request $request)
    {
        $Contact = $this->contactRepository->find($request->request->get('id'));
        $BrowseMember = $this->memberRepository->find($request->request->get('memberId'));

        if (!$Contact) {
            throw new NotFoundHttpException();
        }

        $openModal = false;
        if ($Contact->getBrowseDate()) {
            $BrowseDate = $Contact->getBrowseDate()->format('Y-m-d H:i:s');
            $oneMinuteAgo = date('Y-m-d H:i:s', strtotime('-1 minute'));
            $AnotherBrowseMember = $Contact->getBrowseMember();

            if ($oneMinuteAgo < $BrowseDate && $BrowseMember !== $AnotherBrowseMember) {
                $openModal = true;
            }
        }
        return $this->json($openModal);
    }
}
