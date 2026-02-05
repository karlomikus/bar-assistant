<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Common;

use PHPUnit\Framework\TestCase;
use BarAssistant\Domain\Common\Name;
use BarAssistant\Domain\Exception\DomainException;

final class NameTest extends TestCase
{
    public function test_from_string_accepts_valid_name(): void
    {
        $name = Name::fromString('Vodka');

        $this->assertSame('Vodka', $name->toString());
    }

    public function test_from_string_accepts_name_with_spaces(): void
    {
        $name = Name::fromString('Grey Goose Vodka');

        $this->assertSame('Grey Goose Vodka', $name->toString());
    }

    public function test_from_string_accepts_name_with_special_characters(): void
    {
        $name = Name::fromString('Maker\'s Mark');

        $this->assertSame('Maker\'s Mark', $name->toString());
    }

    public function test_from_string_accepts_name_with_leading_and_trailing_spaces(): void
    {
        $name = Name::fromString('  Gin  ');

        $this->assertSame('  Gin  ', $name->toString());
    }

    public function test_from_string_rejects_empty_string(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Name cannot be empty');

        Name::fromString('');
    }

    public function test_from_string_rejects_whitespace_only_string(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Name cannot be empty');

        Name::fromString('   ');
    }

    public function test_from_string_rejects_tabs_only_string(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Name cannot be empty');

        Name::fromString("\t\t");
    }

    public function test_to_string_magic_method_returns_value(): void
    {
        $name = Name::fromString('Whiskey');

        $this->assertSame('Whiskey', (string) $name);
    }

    public function test_json_serialize_returns_string(): void
    {
        $name = Name::fromString('Rum');

        $this->assertSame('"Rum"', json_encode($name));
    }

    public function test_equals_returns_true_for_same_value(): void
    {
        $name1 = Name::fromString('Tequila');
        $name2 = Name::fromString('Tequila');

        $this->assertTrue($name1->equals($name2));
    }

    public function test_equals_returns_false_for_different_value(): void
    {
        $name1 = Name::fromString('Scotch');
        $name2 = Name::fromString('Bourbon');

        $this->assertFalse($name1->equals($name2));
    }

    public function test_equals_is_case_sensitive(): void
    {
        $name1 = Name::fromString('vodka');
        $name2 = Name::fromString('Vodka');

        $this->assertFalse($name1->equals($name2));
    }
}
