<?php

namespace Aliaswpeu\SferaApi\DTOs;

class KontrahentDTO
{
    public function __construct(
        // Basic identity
        public ?string $Symbol = null,
        public ?string $Nazwa = null,
        public ?string $NazwaPelna = null,
        public ?string $NIP = null,
        public ?string $REGON = null,
        public ?string $Email = null,
        public ?string $WWW = null,

        // Main address
        public ?string $Ulica = null,
        public ?string $NrDomu = null,
        public ?string $NrLokalu = null,
        public ?string $KodPocztowy = null,
        public ?string $Miejscowosc = null,
        public ?string $Wojewodztwo = null,
        public ?string $Panstwo = null,

        // Delivery address (grouped)
        public ?array $AdresDostawy = null,
        // [
        //   'Nazwa' => '',
        //   'Ulica' => '',
        //   'NrDomu' => '',
        //   'NrLokalu' => '',
        //   'KodPocztowy' => '',
        //   'Miejscowosc' => '',
        //   'Wojewodztwo' => '',
        //   'Panstwo' => '',
        // ]

        // CRM address (grouped)
        public ?array $AdresKorespondencyjny = null,

        // Commercial settings
        public ?int $PoziomCenPrzySprzedazyId = null,
        public ?int $KredytKupieckiTerminId = null,
        public ?float $KredytKupiecki = null,
        public ?int $OpiekunId = null,
        public ?bool $KhSklepuInternetowego = null,

        // Custom fields
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
            'NazwaPelna' => ['nullable', 'string', 'max:255'],
            'NIP' => ['nullable', 'string', 'max:20'],
            'REGON' => ['nullable', 'string', 'max:20'],
            'Email' => ['nullable', 'email', 'max:255'],
            'WWW' => ['nullable', 'string', 'max:255'],

            // Main address
            'Ulica' => ['nullable', 'string', 'max:100'],
            'NrDomu' => ['nullable', 'string', 'max:20'],
            'NrLokalu' => ['nullable', 'string', 'max:20'],
            'KodPocztowy' => ['nullable', 'string', 'max:10'],
            'Miejscowosc' => ['nullable', 'string', 'max:100'],
            'Wojewodztwo' => ['nullable', 'string', 'max:100'],
            'Panstwo' => ['nullable', 'string', 'max:100'],

            // Delivery address
            'AdresDostawy' => ['nullable', 'array'],
            'AdresDostawy.Nazwa' => ['nullable', 'string', 'max:200'],
            'AdresDostawy.Ulica' => ['nullable', 'string', 'max:100'],
            'AdresDostawy.NrDomu' => ['nullable', 'string', 'max:20'],
            'AdresDostawy.NrLokalu' => ['nullable', 'string', 'max:20'],
            'AdresDostawy.KodPocztowy' => ['nullable', 'string', 'max:10'],
            'AdresDostawy.Miejscowosc' => ['nullable', 'string', 'max:100'],
            'AdresDostawy.Wojewodztwo' => ['nullable', 'string', 'max:100'],
            'AdresDostawy.Panstwo' => ['nullable', 'string', 'max:100'],

            // CRM address
            'AdresKorespondencyjny' => ['nullable', 'array'],
            'AdresKorespondencyjny.Nazwa' => ['nullable', 'string', 'max:200'],
            'AdresKorespondencyjny.Ulica' => ['nullable', 'string', 'max:100'],
            'AdresKorespondencyjny.NrDomu' => ['nullable', 'string', 'max:20'],
            'AdresKorespondencyjny.NrLokalu' => ['nullable', 'string', 'max:20'],
            'AdresKorespondencyjny.KodPocztowy' => ['nullable', 'string', 'max:10'],
            'AdresKorespondencyjny.Miejscowosc' => ['nullable', 'string', 'max:100'],
            'AdresKorespondencyjny.Wojewodztwo' => ['nullable', 'string', 'max:100'],
            'AdresKorespondencyjny.Panstwo' => ['nullable', 'string', 'max:100'],

            // Commercial
            'PoziomCenPrzySprzedazyId' => ['nullable', 'integer'],
            'KredytKupieckiTerminId' => ['nullable', 'integer'],
            'KredytKupiecki' => ['nullable', 'numeric'],
            'OpiekunId' => ['nullable', 'integer'],
            'KhSklepuInternetowego' => ['nullable', 'boolean'],

            // Custom fields
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
        return new self(...$data);
    }

    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
