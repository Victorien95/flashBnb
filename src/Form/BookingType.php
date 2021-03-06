<?php

namespace App\Form;

use App\Entity\Booking;
use App\Entity\PromoCode;
use App\Form\DataTransformer\FrenchToDateTimeTransformer;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BookingType extends ApplicationType
{
    private $transformer;
    public function __construct(FrenchToDateTimeTransformer $transformer)
    {
        $this->transformer = $transformer;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('startDate', TextType::class, $this->getConfiguration("La date à laquelle vous comptez arriver"))
            ->add('endDate', TextType::class, $this->getConfiguration("La date à laquelle vous quittez les lieux"))
            ->add('promo_id', HiddenType::class, $this->getConfiguration('Choisissez les spécificités du logement',
                [
                    'mapped' => false,
                    'required' => false
                ]))
            ->add('comment', TextareaType::class, $this->getConfiguration("Si vous avez un commentaire, n'hésitez pas à en faire part !", ['label' => false, 'required' => false]))
        ;
        $builder->get('startDate')->addModelTransformer($this->transformer);
        $builder->get('endDate')->addModelTransformer($this->transformer);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Booking::class,
            'translation_domain' => 'bookingForm',
            'validation_groups' => ['Default', 'Front']
        ]);
    }
}
