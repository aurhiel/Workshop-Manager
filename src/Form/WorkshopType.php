<?php
namespace App\Form;

use App\Entity\User;
use App\Entity\Address;
use App\Entity\WorkshopTheme;
use App\Entity\Workshop;

use App\Repository\UserRepository;
use App\Repository\WorkshopThemeRepository;
use App\Repository\AddressRepository;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;


class WorkshopType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
          ->add('theme', EntityType::class, array(
              'class'         => WorkshopTheme::class,
              'label'         => 'form_workshop.theme.label',
              'required'      => true,
              'placeholder'   => 'form_workshop.theme.placeholder',
              'query_builder' => function (WorkshopThemeRepository $repo) {
                  return $repo->createQueryBuilder('t')
                      // Order on theme name
                      ->addOrderBy('t.name', 'ASC');
              },
              'choice_label'  => function ($workshop_theme) {
                  return $workshop_theme->getName();
              }
          ))
          ->add('lecturer', EntityType::class, array(
              'class'         => User::class,
              'label'         => 'form_workshop.lecturer.label',
              'required'      => true,
              'placeholder'   => 'form_workshop.lecturer.placeholder',
              'query_builder' => function (UserRepository $ur) {
                  return $ur->findLecturer();
              },
              'choice_label'  => function ($user) {
                  return $user->getLastname() . ' ' . $user->getFirstname() . ($user->getIsActive() == false ? ' (désactivé·e)' : '');
              }
          ))
          ->add('address', EntityType::class, array(
              'class'         => Address::class,
              'label'         => 'form_workshop.address.label',
              'required'      => true,
              'placeholder'   => 'form_workshop.address.placeholder',
              'query_builder' => function (AddressRepository $repo) {
                  return $repo->createQueryBuilder('a')
                      // Order on theme name
                      ->addOrderBy('a.name', 'ASC');
              },
              'choice_label'  => function ($address) {
                  return $address->getName();
              }
          ))
          ->add('date_start', DateTimeType::class, array(
              'label'   => 'form_workshop.date_start.label',
              'widget'  => 'single_text',
              'attr'    => array('step' => 900)
          ))
          ->add('date_end', DateTimeType::class, array(
              'label'   => 'form_workshop.date_end.label',
              'widget'  => 'single_text',
              'attr'    => array('step' => 900)
          ))
          ->add('nb_seats', IntegerType::class, array(
              'label'   => 'form_workshop.nb_seats.label'
          ))
          ->add('description', TextareaType::class, array(
              'label' 		=> 'form_workshop.description.label',
							'required' 	=> false
					))
					->add('is_VSI_type', CheckboxType::class, array(
							'label' 		=> 'form_workshop.is_vsi_type.label',
							'label_attr' => ['class' => 'checkbox-custom'],
							'required' 	=> false
          ));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'csrf_protection' => false,             // NOTE : Remove CSRF protection to get ajax submit working
            'data_class'      => Workshop::class,
        ));
    }
}
