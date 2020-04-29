<?php

namespace App\Form;

use App\Entity\Clan;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ClanEditType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name')
            ->add('joinPassword', TextType::class, [
                'mapped' => false,
            ])
            ->add('website')
            ->add('clantag')
            ->add('description')
            ->add('admins', CollectionType::class, [
                'mapped' => false,
                'allow_add' => true,
                'entry_type' => TextType::class,
            ]);
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Clan::class,
        ]);
    }
}
