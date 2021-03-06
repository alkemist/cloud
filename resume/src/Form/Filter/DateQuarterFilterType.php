<?php
namespace App\Form\Filter;

use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Form\Filter\Type\FilterType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DateQuarterFilterType extends FilterType
{
    public function configureOptions(OptionsResolver $resolver)
    {
        $values = range(1, 4);
        $choices = array_combine($values, $values);
        $resolver->setDefaults([
            'choices' => $choices,
        ]);
    }

    public function getParent()
    {
        return ChoiceType::class;
    }

    public function filter(QueryBuilder $queryBuilder, FormInterface $form, array $metadata)
    {
        $queryBuilder->andWhere('ToChar(entity.'.$metadata['type_options']['data'].', \'Q\') = :quarter')
            ->setParameter('quarter', (string) $form->getData());
    }
}