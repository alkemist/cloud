<?php

namespace App\Controller;

use AlterPHP\EasyAdminExtensionBundle\Controller\EasyAdminController;
use App\Entity\Invoice;
use App\Repository\InvoiceRepository;
use App\Service\DeclarationService;
use App\Service\InvoiceService;
use Doctrine\ORM\EntityManager;
use http\Client\Request;
use Konekt\PdfInvoice\InvoicePrinter;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\RequestInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class InvoiceController extends EasyAdminController
{
    /** @var InvoiceRepository */
    private $invoiceRepository;

    /** @var InvoiceService */
    private $invoiceService;

    /** @var DeclarationService */
    private $declarationService;

    /** @var MailerInterface */
    private $mailer;

    /** @var TranslatorInterface */
    private $translator;

    public function __construct(InvoiceRepository $invoiceRepository,
                                InvoiceService $invoiceService,
                                DeclarationService $declarationService,
                                MailerInterface $mailer,
                                TranslatorInterface $translator)
    {
        $this->invoiceRepository = $invoiceRepository;
        $this->invoiceService = $invoiceService;
        $this->declarationService = $declarationService;
        $this->mailer = $mailer;
        $this->translator = $translator;
    }

    /**
     * @Route("/admin/invoice/{id}/pdf", name="invoice_pdf")
     * @ParamConverter("invoice", class="App:Invoice")
     */
    public function pdf(Invoice $invoice)
    {
        return new Response(
            $this->invoiceService->getOrCreatePdf($invoice, true),
            200,
            array(
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $invoice->getFilename() . '"'
            )
        );
    }

    public function pdfAction()
    {
        $id = $this->request->query->get('id');
        /** @var Invoice $entity */
        $entity = $this->em->getRepository(Invoice::class)->find($id);

        return $this->pdf($entity);
    }

    public function validateAction()
    {
        $id = $this->request->query->get('id');
        /** @var Invoice $entity */
        $entity = $this->em->getRepository(Invoice::class)->find($id);

        $entity->setStatus(Invoice::STATUS_PAYED);
        $entity->setPayedAt(new \DateTime('now'));

        $this->em->flush();

        $this->declarationService->attachInvoice($entity);

        return $this->redirectToReferrer();
    }

    public function sendAction()
    {
        $id = $this->request->query->get('id');
        /** @var Invoice $entity */
        $entity = $this->em->getRepository(Invoice::class)->find($id);

        if ($entity->getFilename() && $entity->getCompany()->getEmail()) {
            $this->invoiceService->createPdf($entity);

            $email = (new Email())
                ->from($this->getParameter('MAILER_FROM'))
                ->to($this->getParameter('APP_ENV') == 'prod'
                    ? $entity->getCompany()->getEmail()
                    : $this->getParameter('MAILER_FROM')
                )
                ->subject($this->getParameter('MAILER_SUBJECT') . ' ' .
                    $this->translator->trans('Invoice') . ' n°' . $entity->getNumber())
                ->text($this->renderView(
                    'email/invoice.txt.twig',
                    ['invoice' => $entity]
                ))
                ->attachFromPath(
                    $this->getParameter('PDF_DIRECTORY').$entity->getFilename(),
                    'invoice-jeremy-achain-'.$entity->getNumber());

            $this->mailer->send($email);

            return $this->redirectToReferrer();
        }
        throw new \Exception('Email not found');
    }


    /**
     * @Route("/admin/invoice/csv", name="invoices_csv")
     */
    public function csv()
    {
        $filename = $this->invoiceService->generateInvoicesBook();

        return $this->file(
            $filename,
            'livre-recettes.csv',
            ResponseHeaderBag::DISPOSITION_ATTACHMENT
        );
    }

    private function calculTotalHt(Invoice $invoice)
    {
        $invoice->setTotalHt($invoice->getTjm() * $invoice->getDaysCount());
    }

    // Creates the form object passed to the 'edit' and 'new' templates (using the
    // form builder created by 'createEntityFormBuilder()')
    /*protected function createEntityForm($entity, array $entityProperties, $view)
    {
        dump('createEntityForm');
        dump($entityProperties);
        dump($view);
        return parent::createEntityForm($entity, $entityProperties, $view);
    }*/

    // Creates the form builder used to create the form rendered in the
    // create and edit actions
    protected function createEntityFormBuilder($entity, $view)
    {
        /** @var FormBuilderInterface $formBuilder */
        $formBuilder = parent::createEntityFormBuilder($entity, $view);
        $formBuilder->get('company');

        dump($formBuilder->get('company'));
        return $formBuilder;
    }

    // Returns the list of form options used by 'createEntityFormBuilder()'
    /*protected function getEntityFormOptions($entity, $view)
    {
        dump('getEntityFormOptions');
        dump(parent::getEntityFormOptions($entity, $view));
        return parent::getEntityFormOptions($entity, $view);
    }*/

    /**
     * @param Invoice $entity
     */
    // It persists and flushes the given Doctrine entity. It allows to modify the entity
    // before/after being saved in the database (e.g. to transform a DTO into a Doctrine entity)
    protected function persistEntity($entity){
        dump('persistEntity');
        dump($entity);

        $this->calculTotalHt($entity);
        $this->declarationService->attachInvoice($entity);

        $isOutOfTaxLimit = $this->invoiceRepository->isOutOfTaxLimit($entity->getTotalHt());

        if ($isOutOfTaxLimit) {
            $entity->setTotalTax($entity->getTotalHt() * Invoice::TAX_MULTIPLIER);
        }

        parent::persistEntity($entity);
    }

    /**
     * @param Invoice $entity
     */
    // It flushes the given Doctrine entity to save its changes. It allows to modify
    // the entity before it's saved in the database.
    protected function updateEntity($entity){
        dump('updateEntity');
        dump($entity);

        $this->calculTotalHt($entity);
        $this->declarationService->attachInvoice($entity);

        parent::updateEntity($entity);
    }
}
