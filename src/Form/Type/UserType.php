<?php
/**
 * Created by PhpStorm.
 * UserForm: Ahmad Adlouni
 * Date: 1/24/2018
 * Time: 3:09 PM
 */

namespace App\Form\Type;

use App\Entity\Building;
use App\Entity\Role;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\BirthdayType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\ResetType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

class UserType extends AbstractType
{

    private $session;
    private $em;
    private $roleChoices;
    private $buildingChoices;

    public function __construct(SessionInterface $session, EntityManagerInterface $em)
    {
        $this->session = $session;
        $this->em = $em;
        $this->roleChoices = array();
        $this->buildingChoices = array();
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $this->setRoleChoices();
        $this->setBuildingChoices();

        $builder
            ->add('givenName', TextType::class, array(
                'attr' => ['class' => 'form-control', 'placeholder' => 'e.g. Ahmad'],
                'label' => 'First Name',
            ))
            ->add('familyName', TextType::class, array(
                'attr' => ['class' => 'form-control', 'placeholder' => 'e.g. Adlouni'],
                'label' => 'Last Name',
            ))
            ->add('gmail', EmailType::class, array(
                'attr' => ['class' => 'form-control', 'placeholder' => 'e.g. example@gmail.com'],
                'label' => 'Gmail',
            ))
            ->add('phoneNb', TextType::class, array(
                'attr' => ['class' => 'form-control', 'placeholder' => 'e.g. 71387643'],
                'label' => 'Phone Number'
            ))
            ->add('dob', BirthdayType::class, array(
                'label' => 'Date of Birth',
                'placeholder' => array(
                    'year' => 'Year', 'month' => 'Month', 'day' => 'Day',
                )
            ))
            ->add('role', ChoiceType::class, array(
                'choices' => $this->roleChoices,
                'mapped' => false,
            ))
            ->add('save', SubmitType::class, array(
                'attr' => ['class' => 'btn green'],
                'label' => 'Add Member'
            ))
            ->add('reset', ResetType::class, array(
                'attr' => ['class' => 'btn default'],
                'label' => 'Reset'
            ));

        $formModifier = function (FormInterface $form, Role $role = null) {

            if ($role->getRoleName() == 'sguard') {

                if ($form->has('building'))
                    $form->remove('building');

                $form->add('device', TextType::class, array(
                    'attr' => ['class' => 'form-control', 'placeholder' => 'e.g. aa:88:44:f3:ab:58'],
                    'label' => 'MAC Address',
                    'mapped' => false,
                    'constraints' => array(
                        new NotBlank(),
                        new Regex(array(
                            'pattern' => '/^([0-9A-Fa-f]{2}[:]){5}([0-9A-Fa-f]{2})$/',
                            'message' => 'MAC address must be consist of six groups of two hexadecimal digits, separated by colons :',
                        ))
                    ),
                ));

            } else if ($role->getRoleName() == 'fadmin') {

                if ($form->has('device'))
                    $form->remove('device');

                $this->setBuildingChoices();
                $form->add('building', ChoiceType::class, array(
                    'choices' => $this->buildingChoices,
                    'label' => 'Administrator of',
                    'multiple' => false,
                    'mapped' => false
                ));
            }

        };

        $builder->get('role')->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $event) use ($formModifier) {
                $role = $event->getForm()->getData();
                $formModifier($event->getForm()->getParent(), $role);
            }
        );

    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => User::class,
        ));
    }

    private function setRoleChoices()
    {
        $roles = '';

        if (in_array('fowner', $this->session->get('roles')))
            $roles = $this->em->getRepository(Role::class)->findAll();
        else if (in_array('fadmin', $this->session->get('roles')))
            $roles = $this->em->createQuery('select r from role r where r.roleName != "fowner" and r.roleName != "fadmin"')->getResult();

        if ($roles) {
            foreach ($roles as $role) {
                $roleName = $this->getRoleName($role);
                $this->roleChoices[$roleName] = $role;
            }
        }
    }

    /**
     * @param Role $role
     * @return string
     */
    private function getRoleName(Role $role)
    {
        if ($role->getRoleName() == 'fadmin')
            $roleName = 'Facility Administrator';
        else if ($role->getRoleName() == 'fowner')
            $roleName = 'Facility Owner';
        else if ($role->getRoleName() == 'powner')
            $roleName = 'Premise Owner';
        else
            $roleName = 'Security Guard';

        return $roleName;
    }

    private function setBuildingChoices()
    {
        if (in_array('fowner', $this->session->get('roles'))) {
            $result = $this->em->getRepository(Building::class)->findAll();
            foreach ($result as $building)
                $this->buildingChoices[$building->getName()] = $building;

        } else if (in_array('fadmin', $this->session->get('roles'))) {
            $result = $this->em->getRepository(Building::class)->findBy(['admin' => $this->session->get('user')->getId()]);
            foreach ($result as $building)
                $this->buildingChoices[$building->getName()] = $building;
        }

    }

}