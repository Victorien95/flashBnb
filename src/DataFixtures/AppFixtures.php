<?php

namespace App\DataFixtures;

use App\Entity\Ad;
use App\Entity\Booking;
use App\Entity\Comment;
use App\Entity\Image;
use App\Entity\Like;
use App\Entity\Option;
use App\Entity\PromoCode;
use App\Entity\Role;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class AppFixtures extends Fixture
{
    /**
     * @var UserPasswordEncoderInterface
     */
    private $encoder;

    public function __construct(UserPasswordEncoderInterface $encoder)
    {

        $this->encoder = $encoder;
    }
    
    public function load(ObjectManager $manager)
    {
        $faker = Factory::create('fr_FR');
        $adminRole = new Role();
        $adminRole->setTitle('ROLE_ADMIN');
        $manager->persist($adminRole);

        
        $adminUser = new User();
        $adminUser->setFirstName('Victorien')
            ->setLastName('Faton')
            ->setEmail('admin@gmail.com')
            ->setHash($this->encoder->encodePassword($adminUser, 'admin'))
            ->setPicture('http://randomuser.me/api/portraits/men/67.jpg')
            ->setIntroduction('Administrateur de FlashBNB')
            ->setDescription('<p>' . join('</p><p>', $faker->paragraphs(random_int(2, 4) )) . '</p>')
            ->setUpdatedAt($faker->dateTime('now'))
            ->addUserRole($adminRole);
        $manager->persist($adminUser);


        $options_names = ['Adapté PMR', 'Wifi', 'Balcon'];
        $options = [];

        for($i = 0; $i < count($options_names); $i ++){
            $option = new Option();
            $option->setName($options_names[$i]);
            $manager->persist($option);
            $options[] = $option;
        }

        //Promocode
        $promocode_flat = new PromoCode();
        $promocode_prct = new PromoCode();
        $promocode_flat->setAmount(15)
            ->setCode('FLAT15')
            ->setType('FIXE');
        $promocode_prct->setAmount(15)
            ->setCode('PRCT15')
            ->setType('POURCENTAGE');

        $manager->persist($promocode_prct);
        $manager->persist($promocode_flat);


        // Users
        $users = [];
        $genres = ['male', 'female'];
        for ($i = 1; $i<=20; $i++){
            $user = new User();

            $genre = $faker->randomElement($genres);

            $picture = 'https://randomuser.me/api/portraits/';
            $pictureId = $faker->numberBetween(1, 99) . '.jpg';
            $picture .= ($genre == 'male' ? 'men/' : 'women/') . $pictureId;

            $hash = $this->encoder->encodePassword($user, 'password');

            $user->setFirstName($faker->firstName($genre))
                ->setLastName($faker->lastName)
                ->setEmail($faker->email)
                ->setIntroduction($faker->sentence)
                ->setDescription('<p>' . join('</p><p>', $faker->paragraphs(random_int(2, 4) )) . '</p>')
                ->setHash($hash)
                ->setUpdatedAt($faker->dateTime('now'))
                ->setPicture($picture);

            $manager->persist($user);
            $users[] = $user;
        }

        // Annonces
        for ($i = 1; $i < 30; $i++){
            $ad = new Ad();

            $title = $faker->sentence($nbWords = 7, $variableNbWords = true);
            $user = $users[mt_rand(0, count($users) - 1)];
            $option = $options[mt_rand(0, count($options) - 1)];
            $ad->setTitle($title)
                ->setCoverImage($faker->imageUrl(1000, 350))
                ->setIntroduction($faker->paragraph(random_int(1, 3)))
                ->setContent('<p>' . join('</p><p>', $faker->paragraphs(random_int(2, 6) )) . '</p>')
                ->setPrice(random_int(20, 350))
                ->setRooms(random_int(1, 5))
                ->setAuthor($user)
                ->setCity($faker->city)
                ->setPostalCode($faker->postcode)
                ->setAdress($faker->address)
                ->setStreetAddress($faker->streetAddress)
                ->setLng($faker->longitude($min = -0.5, $max =3.6))
                ->setLat($faker->latitude($min = 47, $max =49))
                ->setUpdatedAt($faker->dateTime('now'))
                ->addOption($option);

            for($j = 1; $j <= mt_rand(2, 5); $j++){
                $image = new Image();
                $image->setUrl("http://placeimg.com/640/480/nature")
                    ->setCaption($faker->sentence)
                    ->setAd($ad);
                $manager->persist($image);

            }
            // Likes
            for($j = 1; $j <= mt_rand(7, 20); $j++){
                $like = new Like();
                $like->setAd($ad)
                    ->setUser($faker->randomElement($users));
                $manager->persist($like);

            }

            // Réservations

            for ($k = 1; $k <= mt_rand(0,10); $k++){
                $booking = new Booking();

                $createdAt = $faker->dateTimeBetween('-6 months');
                $startDate = $faker->dateTimeBetween('-3months');
                // Gestion date de fin
                $duration = mt_rand(3, 10);
                $endDate = (clone $startDate)->modify("+$duration days");

                $amount = $ad->getPrice() * $duration;
                $booker = $users[mt_rand(0, count($users) -1)];
                $comment = $faker->paragraph(mt_rand(1, 6));

                $booking->setBooker($booker)
                        ->setAd($ad)
                        ->setStartDate($startDate)
                        ->setEndDate($endDate)
                        ->setCreatedAt($createdAt)
                        ->setAmount($amount)
                    ->setComment($comment);

                $manager->persist($booking);
                
                // Commentaires
                if(mt_rand(0,1)){
                    $comment = new Comment();
                    $comment->setContent($faker->paragraph())
                            ->setRating(mt_rand(1, 5))
                            ->setAuthor($booker)
                            ->setAd($ad);

                    $manager->persist($comment);
                }
            }

            $manager->persist($ad);
        }

        $manager->flush();
    }
}
