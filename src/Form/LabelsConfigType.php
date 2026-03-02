<?php
declare(strict_types=1);

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LabelsConfigType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $exhibitions = $options['exhibitions'];
        $shortClass  = $options['short_class'];

        if ($shortClass === 'obra' && $exhibitions) {
            $builder->add('exhibition', ChoiceType::class, [
                'label'       => 'Exhibition',
                'choices'     => array_combine($exhibitions, $exhibitions),
                'placeholder' => 'All exhibitions',
                'required'    => false,
                'help'        => 'Only print labels for obra from this exhibition tab.',
            ]);
        }

        $builder->add('showQrCode', CheckboxType::class, [
            'label'    => 'Show QR code',
            'required' => false,
            'data'     => true,
            'help'     => 'Include the audio tour QR code on each label.',
        ]);

        $builder->add('print', SubmitType::class, [
            'label' => 'Print labels',
            'attr'  => ['class' => 'btn btn-primary'],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'exhibitions' => [],
            'short_class' => 'obra',
        ]);
    }
}
