<?php

declare(strict_types=1);

namespace App\Form\Type\Entity;

use App\Entity\Loan;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Security;

class LoanType extends AbstractType
{
    /**
     * @var Security
     */
    private Security $security;

    /**
     * LoanType constructor.
     * @param Security $security
     */
    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('lentAt', DateType::class, [
                'required' => true,
                'html5' => false,
                'widget' => 'single_text',
                'format' => $this->security->getUser()->getDateFormatForForm(),
            ])
            ->add('lentTo', TextType::class, [
                'attr' => ['length' => 255],
                'required' => true,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Loan::class
        ]);
    }
}
