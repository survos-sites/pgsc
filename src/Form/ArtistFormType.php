<?php

namespace App\Form;

use AlexandreFernandez\JsonTranslationBundle\Form\JsonTranslationType;
use App\Entity\Artist;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Intl\Languages;
use Symfony\Component\OptionsResolver\OptionsResolver;

// verify you're not importing the doctrine type

class ArtistFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
//        $isValidLanguage = Languages::exists('tzo');

        // The Doctrine type of the "bio" field is "json_translation", which is not supported by EasyAdmin.
        // For Doctrine's Custom Mapping Types have a look at EasyAdmin's field docs.
        $builder->add('bio', JsonTranslationType::class, [
            'attr' => [
                'rows' => 10,
            ],
            'locales' => ['en', 'es', 'tzo'], // optional, defaults to the configuration's `enabled_locales`
        ]);

        $builder
            ->add('name')
            ->add('birthYear')
            ->add('email')
            ->add('code')
            ->add('instagram')
//            ->add('studioAddress')
//            ->add('studioVisitable')
        ;
        $builder->add('submit', SubmitType::class, []);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Artist::class,
        ]);
    }
}
