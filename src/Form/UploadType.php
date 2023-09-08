<?php

namespace App\Form;

use App\Entity\Audit;
use App\Validator\MinSize;
use App\Validator\RegistrationNumber;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotBlank;

class UploadType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $maxFileSize = $options['maxFileSize'];
        $minFileSize = $options['minFileSize'];
        $receptionEmail = $options['receptionEmail'];
        $builder
            ->add('file', FileType::class, [
                 'label' => 'upload.file',
                'constraints' => [
                    new MinSize($minFileSize),
                    new File([
                        'maxSize' => $maxFileSize,

                        // 'mimeTypes' => [
                        //     'application/pdf',
                        //     'application/x-pdf',
                        // ]
                        ]),
                ],
            ])
            ->add('senderEmail', EmailType::class, [
                'label' => 'upload.senderEmail',
                'constraints' => [
                    new Email(),
                ],
                ])
            ->add('receiverEmail', HiddenType::class, [
                'label' => 'upload.receiverEmail',
                'constraints' => [
                    new Email(),
                ],
                'data' => $receptionEmail,
            ])
            ->add('registrationNumber', null, [
                'label' => 'upload.registrationNumber',
                'help' => new TranslatableMessage('upload.registrationNumber.help'),
                'required' => true,
                'constraints' => [
                    new NotBlank(),
                    new RegistrationNumber(),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Audit::class,
            'maxFileSize' => '500M',
            'minFileSize' => '50Mi',
            'receptionEmail' => null,
        ]);
    }
}
