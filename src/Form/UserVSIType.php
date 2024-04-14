<?php
namespace App\Form;

// Entities
use App\Entity\User;
use App\Entity\UserVSI;

// Repositories
use App\Repository\UserRepository;
use App\Repository\UserVSIRepository;

// Form types
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

// Form events
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class UserVSIType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $is_edit = ($options['type_form'] == 'edit');

        $builder
            ->add('idVSI',      TextType::class,  array('label' => 'form_user_vsi.id_vsi.label', 'required' => false))
            ->add('idCohort',   TextType::class,  array('label' => 'form_user_vsi.id_cohort.label'))
            ->add('firstname',  TextType::class,  array('label' => 'form_user_vsi.firstname.label'))
            ->add('lastname',   TextType::class,  array('label' => 'form_user_vsi.lastname.label'))
            ->add('email',      EmailType::class, array('label' => 'form_user_vsi.email.label'))
            ->add('workshop_end_date', DateType::class, array(
                'label'   => 'form_user_vsi.workshop_end_date.label',
                'widget'  => 'single_text'
            ))
            ->add('referent_consultant', EntityType::class, array(
                'class'         => User::class,
                'label'         => 'form_user_vsi.referent_consultant.label',
                'required'      => true,
                'placeholder'   => 'form_user_vsi.referent_consultant.placeholder',
                'query_builder' => function (UserRepository $repo) {
                    return $repo->findConsultant(false, true);
                },
                'choice_label'  => function ($user) {
                    return $user->getLastname() . ' ' . $user->getFirstname();
                }
            ))
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class'  => UserVSI::class,
            'type_form'   => 'add'
        ));
    }
}
