<?php

namespace Luminarix\EnumConcern;

use BackedEnum;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use ReflectionClass;
use UnitEnum;

trait EnumConcern
{
    /**
     * Retrieves a Collection of all the values defined in the enum.
     * For pure enums, returns the names as values.
     */
    public static function values(): Collection
    {
        if (is_subclass_of(self::class, BackedEnum::class)) {
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
     */
    public static function toArray(): array
    {
        return self::toKeyValueCollection()->toArray();
    }

    /**
     * Returns a Laravel Collection instance containing all enum cases.
     */
    public static function toCollection(): Collection
    {
        return collect(self::cases());
    }

    /**
     * Provides a Collection suitable for populating HTML select options.
     * For pure enums, uses names as values.
     */
    public static function toSelectCollection(): Collection
    {
        return self::toCollection()->mapWithKeys(function ($case) {
            $key = is_subclass_of(self::class, BackedEnum::class) ? $case->value : $case->name;

            return [$key => self::getLabel($case)];
        });
    }

    /**
     * Returns a Collection of human-readable labels or descriptions for each enum case.
     */
    public static function labels(): Collection
    {
        return self::toCollection()->mapWithKeys(fn ($case) => [$case->name => self::getLabel($case)]);
    }

    /**
     * Retrieves the label or description for a specific enum case.
     */
    public static function getLabel(UnitEnum $case): string
    {
        // Customize this method based on how you define labels.
        return Str::headline(mb_strtolower($case->name));
    }

    /**
     * Selects and returns a random enum case.
     */
    public static function random(): self
    {
        return self::toCollection()->random();
    }

    /**
     * Attempts to return the enum case for the given value; returns null if not found.
     * For pure enums, uses the name instead.
     */
    public static function tryFromValue(mixed $value): ?self
    {
        if (is_subclass_of(self::class, BackedEnum::class)) {
            return self::tryFrom($value);
        }

        // For pure enums, value is the name
        return self::tryFromName($value);
    }

    /**
     * Attempts to return the enum case for the given name; returns null if not found.
     */
    public static function tryFromName(string $name): ?self
    {
        return self::toCollection()->firstWhere('name', $name);
    }

    /**
     * Checks if the enum contains the specified value; returns a boolean.
     * For pure enums, checks the name.
     */
    public static function hasValue(mixed $value): bool
    {
        if (is_subclass_of(self::class, BackedEnum::class)) {
            return self::values()->contains($value);
        }

        // For pure enums, value is the name
        return self::hasName($value);
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
        return $this !== $other;
    }

    /**
     * Retrieves a description for the current enum case.
     */
    public function getDescription(): string
    {
        // Customize this method based on how you define descriptions.
        return self::getLabel($this);
    }

    /**
     * Returns any additional attributes associated with the enum case.
     */
    public function getAttributes(): array
    {
        // Implement attribute retrieval logic here.
        return [];
    }

    /**
     * Gets the next enum case in the sequence.
     */
    public function next(): ?self
    {
        $cases = self::toCollection();
        $index = $cases->search($this, true);

        return $cases->get($index + 1);
    }

    /**
     * Gets the previous enum case in the sequence.
     */
    public function previous(): ?self
    {
        $cases = self::toCollection();
        $index = $cases->search($this, true);

        return $cases->get($index - 1);
    }

    /**
     * Returns the index (position) of the current enum case within the enum.
     */
    public function index(): ?int
    {
        $index = self::toCollection()->search($this, true);

        return $index !== false ? $index : null;
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
        $data = json_decode($json, true);

        if (is_subclass_of(self::class, BackedEnum::class)) {
            return collect($data)->map(fn ($value) => self::from($value));
        }

        // For pure enums, keys are names
        return collect($data)->keys()->map(fn ($name) => self::tryFromName($name));
    }

    /**
     * Provides validation rules for the enum, useful for Form Requests.
     */
    public static function rules(): array
    {
        $values = is_subclass_of(self::class, BackedEnum::class) ? self::values() : self::names();

        return ['in:' . $values->implode(',')];
    }

    /**
     * Returns a Collection of translated labels for each enum case.
     */
    public static function transLabels(): Collection
    {
        return self::toCollection()->mapWithKeys(function ($case) {
            $key = is_subclass_of(self::class, BackedEnum::class) ? $case->value : $case->name;

            return [$key => __($case->name)];
        });
    }

    /**
     * Returns data formatted for Blade templates, facilitating the creation of form select inputs.
     */
    public static function selectOptions(): Collection
    {
        return self::toSelectCollection();
    }

    /**
     * Specifies a default enum case, if applicable.
     */
    public static function getDefault(): ?self
    {
        // Define your default case logic here.
        return null;
    }

    /**
     * Retrieves the name/key associated with a given value.
     * For pure enums, returns the name if it exists.
     */
    public static function getKeyByValue(mixed $value): ?string
    {
        if (is_subclass_of(self::class, BackedEnum::class)) {
            return self::tryFromValue($value)?->name;
        }

        // For pure enums, value is the name
        return self::hasName($value) ? $value : null;
    }

    /**
     * Retrieves the value associated with a given name/key.
     * For pure enums, value is the name.
     */
    public static function getValueByKey(string $key): mixed
    {
        if (is_subclass_of(self::class, BackedEnum::class)) {
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
        $sorted = self::toCollection()->sortBy(function ($case) {
            return is_subclass_of(self::class, BackedEnum::class) ? $case->value : $case->name;
        }, SORT_REGULAR, $direction === 'desc');

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
    public static function contains(mixed $valueOrName): bool
    {
        return self::hasValue($valueOrName) || self::hasName($valueOrName);
    }

    /**
     * Returns a Collection of key-value pairs representing names and values.
     * For pure enums, values are names.
     */
    public static function toKeyValueCollection(): Collection
    {
        return self::toCollection()->mapWithKeys(function ($case) {
            $value = is_subclass_of(self::class, BackedEnum::class) ? $case->value : $case->name;

            return [$case->name => $value];
        });
    }

    /**
     * Lists all constants defined in the enum class.
     */
    public static function listConstants(): array
    {
        return (new ReflectionClass(static::class))->getConstants();
    }
}
