<?php

namespace App\Validator;

use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class MinSizeValidator extends ConstraintValidator
{
    final public const KB_BYTES = 1000;
    final public const MB_BYTES = 1_000_000;
    final public const KIB_BYTES = 1024;
    final public const MIB_BYTES = 1_048_576;

    private const SUFFICES = [
        1 => 'bytes',
        self::KB_BYTES => 'kB',
        self::MB_BYTES => 'MB',
        self::KIB_BYTES => 'KiB',
        self::MIB_BYTES => 'MiB',
    ];

    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof MinSize) {
            throw new UnexpectedTypeException($constraint, MinSize::class);
        }

        /* @var App\Validator\MinSize $constraint */

        if (null === $value || '' === $value) {
            return;
        }

        if ($value instanceof UploadedFile && !$value->isValid()) {
            switch ($value->getError()) {
                case \UPLOAD_ERR_INI_SIZE:
                    $iniLimitSize = UploadedFile::getMaxFilesize();
                    if ($constraint->maxSize && $constraint->maxSize < $iniLimitSize) {
                        $limitInBytes = $constraint->maxSize;
                        $binaryFormat = $constraint->binaryFormat;
                    } else {
                        $limitInBytes = $iniLimitSize;
                        $binaryFormat = $constraint->binaryFormat ?? true;
                    }

                    [, $limitAsString, $suffix] = $this->factorizeSizes(0, $limitInBytes, $binaryFormat);
                    $this->context->buildViolation($constraint->uploadIniSizeErrorMessage)
                        ->setParameter('{{ limit }}', $limitAsString)
                        ->setParameter('{{ suffix }}', $suffix)
                        ->setCode((string) \UPLOAD_ERR_INI_SIZE)
                        ->addViolation();

                    return;
                case \UPLOAD_ERR_FORM_SIZE:
                    $this->context->buildViolation($constraint->uploadFormSizeErrorMessage)
                        ->setCode((string) \UPLOAD_ERR_FORM_SIZE)
                        ->addViolation();

                    return;
                case \UPLOAD_ERR_PARTIAL:
                    $this->context->buildViolation($constraint->uploadPartialErrorMessage)
                        ->setCode((string) \UPLOAD_ERR_PARTIAL)
                        ->addViolation();

                    return;
                case \UPLOAD_ERR_NO_FILE:
                    $this->context->buildViolation($constraint->uploadNoFileErrorMessage)
                        ->setCode((string) \UPLOAD_ERR_NO_FILE)
                        ->addViolation();

                    return;
                case \UPLOAD_ERR_NO_TMP_DIR:
                    $this->context->buildViolation($constraint->uploadNoTmpDirErrorMessage)
                        ->setCode((string) \UPLOAD_ERR_NO_TMP_DIR)
                        ->addViolation();

                    return;
                case \UPLOAD_ERR_CANT_WRITE:
                    $this->context->buildViolation($constraint->uploadCantWriteErrorMessage)
                        ->setCode((string) \UPLOAD_ERR_CANT_WRITE)
                        ->addViolation();

                    return;
                case \UPLOAD_ERR_EXTENSION:
                    $this->context->buildViolation($constraint->uploadExtensionErrorMessage)
                        ->setCode((string) \UPLOAD_ERR_EXTENSION)
                        ->addViolation();

                    return;
                default:
                    $this->context->buildViolation($constraint->uploadErrorMessage)
                        ->setCode((string) $value->getError())
                        ->addViolation();

                    return;
            }
        }

        if (!is_scalar($value) && !$value instanceof MinSize && !(\is_object($value) && method_exists($value, '__toString'))) {
            throw new UnexpectedValueException($value, 'string');
        }

        $path = $value instanceof File ? $value->getPathname() : (string) $value;

        if (!is_file($path)) {
            $this->context->buildViolation($constraint->notFoundMessage)
                ->setParameter('{{ file }}', $this->formatValue($path))
                ->setCode(MinSize::NOT_FOUND_ERROR)
                ->addViolation();

            return;
        }

        if (!is_readable($path)) {
            $this->context->buildViolation($constraint->notReadableMessage)
                ->setParameter('{{ file }}', $this->formatValue($path))
                ->setCode(MinSize::NOT_READABLE_ERROR)
                ->addViolation();

            return;
        }

        $sizeInBytes = filesize($path);
        $basename = $value instanceof UploadedFile ? $value->getClientOriginalName() : basename((string) $path);

        if (0 === $sizeInBytes) {
            $this->context->buildViolation($constraint->disallowEmptyMessage)
                ->setParameter('{{ file }}', $this->formatValue($path))
                ->setParameter('{{ name }}', $this->formatValue($basename))
                ->setCode(MinSize::EMPTY_ERROR)
                ->addViolation();

            return;
        }
        if ($constraint->minSize) {
            $limitInBytes = $constraint->minSize;
            if ($sizeInBytes < $limitInBytes) {
                [$sizeAsString, $limitAsString, $suffix] = $this->factorizeSizes($sizeInBytes, $limitInBytes, $constraint->binaryFormat);
                $this->context->buildViolation($constraint->message)
                    ->setParameter('{{ minSize }}', $constraint->minSizeNumber)
                    ->setParameter('{{ size }}', $sizeAsString)
                    ->setParameter('{{ suffix }}', $suffix)
                    ->setCode(MinSize::TOO_SMALL_ERROR)
                    ->addViolation();

                return;
            }
        }
    }

    private static function moreDecimalsThan(string $double, int $numberOfDecimals): bool
    {
        return \strlen($double) > \strlen(round($double, $numberOfDecimals));
    }

    private function factorizeSizes(int $size, $limit, bool $binaryFormat): array
    {
        if ($binaryFormat) {
            $coef = self::MIB_BYTES;
            $coefFactor = self::KIB_BYTES;
        } else {
            $coef = self::MB_BYTES;
            $coefFactor = self::KB_BYTES;
        }

        $limitAsString = (string) ($limit / $coef);

        // Restrict the limit to 2 decimals (without rounding! we
        // need the precise value)
        while (self::moreDecimalsThan($limitAsString, 2)) {
            $coef /= $coefFactor;
            $limitAsString = (string) ($limit / $coef);
        }

        // Convert size to the same measure, but round to 2 decimals
        $sizeAsString = (string) round($size / $coef, 2);

        // If the size and limit produce the same string output
        // (due to rounding), reduce the coefficient
        while ($sizeAsString === $limitAsString) {
            $coef /= $coefFactor;
            $limitAsString = (string) ($limit / $coef);
            $sizeAsString = (string) round($size / $coef, 2);
        }

        return [$sizeAsString, $limitAsString, self::SUFFICES[$coef]];
    }
}
