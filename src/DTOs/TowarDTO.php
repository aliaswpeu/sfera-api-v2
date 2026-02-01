<?php

namespace Aliaswpeu\SferaApi\DTOs;

class TowarDTO
{
    public function __construct(
        public ?string $Symbol = null,
        public ?string $Nazwa = null,
        public ?string $Opis = null,
        // EANs
        public ?string $PrimaryEan = null,
        /** @var string[] */
        public array $AdditionalEans = [],

        public ?int $GrupaId = null,
        public ?int $SprzedazVatId = null,
        public ?int $ZakupVatId = null,
        public ?string $PKWiU = null,
        public ?string $SymbolUDostawcy = null,
        public ?float $Masa = null,
        public ?float $MasaNetto = null,
        public ?float $Objetosc = null,
        public ?bool $DoSklepuInternetowego = null,
        public ?bool $DoSprzedazyMobilnej = null,
        public ?bool $DoSerwisuAukcyjnego = null,
        public ?string $Pole1 = null,
        public ?string $Pole2 = null,
        public ?string $Pole3 = null,
        public ?string $Pole4 = null,
        public ?string $Pole5 = null,
        public ?string $Pole6 = null,
        public ?string $Pole7 = null,
        public ?string $Pole8 = null,
    ) {
    }

    public static function rules(): array
    {
        return [
            'Symbol' => ['required', 'string', 'max:50'],
            'Nazwa' => ['required', 'string', 'max:200'],
            'Opis' => ['nullable', 'string', 'max:2000'],
            // EAN validation
            'PrimaryEan' => ['nullable', 'string', 'max:50'],
            'AdditionalEans' => ['nullable', 'array'],
            'AdditionalEans.*' => ['string', 'max:50'],

            'GrupaId' => ['nullable', 'integer'],
            'SprzedazVatId' => ['nullable', 'integer'],
            'ZakupVatId' => ['nullable', 'integer'],

            'PKWiU' => ['nullable', 'string', 'max:50'],
            'SymbolUDostawcy' => ['nullable', 'string', 'max:100'],

            'Masa' => ['nullable', 'numeric'],
            'MasaNetto' => ['nullable', 'numeric'],
            'Objetosc' => ['nullable', 'numeric'],

            'DoSklepuInternetowego' => ['nullable', 'boolean'],
            'DoSprzedazyMobilnej' => ['nullable', 'boolean'],
            'DoSerwisuAukcyjnego' => ['nullable', 'boolean'],

            'Pole1' => ['nullable', 'string', 'max:255'],
            'Pole2' => ['nullable', 'string', 'max:255'],
            'Pole3' => ['nullable', 'string', 'max:255'],
            'Pole4' => ['nullable', 'string', 'max:255'],
            'Pole5' => ['nullable', 'string', 'max:255'],
            'Pole6' => ['nullable', 'string', 'max:255'],
            'Pole7' => ['nullable', 'string', 'max:255'],
            'Pole8' => ['nullable', 'string', 'max:255'],
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            Symbol: $data['Symbol'] ?? null,
            Nazwa: $data['Nazwa'] ?? null,
            Opis: $data['Opis'] ?? null,
            PrimaryEan: $data['PrimaryEan'] ?? null,
            AdditionalEans: $data['AdditionalEans'] ?? [],
            GrupaId: $data['GrupaId'] ?? null,
            SprzedazVatId: $data['SprzedazVatId'] ?? null,
            ZakupVatId: $data['ZakupVatId'] ?? null,
            PKWiU: $data['PKWiU'] ?? null,
            SymbolUDostawcy: $data['SymbolUDostawcy'] ?? null,
            Masa: $data['Masa'] ?? null,
            MasaNetto: $data['MasaNetto'] ?? null,
            Objetosc: $data['Objetosc'] ?? null,
            DoSklepuInternetowego: $data['DoSklepuInternetowego'] ?? null,
            DoSprzedazyMobilnej: $data['DoSprzedazyMobilnej'] ?? null,
            DoSerwisuAukcyjnego: $data['DoSerwisuAukcyjnego'] ?? null,
            Pole1: $data['Pole1'] ?? null,
            Pole2: $data['Pole2'] ?? null,
            Pole3: $data['Pole3'] ?? null,
            Pole4: $data['Pole4'] ?? null,
            Pole5: $data['Pole5'] ?? null,
            Pole6: $data['Pole6'] ?? null,
            Pole7: $data['Pole7'] ?? null,
            Pole8: $data['Pole8'] ?? null,
        );
    }

    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
