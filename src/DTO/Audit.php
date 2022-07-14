<?php

namespace App\DTO;

use DateTime;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

class Audit
{
    private $cif;
    private $dni;
    private $fileName;
    private $sha1;
    private $size;
    private $senderEmail;
    private $receiverEmail;
    private $date;

    public function __construct($cif, $dni, $fileName, $sha1, $size, $senderEmail, $receiverEmail)
    {
        $this->cif = $cif;
        $this->dni = $dni;
        $this->fileName = $fileName;
        $this->sha1 = $sha1;
        $this->size = $size;
        $this->senderEmail = $senderEmail;
        $this->receiverEmail = $receiverEmail;
    }

    public function __toString(): string
    {
        $encoders = [new XmlEncoder(), new JsonEncoder()];
        $normalizers = [new ObjectNormalizer()];
        $serializer = new Serializer($normalizers, $encoders);
        $this->date = (new DateTime())->format('Y-m-d H:i:s');

        return $serializer->serialize($this, 'json');
    }

    /**
     * Get the value of cif.
     */
    public function getCif()
    {
        return $this->cif;
    }

    /**
     * Get the value of dni.
     */
    public function getDni()
    {
        return $this->dni;
    }

    /**
     * Get the value of fileName.
     */
    public function getFileName()
    {
        return $this->fileName;
    }

    /**
     * Get the value of sha1.
     */
    public function getSha1()
    {
        return $this->sha1;
    }

    /**
     * Get the value of size.
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * Get the value of date.
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Get the value of senderEmail.
     */
    public function getSenderEmail()
    {
        return $this->senderEmail;
    }

    /**
     * Get the value of receiverEmail.
     */
    public function getReceiverEmail()
    {
        return $this->receiverEmail;
    }
}
