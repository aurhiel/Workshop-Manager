<?php
namespace App\Form;

// Entities
use App\Entity\Survey;

// Form types
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;

// Form events
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class SurveyType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('slug',       TextType::class,  array('label' => 'form_survey.slug.label'))
            ->add('label',      TextType::class,  array('label' => 'form_survey.label.label'))
  					->add('enable_workshops_grade', CheckboxType::class, array(
  							'label' 		=> 'form_survey.enable_workshops_grade.label',
  							'label_attr' => ['class' => 'checkbox-custom'],
  							'required' 	=> false
            ))
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'csrf_protection' => false,             // NOTE : Remove CSRF protection to get ajax submit working
            'data_class'      => Survey::class,
        ));
    }
}
