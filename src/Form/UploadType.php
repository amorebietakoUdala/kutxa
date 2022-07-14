<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\File;

class UploadType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $maxFileSize = $options['maxFileSize'];
        $builder
            ->add('file', FileType::class, [
                 'label' => 'upload.file',
                'constraints' => [
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
            ->add('receiverEmail', EmailType::class, [
                'label' => 'upload.receiverEmail',
                'constraints' => [
                    new Email(),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'maxFileSize' => '500M',
        ]);
    }
}
