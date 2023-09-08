<?php

namespace App\Controller;

use App\Entity\Audit;
use App\Form\UploadType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class UploadController extends AbstractController
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly TranslatorInterface $translator,
        private readonly EntityManagerInterface $em,
        private readonly string $maxFileSize,
        private readonly string $minFileSize,
        private readonly string $receptionEmail,
        private readonly string $receiverDomain,
        private readonly string $uploadDir,
        private readonly string $sendMessagesReceiver,
        private readonly string $sendMessagesSender,
        private readonly string $mailerFrom,
        private readonly string $sendBCC,
        private readonly string $mailerBCC,
        private readonly string $downloadUri = '/uploads',
    ) {
    }

    #[Route(path: '/{_locale}/erregistro', name: 'app_register')]
    public function upload(Request $request): Response
    {
        if ($request->getSession()->get('giltzaUser') === null) {
            return $this->redirectToRoute('app_giltza');
        }
        $form = $this->createForm(UploadType::class, null, [
            'maxFileSize' => $this->maxFileSize,
            'minFileSize' => $this->minFileSize,
            'receptionEmail' => $this->receptionEmail,
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Audit $data */
            $data = $form->getData();
            if (!str_contains($data->getReceiverEmail(), $this->receiverDomain)) {
                $message = $this->translator->trans('message.domainNotAllowed', [
                    'receiverDomain' => $this->receiverDomain,
                ]);
                $this->addFlash('error', $message);

                return $this->render('kutxa/upload.html.twig', [
                    'form' => $form,
                    'maxFileSize' => $this->maxFileSize,
                    'minFileSize' => $this->minFileSize,
                ]);
            }
            /** @var UploadedFile $file */
            $file = $form->get('file')->getData();
            $data->setFileData($file);
            $error = $this->moveUploadedFile($file, $data->getRegistrationNumber());
            if (!$error) {
                $giltzaUser = $request->getSession()->get('giltzaUser');
                $data->fill($giltzaUser);
                $data->setCreatedAt(new \DateTime());
                $this->sendEmails($data);
                $this->em->persist($data);
                $this->em->flush();
                $message = $this->translator->trans('message.fileSaved');
                $this->addFlash('success', $message);

                return $this->redirectToRoute('app_register');
            }
        }

        return $this->render('kutxa/upload.html.twig', [
            'form' => $form,
            'maxFileSize' => $this->maxFileSize,
            'minFileSize' => $this->minFileSize,
        ]);
    }

    private function moveUploadedFile(UploadedFile $file, $directory = null): bool
    {
        $error = false;
        if ($file) {
            $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $newFilename = $originalFilename.'.'.$file->getClientOriginalExtension();
            try {
                $sha1 = sha1_file($file);
                $finalDir = $this->createDirectories($sha1, $directory);
                file_exists($finalDir) ? $this->deleteDirectory($finalDir) : mkdir($finalDir);
                $file->move($finalDir, $newFilename);
            } catch (FileException $e) {
                $error = true;
                $this->addFlash('error', $e->getMessage());
            }
        }

        return $error;
    }

    private function createDirectories($sha1, $directory = null): string
    {
        $year = date('Y');
        $baseDir = $this->uploadDir.'/'.$year;
        if (!file_exists($baseDir)) {
            dump($baseDir);
            mkdir($baseDir, 0777, true);
        }
        if (null !== $directory) {
            $fixedDirectory = str_replace('/', '-', (string) $directory);
            $registrationRootDir = $baseDir.'/'.$fixedDirectory;
            if (file_exists($registrationRootDir)) {
                $this->deleteDirectory($registrationRootDir);
            }
            mkdir($registrationRootDir);
            $finalDir = $registrationRootDir.'/'.$sha1;
        } else {
            $finalDir = $baseDir.'/'.$sha1;
        }

        return $finalDir;
    }

    private function sendEmails(Audit $data): void
    {
        $context = [
            'data' => $data,
            'downloadUri' => $this->downloadUri,
            'year' => date('Y'),
        ];
        if ($this->sendMessagesReceiver) {
            $template = 'kutxa/fileReceptionEmailReceiver.html.twig';
            $subject = $this->translator->trans('message.emailSubjectReceiver');
            $this->sendEmail($data->getReceiverEmail(), $subject, $template, $context);
        }
        if ($this->sendMessagesSender) {
            $template = 'kutxa/fileReceptionEmailSender.html.twig';
            $subject = $this->translator->trans('message.emailSubjectSender');
            $this->sendEmail($data->getSenderEmail(), $subject, $template, $context);
        }
    }

    private function sendEmail($to, $subject, $template, $context): void
    {
        $email = (new TemplatedEmail())
            ->from($this->mailerFrom)
            ->to($to)
            ->subject($subject)
            ->htmlTemplate($template)
            ->context($context);
        if ($this->sendBCC) {
            $addresses = [$this->mailerBCC];
            foreach ($addresses as $address) {
                $email->addBcc($address);
            }
        }
        $this->mailer->send($email);
    }

    private function deleteDirectory($dir): bool
    {
        if (!file_exists($dir)) {
            return true;
        }
        if (!is_dir($dir)) {
            return unlink($dir);
        }

        foreach (scandir($dir) as $item) {
            if ($item == '.' || $item == '..') {
                continue;
            }
            if (!$this->deleteDirectory($dir.DIRECTORY_SEPARATOR.$item)) {
                return false;
            }
        }

        return rmdir($dir);
    }
}
