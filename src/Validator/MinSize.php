<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;

/**
 * @Annotation
 *
 * @Target({"PROPERTY", "METHOD", "ANNOTATION"})
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class MinSize extends Constraint
{
    final public const NOT_FOUND_ERROR = 'd2a3fb6e-7ddc-4210-8fbf-2ab345ce1998';
    final public const NOT_READABLE_ERROR = 'c20c92a4-5bfa-4202-9477-28e800e0f6ff';
    final public const EMPTY_ERROR = '5d743385-9775-4aa5-8ff5-495fb1e60137';
    final public const TOO_SMALL_ERROR = 'df8637af-d466-48c6-a59d-e7126250a654';

    protected const ERROR_NAMES = [
        self::NOT_FOUND_ERROR => 'NOT_FOUND_ERROR',
        self::NOT_READABLE_ERROR => 'NOT_READABLE_ERROR',
        self::EMPTY_ERROR => 'EMPTY_ERROR',
        self::TOO_SMALL_ERROR => 'TOO_LARGE_ERROR',
    ];

    /*
     * Any public properties become valid options for the annotation.
     * Then, use these in your validator class.
     */
    public $message = 'The minimum file size is "{{ minSize }}{{ suffix }}" the file has only "{{ size }}{{ suffix }}".';

    public $size;
    public $binaryFormat = true;
    public $minSize;
    public $minSizeNumber;

    public function __construct($size, bool $binaryFormat = null, array $options = null, string $message = null, array $groups = null, $payload = null)
    {
        if (null !== $size && !\is_int($size) && !\is_string($size)) {
            throw new \TypeError(sprintf('"%s": Expected argument $size to be either null, an integer or a string, got "%s".', __METHOD__, get_debug_type($size)));
        }
        $this->size = $size;
        parent::__construct($options, $groups, $payload);
        $this->message = $message ?? $this->message;
        $this->binaryFormat = $binaryFormat ?? $this->binaryFormat;
        if (null !== $this->size) {
            $this->normalizeBinaryFormat($this->size);
        }
    }

    private function normalizeBinaryFormat($minSize)
    {
        $factors = [
            'k' => 1000,
            'ki' => 1 << 10,
            'm' => 1000 * 1000,
            'mi' => 1 << 20,
            'g' => 1000 * 1000 * 1000,
            'gi' => 1 << 30,
        ];
        if (ctype_digit((string) $minSize)) {
            $this->minSize = (int) $minSize;
            $this->minSizeNumber = (int) $minSize;
            $this->binaryFormat ??= false;
        } elseif (preg_match('/^(\d++)('.implode('|', array_keys($factors)).')$/i', (string) $minSize, $matches)) {
            $this->minSizeNumber = (int) $matches[1];
            $this->minSize = $matches[1] * $factors[$unit = strtolower($matches[2])];
            $this->binaryFormat ??= 2 === \strlen($unit);
        } else {
            throw new ConstraintDefinitionException(sprintf('"%s" is not a valid minimum size.', $minSize));
        }
    }
}
