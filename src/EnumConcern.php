<?php

declare(strict_types=1);

namespace Luminarix\EnumConcern;

use BackedEnum;
use Illuminate\Support\Collection;
use ReflectionClass;
use UnitEnum;

trait EnumConcern
{
    /**
     * Check if the current enum is a BackedEnum.
     */
    public static function isBackedEnum(): bool
    {
        // @phpstan-ignore function.alreadyNarrowedType, function.impossibleType
        return is_subclass_of(static::class, BackedEnum::class);
    }

    /**
     * Retrieves a Collection of all the values defined in the enum.
     * For pure enums, returns the names as values.
     */
    public static function values(): Collection
    {
        if (self::isBackedEnum()) {
            return self::toCollection()->pluck('value');
        }

        // For pure enums, use the names as values
        return self::names();
    }

    /**
     * Gets a Collection of all the names (keys) of the enum cases.
     */
    public static function names(): Collection
    {
        return self::toCollection()->pluck('name');
    }

    /**
     * Converts the enum cases into an associative array with names as keys and values as values.
     * For pure enums, the values are the names.
     *
     * @return array<string, string|int>
     */
    public static function toArray(): array
    {
        /** @var array<string, string|int> $result */
        $result = self::toKeyValueCollection()->toArray();

        return $result;
    }

    /**
     * Returns a Laravel Collection instance containing all enum cases.
     */
    public static function toCollection(): Collection
    {
        return collect(self::cases());
    }

    /**
     * Selects and returns a random enum case.
     */
    public static function random(?int $count = null): Collection|static
    {
        /** @var Collection|static $result */
        $result = self::toCollection()->random($count);

        return $result;
    }

    /**
     * Attempts to return the enum case for the given value; returns null if not found.
     * For pure enums, uses the name instead.
     */
    public static function tryFromValue(string|int $value): ?static
    {
        if (self::isBackedEnum()) {
            // @phpstan-ignore staticMethod.notFound
            return static::tryFrom($value);
        }

        // For pure enums, value is the name
        return self::tryFromName((string)$value);
    }

    /**
     * Attempts to return the enum case for the given name; returns null if not found.
     */
    public static function tryFromName(string $name): ?static
    {
        /** @var ?static $result */
        $result = self::toCollection()->firstWhere('name', $name);

        return $result;
    }

    /**
     * Checks if the enum contains the specified value; returns a boolean.
     * For pure enums, checks the name.
     */
    public static function hasValue(string|int $value): bool
    {
        if (self::isBackedEnum()) {
            return self::values()->contains($value);
        }

        // For pure enums, value is the name
        return self::hasName((string)$value);
    }

    /**
     * Checks if the enum contains the specified name; returns a boolean.
     */
    public static function hasName(string $name): bool
    {
        return self::names()->contains($name);
    }

    /**
     * Compares two enum instances for equality.
     */
    public function is(self $other): bool
    {
        return $this === $other;
    }

    /**
     * Checks if two enum instances are not equal.
     */
    public function isNot(self $other): bool
    {
        return !$this->is($other);
    }

    /**
     * Returns attributes associated with the enum case.
     *
     * @return array<mixed>
     */
    public function getAttributes(): array
    {
        $reflection = new ReflectionClass($this);
        $caseName = $this->name;
        $reflectionConstant = $reflection->getReflectionConstant($caseName);

        if ($reflectionConstant === false) {
            return [];
        }

        $attrs = $reflectionConstant->getAttributes();

        $attributes = [];
        foreach ($attrs as $attr) {
            $attributes[$attr->getName()] = $attr->getArguments();
        }

        return $attributes;
    }

    /**
     * Gets the next enum case in the sequence.
     */
    public function next(): ?static
    {
        $cases = self::toCollection();
        /** @var int|false $index */
        $index = $cases->search($this, strict: true);

        if ($index === false) {
            return null;
        }

        /** @var ?static $result */
        $result = $cases->get($index + 1);

        return $result;
    }

    /**
     * Gets the previous enum case in the sequence.
     */
    public function previous(): ?static
    {
        $cases = self::toCollection();
        /** @var int|false $index */
        $index = $cases->search($this, strict: true);

        if ($index === false) {
            return null;
        }

        /** @var ?static $result */
        $result = $cases->get($index - 1);

        return $result;
    }

    /**
     * Returns the index (position) of the current enum case within the enum.
     */
    public function index(): ?int
    {
        /**
         * @var int|false $index
         */
        $index = self::toCollection()->search($this, true);

        return ($index !== false) ? $index : null;
    }

    /**
     * Serializes the enum cases to a JSON string.
     */
    public static function toJson(): string
    {
        return self::toKeyValueCollection()->toJson();
    }

