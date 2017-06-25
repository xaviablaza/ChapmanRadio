<?php
namespace RestfulBundle\Controller\Api\V3;


use CoreBundle\Entity\Comment;
use CoreBundle\Entity\Event;
use CoreBundle\Entity\Image;
use CoreBundle\Entity\Show;
use CoreBundle\Entity\Tag;
use CoreBundle\Helper\RestfulEnvelope;
use CoreBundle\Normalizer\CommentNormalizer;
use CoreBundle\Normalizer\EventNormalizer;
use CoreBundle\Normalizer\PaginatorNormalizer;
use CoreBundle\Normalizer\ShowNormalizer;
use CoreBundle\Normalizer\UserNormalizer;
use CoreBundle\Repository\CommentRepository;
use CoreBundle\Repository\EventRepository;
use CoreBundle\Repository\ShowRepository;
use CoreBundle\Form\UserType;
use CoreBundle\Repository\TagRepository;
use CoreBundle\Security\ShowVoter;
use CoreBundle\Service\ImageUploadService;
use FOS\RestBundle\Controller\FOSRestController;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpKernel\Exception\HttpException;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @Route("/api/v3/")
 */
class ShowController extends FOSRestController
{
    /**
     * @Rest\Get("show",
     *     options = { "expose" = true },
     *     name="get_shows")
     */
    public function getShowsAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        /** @var ShowRepository $showRepository */
        $showRepository = $em->getRepository(Show::class);

