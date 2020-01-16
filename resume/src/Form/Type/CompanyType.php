<?php

namespace App\Form\Type;

use App\Entity\Company;
use App\Form\DataTransformer\CompanyTransformer;
use EasyCorp\Bundle\EasyAdminBundle\Configuration\ConfigManager;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\EasyAdminAutocompleteType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CompanyType extends EasyAdminAutocompleteType
{
    private $transformer;

    public function __construct(
        ConfigManager $configManager,
        CompanyTransformer $transformer
    ) {
        parent::__construct($configManager);
        $this->transformer = $transformer;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);
        $builder->addModelTransformer($this->transformer);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);
    }
}
