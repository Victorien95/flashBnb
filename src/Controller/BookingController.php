<?php

namespace App\Controller;

use App\Entity\Ad;
use App\Entity\Booking;
use App\Entity\Comment;
use App\Entity\PromoCode;
use App\Form\BookingType;
use App\Form\CommentType;
use App\Repository\PromoCodeRepository;
use App\Service\Mailer;
use App\Service\MailerService;
use App\Service\PromoCodeChecker;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Annotation\Route;

class BookingController extends AbstractController
{
    /**
     * @Route("/ads/{slug}/book", name="booking_create")
     * @IsGranted("ROLE_USER")
     */
    public function book(Ad $ad, Request $request, EntityManagerInterface $manager, PromoCodeRepository $promoCodeRepository, PromoCodeChecker $promoCodeChecker, MailerService $mailerService)
    {
        $booking = new Booking();
        $form = $this->createForm(BookingType::class, $booking);
        $promo = '';

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()){

            $user = $this->getUser();
            $booking->setBooker($user)
                    ->setAd($ad);

            if ($request->request->get('booking')['promo_id']){
                $promo_id = $request->request->get('booking')['promo_id'];
                $promo = $promoCodeRepository->findOneBy(['id' => $promo_id]);
                $booking->setPromoCode($promo);
            }


            // Si les dates ne sont pas disponibles, message d'erreur
            if (!$booking->isBookableDates()){
                $this->addFlash('warning', "Les dates que vous avez choisies sont indisponibles");
            }elseif($promo && !$promoCodeChecker->PromoChecker($promo, $user)){
                return $this->render('booking/book.html.twig', [
                    'ad' => $ad,
                    'form' => $form->createView()
                ]);
            }else{
                //Sinon enregistrement

                $manager->persist($booking);
                $manager->flush();
                $mailerService->booking($user, $booking);


                return $this->redirectToRoute('booking_show',
                    [
                        'id' => $booking->getId(),
                        'withAlert' => true
                    ]);
            }
        }
        return $this->render('booking/book.html.twig', [
            'ad' => $ad,
            'form' => $form->createView()
        ]);
    }

    /**
     * Permet d'afficher la page d'une réservation
     *
     * @Route("/booking/{id}", name="booking_show")
     * @param Booking $booking
     * @return Response
     */
    public function show(Booking $booking, Request $request, EntityManagerInterface $manager)
    {
        $comment = new Comment();
        $form = $this->createForm(CommentType::class, $comment);
        
        $form->handleRequest($request);
        
        if($form->isSubmitted() && $form->isValid()){
            $comment->setAd($booking->getAd())
                    ->setAuthor($this->getUser());
            
            $manager->persist($comment);
            $manager->flush();
            
            $this->addFlash('success', "Votre commentaire a bien été pris en compte !");
        }


        return $this->render("booking/show.html.twig",
            [
                'booking' => $booking,
                'form' => $form->createView()
            ]);
    }

    /**
     * @Route("/ads/{slug}/book/abandon", name="booking_abandon")
     */
    public function abandon(Ad $ad, MailerInterface $mailer, PromoCodeRepository $promoCodeRepository, EntityManagerInterface $manager, MailerService $mailerService)
    {
        $relaunchChecker = $promoCodeRepository->findOneBy(['user' => $this->getUser()]);
        if (!$relaunchChecker && $this->getUser()){
            $promoCode = new PromoCode();
            $promoCode
                ->setCode('10R' . uniqid())
                ->setMaxNumber('1')
                ->setUser($this->getUser())
                ->setAmount('10')
                ->setType('POURCENTAGE');

            $manager->persist($promoCode);
            $manager->flush();

            $mailerService->relance($this->getUser(), $promoCode);
            

        }
        return $this->redirectToRoute('ads_show',
            [
                'slug' => $ad->getSlug(),
            ]);
    }


}
