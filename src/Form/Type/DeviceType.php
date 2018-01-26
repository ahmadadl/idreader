<?php
/**
 * Created by PhpStorm.
 * UserForm: Ahmad Adlouni
 * Date: 1/25/2018
 * Time: 12:45 PM
 */

namespace App\Form\Type;


use App\Entity\Device;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DeviceType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('macAddress', TextType::class, array(
                'attr' => ['class' => 'form-control', 'placeholder' => 'Mobile Mac Address'],
                'label' => 'Mobile Mac Address',
                'label_attr' => ['class' => 'col-md-3 control-label'],
                'required' => false
            ));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => Device::class,
        ));
    }
}