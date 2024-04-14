<?php
namespace App\Form;

// Entities
use App\Entity\User;

// Form types
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;

// Form events
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $listener = function (FormEvent $event) use ($options)
        {
            $data = $event->getData();
            // TODO find a better way to get users connected email
            // Retrieve email from session (= edit settings)
            if(!empty($options['data']->getEmail()))
            {
                $data['email'] = $options['data']->getEmail();
                $event->setData($data);
            }
        };

        $is_edit = ($options['type_form'] == 'edit');

        // add = register
        // if ('add' == $options['type_form'])
        // {
        //     $builder->add('serviceType', ChoiceType::Class, array(
        //         'label' => 'form_user.service_type.label',
        //         'label_attr' => ['class' => 'radio-inline radio-custom'],
        //         'expanded' => true,
        //         'multiple' => false,
        //         'choices' => array(
        //             'label.' . User::SERVICE_TYPE_ACTIV_CREA    => User::SERVICE_TYPE_ACTIV_CREA,
        //             'label.' . User::SERVICE_TYPE_ACTIV_PROJET  => User::SERVICE_TYPE_ACTIV_PROJET
        //         )
        //     ));
        // }

        $builder
            ->add('firstname',      TextType::class,  array('label' => 'form_user.firstname.label'))
            ->add('lastname',       TextType::class,  array('label' => 'form_user.lastname.label'))
            ->add('idPoleEmploi',   TextType::class,  array('label' => 'form_user.id_pole_emploi.label'))
            ->add('email',          EmailType::class, array('label' => 'form_user.email.label', 'disabled' => $is_edit))
            ->add('phone',          TelType::class,   array('label' => 'form_user.phone.label'))
            ->add('plainPassword',  RepeatedType::class, array(
                'type' => PasswordType::class,
                'first_options'  => array(
                    'label' => 'form_user.first_password.label',
                    'attr' => array('value' => ($is_edit ? '0ld-pa$$wo|2d' : ''))
                ),
                'second_options' => array(
                    'label' => 'form_user.second_password.label',
                    'attr' => array('value' => ($is_edit ? '0ld-pa$$wo|2d' : ''))
                ),
            ))
        ;

        // Listener (eg. to fill email field on edit information)
        $builder->addEventListener(FormEvents::PRE_SUBMIT, $listener);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class'  => User::class,
            'type_form'   => 'add'
        ));
    }
}
