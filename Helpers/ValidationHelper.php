<?php

namespace Helpers;

use Models\File;
use Types\ValueType;

class ValidationHelper
{
    public static function integer($value, float $min = -INF, float $max = INF): ?int
    {
        if ($value === '') {
            return null;
        }

        // PHPには、データを検証する組み込み関数があります。詳細は https://www.php.net/manual/en/filter.filters.validate.php を参照ください。
        $value = filter_var($value, FILTER_VALIDATE_INT, ["min_range" => (int) $min, "max_range" => (int) $max]);

        // 結果がfalseの場合、フィルターは失敗したことになります。
        if ($value === false) {
            throw new \InvalidArgumentException("The provided value is not a valid integer.");
        }

        // 値がすべてのチェックをパスしたら、そのまま返します。
        return $value;
    }

    public static function string($value): ?string
    {
        if ($value === '') {
            return null;
        }

        return is_string($value) ? $value : throw new \InvalidArgumentException("The provided value is not a valid string.");
    }

    public static function validateDate(string $date, string $format = 'Y-m-d'): string
    {
        $d = \DateTime::createFromFormat($format, $date);
        if ($d && $d->format($format) === $date) {
            return $date;
        }

        throw new \InvalidArgumentException(sprintf("Invalid date format for %s. Required format: %s", $date, $format));
    }

    public static function validateAuth(array $fields, array $data): array
    {
        $validatedData = [];

        foreach ($fields as $field => $type) {
            if (!isset($data[$field]) || ($data)[$field] === '') {
                throw new \InvalidArgumentException("Missing field: $field");
            }

            $value = $data[$field];

            $validatedValue = match ($type) {
                ValueType::STRING => is_string($value) ? $value : throw new \InvalidArgumentException("The provided value is not a valid string."),
                ValueType::INT => self::integer($value), // 必要に応じて、この方法をさらにカスタマイズすることができます。
                ValueType::FLOAT => filter_var($value, FILTER_VALIDATE_FLOAT),
                ValueType::DATE => self::validateDate($value),
                ValueType::EMAIL => filter_var($value, FILTER_VALIDATE_EMAIL),
                ValueType::PASSWORD =>
                                    is_string($value) &&
                                    strlen($value) >= 8 && // Minimum 8 characters
                                    preg_match('/[A-Z]/', $value) && // 少なくとも1文字の大文字
                                    preg_match('/[a-z]/', $value) && // 少なくとも1文字の小文字
                                    preg_match('/\d/', $value) && // 少なくとも1桁
                                    preg_match('/[\W_]/', $value) // 少なくとも1つの特殊文字（アルファベット以外の文字）
                                        ? $value : throw new \InvalidArgumentException("The provided value is not a valid password."),
                default => throw new \InvalidArgumentException(sprintf("Invalid type for field: %s, with type %s", $field, $type)),
            };

            if ($validatedValue === false) {
                throw new \InvalidArgumentException(sprintf("Invalid value for field: %s", $field));
            }

            $validatedData[$field] = $validatedValue;
        }

        return $validatedData;
    }

    public static function validateFields(array $fields, array $data): array
    {
        $validatedData = [];

        foreach ($fields as $field => $type) {
            if (!isset($data[$field])) {
                throw new \InvalidArgumentException("Missing field: $field");
            }

            $value = $data[$field];

            $validatedValue = match ($type) {
                ValueType::STRING => self::string($value),
                ValueType::INT => self::integer($value),
                ValueType::FLOAT => filter_var($value, FILTER_VALIDATE_FLOAT),
                ValueType::DATE => self::validateDate($value),
                default => throw new \InvalidArgumentException(sprintf("Invalid type for field: %s, with type %s", $field, $type)),
            };

            if ($validatedValue === false) {
                throw new \InvalidArgumentException(sprintf("Invalid value for field: %s", $field));
            }

            $validatedData[$field] = $validatedValue;
        }

        return $validatedData;
    }

    public static function validateFile(File $file): File
    {
        $availableTypeList = ['image/png', 'image/jpg', 'image/jpeg', 'image/gif', 'image/webp'];

        $type = $file->getType();
        $type = in_array($type, $availableTypeList, true) ? $type : false;
        if ($type === false) {
            throw new \InvalidArgumentException('The provided value is not a valid type.');
        }

        $maxSize = 2 * 1024 * 1024; // 2MB
        if ($file->getSize() > $maxSize) {
            throw new \InvalidArgumentException('The uploaded file exceeds the maximum allowed size of 2MB.');
        }

        return $file;
    }
}
