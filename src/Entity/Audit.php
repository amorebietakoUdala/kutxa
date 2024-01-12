<?php

namespace App\Entity;

use App\Repository\AuditRepository;
use App\Validator\RegistrationNumber;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Mapping\ClassMetadata;

#[ORM\Entity(repositoryClass: AuditRepository::class)]
class Audit
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'datetime')]
    private $createdAt;

    #[ORM\Column(type: 'string', length: 255)]
    private $cif;

    #[ORM\Column(type: 'string', length: 1024, nullable: true)]
    private $organization;

    #[ORM\Column(type: 'string', length: 255)]
    private $dni;

    #[ORM\Column(type: 'string', length: 255)]
    private $name;

    #[ORM\Column(type: 'string', length: 255)]
    private $file;

    #[ORM\Column(type: 'string', length: 255)]
    private $sha1;

    #[ORM\Column(type: 'bigint')]
    private $size;

    #[ORM\Column(type: 'string', length: 255)]
    private $senderEmail;

    #[ORM\Column(type: 'string', length: 255)]
    private $receiverEmail;

    #[ORM\Column(type: 'string', length: 1024)]
    private $issuer;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $registrationNumber;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getCif(): ?string
    {
        return $this->cif;
    }

    public function setCif(string $cif): self
    {
        $this->cif = $cif;

        return $this;
    }

    public function getOrganization(): ?string
    {
        return $this->organization;
    }

    public function setOrganization(?string $organization): self
    {
        $this->organization = $organization;

        return $this;
    }

    public function getDni(): ?string
    {
        return $this->dni;
    }

    public function setDni(string $dni): self
    {
        $this->dni = $dni;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getFile(): ?string
    {
        return $this->file;
    }

    public function setFile(string $file): self
    {
        $this->file = $file;

        return $this;
    }

    public function getSha1(): ?string
    {
        return $this->sha1;
    }

    public function setSha1(string $sha1): self
    {
        $this->sha1 = $sha1;

        return $this;
    }

    public function getSize(): ?string
    {
        return $this->size;
    }

    public function setSize(string $size): self
    {
        $this->size = $size;

        return $this;
    }

    public function getSenderEmail(): ?string
    {
        return $this->senderEmail;
    }

    public function setSenderEmail(string $senderEmail): self
    {
        $this->senderEmail = $senderEmail;

        return $this;
    }

    public function getReceiverEmail(): ?string
    {
        return $this->receiverEmail;
    }

    public function setReceiverEmail(string $receiverEmail): self
    {
        $this->receiverEmail = $receiverEmail;

        return $this;
    }

    public function getIssuer(): ?string
    {
        return $this->issuer;
    }

    public function setIssuer(string $issuer): self
    {
        $this->issuer = $issuer;

        return $this;
    }

    public function getRegistrationNumber(): ?string
    {
        return $this->registrationNumber;
    }

    public function setRegistrationNumber(?string $registrationNumber): self
    {
        $this->registrationNumber = $registrationNumber;

        return $this;
    }

    private function formatBytes($bytes, $precision = 2) 
    {
        $units = ['B', 'KiB', 'MiB', 'GiB', 'TiB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= 1024 ** $pow;

        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    public function getSizeFormated() 
    {
        return $this->formatBytes($this->size);
    }

    public function fill($giltzaUser) 
    {
        $this->setCif(array_key_exists('cif',$giltzaUser)? $giltzaUser['cif']: null);
        $this->setDni(array_key_exists('dni',$giltzaUser)? $giltzaUser['dni']: null);
        $this->setName(array_key_exists('name',$giltzaUser)? $giltzaUser['name']: null);
        $this->setOrganization(array_key_exists('organization',$giltzaUser)? $giltzaUser['organization']: null);
        $this->setIssuer(array_key_exists('issuer',$giltzaUser)? $giltzaUser['issuer']: null);
        return $this;
    }

    public function setFileData(UploadedFile $file) 
    {
        $fileName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME).'.'.$file->getClientOriginalExtension();
        $sha1 = sha1_file($file); 
        $size = $file->getSize();
        $this->setSha1($sha1);
        $this->setSize($size);
        $this->setFile($fileName);
        return $this;
    }
}

