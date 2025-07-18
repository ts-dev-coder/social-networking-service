<?php

namespace Validators\Rules;

use Database\DataAccess\DAOFactory;
use Helpers\Authenticate;

class Rule
{
    private const RULE_METHODS = [
        'required' => 'required',
        'string'   => 'string',
        'int'      => 'int',
        'min'      => 'min',
        'max'      => 'max',
        'exists'   => 'exists',
        'email'    => 'email',
        'password' => 'password',
        'date' => 'date'

    ];

    private const MESSAGE_METHODS = [
        'required' => 'requiredMessage',
        'string'   => 'stringMessage',
        'int'      => 'intMessage',
        'min'      => 'minMessage',
        'max'      => 'maxMessage',
        'exists'   => 'existsMessage',
        'email'    => 'emailMessage',
        'password' => 'passwordMessage',
        'date'    => 'dateMessage'
    ];

    public function __construct(
        private string $field,
        private $data,
        private string $rule
    ) {
    }

    public function passes(): ?array
    {
        $ruleName = explode(':', $this->rule)[0];
        $method = self::RULE_METHODS[$ruleName] ?? null;
        if (!$method || !method_exists($this, $method)) {
            throw new \InvalidArgumentException("Unknown validation rule: {$ruleName}");
        }
        return $this->$method();
    }

    public function message(): string
    {
        $ruleName = explode(':', $this->rule)[0];
        $method = self::MESSAGE_METHODS[$ruleName] ?? null;
        if (!$method || !method_exists($this, $method)) {
            throw new \InvalidArgumentException("Unknown validation rule: {$ruleName}");
        }
        return $this->$method();
    }

    private function requiredMessage(): string
    {
        return "{$this->field} is required.";
    }

    private function required(): ?array
    {
        return isset($this->data) && $this->data !== '' ? [$this->field => $this->data] : null;
    }

    private function string(): ?array
    {
        if ($this->data === '') {
            return [$this->field => null];
        }
        return is_string($this->data) ? [$this->field => $this->data] : null;
    }

    private function stringMessage(): string
    {
        return "{$this->field} must be a string.";
    }

    private function int(): ?array
    {
        if ($this->data === '') {
            return [$this->field => null];
        }
        return filter_var($this->data, FILTER_VALIDATE_INT) !== false ?
                    [$this->field => (int)$this->data]
                    :
                    null;
    }

    private function intMessage(): string
    {
        return "{$this->field} must be an integer.";
    }

    private function min(): ?array
    {
        if ($this->data === '') {
            return [$this->field => null];
        }
        $min = explode(':', $this->rule)[1];

        if (is_string($this->data)) {
            if (mb_strlen($this->data) < $min) {
                return null;
            }
            return [$this->field => $this->data];
        }

        if (is_int($this->data) || ctype_digit((string)$this->data)) {
            if ((int)$this->data < $min) {
                return null;
            }
            return [$this->field => (int)$this->data];
        }

        throw new \InvalidArgumentException("{$this->field} must be a string or integer for min validation.");
    }

    private function minMessage(): string
    {
        $min = explode(':', $this->rule)[1];

        if (is_string($this->data)) {
            return "{$this->field} must be at least {$min} characters.";
        }

        if (is_int($this->data) || ctype_digit((string)$this->data)) {
            return "{$this->field} must be at least {$min}.";
        }

        throw new \InvalidArgumentException("{$this->field} must be a string or integer for min validation.");
    }

    private function max(): ?array
    {
        if ($this->data === '') {
            return [$this->field => null];
        }
        $max = explode(':', $this->rule)[1];

        if (is_string($this->data)) {
            if (mb_strlen($this->data) > $max) {
                return null;
            }
            return [$this->field => $this->data];
        }

        if (is_int($this->data) || ctype_digit((string)$this->data)) {
            if ((int)$this->data > $max) {
                return null;
            }
            return [$this->field => (int)$this->data];
        }

        throw new \InvalidArgumentException("{$this->field} must be a string or integer for min validation.");
    }

    private function maxMessage(): string
    {
        $max = explode(':', $this->rule)[1];

        if (is_string($this->data)) {
            return "{$this->field} must be at most {$max} characters.";
        }

        if (is_int($this->data) || ctype_digit((string)$this->data)) {
            return "{$this->field} must be at most {$max}.";
        }

        throw new \InvalidArgumentException("{$this->field} must be a string or integer for min validation.");
    }

    private function exists(): ?array
    {
        [$_, $table, $identifier] = explode(':', str_replace(',', ':', $this->rule));

        if ($table === 'users') {
            $profileDAO = DAOFactory::getProfileDAO();
            $profile = match ($identifier) {
                'id' => $profileDAO->getByUserId($this->data),
                'username' => $profileDAO->getByUsername($this->data),
                default => throw new \InvalidArgumentException('Identifier is not valid: ' . $identifier),
            };
            return isset($profile) ? [$this->field => $profile] : null;
        } elseif ($table === 'posts') {
            $user = Authenticate::getAuthenticatedUser();
            $postDAO = DAOFactory::getPostDAO();
            $post = $postDAO->getById($this->data, $user->getId());
            return isset($post) ? [$this->field => $post] : null;
        } elseif ($table === 'notifications') {
            $notificationDAO = DAOFactory::getNotificationDAO();
            $notification = $notificationDAO->getNotification($this->data);
            return isset($notification) ? [$this->field => $notification] : null;
        } elseif ($table === 'conversations') {
            $conversationDAO = DAOFactory::getConversationDAO();
            $conversation = $conversationDAO->findByConversationId($this->data);
            return isset($conversation) ? [$this->field => $conversation] : null;
        } else {
            throw new \InvalidArgumentException("Invalid table specified in validation rule: {$table}.");
        }
    }

    private function existsMessage(): string
    {
        [$_, $table, $_] = explode(':', str_replace(',', ':', $this->rule));

        if ($table === 'users') {
            return "{$this->data} does not exist.";
        } elseif ($table === 'posts') {
            return "Post does not exist.";
        } elseif ($table === 'conversations') {
            return "Conversation does not exist.";
        } elseif ($table === 'notifications') {
            return "Notification does not exist.";
        } else {
            throw new \InvalidArgumentException("Invalid table specified in validation rule: {$table}.");
        }

    }

    private function email(): ?array
    {
        return filter_var($this->data, FILTER_VALIDATE_EMAIL) ? [$this->field => $this->data] : null;
    }

    private function emailMessage(): string
    {
        return "{$this->field} must be a valid email address.";
    }

    private function password(): ?array
    {
        return is_string($this->data) &&
               strlen($this->data) >= 8 && // Minimum 8 characters
               preg_match('/[A-Z]/', $this->data) && // 少なくとも1文字の大文字
               preg_match('/[a-z]/', $this->data) && // 少なくとも1文字の小文字
               preg_match('/\d/', $this->data) && // 少なくとも1桁
               preg_match('/[\W_]/', $this->data) // 少なくとも1つの特殊文字（アルファベット以外の文字）
                ? [$this->field => $this->data] : null;
    }

    private function passwordMessage(): string
    {
        return "The provided value is not a valid password.";
    }

    private function date(): ?array
    {
        $format = 'Y-m-d';
        $d = \DateTime::createFromFormat($format, $this->data);
        if ($d && $d->format($format) === $this->data) {
            return [$this->field => $this->data];
        }

        return null;
    }

    private function dateMessage(): string
    {
        return "Invalid date format for {$this->data}. Required format: Y-m-d";
    }


}
