<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\AdminUserType;
use App\Repository\RoleRepository;
use App\Repository\UserRepository;
use App\Service\Paginator;
use App\Service\TokenError;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class AdminUserController extends AbstractController
{
    /**
     * @Route("/admin/user/{page<\d+>?1}", name="admin_user_index")
     */
    public function index(Paginator $paginator, $page, UserRepository $userRepository)
    {
        $data = $userRepository->findAll();
        /*$paginator->setEntityClass(User::class)
                  ->setCurrentPage($page)
                  ->setLimit(5);
        */


        return $this->render('admin/user/index.html.twig', [
            'data' => $data,
            'route' => 'admin_user_index'
        ]);
    }

    /**
     * @Route("admin/user/{id}/edit", name="admin_user_edit")
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function edit(User $user, Request $request, EntityManagerInterface $manager, TokenError $tokenError)
    {

        $tokens = $request->getSession()->all();
        $id = $user->getId();
        if ($this->isCsrfTokenValid('edit' . $user->getId(), $tokens['_csrf/edit' . $id])){
            $form = $this->createForm(AdminUserType::class, $user);

            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()){
                if ($this->isCsrfTokenValid('save' . $user->getId(), $request->get('_token_save'))){
                    $user->setUpdatedAt(new \DateTime('now'));
                    $manager->persist($user);

                    $manager->flush();

                    $this->addFlash('success', "L'utilisateur <strong>n°{$user->getId()} a bien été modifié</strong>");
                }
            }

            return $this->render('admin/user/edit.html.twig', [
                'user' => $user,
                'form' => $form->createView()
            ]);
        }
        $this->addFlash('warning',
            $tokenError->ErrorMessage());
        return $this->redirectToRoute("admin_user_index");

    }


    /**
     * Permet de supprimer un utilisateur
     *
     * @Route("admin/user/{id}/delete", name="admin_user_delete")
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function delete(User $user, EntityManagerInterface $manager, Request $request, TokenError $tokenError)
    {
        if ($this->isCsrfTokenValid('delete' . $user->getId(), $request->get('_token'))){
            $userId = $user->getId();
            $manager->remove($user);

            $manager->flush();

            $this->addFlash('success', "L'utilisateur <strong>n°{$userId} a bien été supprimé</strong>");

            return $this->redirectToRoute('admin_user_index');
        }
        $this->addFlash('warning',
            $tokenError->ErrorMessage());
        return $this->redirectToRoute("admin_user_index");

    }

    /**
     * @Route("admin/user/new", name="admin_user_new")
     */
    public function new(Request $request, EntityManagerInterface $manager, UserPasswordEncoderInterface $encoder, RoleRepository $roleRepository)
    {
        $user = new User();
        $adminRole = $roleRepository->findOneBy(['title' => 'ROLE_ADMIN']);

        $form = $this->createForm(AdminUserType::class, $user);
        $form->handleRequest($request);


        if ($form->isSubmitted() && $form->isValid()){
            $hash = $encoder->encodePassword($user, 'admin');

            $user->setHash($hash)
                ->setUpdatedAt(new \DateTime('now'))
                ->addUserRole($adminRole);

            $manager->persist($user);
            $manager->flush();

            $this->addFlash('success', 'Le nouvel administrateur: ' . $user->getFullName() . ' a bien été créé');
            return $this->redirectToRoute('admin_user_index');
        }


        return $this->render('admin/user/new.html.twig',
            [
                'form' => $form->createView()
            ]);

    }
}
