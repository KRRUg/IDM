<?php

namespace App\Form;

use App\Entity\Clan;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class ClanCreateType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name')
            ->add('joinPassword', TextType::class, [
                'mapped' => false,
                'constraints' => [
                    new NotBlank(),
                ],
            ])
            ->add('website')
            ->add('clantag')
            ->add('description')
            ->add('user', TextType::class, [
                'mapped' => false,
                'constraints' => [
                    new NotBlank(),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Clan::class,
        ]);
    }
}
