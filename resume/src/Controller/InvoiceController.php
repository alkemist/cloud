<?php

namespace App\Controller;

use AlterPHP\EasyAdminExtensionBundle\Controller\EasyAdminController;
use App\Entity\Invoice;
use App\Repository\InvoiceRepository;
use App\Service\InvoiceService;
use Doctrine\ORM\EntityManager;
use http\Client\Request;
use Konekt\PdfInvoice\InvoicePrinter;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\RequestInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Message;
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

    /** @var MailerInterface */
    private $mailer;

    /** @var TranslatorInterface */
    private $translator;

    public function __construct(InvoiceRepository $invoiceRepository,
                                InvoiceService $invoiceService,
                                MailerInterface $mailer,
                                TranslatorInterface $translator)
    {
        $this->invoiceRepository = $invoiceRepository;
        $this->invoiceService = $invoiceService;
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

        if (!$entity->getPayedAt()) {
            $entity->setStatus(Invoice::STATUS_PAYED);
            $entity->setPayedAt(new \DateTime('now'));
            $this->invoiceService->updatePeriod($entity);

            $this->em->flush();
        }


        return $this->redirectToReferrer();
    }

    public function sendAction()
    {
        $this->pdfAction();

        $id = $this->request->query->get('id');
        /** @var Invoice $entity */
        $entity = $this->em->getRepository(Invoice::class)->find($id);
        $emails = $entity->getCompany()->getEmails();

        if ($entity && $entity->getFilename() && count($emails) > 0) {
            $this->invoiceService->createPdf($entity);

            $email = (new Email())
                ->from($this->getParameter('MAILER_FROM'))
                ->subject($this->getParameter('MAILER_SUBJECT') . ' ' . $this->translator->trans('Invoice') . ' n°' . $entity->getNumber())
                ->text($this->renderView(
                    'email/invoice.txt.twig',
                    ['invoice' => $entity]
                ))
                ->attachFromPath(
                    $this->getParameter('PDF_DIRECTORY').$entity->getFilename(),
                    'invoice-jeremy-achain-'.$entity->getNumber().'.pdf');

            if ($this->getParameter('APP_ENV') == 'prod') {
                $email->to($emails[0]);
                $email->addCc($this->getParameter('MAILER_FROM'));

                if (count($emails) > 1) {
                    for($i = 1; $i < count($emails); $i++) {
                        $email->addCc($emails[$i]);
                    }
                }
            } else {
                $email->to($this->getParameter('MAILER_FROM'));
            }

            $this->mailer->send($email);

            return $this->redirectToReferrer();
        }
        throw new \Exception('Email not found');
    }

    protected function deleteAction(){
        $id = $this->request->query->get('id');
        /** @var Invoice $entity */
        $entity = $this->em->getRepository(Invoice::class)->find($id);

        if ($entity && $entity->getStatus() === Invoice::STATUS_DRAFT) {
            return parent::deleteAction();
        }

        return $this->redirectToReferrer();
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

    /**
     * @param Invoice $entity
     * @throws \Exception
     */
    protected function persistEntity($entity){
        $this->invoiceService->calculTotalHt($entity);
        $this->invoiceService->calculTva($entity);
        $this->invoiceService->updatePeriod($entity);

        parent::persistEntity($entity);
    }

    /**
     * @param Invoice $entity
     * @throws \Exception
     */
    protected function updateEntity($entity){
        if ($entity->isEditable()) {
            $this->invoiceService->calculTotalHt($entity);
            $this->invoiceService->calculTva($entity);
            $this->invoiceService->updatePeriod($entity);
        }

        parent::updateEntity($entity);
    }
}
