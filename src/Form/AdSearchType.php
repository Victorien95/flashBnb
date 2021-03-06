<?php

namespace App\Form;

use App\Entity\Ad;
use App\Entity\AdSearch;
use App\Entity\Option;
use App\Service\Stats;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\RangeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AdSearchType extends ApplicationType
{


    public function buildForm(FormBuilderInterface $builder, array $options)
    {


        $builder
            ->add('maxPrice', IntegerType::class, $this->getConfiguration('Prix / nuits maximum',
                [
                    'required' => false,
                    'label' => 'Prix / nuits'
                ]))
            ->add('minRooms', IntegerType::class, $this->getConfiguration('Nombre de chambres minimum',
                [
                    'required' => false,
                    'label' => 'Chambres',
                    'attr' =>
                        [
                            'min' => 1
                        ]
                ]))
            ->add('distance', ChoiceType::class, $this->getConfiguration('Distance maximum', [
                'choices' =>
                    [
                        '10 km' => 10,
                        '20 km' => 20,
                        '30 km' => 30,
                        '50 km' => 50,
                        '100 km' => 100
                    ]
            ]))
            ->add('lat', HiddenType::class)
            ->add('lng', HiddenType::class)
            ->add('myadress', HiddenType::class)
            ->add('orderby', ChoiceType::class,
                [
                    'label' => 'Trier',
                    'required' => false,
                    'choices' =>
                        [
                            'Prix croissant'=>'pASC',
                            'Prix décroissant'=>'pDESC',
                            'Chambres croissant'=>'rASC',
                            'Chambres décroissant'=>'rDESC'
                        ]
                ])
            ->add('options', EntityType::class, $this->getConfiguration('Choisissez vos options',
                [
                    'class' => Option::class,
                    'choice_label' => 'name',
                    'multiple' => true,
                    'required' => false
                ]))
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => AdSearch::class,
            'method' => 'get',
            'csrf_protection' => false
        ]);
    }

    public function getBlockPrefix()
    {
        return "";
    }






}