    /**
     * Deserializes a JSON string back into enum cases.
     */
    public static function fromJson(string $json): Collection
    {
        /** @var list<string|int> $data */
        $data = json_decode($json, true);

        if (self::isBackedEnum()) {
            // @phpstan-ignore staticMethod.notFound
            return collect($data)->map(fn (int|string $value) => static::from($value));
        }

        /** @var list<string> $data */
        return collect($data)->map(fn (string $name) => self::tryFromName($name));
    }

    /**
     * Provides validation rules for the enum, useful for Form Requests.
     *
     * @return array<string>
     */
    public static function rules(): array
    {
        $values = self::isBackedEnum() ? self::values() : self::names();

        return ['in:' . $values->implode(',')];
    }

    /**
     * Retrieves the name/key associated with a given value.
     * For pure enums, returns the name if it exists.
     */
    public static function getKeyByValue(string|int $value): ?string
    {
        if (self::isBackedEnum()) {
            return self::tryFromValue($value)?->name;
        }

        // For pure enums, value is the name
        return self::hasName((string)$value) ? (string)$value : null;
    }

    /**
     * Retrieves the value associated with a given name/key.
     * For pure enums, value is the name.
     */
    public static function getValueByKey(string $key): string|int|null
    {
        if (self::isBackedEnum()) {
            // @phpstan-ignore property.notFound
            return self::tryFromName($key)?->value;
        }

        // For pure enums, value is the name
        return self::hasName($key) ? $key : null;
    }

    /**
     * Applies a callback function to all enum cases and returns the modified collection.
     */
    public static function map(callable $callback): Collection
    {
        return self::toCollection()->map($callback);
    }

    /**
     * Filters enum cases based on a callback function; returns matching cases.
     */
    public static function filter(callable $callback): Collection
    {
        return self::toCollection()->filter($callback)->values();
    }

    /**
     * Reduces the enum cases to a single value using a callback function.
     */
    public static function reduce(callable $callback, mixed $initial = null): mixed
    {
        return self::toCollection()->reduce($callback, $initial);
    }

    /**
     * Extracts a specific property from all enum cases.
     */
    public static function pluck(string $property): Collection
    {
        return self::toCollection()->pluck($property);
    }

    /**
     * Groups enum cases based on a specified property.
     */
    public static function groupBy(string|callable $property): Collection
    {
        return self::toCollection()->groupBy($property);
    }

    /**
     * Sorts the enum cases in ascending or descending order.
     */
    public static function sort(string $direction = 'asc'): Collection
    {
        $sorted = self::toCollection()->sortBy(
            // @phpstan-ignore argument.type
            callback: function (UnitEnum $case) {
                if (self::isBackedEnum()) {
                    /** @var BackedEnum $case */
                    return $case->value;
                }

                return $case->name;
            },
            options: SORT_REGULAR,
            descending: ($direction === 'desc')
        );

        return $sorted->values();
    }

    /**
     * Extracts a slice of the enum cases collection.
     */
    public static function slice(int $offset, ?int $length = null): Collection
    {
        return self::toCollection()->slice($offset, $length)->values();
    }

    /**
     * Checks if a value or name exists within the enum cases.
     */
    public static function contains(string|int $valueOrName): bool
    {
        return self::hasValue($valueOrName) || self::hasName((string)$valueOrName);
    }

    /**
     * Returns a Collection of key-value pairs representing names and values.
     * For pure enums, values are names.
     */
    public static function toKeyValueCollection(): Collection
    {
        // @phpstan-ignore argument.type
        return self::toCollection()->mapWithKeys(function (UnitEnum $case) {
            if (self::isBackedEnum()) {
                /** @var BackedEnum $case */
                return [$case->name => $case->value];
            }

            return [$case->name => $case->name];
        });
    }

    /**
     * Lists all constants defined in the enum class.
     *
     * @return array<string, static>
     */
    public static function listConstants(): array
    {
        /** @var array<string, static> $result */
        $result = (new ReflectionClass(static::class))->getConstants();

        return $result;
    }

    /**
     * Return the first case (or null if empty).
     */
    public static function first(): ?static
    {
        /** @var ?static $result */
        $result = self::toCollection()->first();

        return $result;
    }

    /**
     * Return the last case (or null if empty).
     */
    public static function last(): ?static
    {
        /** @var ?static $result */
        $result = self::toCollection()->last();

        return $result;
    }

    /**
     * Return how many cases are in the enum.
     */
    public static function count(): int
    {
        return self::toCollection()->count();
    }

    /**
     * Returns a new collection containing only the specified enum cases.
     *
     * @param  array<static>|Collection<static>  $cases
     */
    public static function only(Collection|array $cases): Collection
    {
        $cases = collect($cases);

        return self::filter(fn (UnitEnum $case) => $cases->contains($case));
    }

    /**
     * Returns a new collection excluding the specified enum cases.
     *
     * @param  array<static>|Collection<static>  $cases
     */
    public static function except(Collection|array $cases): Collection
    {
        $cases = collect($cases);

        return self::filter(fn (UnitEnum $case) => !$cases->contains($case));
    }
}
