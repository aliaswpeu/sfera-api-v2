<?php

namespace Aliaswpeu\SferaApi\DTOs;

class DokumentDTO
{
    /**
     * @param PozycjaDTO[] $Pozycje
     */
    public function __construct(
        public string $Typ,
        public ?int $KontrahentId = null,
        public ?array $Kontrahent = null,

        // Header
        public ?string $Tytul = null,
        public ?string $Uwagi = null,
        public ?string $NumerOryginalny = null,
        public ?string $MiejsceWystawienia = null,

        // Dates
        public ?string $DataWystawienia = null,
        public ?string $DataSprzedazy = null,
        public ?string $TerminRealizacji = null,
        public ?string $DataMagazynowa = null,

        // Flags
        public ?string $FlagaNazwa = null,
        public ?string $FlagaKomentarz = null,

        // Payment
        public ?string $PaymentType = 'transfer',
        public float $Amount = 0,
        public ?int $PayPointId = null,

        // Items (array of PozycjaDTO)
        public array $Pozycje = [],

        // Settings
        public bool $Rezerwacja = true,
        public ?int $MagazynNadawczyId = null,
        public ?int $KategoriaId = null,
        public ?int $PoziomCenyId = null,
    ) {}

    public static function rules(): array
    {
        return [
            'Typ' => ['required', 'string'],

            'KontrahentId' => ['nullable', 'integer'],
            'Kontrahent' => ['nullable', 'array'],

            'Tytul' => ['nullable', 'string', 'max:200'],
            'Uwagi' => ['nullable', 'string', 'max:2000'],
            'NumerOryginalny' => ['nullable', 'string', 'max:200'],
            'MiejsceWystawienia' => ['nullable', 'string', 'max:200'],

            'DataWystawienia' => ['nullable', 'string'],
            'DataSprzedazy' => ['nullable', 'string'],
            'TerminRealizacji' => ['nullable', 'string'],
            'DataMagazynowa' => ['nullable', 'string'],

            'FlagaNazwa' => ['nullable', 'string', 'max:200'],
            'FlagaKomentarz' => ['nullable', 'string', 'max:2000'],

            'PaymentType' => ['nullable', 'string'],
            'Amount' => ['numeric'],
            'PayPointId' => ['nullable', 'integer'],

            // Pozycje validation
            'Pozycje' => ['required', 'array', 'min:1'],
            'Pozycje.*' => ['array'], // each must be a PozycjaDTO array

            'Rezerwacja' => ['boolean'],
            'MagazynNadawczyId' => ['nullable', 'integer'],
            'KategoriaId' => ['nullable', 'integer'],
            'PoziomCenyId' => ['nullable', 'integer'],
        ];
    }

    public static function fromArray(array $data): self
    {
        // Convert Pozycje arrays → PozycjaDTO objects
        $pozycje = [];
        if (!empty($data['Pozycje'])) {
            foreach ($data['Pozycje'] as $item) {
                $pozycje[] = PozycjaDTO::fromArray($item);
            }
        }

        $data['Pozycje'] = $pozycje;

        return new self(...$data);
    }

    public function toArray(): array
    {
        $arr = get_object_vars($this);

        // Convert PozycjaDTO objects → arrays
        $arr['Pozycje'] = array_map(
            fn($p) => $p instanceof PozycjaDTO ? $p->toArray() : $p,
            $this->Pozycje
        );

        return $arr;
    }
}
