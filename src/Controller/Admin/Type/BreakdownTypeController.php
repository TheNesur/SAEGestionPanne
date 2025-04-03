<?php

namespace App\Controller\Admin\Type;

use App\Entity\Breakdown;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
class BreakdownTypeController extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('date', DateType::class, ['disabled' => true])
            ->add('description', TextType::class, ['disabled' => true])
            ->add('comment', TextType::class, ['disabled' => true])
            ->add('status', TextType::class, ['disabled' => true]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Breakdown::class,
        ]);
    }
}