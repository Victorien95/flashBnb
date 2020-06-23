<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Form\AdminCommentType;
use App\Service\Paginator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class AdminCommentController extends AbstractController
{
    /**
     * @Route("/admin/comment/{page<\d+>?1}", name="admin_comment_index")
     */
    public function index(Paginator $paginator, $page)
    {
        $paginator->setEntityClass(Comment::class)
                  ->setCurrentPage($page)
                  ->setLimit(5);



        return $this->render('admin/comment/index.html.twig', [
            'pagination' => $paginator,
            'route' => 'admin_comment_index'
        ]);
    }

    /**
     * Afficher et editer un commentaire
     *
     * @Route("/admin/comment/{id}/edit", name="admin_comment_edit")
     *
     * @param Comment $comment
     * @param EntityManagerInterface $manager
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function edit(Comment $comment, \Symfony\Component\HttpFoundation\Request $request, EntityManagerInterface $manager)
    {
        $form = $this->createForm(AdminCommentType::class, $comment);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()){

            $manager->persist($comment);

            $manager->flush();

            $this->addFlash('success', "Le commantaire <strong>n°{$comment->getId()}</strong> a bien été modifié");

        }

        return $this->render('admin/comment/edit.html.twig', [
            'form' => $form->createView(),
            'comment' => $comment
        ]);
    }

    /**
     * Supprimer un commentaire
     *
     * @Route("admin/comment/{id}/delete", name="admin_comment_delete")
     * 
     * @param Comment $comment
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function delete(Comment $comment, EntityManagerInterface $manager)
    {
        $id = $comment->getId();
        $manager->remove($comment);

        $manager->flush();

        $this->addFlash('success', "Le commentaire <strong>n°{$id} posté par {$comment->getAuthor()->getFullName()}</strong> a bien été supprimé");
        return $this->redirectToRoute('admin_comment_index');
    }
}