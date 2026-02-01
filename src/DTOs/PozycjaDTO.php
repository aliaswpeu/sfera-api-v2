<?php

namespace Aliaswpeu\SferaApi\DTOs;

class PozycjaDTO
{
    public function __construct(
    // Required
    public float $Qty,
    public float $Price,

    // Either Symbol OR TowarDTO
    public ?string $Symbol = null,
    public ?TowarDTO $Towar = null,

    // Optional
    public ?float $PriceBeforeDiscount = null,
    public ?string $Opis = null,
    public ?string $Jm = null,
    public ?int $VatId = null,
    public ?float $RabatProcent = null,
    public ?int $MagazynId = null,
    public ?string $OznaczenieJpkVat = null,
    public ?bool $PodlegaAkcyzie = null,
    public ?string $Termin = null,
    public ?string $SymbolUDostawcy = null,
) {}


    public static function rules(): array
    {
        return [
            // Either Symbol or Towar must be provided
            'Symbol' => ['nullable', 'string'],
            'Towar' => ['nullable', 'array'],

            'Qty' => ['required', 'numeric', 'min:0.0001'],
            'Price' => ['required', 'numeric', 'min:0'],

            'PriceBeforeDiscount' => ['nullable', 'numeric', 'min:0'],
            'Opis' => ['nullable', 'string', 'max:500'],
            'Jm' => ['nullable', 'string', 'max:20'],
            'VatId' => ['nullable', 'integer'],
            'RabatProcent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'MagazynId' => ['nullable', 'integer'],
            'OznaczenieJpkVat' => ['nullable', 'string', 'max:10'],
            'PodlegaAkcyzie' => ['nullable', 'boolean'],
            'Termin' => ['nullable', 'string'],
            'SymbolUDostawcy' => ['nullable', 'string', 'max:100'],
        ];
    }

    public static function fromArray(array $data): self
{
    if (isset($data['Towar']) && is_array($data['Towar'])) {
        $data['Towar'] = TowarDTO::fromArray($data['Towar']);
    }

    return new self(
        Qty: $data['Qty'],
        Price: $data['Price'],
        Symbol: $data['Symbol'] ?? null,
        Towar: $data['Towar'] ?? null,
        PriceBeforeDiscount: $data['PriceBeforeDiscount'] ?? null,
        Opis: $data['Opis'] ?? null,
        Jm: $data['Jm'] ?? null,
        VatId: $data['VatId'] ?? null,
        RabatProcent: $data['RabatProcent'] ?? null,
        MagazynId: $data['MagazynId'] ?? null,
        OznaczenieJpkVat: $data['OznaczenieJpkVat'] ?? null,
        PodlegaAkcyzie: $data['PodlegaAkcyzie'] ?? null,
        Termin: $data['Termin'] ?? null,
        SymbolUDostawcy: $data['SymbolUDostawcy'] ?? null,
    );
}


    public function toArray(): array
    {
        $arr = get_object_vars($this);

        if ($this->Towar instanceof TowarDTO) {
            $arr['Towar'] = $this->Towar->toArray();
        }

        return $arr;
    }
}
