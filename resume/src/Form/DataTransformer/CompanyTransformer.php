<?php
namespace App\Form\DataTransformer;

use App\Entity\Company;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class CompanyTransformer implements DataTransformerInterface
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * Transforms an object (company) to a string (number).
     *
     * @param  Company|null $company
     * @return Company
     */
    public function transform($company)
    {
        dump('transform');
        dump($company);

        if (null === $company) {
            return '';
        }

        return $company;
    }

    /**
     * Transforms a string (number) to an object (company).
     *
     * @param  int $id
     * @return Company|null
     * @throws TransformationFailedException if object (company) is not found.
     */
    public function reverseTransform($id)
    {
        dump('reverseTransform');
        dump($id);
        // no company number? It's optional, so that's ok
        if (!$id) {
            return;
        }

        /** @var Company $company */
        $company = $this->entityManager
            ->getRepository(Company::class)
            // query for the company with this id
            ->find($id)
        ;

        if (null === $company) {
            // causes a validation error
            // this message is not shown to the user
            // see the invalid_message option
            throw new TransformationFailedException(sprintf(
                'An company with number "%s" does not exist!',
                $id
            ));
        }

        return $company;
    }
}