        return $this->view(['payload' =>
            $showRepository->paginator($showRepository->filter($request),
                (int)$request->get('page', 0),
                (int)$request->get('perPage', 10), 20)]);
    }



    /**
     * @Rest\Get("show/{token}/{slug}",
     *     options = { "expose" = true },
     *     name="get_show")
     */
    public function getShowAction(Request $request,$token,$slug){
        $em = $this->getDoctrine()->getManager();
        /** @var ShowRepository $showRepository */
        $showRepository = $em->getRepository(Show::class);

        if($show = $showRepository->getShowByTokenAndSlug($token,$slug))
            return $this->view(['show' => $show]);
        throw $this->createNotFoundException("Show Not Found");
    }

    /**
     * @Rest\Get("show/{token}/{slug}/comment/{comment_token}",
     *     options = { "expose" = true },
     *     name="get_show_comment")
     */
    public function getShowCommentAction(Request $request,$token,$slug,$comment_token = null){
        $em = $this->getDoctrine()->getManager();

        /** @var CommentRepository $commentRepository */
        $commentRepository = $em->getRepository(Comment::class);

        /** @var ShowRepository $showRepository */
        $showRepository = $em->getRepository(Show::class);

        /** @var Show $show */
        if( $show = $showRepository->getShowByTokenAndSlug($token,$slug)) {
            if ($comment_token) {
                return $this->view(["comments" => $commentRepository->getCommentByShowAndToken($show, $comment_token)]);
            } else {
                return $this->view(["comments" => $commentRepository->getAllRootCommentsForShow($show)]);
            }
        }
        throw $this->createNotFoundException('Show not Found');
    }

    /**
     * @Rest\Get("show/active",
     *     options = { "expose" = true },
     *     name="get_show_active")
     */
    public function getActiveShowAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        /** @var EventRepository $eventRepository */
        $eventRepository = $em->getRepository(Event::class);
        $activeEvent = $eventRepository->getEventByTime(new \DateTime('now'));
        return $this->view(["event" => $activeEvent]);
    }

    /**
     * @Security("has_role('ROLE_USER')")
     * @Rest\Post("show/{token}/{slug}/comment/{comment_token}",
     *     options = { "expose" = true },
     *     name="post_show_comment")
     */
    public function postPostCommentAction(Request $request, $token, $slug, $comment_token = null)
    {
        $em = $this->getDoctrine()->getManager();

        /** @var ShowRepository $showRepository */
        $showRepository = $em->getRepository(Show::class);
        /** @var CommentRepository $commentRepository */
        $commentRepository = $em->getRepository(Comment::class);

        /** @var Show $show */
        if ($show = $showRepository->getShowByTokenAndSlug($token,$slug))
        {

            $comment = new Comment();
            $comment->setUser($this->getUser());
            $show->addComment($comment);
            if ($comment_token !== null) {
                if($c = $commentRepository->getCommentByShowAndToken($show, $comment_token))
                    $comment->setParentComment($c);
                else
                    throw $this->createNotFoundException('Comment not Found');
            }

            $form = $this->createForm(UserType::class,$comment);
            $form->submit($request->request->all());
            if($form->isValid())
            {
                $em->persist($comment);
                $em->persist($show);
                $em->flush();
            }
            return $this->view($form);
        }
        throw $this->createNotFoundException('Show not Found');
    }

    /**
     * @Security("has_role('ROLE_STAFF')")
     * @Rest\Get("/show/datatable",
     *     options = { "expose" = true },
     *     name="get_show_dataTable")
     */
    public function getPostDatatableAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        /** @var ShowRepository $showRepository */
        $showRepository = $em->getRepository(Show::class);

        return $this->view(["dataTable" => $showRepository->dataTableFilter($request)]);
    }

    /**
     * @Security("has_role('ROLE_STAFF')")
     * @Rest\Post("/show",
     *     options = { "expose" = true },
     *     name="post_show")
     */
    public function postShowAction(Request $request)
    {
        /** @var ValidatorInterface $validator */
        $validator = $this->get('validator');

        $em = $this->getDoctrine()->getManager();

        $show = new Show();
        $show->setName($request->get('name'));
        $show->setSlug($request->get('slug', $request->get('name')));
        $show->setDescription($request->get('description'));
        $show->setEnableComments($request->get('enable_comments'));

        $errors = $validator->validate($show);
        if($errors->count() > 0)
            return RestfulEnvelope::errorResponseTemplate('Invalid show')->addErrors($errors)->response();

        $em->persist($show);
        $em->flush();

        return $this->view(["show" => $show]);
    }


    /**
     * @Security("has_role('ROLE_DJ')")
     * @Rest\Patch("/show/{token}/{slug}",
     *     options = { "expose" = true },
     *     name="patch_show")
     */
    public function patchShowAction(Request $request, $token, $slug)
    {
        /** @var ValidatorInterface $validator */
        $validator = $this->get('validator');

        $em = $this->getDoctrine()->getManager();

        /** @var ShowRepository $showRepository */
        $showRepository = $this->get(Show::class);

        /** @var Show $show */
        if ($show = $showRepository->getShowByTokenAndSlug($token, $slug))
        {
            $this->denyAccessUnlessGranted(ShowVoter::EDIT, $show);

            $show->setName($request->get('name', $show->getName()));
            $show->setSlug($request->get('slug', $show->getSlug()));
            $show->setDescription($request->get('description', $show->getDescription()));
            $show->setEnableComments($request->get('enable_comments', $show->getEnableComments()));

            $errors = $validator->validate($show);
            if($errors->count() > 0)
                return RestfulEnvelope::errorResponseTemplate('Invalid show')->addErrors($errors)->response();

            $em->persist($show);
            $em->flush();

            return RestfulEnvelope::successResponseTemplate('Show updated',$show,[new ShowNormalizer()]);
        }
        throw $this->createNotFoundException('Show not Found');
    }


    /**
     * @Security("has_role('ROLE_DJ') | has_role('ROLE_STAFF')")
     * @Rest\Delete("/show/{token}/{slug}",
     *     options = { "expose" = true },
     *     name="delete_show")
     */
    public function deleteShowAction(Request $request, $token, $slug)
    {
        $em = $this->getDoctrine()->getManager();

        /** @var ShowRepository $showRepository */
        $showRepository = $this->get(Show::class);

        /** @var Show $show */
        if ( $show = $showRepository->getShowByTokenAndSlug($token, $slug))
        {
            $this->denyAccessUnlessGranted(ShowVoter::DELETE, $show);
            $em->remove($show);
            $em->flush();
        }
        return RestfulEnvelope::errorResponseTemplate('Show not found')->setStatus(410)->response();
    }


    /**
     * @Rest\Put("/show/{token}/{slug}/tag/{tag}",
     *      options = { "expose" = true },
     *     name="put_tag_show")
     */
    public function putTagForShowAction(Request $request, $token, $slug, $tag)
    {
        $em = $this->getDoctrine()->getManager();

        /** @var ShowRepository $showRepository */
        $showRepository = $em->getRepository(Show::class);
        /** @var Show $show */
        if($show = $showRepository->getShowByTokenAndSlug($token, $slug))
        {
            $this->denyAccessUnlessGranted(ShowVoter::EDIT, $show);
            if($show->getTags()->containsKey($tag))
                throw $this->createNotFoundException('Show Not Found');

            /** @var TagRepository $tagRepository */
            $tagRepository = $this->get(Tag::class);

            $tag = $tagRepository->getOrCreateTag($tag);
            $em->persist($tag);
            $show->addTag($tag);
            $em->persist($show);
            $em->flush();

            return $this->view(['tag' => $tag->getTag()]);
        }
        throw $this->createNotFoundException('Show Not Found');
    }

    /**
     * @Rest\Delete("/show/{token}/{slug}/tag/{tag}",
     *     options = { "expose" = true },
     *     name="delete_tag_show")
     */
    public function deleteTagForPostAction(Request $request, $token, $slug, $tag)
    {
        $em = $this->getDoctrine()->getManager();

        /** @var ShowRepository $showRepository */
        $showRepository = $em->getRepository(Show::class);
        /** @var Show $show */
        if($show = $showRepository->getShowByTokenAndSlug($token, $slug))
        {
            $this->denyAccessUnlessGranted(ShowVoter::EDIT, $show);

            if($s = $show->removeTag($tag)) {
                $em->persist($show);
                $em->flush();
                return $this->view(['tag' => $s->getTag()]);
            }
            throw $this->createNotFoundException('Tag Not Found');
        }
        throw $this->createNotFoundException('Show Not Found');
    }

    /**
     * @Route("/show/{token}/{slug}/image",
     *     options = { "expose" = true },
     *     name="put_image_show")
     * @Method({"PUT"})
     */
    public function putImageForShowAction(Request $request, $token, $slug)
    {
        /** @var ValidatorInterface $validator */
        $validator = $this->get('validator');

        /** @var ImageUploadService $imageService */
        $imageService = $this->get(ImageUploadService::class);

        $em = $this->getDoctrine()->getManager();

        /** @var ShowRepository $showRepository */
        $showRepository = $em->getRepository(Show::class);
        /** @var Show $show */
        if($show = $showRepository->getShowByTokenAndSlug($token, $slug))
        {
            $this->denyAccessUnlessGranted(ShowVoter::EDIT, $show);

            $src = $request->files->get('image', null);
            $image = new Image();
            $image->setImage($src);
            $image->setAuthor($this->getUser());

            $errors = $validator->validate($image);
            if($errors->count() > 0)
                return RestfulEnvelope::errorResponseTemplate('invalid Image')->addErrors($errors)->response();

            $imageService->saveImageToFilesystem($image);
            $em->persist($image);

            $show->addImage($image);
            $em->persist($show);
            $em->flush();
            return $this->view(['image' => $image]);
        }
        throw $this->createNotFoundException('Show not Found');
    }

    /**
     * @Route("/show/{token}/{slug}/image",
     *     options = { "expose" = true },
     *     name="get_image_show")
     * @Method({"GET"})
     */
    public function getImageForShowAction(Request $request, $token, $slug)
    {
        $em = $this->getDoctrine()->getManager();

        /** @var ShowRepository $showRepository */
        $showRepository = $em->getRepository(Show::class);

        /** @var Show $show */

        if ($show = $showRepository->getShowByTokenAndSlug($token, $slug))
        {
            $this->denyAccessUnlessGranted(ShowVoter::EDIT, $show);
            return $this->view(['image' => $show->getImages()->toArray()]);
        }
        throw $this->createNotFoundException('Show not Found');

    }

    /**
     * @Route("/show/{token}/{slug}/header",
     *     options = { "expose" = true },
     *     name="post_header_image_show")
     * @Method({"POST"})
     */
    public function postImageShowHeaderAction(Request $request, $token, $slug)
    {
        /** @var ValidatorInterface $validator */
        $validator = $this->get('validator');

        $em = $this->getDoctrine()->getManager();

        /** @var ImageUploadService $imageService */
        $imageService = $this->get(ImageUploadService::class);

        /** @var ShowRepository $showRepository */
        $showRepository = $em->getRepository(Show::class);

        /** @var Show $show */
        if ( $show = $showRepository->getShowByTokenAndSlug($token, $slug))
        {
            $this->denyAccessUnlessGranted(ShowVoter::EDIT, $show);

            $image  = $imageService->createImage($request->files->get('image', null),$this->getUser());
            $errors = $validator->validate($image);
            if($errors->count() > 0)
                return RestfulEnvelope::errorResponseTemplate('invalid Image')->addErrors($errors)->response();

            $imageService->saveImageToFilesystem($image);
            $em->persist($image);

            $show->setHeaderImage($image);
            $em->persist($show);
            $em->flush();
            return $this->view(['image' => $image]);
        }
        throw $this->createNotFoundException('Show not Found');
    }

    /**
     * @Route("/show/{token}/{slug}/header",
     *     options = { "expose" = true },
     *     name="delete_show_image_header")
     * @Method({"DELETE"})
     */
    public function deleteImageShowHeaderAction(Request $request, $token, $slug)
    {
        $em = $this->getDoctrine()->getManager();

        /** @var ShowRepository $showRepository */
        $showRepository = $em->getRepository(Show::class);

        /** @var Show $show */
        if ($show = $showRepository->getShowByTokenAndSlug($token, $slug)) {
            $this->denyAccessUnlessGranted(ShowVoter::EDIT, $show);
            $em->remove($show->getHeaderImage());
            return $this->view();
        }
        throw $this->createNotFoundException('Show not Found');
    }


}
