<?php

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Luminarix\EnumConcern\Tests\Enums\IntBackedEnum;
use Luminarix\EnumConcern\Tests\Enums\SimpleEnum;
use Luminarix\EnumConcern\Tests\Enums\StringBackedEnum;

/**
 * Helper function to test methods across different enums.
 */
function testEnumMethods($enumClass, $expectedValues, $expectedNames, $expectedArray)
{
    it("returns the values as a collection for {$enumClass}", function () use ($enumClass, $expectedValues) {
        $test = $enumClass::values();

        expect($test)
            ->toBeInstanceOf(Collection::class)
            ->toEqual(collect($expectedValues));
    });

    it("returns the names as a collection for {$enumClass}", function () use ($enumClass, $expectedNames) {
        $test = $enumClass::names();

        expect($test)
            ->toBeInstanceOf(Collection::class)
            ->toEqual(collect($expectedNames));
    });

    it("returns the enum as an array for {$enumClass}", function () use ($enumClass, $expectedArray) {
        $test = $enumClass::toArray();

        expect($test)
            ->toMatchArray($expectedArray);
    });

    it("returns a collection with the enum cases for {$enumClass}", function () use ($enumClass) {
        $test = $enumClass::toCollection();
        $cases = $enumClass::cases();
        $expectation = collect($cases);

        expect($test)
            ->toBeInstanceOf(Collection::class)
            ->toEqual($expectation);
    });

    it("returns the labels as a collection for {$enumClass}", function () use ($enumClass, $expectedNames) {
        $test = $enumClass::labels();
        $expectedLabels = collect($expectedNames)->mapWithKeys(fn ($name) => [$name => Str::headline(mb_strtolower($name))]);

        expect($test)
            ->toBeInstanceOf(Collection::class)
            ->toEqual($expectedLabels);
    });

    it("gets a random enum case for {$enumClass}", function () use ($enumClass) {
        $test = $enumClass::random();

        expect($test)
            ->toBeInstanceOf($enumClass)
            ->toBeIn($enumClass::cases());
    });

    it("gets a random enum case for {$enumClass} with count", function () use ($enumClass) {
        $test = $enumClass::random(2);

        expect($test)
            ->toBeInstanceOf(Collection::class)
            ->toHaveCount(2);
    });

    it("checks if a value exists in the enum for {$enumClass}", function () use ($enumClass, $expectedValues) {
        $exists = $enumClass::hasValue($expectedValues[0]);
        $notExists = $enumClass::hasValue('nonexistent');

        expect($exists)->toBeTrue()
            ->and($notExists)->toBeFalse();
    });

    it("checks if a name exists in the enum for {$enumClass}", function () use ($enumClass, $expectedNames) {
        $exists = $enumClass::hasName($expectedNames[0]);
        $notExists = $enumClass::hasName('NONEXISTENT');

        expect($exists)->toBeTrue()
            ->and($notExists)->toBeFalse();
    });

    it("retrieves a case from a value for {$enumClass}", function () use ($enumClass, $expectedValues) {
        $case = $enumClass::tryFromValue($expectedValues[0]);

        expect($case)
            ->toBeInstanceOf($enumClass)
            ->toBe($enumClass::cases()[0]);
    });

    it("retrieves a case from a name for {$enumClass}", function () use ($enumClass, $expectedNames) {
        $case = $enumClass::tryFromName($expectedNames[0]);

        expect($case)
            ->toBeInstanceOf($enumClass)
            ->toBe($enumClass::cases()[0]);
    });

    it("compares two enum instances for equality in {$enumClass}", function () use ($enumClass) {
        $cases = $enumClass::cases();
        [$caseA, $caseB] = $cases;

        expect($caseA->is($caseA))->toBeTrue()
            ->and($caseA->is($caseB))->toBeFalse();
    });

    it("gets the next enum case in {$enumClass}", function () use ($enumClass) {
        $cases = $enumClass::cases();
        $next = $cases[0]->next();

        expect($next)
            ->toBeInstanceOf($enumClass)
            ->toBe($cases[1]);
    });

    it("gets the previous enum case in {$enumClass}", function () use ($enumClass) {
        $cases = $enumClass::cases();
        $previous = $cases[2]->previous();

        expect($previous)
            ->toBeInstanceOf($enumClass)
            ->toBe($cases[1]);
    });

    it("gets the index of an enum case in {$enumClass}", function () use ($enumClass) {
        $cases = $enumClass::cases();
        $index = $cases[1]->index();

        expect($index)->toBe(1);
    });

    it("serializes and deserializes enum cases to JSON in {$enumClass}", function () use ($enumClass) {
        $json = $enumClass::toJson();
        $collection = $enumClass::fromJson($json);

        expect($json)->toBeJson()
            ->and($collection)
            ->toBeInstanceOf(Collection::class)
            ->toHaveCount(3)
            ->each(fn ($case) => $case->toBeInstanceOf($enumClass));
    });

    it("provides validation rules in {$enumClass}", function () use ($enumClass, $expectedValues, $expectedNames) {
        $rules = $enumClass::rules();
        $expectedRule = is_subclass_of($enumClass, BackedEnum::class)
            ? ['in:' . implode(',', $expectedValues)]
            : ['in:' . implode(',', $expectedNames)];

        expect($rules)
            ->toBeArray()
            ->toEqual($expectedRule);
    });

    it("translates labels in {$enumClass}", function () use ($enumClass, $expectedValues, $expectedNames) {
        // Assuming translation files are set up
        $labels = $enumClass::transLabels();
        $expectedKeys = is_subclass_of($enumClass, BackedEnum::class) ? $expectedValues : $expectedNames;

        expect($labels)
            ->toBeInstanceOf(Collection::class)
            ->toHaveKeys($expectedKeys);
    });

    it("applies a callback with map in {$enumClass}", function () use ($enumClass) {
        $mapped = $enumClass::map(fn ($case) => $case->name . '_mapped');

        expect($mapped)
            ->toBeInstanceOf(Collection::class)
            ->toEqual(collect(['A_mapped', 'B_mapped', 'C_mapped']));
    });

    it("filters enum cases in {$enumClass}", function () use ($enumClass) {
        $filtered = $enumClass::filter(fn ($case) => $case->name !== 'B');
        $cases = $enumClass::cases();
        $expected = collect([$cases[0], $cases[2]]);

        expect($filtered)
            ->toBeInstanceOf(Collection::class)
            ->toEqual($expected);
    });

    it("reduces enum cases to a single value in {$enumClass}", function () use ($enumClass) {
        $reduced = $enumClass::reduce(fn ($carry, $case) => $carry . $case->name, '');

        expect($reduced)->toBe('ABC');
    });

    it("plucks a property from enum cases in {$enumClass}", function () use ($enumClass) {
        $plucked = $enumClass::pluck('name');

        expect($plucked)
            ->toBeInstanceOf(Collection::class)
            ->toEqual(collect(['A', 'B', 'C']));
    });

    it("groups enum cases by a property in {$enumClass}", function () use ($enumClass) {
        // For demonstration, group by the first letter of the name
        $grouped = $enumClass::groupBy(fn ($case) => mb_substr($case->name, 0, 1));

        expect($grouped)
            ->toBeInstanceOf(Collection::class)
            ->toHaveKeys(['A', 'B', 'C']);
    });

    it("sorts enum cases in {$enumClass}", function () use ($enumClass) {
        $sortedAsc = $enumClass::sort('asc')->values();
        $sortedDesc = $enumClass::sort('desc')->values();
        $cases = $enumClass::cases();

        expect($sortedAsc)->toEqual(collect($cases))
            ->and($sortedDesc)->toEqual(collect(array_reverse($cases)));
    });

    it("slices enum cases in {$enumClass}", function () use ($enumClass) {
        $sliced = $enumClass::slice(1, 1);
        $cases = $enumClass::cases();

        expect($sliced)
            ->toBeInstanceOf(Collection::class)
            ->toEqual(collect([$cases[1]]));
    });

    it("checks if a value or name exists in {$enumClass}", function () use ($enumClass, $expectedValues, $expectedNames) {
        $containsValue = $enumClass::contains($expectedValues[0]);
        $containsName = $enumClass::contains($expectedNames[1]);
        $notContains = $enumClass::contains('Nonexistent');

        expect($containsValue)->toBeTrue()
            ->and($containsName)->toBeTrue()
            ->and($notContains)->toBeFalse();
    });

    it("returns a key-value collection in {$enumClass}", function () use ($enumClass, $expectedArray) {
        $keyValue = $enumClass::toKeyValueCollection();

        expect($keyValue)
            ->toBeInstanceOf(Collection::class)
            ->toEqual(collect($expectedArray));
    });

    it("lists all constants in the enum class {$enumClass}", function () use ($enumClass) {
        $constants = $enumClass::listConstants();

        expect($constants)
            ->toBeArray()
            ->toHaveKeys(['A', 'B', 'C']);
    });
}

// Test for StringBackedEnum
testEnumMethods(
    StringBackedEnum::class,
    ['a', 'b', 'c'],
    ['A', 'B', 'C'],
    ['A' => 'a', 'B' => 'b', 'C' => 'c']
);

// Test for IntBackedEnum
testEnumMethods(
    IntBackedEnum::class,
    [1, 2, 3],
    ['A', 'B', 'C'],
    ['A' => 1, 'B' => 2, 'C' => 3]
);

// Test for SimpleEnum
testEnumMethods(
    SimpleEnum::class,
    ['A', 'B', 'C'], // Since pure enums use names as values
    ['A', 'B', 'C'],
    ['A' => 'A', 'B' => 'B', 'C' => 'C']
);
