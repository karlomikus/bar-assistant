<?php

declare(strict_types=1);

namespace Tests\Unit\External;

use Tests\TestCase;
use Kami\Cocktail\External\Model\Cocktail;

class CocktailTest extends TestCase
{
    public function testSchema4RoundTripPreservesMetadataFields(): void
    {
        $source = [
            'name' => 'Negroni',
            'instructions' => 'Stir and strain.',
            'author' => 'John Doe',
            'origin_bar' => 'Caffe Casoni',
            'year' => 1919,
        ];

        $cocktail = Cocktail::fromSchema4Array($source);
        $result = $cocktail->toSchema4Array();

        $this->assertSame('John Doe', $result['author']);
        $this->assertSame('Caffe Casoni', $result['origin_bar']);
        $this->assertSame(1919, $result['year']);

        $roundTripped = Cocktail::fromSchema4Array($result);

        $this->assertSame('John Doe', $roundTripped->author);
        $this->assertSame('Caffe Casoni', $roundTripped->originBar);
        $this->assertSame(1919, $roundTripped->year);
    }

    public function testSchema4EmitsMetadataFieldsAsNullWhenAbsent(): void
    {
        $source = [
            'name' => 'Negroni',
            'instructions' => 'Stir and strain.',
        ];

        $cocktail = Cocktail::fromSchema4Array($source);
        $result = $cocktail->toSchema4Array();

        $this->assertArrayHasKey('author', $result);
        $this->assertArrayHasKey('origin_bar', $result);
        $this->assertArrayHasKey('year', $result);
        $this->assertNull($result['author']);
        $this->assertNull($result['origin_bar']);
        $this->assertNull($result['year']);
    }

    public function testOmittedMetadataFieldsDeserializeAsNull(): void
    {
        $source = [
            'name' => 'Negroni',
            'instructions' => 'Stir and strain.',
        ];

        $cocktail = Cocktail::fromSchema4Array($source);

        $this->assertNull($cocktail->author);
        $this->assertNull($cocktail->originBar);
        $this->assertNull($cocktail->year);
    }

    public function testYearRejectsNonIntegerValues(): void
    {
        $source = [
            'name' => 'Negroni',
            'instructions' => 'Stir and strain.',
            'year' => '1919',
        ];

        $cocktail = Cocktail::fromSchema4Array($source);

        $this->assertNull($cocktail->year);
    }
}
