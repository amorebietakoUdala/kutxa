<?php

namespace App\Form;

use App\Entity\Audit;
use App\Validator\MinSize;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;

class UploadType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $maxFileSize = $options['maxFileSize'];
        $minFileSize = $options['minFileSize'];
        $register = $options['register'];
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
                    new Email()
                ]
                ]);
            if ( $register) {
                $builder->add('receiverEmail', HiddenType::class, [
                    'label' => 'upload.receiverEmail',
                    'constraints' => [
                        new Email()
                    ],
                    'data' => $receptionEmail,
                ]);
            } else {
                $builder->add('receiverEmail', EmailType::class, [
                    'label' => 'upload.receiverEmail',
                    'constraints' => [
                        new Email()
                    ]
                ]);
            }
            if ($register) {
                $builder->add('registrationNumber', null, [
                    'label' => 'upload.registrationNumber',
                    'required' => true,
                    'constraints' => [
                        new NotBlank(),
                    ]
                ]);
            }
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Audit::class,
            'maxFileSize' => '500M',
            'minFileSize' => '50Mi',
            'register' => false,
            'receptionEmail' => null,
        ]);
    }
}