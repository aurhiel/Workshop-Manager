<?php
namespace App\Form;

// Entities
use App\Entity\SurveyGrade;

// Form types
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;

// Form events
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class SurveyGradeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('position', IntegerType::class,  array('label' => 'form_survey_grade.position.label'))
            ->add('label',    TextType::class,  array('label' => 'form_survey_grade.label.label'))
            ->add('value',    TextType::class,  array('label' => 'form_survey_grade.value.label'))
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'csrf_protection' => false,             // NOTE : Remove CSRF protection to get ajax submit working
            'data_class'      => SurveyGrade::class,
        ));
    }
}
