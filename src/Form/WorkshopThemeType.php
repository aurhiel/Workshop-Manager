<?php
namespace App\Form;

use App\Entity\WorkshopTheme;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

// use Symfony\Component\Form\FormEvent;
// use Symfony\Component\Form\FormEvents;

class WorkshopThemeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name',         TextType::class,    array(
              'label' => 'form_workshop_theme.name.label'
            ))
            ->add('description',  TextareaType::class,    array(
              'label' => 'form_workshop_theme.description.label',
              'required' => false
            ));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => WorkshopTheme::class,
        ));
    }
}
